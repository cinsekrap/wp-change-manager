<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->subMonths(5)->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $requests = ChangeRequest::with(['site', 'statusLogs', 'approvers', 'cptType'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        // Completion timestamp = latest transition into 'done'
        $doneAt = function (ChangeRequest $cr): ?Carbon {
            $log = $cr->statusLogs->where('new_status', 'done')->last();
            return $log?->created_at;
        };

        $completed = $requests->filter(fn ($cr) => $cr->status === 'done');

        // A request meets SLA when completed before its deadline, or when the
        // SLA clock was stopped (scheduled to an agreed date / awaiting training).
        $metSla = $completed->filter(function ($cr) use ($doneAt) {
            $done = $doneAt($cr);
            $clockStopped = $cr->statusLogs->whereIn('new_status', ['scheduled', 'training'])->isNotEmpty();
            return $clockStopped || ($done !== null && $done->lte($cr->slaDeadline()));
        });

        $completionDays = $completed
            ->map(fn ($cr) => ($done = $doneAt($cr)) ? round($cr->created_at->diffInHours($done) / 24, 1) : null)
            ->filter(fn ($d) => $d !== null);

        // Content is slow by design and that is reported honestly — but averaged
        // together with changes it produces a middle number describing neither
        // lane, so each is reported on its own as well.
        $daysFor = fn (string $type) => $completed
            ->where('request_type', $type)
            ->map(fn ($cr) => ($done = $doneAt($cr)) ? round($cr->created_at->diffInHours($done) / 24, 1) : null)
            ->filter(fn ($d) => $d !== null);

        $changeDays = $daysFor('change');
        $contentDays = $daysFor('content');

        $kpis = [
            'submitted' => $requests->count(),
            'completed' => $completed->count(),
            'avg_days' => $completionDays->isNotEmpty() ? round($completionDays->avg(), 1) : null,
            'avg_days_change' => $changeDays->isNotEmpty() ? round($changeDays->avg(), 1) : null,
            'avg_days_content' => $contentDays->isNotEmpty() ? round($contentDays->avg(), 1) : null,
            // Split like avg_days above: content is slow by design and would
            // otherwise drag the compliance figure down for the change lane.
            'sla_pct' => $completed->count() ? (int) round($metSla->count() / $completed->count() * 100) : null,
            'sla_pct_change' => ($changeCompleted = $completed->where('request_type', 'change'))->count()
                ? (int) round($metSla->where('request_type', 'change')->count() / $changeCompleted->count() * 100)
                : null,
            'declined' => $requests->where('status', 'declined')->count(),
            'cancelled' => $requests->where('status', 'cancelled')->count(),
            'open' => $requests->whereNotIn('status', ChangeRequest::TERMINAL_STATUSES)->count(),
        ];

        // Month-by-month: submitted vs completed (bucketed by completion date)
        $months = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to)) {
            $months[$cursor->format('Y-m')] = ['submitted' => 0, 'completed' => 0, 'days' => []];
            $cursor->addMonth();
        }
        foreach ($requests as $cr) {
            $key = $cr->created_at->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['submitted']++;
            }
            if ($cr->status === 'done' && ($done = $doneAt($cr))) {
                $dKey = $done->format('Y-m');
                if (isset($months[$dKey])) {
                    $months[$dKey]['completed']++;
                    $months[$dKey]['days'][] = $cr->created_at->diffInHours($done) / 24;
                }
            }
        }
        $monthly = collect($months)->map(fn ($m) => [
            'submitted' => $m['submitted'],
            'completed' => $m['completed'],
            'avg_days' => $m['days'] !== [] ? round(array_sum($m['days']) / count($m['days']), 1) : null,
        ]);

        // Breakdown by site and content type
        $bySite = $requests->groupBy(fn ($cr) => $cr->site?->name ?? 'Unknown')
            ->map(fn ($group) => [
                'total' => $group->count(),
                'completed' => $group->where('status', 'done')->count(),
            ])
            ->sortByDesc('total')
            ->take(8);

        $byCpt = $requests->groupBy(fn ($cr) => $cr->cptType?->name ?? ucfirst($cr->cpt_slug ?? 'Unknown'))
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(8);

        // Approval friction
        $responded = $requests->flatMap->approvers->whereNotNull('responded_at');
        $approvals = [
            'avg_response_days' => $responded->isNotEmpty()
                ? round($responded->map(fn ($a) => $a->created_at->diffInHours($a->responded_at) / 24)->avg(), 1)
                : null,
            'responded' => $responded->count(),
            'rejected' => $responded->where('status', 'rejected')->count(),
        ];

        // Access requests: training turnaround (approved -> trained)
        $accessRequests = $requests->filter(fn ($cr) => $cr->isAccessRequest());
        $trainingDays = $accessRequests->map(function ($cr) {
            $start = $cr->statusLogs->where('new_status', 'training')->first()?->created_at;
            $end = $cr->statusLogs->where('new_status', 'trained')->first()?->created_at;
            return ($start && $end) ? $start->diffInHours($end) / 24 : null;
        })->filter(fn ($d) => $d !== null);
        $access = [
            'total' => $accessRequests->count(),
            'avg_training_days' => $trainingDays->isNotEmpty() ? round($trainingDays->avg(), 1) : null,
        ];

        return view('admin.reports', compact('from', 'to', 'kpis', 'monthly', 'bySite', 'byCpt', 'approvals', 'access'));
    }
}
