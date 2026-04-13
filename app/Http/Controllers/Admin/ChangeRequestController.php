<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RequestAssigned;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\Site;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters($request, ChangeRequest::with(['site', 'assignee'])->withCount('items')->withCount(['items as items_done_count' => function ($q) {
            $q->where('status', 'done');
        }]));

        // Sorting
        $sortColumn = $request->input('sort');
        $sortDirection = in_array($request->input('direction'), ['asc', 'desc']) ? $request->input('direction') : 'asc';

        if ($sortColumn === 'site') {
            $dir = $sortDirection;
            $query->orderByRaw("(SELECT name FROM sites WHERE sites.id = change_requests.site_id) $dir");
        } elseif ($sortColumn === 'priority') {
            $order = $sortDirection === 'asc'
                ? "FIELD(priority, 'urgent', 'high', 'normal', 'low')"
                : "FIELD(priority, 'low', 'normal', 'high', 'urgent')";
            $query->orderByRaw($order);
        } elseif (in_array($sortColumn, ['reference', 'requester_name', 'status', 'created_at'])) {
            $query->orderBy("change_requests.{$sortColumn}", $sortDirection);
        } else {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")->latest();
        }

        $requests = $query->paginate(25)->withQueryString();
        $sites = Site::orderBy('name')->get();
        $adminUsers = User::admins()->orderBy('name')->get();

        return view('admin.requests.index', compact('requests', 'sites', 'adminUsers'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->applyFilters($request, ChangeRequest::with(['site'])->withCount('items'));

        // Support exporting specific IDs (for bulk export)
        if ($request->filled('ids')) {
            $ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
            $query->whereIn('id', $ids);
        }

        $query->latest();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="change-requests-' . now()->format('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Reference', 'Site', 'Page', 'Content Type', 'Requester Name',
                'Requester Email', 'Requester Role', 'Status', 'Priority', 'Items Count',
                'Deadline', 'Submitted Date',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->reference,
                        $row->site->name ?? '',
                        $row->page_title ?: $row->page_url,
                        $row->cpt_slug ?? '',
                        $row->requester_name,
                        $row->requester_email,
                        $row->requester_role ?? '',
                        $row->status,
                        $row->priority ?? 'normal',
                        $row->items_count,
                        $row->deadline_date?->format('Y-m-d') ?? '',
                        $row->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function show(ChangeRequest $changeRequest)
    {
        $changeRequest->load(['site', 'items.files', 'notes.user', 'statusLogs.user', 'approvers.recordedByUser', 'assignee', 'approvalOverriddenByUser', 'emailLogs']);

        $pageHistory = ChangeRequest::where('page_url', $changeRequest->page_url)
            ->where('site_id', $changeRequest->site_id)
            ->where('id', '!=', $changeRequest->id)
            ->latest()
            ->take(10)
            ->get();

        $activities = collect();

        foreach ($changeRequest->notes as $note) {
            $activities->push((object) [
                'type' => 'note',
                'date' => $note->created_at,
                'user' => $note->user->name,
                'note' => $note->note,
            ]);
        }

        foreach ($changeRequest->statusLogs as $log) {
            $activities->push((object) [
                'type' => 'status',
                'date' => $log->created_at,
                'user' => $log->user?->name ?? 'System',
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
            ]);
        }

        foreach ($changeRequest->approvers->where('status', '!=', 'pending') as $approver) {
            $activities->push((object) [
                'type' => 'approval',
                'date' => $approver->responded_at ?? $approver->updated_at,
                'user' => $approver->name,
                'approval_status' => $approver->status,
                'notes' => $approver->notes,
            ]);
        }

        if ($changeRequest->approval_overridden) {
            $activities->push((object) [
                'type' => 'override',
                'date' => $changeRequest->approval_overridden_at,
                'user' => $changeRequest->approvalOverriddenByUser->name ?? 'Unknown',
            ]);
        }

        foreach ($changeRequest->emailLogs as $emailLog) {
            $activities->push((object) [
                'type' => 'email',
                'date' => $emailLog->created_at,
                'subject' => $emailLog->subject,
                'recipient' => $emailLog->recipient_email,
                'status' => $emailLog->status,
            ]);
        }

        $activities->push((object) [
            'type' => 'created',
            'date' => $changeRequest->created_at,
            'user' => $changeRequest->requester_name,
        ]);

        $activities = $activities->sortBy('date');

        $adminUsers = User::admins()->orderBy('name')->get();

        return view('admin.requests.show', compact('changeRequest', 'pageHistory', 'activities', 'adminUsers'));
    }

    public function updateStatus(Request $request, ChangeRequest $changeRequest)
    {
        $rules = [
            'status' => 'required|in:' . implode(',', ChangeRequest::STATUSES),
        ];

        if (in_array($request->status, ['declined', 'cancelled'])) {
            $rules['rejection_reason'] = 'required|string|max:2000';
        }

        $request->validate($rules);

        $oldStatus = $changeRequest->status;
        $newStatus = $request->status;

        if ($oldStatus !== $newStatus) {
            // Block moving past "referred" if approvals are outstanding
            if (in_array($newStatus, ChangeRequest::POST_REFERRED_STATUSES) && !$changeRequest->canMovePastReferred()) {
                return back()->with('error', 'Cannot move to "' . ucfirst($newStatus) . '" — there are outstanding approvals.');
            }

            $updateData = ['status' => $newStatus];

            if (in_array($newStatus, ['declined', 'cancelled'])) {
                $updateData['rejection_reason'] = $request->rejection_reason;
            } else {
                $updateData['rejection_reason'] = null;
            }

            $changeRequest->update($updateData);

            // Mark any unresolved items as not done when closing a request
            if (in_array($newStatus, ChangeRequest::TERMINAL_STATUSES)) {
                $changeRequest->items()->where('status', 'in_progress')->update(['status' => 'not_done']);
            }

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $changeRequest,
                description: "Status changed on {$changeRequest->reference}: {$oldStatus} → {$newStatus}",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
            );

            // Notify the requester of the status change
            EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, $oldStatus, $newStatus), $changeRequest);
        }

        return back()->with('success', 'Status updated.');
    }

    public function addNote(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'note' => 'required|string|max:5000',
        ]);

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => $request->note,
        ]);

        AuditService::log(
            action: 'note_added',
            model: $changeRequest,
            description: "Note added to {$changeRequest->reference}",
        );

        return back()->with('success', 'Note added.');
    }

    public function downloadFile(ChangeRequest $changeRequest, ChangeRequestItemFile $file)
    {
        $itemIds = $changeRequest->items()->pluck('id');
        if (!$itemIds->contains($file->change_request_item_id)) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($file->stored_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('local')->download($file->stored_path, $file->original_filename);
    }

    public function updateItemStatus(Request $request, ChangeRequest $changeRequest, ChangeRequestItem $item)
    {
        abort_unless($item->change_request_id === $changeRequest->id, 404);

        $request->validate([
            'status' => 'required|in:' . implode(',', ChangeRequestItem::STATUSES),
        ]);

        $oldItemStatus = $item->status;
        $item->update(['status' => $request->status]);

        $statusLabel = str_replace('_', ' ', $request->status);
        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => "Item #{$item->sort_order} ({$item->content_area}) marked as {$statusLabel}",
        ]);

        AuditService::log(
            action: 'item_status_changed',
            model: $changeRequest,
            description: "Item #{$item->sort_order} on {$changeRequest->reference} changed to {$statusLabel}",
            oldValues: ['item_status' => $oldItemStatus],
            newValues: ['item_status' => $request->status],
        );

        // Auto-complete request when all items are resolved (done or not done)
        if ($changeRequest->items()->where('status', 'in_progress')->doesntExist() && !in_array($changeRequest->status, ChangeRequest::TERMINAL_STATUSES)) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'done']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'done',
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $changeRequest,
                description: "Status changed on {$changeRequest->reference}: {$oldStatus} → done (all items complete)",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'done'],
            );

            EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, $oldStatus, 'done'), $changeRequest);

            return back()->with('success', 'Item status updated. All items complete — request marked as done.');
        }

        return back()->with('success', 'Item status updated.');
    }

    public function updatePriority(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'priority' => 'required|in:' . implode(',', ChangeRequest::PRIORITIES),
        ]);

        $oldPriority = $changeRequest->priority;
        $newPriority = $request->priority;

        if ($oldPriority !== $newPriority) {
            $changeRequest->update(['priority' => $newPriority]);

            $changeRequest->notes()->create([
                'user_id' => auth()->id(),
                'note' => 'Priority changed from ' . ($oldPriority ?: 'normal') . ' to ' . $newPriority,
            ]);

            AuditService::log(
                action: 'priority_changed',
                model: $changeRequest,
                description: "Priority changed on {$changeRequest->reference}: " . ($oldPriority ?: 'normal') . " → {$newPriority}",
                oldValues: ['priority' => $oldPriority ?: 'normal'],
                newValues: ['priority' => $newPriority],
            );
        }

        return back()->with('success', 'Priority updated.');
    }

    public function updateAssignment(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $oldAssigneeId = $changeRequest->assigned_to;
        $newAssigneeId = $request->assigned_to ?: null;

        $changeRequest->update(['assigned_to' => $newAssigneeId]);

        // Log assignment change as a note
        if ($newAssigneeId) {
            $assignee = User::find($newAssigneeId);
            $changeRequest->notes()->create([
                'user_id' => auth()->id(),
                'note' => 'Assigned to ' . $assignee->name,
            ]);

            // Send email notification if assigned to someone else
            if ((int) $newAssigneeId !== auth()->id()) {
                EmailLog::dispatch($assignee->email, new RequestAssigned($changeRequest, $assignee), $changeRequest);
            }
            AuditService::log(
                action: 'assigned',
                model: $changeRequest,
                description: "Assigned {$changeRequest->reference} to {$assignee->name}",
                oldValues: ['assigned_to' => $oldAssigneeId],
                newValues: ['assigned_to' => $newAssigneeId],
            );
        } else {
            $changeRequest->notes()->create([
                'user_id' => auth()->id(),
                'note' => 'Unassigned',
            ]);

            AuditService::log(
                action: 'assigned',
                model: $changeRequest,
                description: "Unassigned {$changeRequest->reference}",
                oldValues: ['assigned_to' => $oldAssigneeId],
                newValues: ['assigned_to' => null],
            );
        }

        return back()->with('success', 'Assignment updated.');
    }

    private function applyFilters(Request $request, $query)
    {
        if ($request->filled('status')) {
            $statuses = (array) $request->status;
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('site_id')) {
            $siteIds = (array) $request->site_id;
            $query->whereIn('site_id', $siteIds);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhere('requester_email', 'like', "%{$search}%")
                  ->orWhere('page_url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('my_requests')) {
            $query->where('assigned_to', auth()->id());
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        if ($request->filled('priority')) {
            $priorities = (array) $request->priority;
            $query->whereIn('priority', $priorities);
        }

        return $query;
    }
}
