<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => ChangeRequest::count(),
            'requested' => ChangeRequest::whereIn('status', ['requested', 'requires_referral'])->count(),
            'in_progress' => ChangeRequest::whereIn('status', ['referred', 'approved', 'training', 'trained', 'scheduled'])->count(),
            'done' => ChangeRequest::where('status', 'done')->count(),
            'sites' => Site::active()->count(),
            'my_requests' => ChangeRequest::where('assigned_to', auth()->id())
                ->whereNotIn('status', ChangeRequest::TERMINAL_STATUSES)
                ->count(),
        ];

        // Chart 1: Requests by status
        $statusCounts = ChangeRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Chart 2: Requests by month (last 6 months)
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $monthlyRaw = ChangeRequest::where('created_at', '>=', $sixMonthsAgo)
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->format('Y-m'));

        // Fill in missing months with zero
        $monthlyCounts = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $monthlyCounts[$month] = $monthlyRaw[$month] ?? 0;
        }

        // Count overdue requests (active requests past SLA deadline)
        $overdueCount = ChangeRequest::whereNotIn('status', ChangeRequest::TERMINAL_STATUSES)
            ->select(['id', 'status', 'priority', 'created_at'])->get()
            ->filter(fn($cr) => $cr->isOverSla())
            ->count();

        $stats['overdue'] = $overdueCount;

        $recent = ChangeRequest::with('site')
            ->latest()
            ->take(10)
            ->get();

        $topRequesters = ChangeRequest::selectRaw('requester_email, requester_name, count(*) as total')
            ->groupBy('requester_email', 'requester_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Scheduler heartbeat, written every minute by routes/console.php.
        // Defensive on purpose: whatever is in the cache (missing, legacy
        // object, corrupt), a bad heartbeat must show the warning, never 500.
        try {
            $heartbeat = Cache::get('scheduler_last_run');
        } catch (\Throwable) {
            $heartbeat = null;
        }
        $schedulerLastRun = is_numeric($heartbeat) ? Carbon::createFromTimestamp((int) $heartbeat) : null;
        $schedulerOk = $schedulerLastRun !== null && $schedulerLastRun->gt(now()->subMinutes(5));

        return view('admin.dashboard', compact('stats', 'recent', 'statusCounts', 'monthlyCounts', 'topRequesters', 'schedulerLastRun', 'schedulerOk'));
    }

    public function dismissWhatsNew(Request $request)
    {
        Setting::set('whats_new_seen_' . auth()->id(), config('version.current'));

        return response()->json(['ok' => true]);
    }
}
