<?php

namespace App\Services;

use App\Mail\ApprovalDeclined;
use App\Mail\GroupApprovalSatisfied;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;

class ApprovalWorkflowService
{
    /**
     * Handle rejection of a change request by an approver.
     *
     * Builds the rejection reason, declines the request, logs the status
     * change, and sends notifications to both the requester and any
     * remaining pending approvers.
     *
     * @param  int|null  $userId  The admin user ID who recorded the rejection (null for public responses)
     */
    public static function handleRejection(
        ChangeRequest $changeRequest,
        ChangeRequestApprover $approver,
        ?string $notes,
        bool $shareDetails,
        ?int $userId = null,
    ): void {
        $rejectionReason = $shareDetails
            ? "Declined by {$approver->name}: {$notes}"
            : ($notes ?: 'Rejected by approver.');

        $changeRequest->update([
            'status' => 'declined',
            'rejection_reason' => $rejectionReason,
        ]);

        ChangeRequestStatusLog::create([
            'change_request_id' => $changeRequest->id,
            'user_id' => $userId,
            'old_status' => 'referred',
            'new_status' => 'declined',
        ]);

        // Notify the requester
        EmailLog::dispatch(
            $changeRequest->requester_email,
            new RequestStatusChanged($changeRequest, 'referred', 'declined'),
            $changeRequest,
        );

        // Notify other pending approvers that their approval is no longer needed
        $pendingApprovers = $changeRequest->approvers()
            ->where('status', 'pending')
            ->whereNotNull('email')
            ->get();

        foreach ($pendingApprovers as $pending) {
            EmailLog::dispatch(
                $pending->email,
                new ApprovalDeclined($changeRequest, $pending),
                $changeRequest,
            );
        }
    }

    /**
     * Handle a group approval being satisfied.
     *
     * Notifies remaining pending group members that their approval is no
     * longer needed and clears their tokens to prevent stale approval links.
     */
    public static function handleGroupSatisfied(
        ChangeRequest $changeRequest,
        ChangeRequestApprover $respondent,
    ): void {
        $pendingMembers = $changeRequest->approvers()
            ->where('group', $respondent->group)
            ->where('status', 'pending')
            ->where('id', '!=', $respondent->id)
            ->get();

        foreach ($pendingMembers as $member) {
            if ($member->email) {
                EmailLog::dispatch(
                    $member->email,
                    new GroupApprovalSatisfied($changeRequest, $member, $respondent->name),
                    $changeRequest,
                );
            }

            $member->update(['token' => null]);
        }
    }
}
