<?php

namespace App\Services;

use App\Mail\AccessGranted;
use App\Mail\ApprovalDeclined;
use App\Mail\ContentRevisionNeeded;
use App\Mail\GroupApprovalSatisfied;
use App\Mail\RequestStatusChanged;
use App\Mail\TrainingRequested;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;

class ApprovalWorkflowService
{
    /**
     * Advance a change request to "approved" once all approvals are in.
     *
     * Centralises the auto-advance previously duplicated across the public
     * approval response, admin approval recording, and the override path.
     * For access requests this also kicks off the training step.
     *
     * @param  int|null  $userId  The admin user ID who triggered the advance (null for public responses)
     * @param  bool  $notifyRequester  Whether to email the requester about the status change
     */
    public static function advanceToApproved(
        ChangeRequest $changeRequest,
        ?int $userId = null,
        bool $notifyRequester = true,
    ): void {
        // The content lane waits at awaiting_approval rather than referred, so it
        // needs naming here too — otherwise an all-approved content request never
        // leaves approval: no status change, no log, no email.
        if (!in_array($changeRequest->status, ['requires_referral', 'referred', 'awaiting_approval'])) {
            return;
        }

        $oldStatus = $changeRequest->status;
        $changeRequest->update(['status' => 'approved']);

        ChangeRequestStatusLog::create([
            'change_request_id' => $changeRequest->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => 'approved',
        ]);

        if ($notifyRequester) {
            EmailLog::dispatch(
                $changeRequest->requester_email,
                new RequestStatusChanged($changeRequest, $oldStatus, 'approved'),
                $changeRequest,
            );
        }

        if ($changeRequest->isAccessRequest()) {
            static::startTraining($changeRequest, $userId);
        }
    }

    /**
     * Send the training email to the access recipient and move the request
     * to "training". Safe to re-call as a resend: the token is reused, the
     * email re-dispatched, and the status only transitions from "approved".
     *
     * Returns false when no training video URL is configured for the CPT —
     * the request stays at "approved" and a system note records why.
     */
    public static function startTraining(ChangeRequest $changeRequest, ?int $userId = null): bool
    {
        $trainingUrl = $changeRequest->cptType?->training_url;

        if (!$trainingUrl || !$changeRequest->access_recipient_email) {
            $cptName = $changeRequest->cptType->name ?? $changeRequest->cpt_slug;
            $reason = !$trainingUrl
                ? "no training video URL configured for content type \"{$cptName}\""
                : 'no access recipient email on the request';

            $changeRequest->notes()->create([
                'user_id' => $userId,
                'note' => "Training email not sent — {$reason}.",
            ]);

            return false;
        }

        if (!$changeRequest->training_token) {
            $changeRequest->training_token = ChangeRequest::generateTrainingToken();
        }
        $changeRequest->training_sent_at = now();
        $changeRequest->save();

        EmailLog::dispatch(
            $changeRequest->access_recipient_email,
            new TrainingRequested($changeRequest),
            $changeRequest,
        );

        if ($changeRequest->status === 'approved') {
            $changeRequest->update(['status' => 'training']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => $userId,
                'old_status' => 'approved',
                'new_status' => 'training',
            ]);
        }

        return true;
    }

    /**
     * Tell the access recipient their access has been set up. Sent when an
     * access request is completed; skipped when the recipient is also the
     * requester, who already gets the standard status-changed email.
     */
    public static function notifyAccessGranted(ChangeRequest $changeRequest): void
    {
        if (!$changeRequest->isAccessRequest() || !$changeRequest->access_recipient_email) {
            return;
        }

        if (strcasecmp($changeRequest->access_recipient_email, $changeRequest->requester_email) === 0) {
            return;
        }

        EmailLog::dispatch(
            $changeRequest->access_recipient_email,
            new AccessGranted($changeRequest),
            $changeRequest,
        );
    }

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

        // Record where it actually came from — content requests are declined from
        // awaiting_approval, not referred.
        $oldStatus = $changeRequest->status;

        // A clinician turning down copy is saying it is not safe as written, not
        // that the page should not exist. Content goes back to the designer who
        // wrote it, keeping the brief, the funding and the hours already spent.
        // Declining outright stays available as a deliberate status change.
        $newStatus = $changeRequest->isContentRequest() ? 'in_progress' : 'declined';

        $changeRequest->update([
            'status' => $newStatus,
            // Only a decline carries a reason; revision feedback lives on the
            // approver's row and in the note below.
            'rejection_reason' => $newStatus === 'declined' ? $rejectionReason : null,
        ]);

        ChangeRequestStatusLog::create([
            'change_request_id' => $changeRequest->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        if ($changeRequest->isContentRequest()) {
            $changeRequest->notes()->create([
                'user_id' => $userId,
                'note' => "Clinical approval not given by {$approver->name}".($notes ? ": {$notes}" : '.'),
            ]);

            // The designer wrote the copy and is the only person who can act on
            // the feedback. Nobody was telling them.
            EmailLog::dispatch(
                $changeRequest->assignee?->email,
                new ContentRevisionNeeded($changeRequest, $approver),
                $changeRequest,
            );
        }

        // Whoever asked for it, if anyone did — content the team started itself
        // has no requester.
        EmailLog::dispatch(
            $changeRequest->requester_email,
            new RequestStatusChanged($changeRequest, $oldStatus, $newStatus),
            $changeRequest,
        );

        // Notify other pending approvers that their approval is no longer
        // needed, and clear their tokens so stale approval links can't be
        // used and the requests no longer appear in approver queues
        $pendingApprovers = $changeRequest->approvers()
            ->where('status', 'pending')
            ->get();

        foreach ($pendingApprovers as $pending) {
            if ($pending->email) {
                EmailLog::dispatch(
                    $pending->email,
                    new ApprovalDeclined($changeRequest, $pending),
                    $changeRequest,
                );
            }

            $pending->update(['token' => null]);
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
