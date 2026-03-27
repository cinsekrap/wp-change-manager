<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
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

        return view('public.approval', compact('approver', 'changeRequest'));
    }

    public function respond(Request $request, string $token)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $approver = ChangeRequestApprover::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $approver->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'responded_at' => now(),
            'recorded_by' => null,
            'token' => null,
        ]);

        // Check if all approvers are now approved — auto-advance if so
        $changeRequest = $approver->changeRequest;
        $changeRequest->refresh();

        if ($changeRequest->approvalsAllApproved() && in_array($changeRequest->status, ['requires_referral', 'referred'])) {
            $oldStatus = $changeRequest->status;
            $changeRequest->update(['status' => 'approved']);

            // Use null user_id for system-triggered status changes
            ChangeRequestStatusLog::create([
                'change_request_id' => $changeRequest->id,
                'user_id' => null,
                'old_status' => $oldStatus,
                'new_status' => 'approved',
            ]);
        }

        return view('public.approval-complete', [
            'status' => $request->status,
            'changeRequest' => $changeRequest,
        ]);
    }
}
