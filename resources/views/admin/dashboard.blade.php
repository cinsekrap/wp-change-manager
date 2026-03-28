@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
@php $update = app(App\Services\UpdateService::class)->checkForUpdates(); @endphp
@if($update['available'])
<div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between">
    <span class="text-sm text-amber-800">A new version ({{ $update['latest_version'] }}) is available.</span>
    <a href="{{ route('admin.settings.updates') }}" class="text-sm font-medium text-hcrg-burgundy hover:underline">View update</a>
</div>
@endif
<h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">New Requests</div>
        <div class="text-3xl font-bold text-amber-600 mt-1">{{ $stats['requested'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">In Progress</div>
        <div class="text-3xl font-bold text-hcrg-burgundy mt-1">{{ $stats['in_progress'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ $stats['done'] }}</div>
    </div>
    <div class="bg-red-50 rounded-lg shadow p-5 ring-1 ring-red-100">
        <div class="text-xs font-medium text-red-400 uppercase tracking-wide">Overdue</div>
        <div class="text-3xl font-bold text-red-600 mt-1">{{ $stats['overdue'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Active Sites</div>
        <div class="text-3xl font-bold text-hcrg-charcoal mt-1">{{ $stats['sites'] }}</div>
    </div>
    <a href="{{ route('admin.requests.index', ['my_requests' => 1]) }}" class="bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-hcrg-burgundy transition-all block">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">My Requests</div>
        <div class="text-3xl font-bold text-hcrg-burgundy mt-1">{{ $stats['my_requests'] }}</div>
    </a>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8" style="margin-top: 0.5rem;">
    {{-- Chart 1: Requests by Status --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Requests by Status</h2>
        @php
            $statusColors = [
                'requested' => 'bg-amber-400',
                'requires_referral' => 'bg-pink-400',
                'referred' => 'bg-orange-400',
                'approved' => 'bg-hcrg-burgundy',
                'scheduled' => 'bg-purple-500',
                'done' => 'bg-emerald-500',
                'declined' => 'bg-red-500',
                'cancelled' => 'bg-gray-400',
            ];
            $statusLabels = [
                'requires_referral' => 'Requires Referral',
            ];
            $maxStatus = $statusCounts->max() ?: 1;
        @endphp
        <div class="space-y-3">
            @foreach(\App\Models\ChangeRequest::STATUSES as $status)
                @php
                    $count = $statusCounts[$status] ?? 0;
                    $pct = $maxStatus > 0 ? round(($count / $maxStatus) * 100) : 0;
                    $barColor = $statusColors[$status] ?? 'bg-gray-400';
                    $label = $statusLabels[$status] ?? ucfirst($status);
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-600">{{ $label }}</span>
                        <span class="text-sm font-semibold text-gray-800">{{ $count }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-4">
                        <div class="{{ $barColor }} h-4 rounded-full transition-all" style="width: {{ $pct }}%{{ $count > 0 && $pct < 3 ? '; min-width: 0.75rem' : '' }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Chart 2: Requests by Month (last 6 months) --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Requests by Month</h2>
        @php
            $maxMonth = $monthlyCounts->max() ?: 1;
        @endphp
        <div class="flex items-end justify-between gap-3" style="height: 200px;">
            @foreach($monthlyCounts as $month => $count)
                @php
                    $pct = $maxMonth > 0 ? round(($count / $maxMonth) * 100) : 0;
                    $monthLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M');
                @endphp
                <div class="flex flex-col items-center flex-1">
                    <span class="text-xs font-semibold text-gray-700 mb-1">{{ $count }}</span>
                    <div class="w-full flex flex-col justify-end" style="height: 160px;">
                        <div class="bg-hcrg-burgundy rounded-t-md w-full transition-all" style="height: {{ $pct }}%{{ $count > 0 && $pct < 5 ? '; min-height: 0.5rem' : '' }}"></div>
                    </div>
                    <span class="text-xs text-gray-500 mt-2">{{ $monthLabel }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Recent Requests</h2>
    </div>
    @if($recent->isEmpty())
        <div class="px-6 py-8 text-center text-gray-500">No requests yet.</div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($recent as $req)
                <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium">{{ $req->reference }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->site->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $req->requester_name }}</td>
                    <td class="px-6 py-4">
                        @include('admin.partials.status-badge', ['status' => $req->status])
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $req->created_at->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
