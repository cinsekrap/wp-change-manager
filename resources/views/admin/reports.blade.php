@extends('layouts.admin')
@section('title', 'Reports')

@section('content')
@php $update = app(App\Services\UpdateService::class)->checkForUpdates(); @endphp
@if($update['available'])
<div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between">
    <span class="text-sm text-amber-800">A new version ({{ $update['latest_version'] }}) is available.</span>
    <a href="{{ route('admin.settings.updates') }}" class="text-sm font-medium text-hcrg-burgundy hover:underline">View update</a>
</div>
@endif
@unless($schedulerOk)
<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
    <span class="text-sm text-red-800">
        <strong>Scheduled tasks are not running.</strong>
        {{ $schedulerLastRun ? 'Last heartbeat ' . $schedulerLastRun->diffForHumans() . '.' : 'No heartbeat has ever been recorded.' }}
        Daily reminders and upload cleanup will not happen — check the hosting panel's scheduled task is enabled and running <code class="text-xs">artisan schedule:run</code> every minute.
    </span>
</div>
@endunless

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
        <p class="text-sm text-gray-500 mt-1">Management reporting on request volumes, turnaround and approvals.</p>
    </div>
    <form method="GET" action="{{ route('admin.reports') }}" class="flex items-end gap-2">
        <div>
            <label for="from" class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" id="from" name="from" value="{{ $from->format('Y-m-d') }}"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div>
            <label for="to" class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" id="to" name="to" value="{{ $to->format('Y-m-d') }}"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Apply</button>
    </form>
</div>

{{-- Where things stand right now. Deliberately above and outside the date range:
     an overdue request is overdue today whatever period is being reported on. --}}
<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="{{ route('admin.requests.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition-colors
              {{ $overdue > 0 ? 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
        <span class="text-lg font-bold leading-none">{{ $overdue }}</span> overdue
    </a>
    <a href="{{ route('admin.requests.index', ['my_requests' => 1]) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
        <span class="text-lg font-bold leading-none text-hcrg-burgundy">{{ $myRequests }}</span> assigned to me
    </a>
    @if($schedulerOk)
    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="Last heartbeat {{ $schedulerLastRun->diffForHumans() }}">
        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>Scheduler running
    </span>
    @else
    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
        <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span>Scheduler not running
    </span>
    @endif
</div>

{{-- Everything below is for the selected period. --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Submitted</div>
        <div class="text-3xl font-bold text-hcrg-charcoal mt-1">{{ $kpis['submitted'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</div>
        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ $kpis['completed'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg Days to Complete</div>
        <div class="text-3xl font-bold text-hcrg-burgundy mt-1">{{ $kpis['avg_days'] ?? '—' }}</div>
        {{-- Blended across both lanes this describes neither, so each is shown too. --}}
        <div class="mt-2 text-xs text-hcrg-grey-400 space-y-0.5">
            <div>Changes: <span class="font-semibold text-hcrg-charcoal">{{ $kpis['avg_days_change'] ?? '—' }}</span></div>
            <div>Content: <span class="font-semibold text-hcrg-charcoal">{{ $kpis['avg_days_content'] ?? '—' }}</span></div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Met SLA</div>
        <div class="text-3xl font-bold {{ ($kpis['sla_pct'] ?? 100) >= 80 ? 'text-emerald-600' : 'text-amber-600' }} mt-1">{{ $kpis['sla_pct'] !== null ? $kpis['sla_pct'] . '%' : '—' }}</div>
        <div class="mt-2 text-xs text-hcrg-grey-400">Changes only: <span class="font-semibold text-hcrg-charcoal">{{ $kpis['sla_pct_change'] !== null ? $kpis['sla_pct_change'] . '%' : '—' }}</span></div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Declined / Cancelled</div>
        <div class="text-3xl font-bold text-red-600 mt-1">{{ $kpis['declined'] + $kpis['cancelled'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Still Open</div>
        <div class="text-3xl font-bold text-amber-600 mt-1">{{ $kpis['open'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Submitted vs completed by month --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Submitted vs Completed</h2>
        <p class="text-xs text-gray-500 mb-4">Per month; completions counted in the month they were finished.</p>
        @php $maxMonthly = max($monthly->map(fn ($m) => max($m['submitted'], $m['completed']))->max() ?: 0, 1); @endphp
        <div class="flex items-end justify-between gap-2" style="height: 200px;">
            @foreach($monthly as $month => $m)
            <div class="flex flex-col items-center flex-1 min-w-0">
                <div class="w-full flex items-end justify-center gap-1" style="height: 160px;">
                    <div class="w-1/2 max-w-6 bg-hcrg-burgundy rounded-t-md" title="{{ $m['submitted'] }} submitted"
                         style="height: {{ round($m['submitted'] / $maxMonthly * 100) }}%{{ $m['submitted'] > 0 ? '; min-height: 0.375rem' : '' }}"></div>
                    <div class="w-1/2 max-w-6 bg-emerald-500 rounded-t-md" title="{{ $m['completed'] }} completed"
                         style="height: {{ round($m['completed'] / $maxMonthly * 100) }}%{{ $m['completed'] > 0 ? '; min-height: 0.375rem' : '' }}"></div>
                </div>
                <span class="text-xs text-gray-500 mt-2 truncate">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M y') }}</span>
                <span class="text-[10px] text-gray-400">{{ $m['submitted'] }}/{{ $m['completed'] }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
            <span class="inline-flex items-center"><span class="w-3 h-3 rounded-sm bg-hcrg-burgundy mr-1.5"></span>Submitted</span>
            <span class="inline-flex items-center"><span class="w-3 h-3 rounded-sm bg-emerald-500 mr-1.5"></span>Completed</span>
        </div>
    </div>

    {{-- Turnaround trend --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Average Days to Complete</h2>
        <p class="text-xs text-gray-500 mb-4">From submission to marked done, by month of completion.</p>
        @php $maxDays = max($monthly->pluck('avg_days')->filter()->max() ?: 0, 1); @endphp
        <div class="flex items-end justify-between gap-3" style="height: 200px;">
            @foreach($monthly as $month => $m)
            <div class="flex flex-col items-center flex-1 min-w-0">
                <span class="text-xs font-semibold text-gray-700 mb-1">{{ $m['avg_days'] ?? '' }}</span>
                <div class="w-full flex flex-col justify-end" style="height: 140px;">
                    <div class="bg-sky-500 rounded-t-md w-full" style="height: {{ $m['avg_days'] ? round($m['avg_days'] / $maxDays * 100) : 0 }}%{{ $m['avg_days'] ? '; min-height: 0.375rem' : '' }}"></div>
                </div>
                <span class="text-xs text-gray-500 mt-2 truncate">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M y') }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- By site --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Requests by Site</h2>
        @php $maxSite = max($bySite->pluck('total')->max() ?: 0, 1); @endphp
        <div class="space-y-3">
            @forelse($bySite as $site => $counts)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-600 truncate mr-2">{{ $site }}</span>
                    <span class="text-sm font-semibold text-gray-800 flex-shrink-0">{{ $counts['completed'] }}/{{ $counts['total'] }} done</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-4 relative">
                    <div class="bg-hcrg-burgundy/30 h-4 rounded-full absolute inset-y-0 left-0" style="width: {{ round($counts['total'] / $maxSite * 100) }}%"></div>
                    <div class="bg-hcrg-burgundy h-4 rounded-full absolute inset-y-0 left-0" style="width: {{ round($counts['completed'] / $maxSite * 100) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500">No requests in this period.</p>
            @endforelse
        </div>
    </div>

    {{-- By content type --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Requests by Content Type</h2>
        @php $maxCpt = max($byCpt->max() ?: 0, 1); @endphp
        <div class="space-y-3">
            @forelse($byCpt as $cpt => $count)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm text-gray-600 truncate mr-2">{{ $cpt }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ $count }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-4">
                    <div class="bg-teal-500 h-4 rounded-full" style="width: {{ round($count / $maxCpt * 100) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500">No requests in this period.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Approvals --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Approvals</h2>
        <dl class="grid grid-cols-3 gap-4">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg Response</dt>
                <dd class="text-2xl font-bold text-hcrg-charcoal mt-1">{{ $approvals['avg_response_days'] !== null ? $approvals['avg_response_days'] . ' days' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Responses</dt>
                <dd class="text-2xl font-bold text-hcrg-charcoal mt-1">{{ $approvals['responded'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Rejections</dt>
                <dd class="text-2xl font-bold {{ $approvals['rejected'] ? 'text-red-600' : 'text-hcrg-charcoal' }} mt-1">{{ $approvals['rejected'] }}</dd>
            </div>
        </dl>
        <p class="text-xs text-gray-500 mt-3">Response time is measured from when the approver was added to when they responded.</p>
    </div>

    {{-- Access requests --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Access Requests</h2>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Submitted</dt>
                <dd class="text-2xl font-bold text-hcrg-charcoal mt-1">{{ $access['total'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg Days to Train</dt>
                <dd class="text-2xl font-bold text-hcrg-charcoal mt-1">{{ $access['avg_training_days'] !== null ? $access['avg_training_days'] : '—' }}</dd>
            </div>
        </dl>
        <p class="text-xs text-gray-500 mt-3">Training turnaround runs from the training email being sent to the recipient confirming.</p>
    </div>
</div>
@endsection
