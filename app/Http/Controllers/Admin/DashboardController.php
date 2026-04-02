<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => ChangeRequest::count(),
            'requested' => ChangeRequest::whereIn('status', ['requested', 'requires_referral'])->count(),
            'in_progress' => ChangeRequest::whereIn('status', ['referred', 'approved', 'scheduled'])->count(),
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
        $monthlyRaw = ChangeRequest::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        // Fill in missing months with zero
        $monthlyCounts = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $monthlyCounts[$month] = $monthlyRaw[$month] ?? 0;
        }

        // Count overdue requests (active requests past SLA deadline)
        $overdueCount = ChangeRequest::whereNotIn('status', ChangeRequest::TERMINAL_STATUSES)
            ->select(['id', 'priority', 'created_at'])->get()
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

        return view('admin.dashboard', compact('stats', 'recent', 'statusCounts', 'monthlyCounts', 'topRequesters'));
    }
}
