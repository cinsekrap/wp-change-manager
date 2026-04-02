@extends('layouts.admin')
@section('title', 'Change Requests')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Change Requests</h1>

@php
    $selectedStatuses = (array) request('status', []);
    $selectedSites = (array) request('site_id', []);
    $selectedPriorities = (array) request('priority', []);
    $selectedTags = (array) request('tags', []);
    $myRequestsActive = request('my_requests');
    $currentSort = request('sort');
    $currentDir = request('direction', 'asc');
    $sortParams = function($column) use ($currentSort, $currentDir) {
        $params = request()->except('page');
        $params['sort'] = $column;
        $params['direction'] = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        return $params;
    };
@endphp

{{-- My Requests toggle --}}
<div class="mb-4">
    @php
        $myParams = array_merge(request()->query(), ['my_requests' => 1]);
        $allParams = request()->except('my_requests');
    @endphp
    <a href="{{ route('admin.requests.index', $allParams) }}"
        class="inline-flex items-center px-4 py-1.5 text-sm font-medium rounded-full transition-colors {{ !$myRequestsActive ? 'bg-hcrg-burgundy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        All Requests
    </a>
    <a href="{{ route('admin.requests.index', $myParams) }}"
        class="inline-flex items-center px-4 py-1.5 text-sm font-medium rounded-full transition-colors {{ $myRequestsActive ? 'bg-hcrg-burgundy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
        My Requests
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.requests.index') }}" class="bg-white rounded-lg shadow p-4 mb-6" id="filterForm">
    @if($myRequestsActive)<input type="hidden" name="my_requests" value="1">@endif
    @if($currentSort)<input type="hidden" name="sort" value="{{ $currentSort }}"><input type="hidden" name="direction" value="{{ $currentDir }}">@endif
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, name, email..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        {{-- Status multi-select dropdown --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <div class="relative multi-dropdown">
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-haspopup="true" aria-expanded="false"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy flex items-center justify-between">
                    <span class="multi-label truncate">{{ empty($selectedStatuses) ? 'All statuses' : count($selectedStatuses) . ' selected' }}</span>
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
                    <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm border-b border-gray-100 font-medium">
                        <input type="checkbox" id="statusSelectAll" class="select-all-toggle h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded mr-2">
                        All
                    </label>
                    @foreach(\App\Models\ChangeRequest::STATUSES as $status)
                    <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm">
                        <input type="checkbox" name="status[]" value="{{ $status }}" {{ in_array($status, $selectedStatuses) ? 'checked' : '' }}
                            class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded mr-2">
                        {{ $status === 'requires_referral' ? 'Requires Referral' : ucfirst($status) }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Priority multi-select dropdown --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
            <div class="relative multi-dropdown">
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-haspopup="true" aria-expanded="false"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy flex items-center justify-between">
                    <span class="multi-label truncate">{{ empty($selectedPriorities) ? 'All priorities' : count($selectedPriorities) . ' selected' }}</span>
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
                    @foreach(\App\Models\ChangeRequest::PRIORITIES as $priority)
                    <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm">
                        <input type="checkbox" name="priority[]" value="{{ $priority }}" {{ in_array($priority, $selectedPriorities) ? 'checked' : '' }}
                            class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded mr-2">
                        {{ ucfirst($priority) }}
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Site multi-select dropdown --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Site</label>
            <div class="relative multi-dropdown">
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-haspopup="true" aria-expanded="false"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy flex items-center justify-between">
                    <span class="multi-label truncate">{{ empty($selectedSites) ? 'All sites' : count($selectedSites) . ' selected' }}</span>
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
                    @foreach($sites as $site)
                    <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm whitespace-nowrap">
                        <input type="checkbox" name="site_id[]" value="{{ $site->id }}" {{ in_array((string)$site->id, $selectedSites) ? 'checked' : '' }}
                            class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded mr-2 flex-shrink-0">
                        <span class="truncate">{{ $site->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tags multi-select dropdown --}}
        @if($allTags->isNotEmpty())
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Tags</label>
            <div class="relative multi-dropdown">
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-haspopup="true" aria-expanded="false"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy flex items-center justify-between">
                    <span class="multi-label truncate">{{ empty($selectedTags) ? 'All tags' : count($selectedTags) . ' selected' }}</span>
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
                    @foreach($allTags as $tag)
                    <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer text-sm whitespace-nowrap">
                        <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array((string)$tag->id, $selectedTags) ? 'checked' : '' }}
                            class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded mr-2 flex-shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full mr-1.5 flex-shrink-0" style="background-color: {{ $tag->colour }}"></span>
                        <span class="truncate">{{ $tag->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Assigned to dropdown --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Assigned to</label>
            <select name="assigned_to" onchange="this.form.submit()"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <option value="">All</option>
                <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                @foreach($adminUsers as $admin)
                    <option value="{{ $admin->id }}" {{ request('assigned_to') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy" onchange="this.form.submit()">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy" onchange="this.form.submit()">
        </div>
    </div>
    <div class="flex items-center justify-end gap-3 mt-3 pt-3 border-t border-gray-100">
        <a href="{{ route('admin.requests.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy text-sm font-medium rounded-full hover:bg-hcrg-burgundy hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export CSV
        </a>
        @if(request('search') || !empty($selectedStatuses) || !empty($selectedSites) || !empty($selectedPriorities) || !empty($selectedTags) || request('date_from') || request('date_to') || request('assigned_to') || request('my_requests') || request('sort'))
            <a href="{{ route('admin.requests.index') }}" onclick="localStorage.removeItem('acme_requests_filters')" class="text-sm text-gray-500 hover:text-gray-700">Clear filters</a>
        @endif
    </div>
</form>

{{-- Results --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left w-8">
                    <input type="checkbox" id="selectAll" class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded">
                </th>
                @foreach([
                    'reference' => 'Reference',
                    'site' => 'Site',
                    'requester_name' => 'Requester',
                ] as $col => $label)
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ route('admin.requests.index', $sortParams($col)) }}" class="group inline-flex items-center hover:text-gray-700 transition-colors">
                        {{ $label }}
                        @if($currentSort === $col)
                            <svg class="w-3 h-3 ml-1 text-hcrg-burgundy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $currentDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                        @else
                            <svg class="w-3 h-3 ml-1 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5 5 5M7 13l5 5 5-5"/></svg>
                        @endif
                    </a>
                </th>
                @endforeach
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                @foreach([
                    'priority' => 'Priority / SLA',
                    'status' => 'Status',
                    'created_at' => 'Date',
                ] as $col => $label)
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    <a href="{{ route('admin.requests.index', $sortParams($col)) }}" class="group inline-flex items-center hover:text-gray-700 transition-colors">
                        {{ $label }}
                        @if($currentSort === $col)
                            <svg class="w-3 h-3 ml-1 text-hcrg-burgundy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $currentDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                        @else
                            <svg class="w-3 h-3 ml-1 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5 5 5M7 13l5 5 5-5"/></svg>
                        @endif
                    </a>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($requests as $req)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-3 py-3" onclick="event.stopPropagation()">
                    <input type="checkbox" class="row-checkbox h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded" value="{{ $req->id }}">
                </td>
                <td class="px-3 py-3 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium text-sm whitespace-nowrap">{{ $req->reference }}</a>
                </td>
                <td class="px-3 py-3 text-gray-600 max-w-[150px] truncate cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->site->name ?? '—' }}</td>
                <td class="px-3 py-3 text-gray-600 cursor-pointer whitespace-nowrap" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->requester_name }}</td>
                <td class="px-3 py-3 text-gray-600 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <div class="flex items-center space-x-1.5">
                        @if($req->items_count > 0)
                        <span class="text-xs {{ $req->items_done_count === $req->items_count ? 'text-emerald-600 font-medium' : 'text-gray-500' }}">{{ $req->items_done_count }}/{{ $req->items_count }}</span>
                        <div class="w-10 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $req->items_done_count === $req->items_count ? 'bg-emerald-500' : 'bg-hcrg-burgundy' }}" style="width: {{ round(($req->items_done_count / $req->items_count) * 100) }}%"></div>
                        </div>
                        @else
                        <span class="text-xs text-gray-300">0</span>
                        @endif
                    </div>
                </td>
                <td class="px-3 py-3 text-gray-600 cursor-pointer whitespace-nowrap" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    @if($req->assignee)
                        <span class="inline-flex items-center">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-hcrg-burgundy/10 text-hcrg-burgundy text-[10px] font-semibold mr-1.5">{{ strtoupper(substr($req->assignee->name, 0, 1)) }}</span>
                            <span class="text-sm">{{ $req->assignee->name }}</span>
                        </span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-3 py-3 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <div class="flex items-center space-x-1.5">
                        @include('admin.partials.priority-badge', ['priority' => $req->priority ?? 'normal'])
                        @if($req->isActive())
                            @php $slaStatus = $req->slaStatus(); @endphp
                            @if($slaStatus === 'on_track')
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0" title="On track"></span>
                            @elseif($slaStatus === 'at_risk')
                                <span class="inline-block w-2 h-2 rounded-full bg-amber-500 flex-shrink-0" title="At risk"></span>
                            @else
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 flex-shrink-0 animate-pulse" title="Overdue"></span>
                            @endif
                        @endif
                    </div>
                </td>
                <td class="px-3 py-3 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <div class="flex items-center space-x-1">
                        @include('partials.status-badge', ['status' => $req->status])
                        @if($req->tags->isNotEmpty())
                            <span class="inline-flex items-center space-x-0.5 ml-0.5">
                                @foreach($req->tags->take(3) as $tag)
                                    <span class="inline-block w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $tag->colour }}" title="{{ $tag->name }}"></span>
                                @endforeach
                                @if($req->tags->count() > 3)
                                    <span class="text-[10px] text-gray-400">+{{ $req->tags->count() - 3 }}</span>
                                @endif
                            </span>
                        @endif
                    </div>
                </td>
                <td class="px-3 py-3 text-gray-500 cursor-pointer whitespace-nowrap" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="px-6 py-8 text-center text-gray-500">No requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="mt-4">{{ $requests->links() }}</div>

{{-- Bulk action bar --}}
<div id="bulkBar" class="hidden fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-lg border-t border-gray-200/60 shadow-[0_-4px_24px_rgba(0,0,0,0.08)] z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-hcrg-burgundy text-white text-sm font-bold"><span id="bulkCount">0</span><span class="ml-1 font-medium">selected</span></span>
            <button type="button" onclick="deselectAll()" class="text-sm text-gray-500 hover:text-gray-700 underline">Deselect all</button>
        </div>
        <div class="flex items-center space-x-3">
            {{-- Change Status --}}
            <div class="relative" id="bulkStatusDropdown">
                <button type="button" onclick="document.getElementById('bulkStatusMenu').classList.toggle('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-hcrg-burgundy text-white text-sm font-medium rounded-full hover:bg-[#9A1B4B] transition-colors shadow-sm">
                    Change Status
                    <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="bulkStatusMenu" class="hidden absolute bottom-full mb-2 right-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl py-1">
                    @foreach(\App\Models\ChangeRequest::STATUSES as $status)
                    <button type="button" onclick="bulkChangeStatus('{{ $status }}')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                        {{ $status === 'requires_referral' ? 'Requires Referral' : ucfirst($status) }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Assign to --}}
            <div class="relative" id="bulkAssignDropdown">
                <button type="button" onclick="document.getElementById('bulkAssignMenu').classList.toggle('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-hcrg-burgundy text-white text-sm font-medium rounded-full hover:bg-[#9A1B4B] transition-colors shadow-sm">
                    Assign to
                    <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="bulkAssignMenu" class="hidden absolute bottom-full mb-2 right-0 w-48 bg-white border border-gray-200 rounded-lg shadow-xl py-1 max-h-60 overflow-y-auto">
                    <button type="button" onclick="bulkAssign('')" class="block w-full text-left px-4 py-2 text-sm text-gray-400 hover:bg-gray-50 transition-colors">Unassign</button>
                    @foreach($adminUsers as $admin)
                    <button type="button" onclick="bulkAssign('{{ $admin->id }}')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">{{ $admin->name }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Export selected --}}
            <button type="button" onclick="exportSelected()" class="inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy text-sm font-medium rounded-full hover:bg-hcrg-burgundy hover:text-white transition-colors shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export
            </button>
        </div>
    </div>
</div>

<script>
var form = document.getElementById('filterForm');
var submitTimer = null;
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
var FILTER_KEY = 'acme_requests_filters';

// Filter persistence — save filters to localStorage, restore on empty visit
(function() {
    var params = new URLSearchParams(window.location.search);
    params.delete('page');
    if (params.toString().length > 0) {
        var saveParams = new URLSearchParams(window.location.search);
        saveParams.delete('page');
        localStorage.setItem(FILTER_KEY, '?' + saveParams.toString());
    } else {
        var saved = localStorage.getItem(FILTER_KEY);
        if (saved) {
            window.location.replace(window.location.pathname + saved);
            return;
        }
    }
})();

// Debounced auto-submit -- waits 800ms after last checkbox change
function debouncedSubmit() {
    clearTimeout(submitTimer);
    submitTimer = setTimeout(function() { form.submit(); }, 800);
}

form.querySelectorAll('input[type="checkbox"]:not(.select-all-toggle)').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var dd = this.closest('.multi-dropdown');
        if (!dd) return;
        var checked = dd.querySelectorAll('input[type="checkbox"]:checked:not(.select-all-toggle)');
        var total = dd.querySelectorAll('input[type="checkbox"]:not(.select-all-toggle)');
        var label = dd.querySelector('.multi-label');
        var nameAttr = this.name;
        var defaultLabels = { 'status[]': 'All statuses', 'priority[]': 'All priorities', 'site_id[]': 'All sites', 'tags[]': 'All tags' };
        var defaultLabel = defaultLabels[nameAttr] || 'All';
        label.textContent = checked.length === 0 ? defaultLabel : checked.length + ' selected';
        // Update "All" toggle if present
        var toggle = dd.querySelector('.select-all-toggle');
        if (toggle) {
            toggle.checked = checked.length === total.length;
            toggle.indeterminate = checked.length > 0 && checked.length < total.length;
        }
        debouncedSubmit();
    });
});

// "All" toggle for status dropdown
var statusSelectAll = document.getElementById('statusSelectAll');
if (statusSelectAll) {
    var statusDd = statusSelectAll.closest('.multi-dropdown');
    var statusCbs = statusDd.querySelectorAll('input[type="checkbox"]:not(.select-all-toggle)');
    var statusChecked = statusDd.querySelectorAll('input[type="checkbox"]:checked:not(.select-all-toggle)');
    statusSelectAll.checked = statusCbs.length > 0 && statusChecked.length === statusCbs.length;
    statusSelectAll.indeterminate = statusChecked.length > 0 && statusChecked.length < statusCbs.length;

    statusSelectAll.addEventListener('change', function() {
        var cbs = statusDd.querySelectorAll('input[type="checkbox"]:not(.select-all-toggle)');
        cbs.forEach(function(cb) { cb.checked = statusSelectAll.checked; });
        var label = statusDd.querySelector('.multi-label');
        label.textContent = statusSelectAll.checked ? cbs.length + ' selected' : 'All statuses';
        debouncedSubmit();
    });
}

// Submit search on Enter
form.querySelector('input[name="search"]').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
});

// Close multi-select dropdowns on click outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('.multi-dropdown').forEach(function(dd) {
        if (!dd.contains(e.target)) {
            var panel = dd.querySelector('.absolute');
            if (panel) panel.classList.add('hidden');
        }
    });
    // Close bulk action menus on click outside
    ['bulkStatusDropdown', 'bulkAssignDropdown'].forEach(function(id) {
        var dd = document.getElementById(id);
        if (dd && !dd.contains(e.target)) {
            var menu = dd.querySelector('[id$="Menu"]');
            if (menu) menu.classList.add('hidden');
        }
    });
});

// ---- Bulk actions ----
var selectAllCb = document.getElementById('selectAll');
var rowCheckboxes = document.querySelectorAll('.row-checkbox');
var bulkBar = document.getElementById('bulkBar');
var bulkCount = document.getElementById('bulkCount');

function getSelectedIds() {
    var ids = [];
    rowCheckboxes.forEach(function(cb) {
        if (cb.checked) ids.push(parseInt(cb.value));
    });
    return ids;
}

function updateBulkBar() {
    var ids = getSelectedIds();
    bulkCount.textContent = ids.length;
    if (ids.length > 0) {
        bulkBar.classList.remove('hidden');
    } else {
        bulkBar.classList.add('hidden');
    }
    // Update select-all state
    selectAllCb.checked = rowCheckboxes.length > 0 && ids.length === rowCheckboxes.length;
    selectAllCb.indeterminate = ids.length > 0 && ids.length < rowCheckboxes.length;
}

selectAllCb.addEventListener('change', function() {
    rowCheckboxes.forEach(function(cb) { cb.checked = selectAllCb.checked; });
    updateBulkBar();
});

rowCheckboxes.forEach(function(cb) {
    cb.addEventListener('change', updateBulkBar);
});

function deselectAll() {
    selectAllCb.checked = false;
    rowCheckboxes.forEach(function(cb) { cb.checked = false; });
    updateBulkBar();
}

function bulkChangeStatus(status) {
    var ids = getSelectedIds();
    if (!ids.length) return;
    if (!confirm('Change status of ' + ids.length + ' request(s) to "' + status + '"?')) return;
    document.getElementById('bulkStatusMenu').classList.add('hidden');

    fetch('{{ route("admin.requests.bulk.status") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ ids: ids, status: status })
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Something went wrong.'));
        }
    }).catch(function() { alert('An error occurred.'); });
}

function bulkAssign(userId) {
    var ids = getSelectedIds();
    if (!ids.length) return;
    document.getElementById('bulkAssignMenu').classList.add('hidden');

    fetch('{{ route("admin.requests.bulk.assign") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ ids: ids, assigned_to: userId || null })
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Something went wrong.'));
        }
    }).catch(function() { alert('An error occurred.'); });
}

function exportSelected() {
    var ids = getSelectedIds();
    if (!ids.length) return;
    window.location.href = '{{ route("admin.requests.export") }}?ids=' + ids.join(',');
}
</script>
@endsection
