<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ClarificationRequested;
use App\Mail\RequestAssigned;
use App\Mail\RequestOnHold;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestItem;
use App\Models\ChangeRequestItemFile;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\Site;

use App\Models\User;
use App\Services\ApprovalWorkflowService;
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
        $query = $this->applyFilters($request, ChangeRequest::with(['site', 'additionalSites'])->withCount('items'));

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
                'Reference', 'Type', 'Site', 'All Sites', 'Page or Content', 'CPT',
                'Content Type', 'Public Title', 'Published URL', 'Requester Name',
                'Requester Email', 'Requester Role', 'Status', 'Priority', 'Items Count',
                'Deadline', 'Submitted Date',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->reference,
                        $row->request_type ?? 'change',
                        $row->site->name ?? '',
                        // Content can span sites; the main home alone understates it.
                        $row->allSites()->pluck('name')->join('; '),
                        $row->subjectDescription(),
                        $row->cpt_slug ?? '',
                        $row->content_type ? config("content-types.{$row->content_type}.label", $row->content_type) : '',
                        $row->public_title ?? '',
                        $row->published_url ?? '',
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

        // Page history only means something for a real page. Every content request
        // shares the placeholder page_url, so this would list all of them as prior
        // requests for the same page.
        $pageHistory = $changeRequest->isContentRequest()
            ? collect()
            : ChangeRequest::where('page_url', $changeRequest->page_url)
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
                'user' => $note->user?->name ?? 'System',
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
            'status' => 'required|in:' . implode(',', $changeRequest->statusOptions()),
        ];

        if (in_array($request->status, ['declined', 'cancelled'])) {
            $rules['rejection_reason'] = 'required|string|max:2000';
        }

        if ($request->status === 'on_hold') {
            $rules['hold_reason'] = 'required|string|max:2000';
        }

        if ($request->status === 'scheduled') {
            $rules['scheduled_date'] = 'required|date|after_or_equal:today';
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

            if ($newStatus === 'on_hold') {
                $updateData['hold_reason'] = $request->hold_reason;
            }

            // Record the scheduled date when scheduling; clear it otherwise so
            // un-scheduling restarts the SLA clock.
            $updateData['scheduled_date'] = $newStatus === 'scheduled' ? $request->scheduled_date : null;

            $changeRequest->update($updateData);

            // Mark any unresolved items as not done when closing a request,
            // and clear pending approval tokens so stale links die with it
            if (in_array($newStatus, ChangeRequest::TERMINAL_STATUSES)) {
                $changeRequest->items()->where('status', 'in_progress')->update(['status' => 'not_done']);
                $changeRequest->approvers()->where('status', 'pending')->update(['token' => null]);
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

            // Notify the requester of the status change. Going on hold gets a
            // dedicated email explaining the reason and the paused SLA.
            if ($newStatus === 'on_hold') {
                EmailLog::dispatch($changeRequest->requester_email, new RequestOnHold($changeRequest), $changeRequest);
            } elseif ($newStatus === 'awaiting_funding' && $changeRequest->isContentRequest()) {
                EmailLog::dispatch($changeRequest->requester_email, new \App\Mail\ContentAwaitingFunding($changeRequest), $changeRequest);
                $this->notifyWatchers($changeRequest, fn ($w) => new \App\Mail\ContentAwaitingFunding($changeRequest, $w));
            } elseif ($newStatus === 'done' && $changeRequest->isContentRequest()) {
                // The email the suggester has been waiting months for: where it landed.
                EmailLog::dispatch($changeRequest->requester_email, new \App\Mail\ContentPublished($changeRequest), $changeRequest);
                $this->notifyWatchers($changeRequest, fn ($w) => new \App\Mail\ContentPublished($changeRequest, $w));
            } else {
                EmailLog::dispatch($changeRequest->requester_email, new RequestStatusChanged($changeRequest, $oldStatus, $newStatus), $changeRequest);
            }

            // Manually approving an access request kicks off training
            if ($newStatus === 'approved' && $changeRequest->isAccessRequest()) {
                ApprovalWorkflowService::startTraining($changeRequest, auth()->id());
            }

            // Completing an access request tells the recipient their access is ready
            if ($newStatus === 'done') {
                ApprovalWorkflowService::notifyAccessGranted($changeRequest);
            }
        }

        return back()->with('success', 'Status updated.');
    }

    public function requestClarification(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'clarification_message' => 'required|string|max:2000',
        ]);

        if (!$changeRequest->isActive()) {
            return back()->with('error', 'Clarification cannot be requested on a closed request.');
        }

        $oldStatus = $changeRequest->status;

        $changeRequest->update([
            'status' => 'awaiting_user',
            'clarification_message' => $request->clarification_message,
            'clarification_requested_at' => now(),
        ]);

        if ($oldStatus !== 'awaiting_user') {
            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => auth()->id(),
                'old_status' => $oldStatus,
                'new_status' => 'awaiting_user',
            ]);

            AuditService::log(
                action: 'status_changed',
                model: $changeRequest,
                description: "Clarification requested on {$changeRequest->reference}: {$oldStatus} → awaiting_user",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'awaiting_user'],
            );
        }

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => 'Clarification requested: ' . $request->clarification_message,
        ]);

        EmailLog::dispatch($changeRequest->requester_email, new ClarificationRequested($changeRequest), $changeRequest);

        return back()->with('success', 'Clarification requested — the requester has been emailed.');
    }

    public function sendTrainingEmail(ChangeRequest $changeRequest)
    {
        abort_unless($changeRequest->isAccessRequest(), 404);

        if ($changeRequest->training_confirmed_at) {
            return back()->with('info', 'Training has already been confirmed.');
        }

        if (!in_array($changeRequest->status, ['approved', 'training'])) {
            return back()->with('error', 'Training emails can only be sent once the request is approved.');
        }

        $resend = (bool) $changeRequest->training_sent_at;

        if (!ApprovalWorkflowService::startTraining($changeRequest, auth()->id())) {
            return back()->with('error', 'Training email could not be sent — check a training video URL is configured for this content type and the request has a recipient email.');
        }

        $changeRequest->notes()->create([
            'user_id' => auth()->id(),
            'note' => ($resend ? 'Training email resent to ' : 'Training email sent to ') . $changeRequest->access_recipient_email . '.',
        ]);

        return back()->with('success', $resend ? 'Training email resent.' : 'Training email sent.');
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
        // A file belongs either to one of this request's line items, or — for a
        // content brief, which has no items — to the request itself.
        $belongsToItem = $changeRequest->items()->pluck('id')->contains($file->change_request_item_id);
        $belongsToRequest = $file->change_request_id === $changeRequest->id;

        if (!$belongsToItem && !$belongsToRequest) {
            abort(404);
        }

        if ($file->purged_at) {
            abort(410, 'File removed after the request was closed.');
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

        // Block resolving an item while approvals are outstanding — mirrors the
        // request-level gate in updateStatus() so item-by-item completion can't
        // bypass approval (and trip the auto-complete below).
        if (in_array($request->status, ['done', 'not_done']) && !$changeRequest->canMovePastReferred()) {
            return back()->with('error', 'Cannot resolve items — there are outstanding approvals.');
        }

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

            ApprovalWorkflowService::notifyAccessGranted($changeRequest);

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
            // Content can be published to sites beyond its main home, so filtering
            // on site_id alone hides it from the very site it appears on.
            $query->where(fn ($q) => $q
                ->whereIn('site_id', $siteIds)
                ->orWhereHas('additionalSites', fn ($sub) => $sub->whereIn('sites.id', $siteIds)));
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

    /**
     * Save the draft copy. The model voids any clinical approval bound to the
     * previous version — see ChangeRequest::booted().
     */
    public function updateDraft(Request $request, ChangeRequest $changeRequest)
    {
        abort_unless($changeRequest->isContentRequest(), 404);

        $validated = $request->validate([
            'draft_content' => 'nullable|string|max:100000',
            'void_approval' => 'sometimes|accepted',
        ]);

        $new = $validated['draft_content'] ?? null;
        $unchanged = $new === $changeRequest->draft_content;

        // Approved copy is locked. Changing it needs an explicit acknowledgement
        // that the clinical sign-off is being thrown away — enforced here, not
        // just hidden in the UI, so the guarantee does not depend on the form.
        if ($changeRequest->hasBoundApproval() && !$unchanged && empty($validated['void_approval'])) {
            return back()
                ->withInput()
                ->withErrors(['draft_content' => 'This copy has been clinically approved. Unlock it first if you need to change it — doing so withdraws the approval.']);
        }

        $changeRequest->update(['draft_content' => $new]);

        if (!$unchanged && !empty($validated['void_approval'])) {
            AuditService::log(
                action: 'draft_unlocked',
                model: $changeRequest,
                description: "Approved copy edited on {$changeRequest->reference}; clinical approval withdrawn",
            );
        }

        return back()->with('success', $unchanged ? 'No changes to save.' : 'Draft saved.');
    }

    /**
     * Record where the content went live, one address per site.
     */
    public function updatePublished(Request $request, ChangeRequest $changeRequest)
    {
        abort_unless($changeRequest->isContentRequest(), 404);

        $validated = $request->validate([
            'published' => 'nullable|array',
            'published.*.url' => 'nullable|url|max:2048',
            'published.*.title' => 'nullable|string|max:512',
            'add_site_id' => ['nullable', \Illuminate\Validation\Rule::exists('sites', 'id')->where('is_active', true)],
            'remove_site_id' => 'nullable|integer',
        ]);

        // Where content actually lands is the content designer's call, not the
        // requester's — their step 4 answer is a suggestion, so sites can be
        // added and dropped here.
        if (!empty($validated['add_site_id']) && (int) $validated['add_site_id'] !== $changeRequest->site_id) {
            $changeRequest->additionalSites()->syncWithoutDetaching([(int) $validated['add_site_id']]);
        }

        if (!empty($validated['remove_site_id'])) {
            $changeRequest->additionalSites()->detach((int) $validated['remove_site_id']);
        }

        $changeRequest->load('additionalSites');

        foreach (($validated['published'] ?? []) as $siteId => $row) {
            $siteId = (int) $siteId;

            if ($siteId === $changeRequest->site_id) {
                $changeRequest->update([
                    'published_url' => $row['url'] ?? null,
                    'published_title' => $row['title'] ?? null,
                ]);
                continue;
            }

            if ($changeRequest->additionalSites()->where('sites.id', $siteId)->exists()) {
                $changeRequest->additionalSites()->updateExistingPivot($siteId, [
                    'published_url' => $row['url'] ?? null,
                    'published_title' => $row['title'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Addresses saved.');
    }

    /**
     * Only confirmed watchers are ever emailed, and never the suggester twice.
     */
    private function notifyWatchers(ChangeRequest $changeRequest, callable $mailable): void
    {
        $changeRequest->watchers()->confirmed()->get()
            ->reject(fn ($w) => $w->email === $changeRequest->requester_email)
            // Each watcher gets their own copy so it can carry their unsubscribe link.
            ->each(fn ($w) => EmailLog::dispatch($w->email, $mailable($w), $changeRequest));
    }

    /**
     * The public title — the only title safe to show on the suggestions list.
     * Written by the content designer at scoping, not taken from the requester,
     * whose words were not composed for publication.
     */
    public function updatePublicTitle(Request $request, ChangeRequest $changeRequest)
    {
        abort_unless($changeRequest->isContentRequest(), 404);

        $validated = $request->validate([
            'public_title' => 'nullable|string|max:255',
        ]);

        $changeRequest->update(['public_title' => $validated['public_title'] ?: null]);

        return back()->with('success', $validated['public_title']
            ? 'Public title saved — this suggestion now appears on the public list.'
            : 'Public title cleared — this suggestion no longer appears publicly.');
    }

    /**
     * Remove a watcher. Sign-up is public and unconfirmed rows hold an email
     * address nobody consented to, so an operator has to be able to clear them.
     */
    public function removeWatcher(ChangeRequest $changeRequest, \App\Models\ChangeRequestWatcher $watcher)
    {
        abort_unless($watcher->change_request_id === $changeRequest->id, 404);

        $watcher->delete();

        return back()->with('success', 'Watcher removed.');
    }
}
