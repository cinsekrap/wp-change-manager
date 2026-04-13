<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequestApprover;
use App\Models\ChangeRequestStatusLog;
use App\Services\ApprovalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

        if ($approver->group && $changeRequest->groupSatisfied($approver->group)) {
            return view('public.approval-group-satisfied', compact('approver', 'changeRequest'));
        }

        $queue = $this->getApproverQueue($approver);

        return view('public.approval', compact('approver', 'changeRequest', 'queue'));
    }

    public function showFromQueue(Request $request, ChangeRequestApprover $approver)
    {
        if (!$approver->token || $approver->status !== 'pending') {
            return view('public.approval-complete', [
                'status' => $approver->status,
                'changeRequest' => $approver->changeRequest,
                'queue' => [],
            ]);
        }

        return $this->show($approver->token);
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
            $queue = $this->getApproverQueue($approver);

            return view('public.approval-overridden', compact('approver', 'changeRequest', 'queue'));
        }

        if (in_array($changeRequest->status, ['declined', 'cancelled'])) {
            return view('public.approval-closed', compact('approver', 'changeRequest'));
        }

        // Guard against race condition: another group member may have already responded
        if ($approver->group && $changeRequest->groupSatisfied($approver->group)) {
            return view('public.approval-group-satisfied', compact('approver', 'changeRequest'));
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

        // Notify remaining group members if this approval satisfies the group
        if ($request->status === 'approved' && $approver->group) {
            ApprovalWorkflowService::handleGroupSatisfied($changeRequest, $approver);
        }

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
            ApprovalWorkflowService::handleRejection(
                $changeRequest,
                $approver,
                $request->notes,
                (bool) $request->share_details,
            );
        }

        $queue = $this->getApproverQueue($approver);

        return view('public.approval-complete', [
            'status' => $request->status,
            'changeRequest' => $changeRequest,
            'queue' => $queue,
        ]);
    }

    private function getApproverQueue(ChangeRequestApprover $currentApprover): array
    {
        return ChangeRequestApprover::where('email', $currentApprover->email)
            ->where('id', '!=', $currentApprover->id)
            ->where('status', 'pending')
            ->whereNotNull('token')
            ->with(['changeRequest.site'])
            ->get()
            ->filter(fn ($a) => !$a->changeRequest->approval_overridden)
            ->map(fn ($a) => [
                'reference' => $a->changeRequest->reference,
                'site_name' => $a->changeRequest->site->name ?? '—',
                'page_title' => $a->changeRequest->page_title ?? $a->changeRequest->page_url,
                'url' => URL::signedRoute('approval.queue', ['approver' => $a->id], now()->addHours(4)),
            ])
            ->values()
            ->toArray();
    }
}
