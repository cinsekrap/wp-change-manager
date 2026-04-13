<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalOverridden;
use App\Mail\ApprovalRequested;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Services\ApprovalWorkflowService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ApproverController extends Controller
{
    public function addApprover(Request $request, ChangeRequest $changeRequest)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        $approver = $changeRequest->approvers()->create([
            'name' => $request->name,
            'email' => $request->email,
            'group' => $request->group ?: null,
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
            'note' => 'Added approver: ' . $request->name . ($request->group ? " (group: {$request->group})" : ''),
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

        // Notify remaining group members if this approval satisfies the group
        if ($request->status === 'approved' && $approver->group) {
            ApprovalWorkflowService::handleGroupSatisfied($changeRequest, $approver);
        }

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
            ApprovalWorkflowService::handleRejection(
                $changeRequest,
                $approver,
                $request->notes,
                (bool) $request->share_details,
                auth()->id(),
            );

            return back()->with('success', 'Rejection recorded. Request has been declined and notifications sent.');
        }

        return back()->with('success', 'Approval recorded.');
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
                    'group' => $approver['group'] ?? null,
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

        $pendingApprovers = $changeRequest->approvers()->where('status', 'pending')->whereNotNull('token')->get();
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
}
