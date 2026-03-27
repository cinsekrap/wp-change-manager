<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Site;

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
        ];

        $recent = ChangeRequest::with('site')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent'));
    }
}
