<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalOverridden;
use App\Mail\ApprovalRequested;
use App\Mail\RequestAssigned;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\Site;
use App\Models\Tag;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters($request, ChangeRequest::with(['site', 'assignee', 'tags'])->withCount('items')->withCount(['items as items_done_count' => function ($q) {
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
        $allTags = Tag::orderBy('name')->get();

        return view('admin.requests.index', compact('requests', 'sites', 'adminUsers', 'allTags'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->applyFilters($request, ChangeRequest::with(['site', 'tags'])->withCount('items'));

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
                'Deadline', 'Submitted Date', 'Tags',
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
                        $row->tags->pluck('name')->implode(', '),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function show(ChangeRequest $changeRequest)
    {
        $changeRequest->load(['site', 'items.files', 'notes.user', 'statusLogs.user', 'approvers.recordedByUser', 'assignee', 'tags', 'approvalOverriddenByUser', 'emailLogs']);

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

            // Auto-complete unresolved items when marking request as done
            if ($newStatus === 'done') {
                $changeRequest->items()->whereNotIn('status', ['done', 'not_done', 'deferred'])->update(['status' => 'done']);
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

    public function addApprover(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $approver = $changeRequest->approvers()->create([
            'name' => $request->name,
            'email' => $request->email,
            'token' => ChangeRequestApprover::generateToken(),
        ]);

        // Send approval request email if approver has an email
        if ($approver->email && $approver->token) {
            EmailLog::dispatch($approver->email, new ApprovalRequested($changeRequest, $approver), $changeRequest);
        }

        if ($changeRequest->approval_overridden) {
            $changeRequest->update([
                'approval_overridden' => false,
                'approval_overridden_by' => null,
                'approval_overridden_at' => null,
            ]);
            $changeRequest->notes()->create([
                'user_id' => auth()->id(),
                'note' => 'Approval override cleared — new approver added.',
            ]);
        }

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Added approver: ' . $request->name,
        ]);

        AuditService::log(
            action: 'approver_added',
            model: $changeRequest,
            description: "Approver added to {$changeRequest->reference}: {$request->name}",
            newValues: ['approver_name' => $request->name, 'approver_email' => $request->email],
        );

        // Pull back to "requires_referral" if currently past it
        if (in_array($changeRequest->status, ChangeRequest::POST_REFERRED_STATUSES)) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'requires_referral']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'requires_referral',
            ]);

            return back()->with('success', 'Approver added. Status moved back to Referred pending approval.');
        }

        return back()->with('success', 'Approver added.');
    }

    public function updateApprover(Request $request, ChangeRequest $changeRequest, ChangeRequestApprover $approver)
    {
        abort_unless($approver->change_request_id === $changeRequest->id, 404);

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'responded_at' => 'required|date',
            'share_details' => 'nullable|boolean',
        ]);

        $oldApproverStatus = $approver->status;
        $approver->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'responded_at' => $request->responded_at,
            'recorded_by' => auth()->id(),
        ]);

        AuditService::log(
            action: 'approver_updated',
            model: $changeRequest,
            description: "Approver {$approver->name} on {$changeRequest->reference} marked as {$request->status}",
            oldValues: ['approver_status' => $oldApproverStatus],
            newValues: ['approver_status' => $request->status],
        );

        // Auto-advance to "approved" if all approvers have approved
        $changeRequest->refresh();
        if ($changeRequest->approvalsAllApproved() && in_array($changeRequest->status, ['requires_referral', 'referred'])) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'approved']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'approved',
            ]);

            EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, $oldStatus, 'approved'), $changeRequest);

            return back()->with('success', 'Approval recorded. All approvers approved — status moved to Approved.');
        }

        // Auto-decline if an approver rejected and request is at referred
        if ($request->status === 'rejected' && $changeRequest->status === 'referred') {
            $rejectionReason = $request->share_details
                ? "Declined by {$approver->name}: {$request->notes}"
                : ($request->notes ?: 'Rejected by approver.');

            $changeRequest->update([
                'status' => 'declined',
                'rejection_reason' => $rejectionReason,
            ]);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => 'referred',
                'new_status' => 'declined',
            ]);

            EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, 'referred', 'declined'), $changeRequest);

            // Notify other pending approvers
            $pendingApprovers = $changeRequest->approvers()
                ->where('status', 'pending')
                ->whereNotNull('email')
                ->get();

            foreach ($pendingApprovers as $pending) {
                EmailLog::dispatch($pending->email, new \App\Mail\ApprovalDeclined($changeRequest, $pending), $changeRequest);
            }

            return back()->with('success', 'Rejection recorded. Request has been declined and notifications sent.');
        }

        return back()->with('success', 'Approval recorded.');
    }

    public function sendForApproval(ChangeRequest $changeRequest)
    {
        $site = $changeRequest->site;
        $defaultApprovers = $site->default_approvers ?? [];

        // Auto-add site's default approvers if none exist yet
        if ($changeRequest->approvers->isEmpty() && !empty($defaultApprovers)) {
            foreach ($defaultApprovers as $approver) {
                $changeRequest->approvers()->create([
                    'name' => $approver['name'],
                    'email' => $approver['email'] ?? null,
                    'token' => ChangeRequestApprover::generateToken(),
                ]);
            }

            $changeRequest->notes()->create([
                'user_id' => auth()->id(),
                'note' => 'Added default approvers from site: ' . collect($defaultApprovers)->pluck('name')->implode(', '),
            ]);
        }

        $changeRequest->refresh();

        // Send emails to all pending approvers that have email + token
        $sent = 0;
        foreach ($changeRequest->approvers->where('status', 'pending') as $approver) {
            if ($approver->email && $approver->token) {
                EmailLog::dispatch($approver->email, new ApprovalRequested($changeRequest, $approver), $changeRequest);
                $sent++;
            }
        }

        // Update status
        $oldStatus = $changeRequest->status;
        $newStatus = $sent > 0 ? 'referred' : 'requires_referral';
        $changeRequest->update(['status' => $newStatus]);

        ChangeRequestStatusLog::create([
            'change_request_id' => $changeRequest->id,
            'user_id' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        AuditService::log(
            action: 'sent_for_approval',
            model: $changeRequest,
            description: "Sent {$changeRequest->reference} for approval ({$sent} emails sent)",
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
        );

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => $sent > 0
                ? "Sent for approval. {$sent} " . str('email')->plural($sent) . " sent."
                : 'Sent for approval (manual follow-up required — no approver emails configured).',
        ]);

        return back()->with('success', $sent > 0
            ? "Approval emails sent to {$sent} " . str('approver')->plural($sent) . ". Status moved to Referred."
            : 'Approvers added. Manual follow-up required — no approver emails configured.');
    }

    public function overrideApprovals(ChangeRequest $changeRequest)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($changeRequest->approval_overridden) {
            return back()->with('info', 'Approval gate has already been overridden.');
        }

        if (!$changeRequest->hasPendingApprovers()) {
            return back()->with('info', 'There are no pending approvers to override.');
        }

        $pendingApprovers = $changeRequest->approvers()->where('status', 'pending')->get();
        $pendingCount = $pendingApprovers->count();

        $changeRequest->update([
            'approval_overridden' => true,
            'approval_overridden_by' => auth()->id(),
            'approval_overridden_at' => now(),
        ]);

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Approval gate overridden by ' . auth()->user()->name . '. ' . $pendingCount . ' pending ' . str('approver')->plural($pendingCount) . ' bypassed.',
        ]);

        AuditService::log(
            action: 'approval_overridden',
            model: $changeRequest,
            description: "Approval gate overridden on {$changeRequest->reference} by " . auth()->user()->name,
            newValues: ['pending_approvers_bypassed' => $pendingCount],
        );

        $changeRequest->loadMissing('approvalOverriddenByUser');

        foreach ($pendingApprovers as $approver) {
            if ($approver->email) {
                EmailLog::dispatch($approver->email, new ApprovalOverridden($changeRequest, $approver), $changeRequest);
            }
        }

        // Auto-advance to approved if currently at requires_referral or referred
        if (in_array($changeRequest->status, ['requires_referral', 'referred'])) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'approved']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'approved',
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $changeRequest,
                description: "Status changed on {$changeRequest->reference}: {$oldStatus} → approved (approval override)",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'approved'],
            );

            EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, $oldStatus, 'approved'), $changeRequest);
        }

        return back()->with('success', 'Approval gate overridden. ' . $pendingCount . ' pending ' . str('approver')->plural($pendingCount) . ' notified.');
    }

    public function removeApprover(ChangeRequest $changeRequest, ChangeRequestApprover $approver)
    {
        abort_unless($approver->change_request_id === $changeRequest->id, 404);

        $name = $approver->name;
        $approver->delete();

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Removed approver: ' . $name,
        ]);

        AuditService::log(
            action: 'approver_removed',
            model: $changeRequest,
            description: "Approver removed from {$changeRequest->reference}: {$name}",
            oldValues: ['approver_name' => $name],
        );

        return back()->with('success', 'Approver removed.');
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

    public function addTag(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'tag_name' => 'required|string|max:100',
        ]);

        $tag = Tag::firstOrCreate(
            ['name' => trim($request->tag_name)],
            ['colour' => '#6E6E6D']
        );

        if (!$changeRequest->tags()->where('tag_id', $tag->id)->exists()) {
            $changeRequest->tags()->attach($tag->id);
        }

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function removeTag(ChangeRequest $changeRequest, Tag $tag)
    {
        $changeRequest->tags()->detach($tag->id);

        return response()->json(['success' => true]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:change_requests,id',
            'status' => 'required|in:' . implode(',', ChangeRequest::STATUSES),
        ]);

        $newStatus = $request->status;
        $updated = 0;
        $skipped = 0;

        foreach (ChangeRequest::whereIn('id', $request->ids)->get() as $cr) {
            $oldStatus = $cr->status;

            if ($oldStatus === $newStatus) {
                continue;
            }

            // Respect the approval gate
            if (in_array($newStatus, ChangeRequest::POST_REFERRED_STATUSES) && !$cr->canMovePastReferred()) {
                $skipped++;
                continue;
            }

            $updateData = ['status' => $newStatus];

            // Clear rejection reason when not declining/cancelling
            if (!in_array($newStatus, ['declined', 'cancelled'])) {
                $updateData['rejection_reason'] = null;
            }

            $cr->update($updateData);

            if ($newStatus === 'done') {
                $cr->items()->whereNotIn('status', ['done', 'not_done', 'deferred'])->update(['status' => 'done']);
            }

            ChangeRequestStatusLog::create([
                'change_request_id' => $cr->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $cr,
                description: "Bulk status change on {$cr->reference}: {$oldStatus} → {$newStatus}",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
            );

            $updated++;
        }

        $message = "{$updated} request(s) updated.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (outstanding approvals).";
        }

        return response()->json(['success' => true, 'message' => $message, 'updated' => $updated, 'skipped' => $skipped]);
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:change_requests,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $assignedTo = $request->assigned_to ?: null;
        $assignee = $assignedTo ? User::find($assignedTo) : null;

        $updated = 0;
        foreach (ChangeRequest::whereIn('id', $request->ids)->get() as $cr) {
            $cr->update(['assigned_to' => $assignedTo]);

            $cr->notes()->create([
                'user_id' => auth()->id(),
                'note' => $assignee ? 'Bulk assigned to ' . $assignee->name : 'Bulk unassigned',
            ]);

            if ($assignee && (int) $assignedTo !== auth()->id()) {
                EmailLog::dispatch($assignee->email, new RequestAssigned($cr, $assignee), $cr);
            }

            AuditService::log(
                action: 'assigned',
                model: $cr,
                description: $assignee
                    ? "Bulk assigned {$cr->reference} to {$assignee->name}"
                    : "Bulk unassigned {$cr->reference}",
                newValues: ['assigned_to' => $assignedTo],
            );

            $updated++;
        }

        $message = $assignee
            ? "{$updated} request(s) assigned to {$assignee->name}."
            : "{$updated} request(s) unassigned.";

        return response()->json(['success' => true, 'message' => $message, 'updated' => $updated]);
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

        if ($request->filled('tags')) {
            $tagIds = (array) $request->tags;
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        return $query;
    }
}
