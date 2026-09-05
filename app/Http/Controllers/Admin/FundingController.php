<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;

/**
 * Content waiting on a funding decision.
 *
 * Funding happens outside this tool, so this page exists to be taken to that
 * conversation: what people have asked for, what each piece costs, and how long
 * it has been sitting. The decision comes back as a status change, through the
 * same bulk action the request list uses — this is not a second approval system.
 */
class FundingController extends Controller
{
    /**
     * Nothing here is committed yet. Suggested is included deliberately even
     * though it has no estimate: it shows the designer what still needs sizing
     * before it can be taken anywhere.
     */
    public const AWAITING_DECISION = ['suggested', 'scoped', 'awaiting_funding'];

    public function index()
    {
        $requests = ChangeRequest::with(['site', 'additionalSites', 'assignee'])
            ->where('request_type', 'content')
            ->whereIn('status', self::AWAITING_DECISION)
            // Longest wait first: the queue is the argument.
            ->orderBy('created_at')
            ->get();

        return view('admin.funding', [
            'requests' => $requests,
            'totalHours' => $requests->sum(fn ($r) => (float) $r->estimated_hours),
            'unsized' => $requests->filter(fn ($r) => $r->estimated_hours === null)->count(),
        ]);
    }
}
