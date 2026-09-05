<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FundingRequested;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestStatusLog;
use App\Models\EmailLog;
use App\Models\FundingApprover;
use App\Models\FundingRound;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FundingRoundController extends Controller
{
    /**
     * Ask one person to fund a batch of content.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:change_requests,id',
            'funding_approver_id' => [
                'required',
                Rule::exists('funding_approvers', 'id')->where('is_active', true),
            ],
        ], [], ['funding_approver_id' => 'funding approver']);

        $requests = ChangeRequest::whereIn('id', $validated['ids'])
            ->where('request_type', 'content')
            ->whereIn('status', FundingController::AWAITING_DECISION)
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('error', 'Nothing in that selection is waiting on a funding decision.');
        }

        // Nobody can agree to pay for an unknown. An unsized piece has to be
        // sized up before it can go in an ask.
        $unsized = $requests->filter(fn ($r) => $r->estimated_hours === null);
        if ($unsized->isNotEmpty()) {
            return back()->with('error', 'Size these up first, or leave them out: '.$unsized->pluck('reference')->join(', '));
        }

        // A piece already sitting in someone's inbox must not be asked for twice.
        $alreadyAsked = $requests->filter(fn ($r) => $this->pendingRoundFor($r) !== null);
        if ($alreadyAsked->isNotEmpty()) {
            return back()->with('error', 'Already waiting on a funding decision: '.$alreadyAsked->pluck('reference')->join(', '));
        }

        $approver = FundingApprover::findOrFail($validated['funding_approver_id']);

        $round = DB::transaction(function () use ($requests, $approver) {
            $round = FundingRound::create([
                'reference' => FundingRound::generateReference(),
                'funding_approver_id' => $approver->id,
                // Copied in, so the record still says who was asked even after
                // the managed list changes.
                'approver_name' => $approver->label(),
                'approver_email' => $approver->email,
                'status' => 'pending',
                'token' => FundingRound::generateToken(),
                'total_hours' => $requests->sum(fn ($r) => (float) $r->estimated_hours),
                'requested_by' => auth()->id(),
            ]);

            foreach ($requests as $changeRequest) {
                $round->items()->create([
                    'change_request_id' => $changeRequest->id,
                    // The figure they are being shown, kept as it was.
                    'estimated_hours' => $changeRequest->estimated_hours,
                ]);

                if ($changeRequest->status !== 'awaiting_funding') {
                    $old = $changeRequest->status;
                    $changeRequest->update(['status' => 'awaiting_funding']);
                    ChangeRequestStatusLog::create([
                        'change_request_id' => $changeRequest->id,
                        'user_id' => auth()->id(),
                        'old_status' => $old,
                        'new_status' => 'awaiting_funding',
                    ]);
                }
            }

            return $round;
        });

        EmailLog::dispatch($round->approver_email, new FundingRequested($round));

        AuditService::log(
            action: 'funding_requested',
            model: $round,
            description: "Funding requested from {$round->approver_name}: {$round->reference}, {$round->total_hours} hours across {$requests->count()} pieces",
        );

        return back()->with('success', "Sent to {$approver->name}: {$requests->count()} pieces, ".self::hours($round->total_hours).' hours.');
    }

    /** The round a request is currently waiting on, if any. */
    public static function pendingRoundFor(ChangeRequest $changeRequest): ?FundingRound
    {
        return FundingRound::where('status', 'pending')
            ->whereHas('items', fn ($q) => $q->where('change_request_id', $changeRequest->id))
            ->first();
    }

    public static function hours(float|string|null $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 1), '0'), '.') ?: '0';
    }
}
