<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequestStatusLog;
use App\Models\FundingRound;
use App\Services\AuditService;
use Illuminate\Http\Request;

/**
 * Where a budget holder answers. No login: they hold a token, as approvers do.
 */
class FundingApprovalController extends Controller
{
    public function show(string $token)
    {
        $round = FundingRound::where('token', $token)->firstOrFail();

        $round->load(['items.changeRequest.site', 'items.changeRequest.additionalSites']);

        if (! $round->isPending()) {
            return view('public.funding-closed', compact('round'));
        }

        return view('public.funding-approval', compact('round'));
    }

    public function respond(Request $request, string $token)
    {
        $validated = $request->validate([
            'decision' => 'required|in:approved,declined',
            'notes' => 'required_if:decision,declined|nullable|string|max:2000',
        ], [], ['notes' => 'reason']);

        $round = FundingRound::where('token', $token)->where('status', 'pending')->firstOrFail();

        $round->update([
            'status' => $validated['decision'],
            'notes' => $validated['notes'] ?? null,
            'responded_at' => now(),
            // Spent. A link that keeps working after the decision is a link that
            // can undo it.
            'token' => null,
        ]);

        if ($validated['decision'] === 'approved') {
            foreach ($round->items as $item) {
                $changeRequest = $item->changeRequest;
                if (! $changeRequest || $changeRequest->status !== 'awaiting_funding') {
                    continue;
                }

                $changeRequest->update(['status' => 'in_progress']);
                ChangeRequestStatusLog::create([
                    'change_request_id' => $changeRequest->id,
                    'user_id' => null,
                    'old_status' => 'awaiting_funding',
                    'new_status' => 'in_progress',
                ]);
            }
        }

        AuditService::log(
            action: 'funding_'.$validated['decision'],
            model: $round,
            description: "Funding {$validated['decision']} by {$round->approver_name}: {$round->reference}",
        );

        return view('public.funding-closed', ['round' => $round->fresh(), 'justResponded' => true]);
    }
}
