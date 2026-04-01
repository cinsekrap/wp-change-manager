<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Mail\ApprovalDeclined;
use App\Mail\RequestStatusChanged;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function show(string $token)
    {
        $approver = ChangeRequestApprover::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $changeRequest = $approver->changeRequest()->with(['site', 'items'])->first();

        if ($changeRequest->approval_overridden) {
            return view('public.approval-overridden', compact('approver', 'changeRequest'));
        }

        if (in_array($changeRequest->status, ['declined', 'cancelled'])) {
            return view('public.approval-closed', compact('approver', 'changeRequest'));
        }

        return view('public.approval', compact('approver', 'changeRequest'));
    }

    public function respond(Request $request, string $token)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
            'share_details' => 'nullable|boolean',
        ]);

        $approver = ChangeRequestApprover::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $changeRequest = $approver->changeRequest()->with(['site', 'items'])->first();

        if ($changeRequest->approval_overridden) {
            return view('public.approval-overridden', compact('approver', 'changeRequest'));
        }

        if (in_array($changeRequest->status, ['declined', 'cancelled'])) {
            return view('public.approval-closed', compact('approver', 'changeRequest'));
        }

        $approver->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'responded_at' => now(),
            'recorded_by' => null,
            'token' => null,
        ]);

        $changeRequest = $approver->changeRequest;
        $changeRequest->refresh();

        // Auto-advance to approved if all approvers have approved
        if ($changeRequest->approvalsAllApproved() && in_array($changeRequest->status, ['requires_referral', 'referred'])) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'approved']);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'approved',
            ]);
        }

        // Auto-decline if an approver rejected and request is at referred
        if ($request->status === 'rejected' && $changeRequest->status === 'referred') {
            $rejectionReason = $request->share_details
                ? "Declined by {$approver->name}: {$request->notes}"
                : $request->notes;

            $changeRequest->update([
                'status' => 'declined',
                'rejection_reason' => $rejectionReason,
            ]);

            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => null,
                'old_status' => 'referred',
                'new_status' => 'declined',
            ]);

            // Notify the requester
            EmailLog::dispatch(
                $changeRequest->requester_email,
                new RequestStatusChanged($changeRequest, 'referred', 'declined'),
                $changeRequest
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
                    $changeRequest
                );
            }
        }

        return view('public.approval-complete', [
            'status' => $request->status,
            'changeRequest' => $changeRequest,
        ]);
    }
}
