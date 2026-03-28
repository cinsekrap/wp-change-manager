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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference, name, email..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        {{-- Status multi-select dropdown --}}
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <div class="relative multi-dropdown">
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-left bg-white focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy flex items-center justify-between">
                    <span class="multi-label truncate">{{ empty($selectedStatuses) ? 'All statuses' : count($selectedStatuses) . ' selected' }}</span>
                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
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
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
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
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
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
                <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')"
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
        <div class="flex items-end space-x-2">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy" onchange="this.form.submit()">
            </div>
            <a href="{{ route('admin.requests.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy text-sm font-medium rounded-full hover:bg-hcrg-burgundy hover:text-white transition-colors flex-shrink-0">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            @if(request('search') || !empty($selectedStatuses) || !empty($selectedSites) || !empty($selectedPriorities) || !empty($selectedTags) || request('date_from') || request('date_to') || request('assigned_to') || request('my_requests'))
                <a href="{{ route('admin.requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700 py-2 flex-shrink-0">Clear</a>
            @endif
        </div>
    </div>
</form>

{{-- Results --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-3 py-3 text-left">
                    <input type="checkbox" id="selectAll" class="h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded">
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SLA</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($requests as $req)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-3 py-4" onclick="event.stopPropagation()">
                    <input type="checkbox" class="row-checkbox h-3.5 w-3.5 text-hcrg-burgundy border-gray-300 rounded" value="{{ $req->id }}">
                </td>
                <td class="px-4 py-4 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium text-sm">{{ $req->reference }}</a>
                </td>
                <td class="px-4 py-4 text-sm text-gray-600 max-w-[150px] truncate cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->site->name ?? '—' }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 max-w-[200px] truncate cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->page_title ?: $req->page_url }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->requester_name }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <div class="flex items-center space-x-2">
                        <span>{{ $req->items_count }}</span>
                        @if($req->items_count > 0)
                        <span class="text-xs {{ $req->items_done_count === $req->items_count ? 'text-emerald-600 font-medium' : 'text-gray-400' }}">{{ $req->items_done_count }}/{{ $req->items_count }}</span>
                        <div class="w-12 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $req->items_done_count === $req->items_count ? 'bg-emerald-500' : 'bg-blue-500' }}" style="width: {{ round(($req->items_done_count / $req->items_count) * 100) }}%"></div>
                        </div>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-4 text-sm cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    @if($req->assignee)
                        <span class="text-gray-700" title="{{ $req->assignee->name }}">{{ explode(' ', $req->assignee->name)[0] }}</span>
                    @else
                        <span class="text-gray-300">&mdash;</span>
                    @endif
                </td>
                <td class="px-4 py-4 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    @include('admin.partials.priority-badge', ['priority' => $req->priority ?? 'normal'])
                </td>
                <td class="px-4 py-4 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    <div class="flex items-center space-x-1">
                        @include('admin.partials.status-badge', ['status' => $req->status])
                        @foreach($req->tags as $tag)
                            <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $tag->colour }}" title="{{ $tag->name }}"></span>
                        @endforeach
                    </div>
                </td>
                <td class="px-4 py-4 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                    @if($req->isActive())
                        @php $slaStatus = $req->slaStatus(); @endphp
                        @if($slaStatus === 'on_track')
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500" title="On track"></span>
                        @elseif($slaStatus === 'at_risk')
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500" title="At risk"></span>
                        @else
                            <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500" title="Overdue"></span>
                        @endif
                    @endif
                </td>
                <td class="px-4 py-4 text-sm text-gray-500 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">{{ $req->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="11" class="px-6 py-8 text-center text-gray-500">No requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $requests->links() }}</div>

{{-- Bulk action bar --}}
<div id="bulkBar" class="hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-gray-700"><span id="bulkCount">0</span> request(s) selected</span>
            <button type="button" onclick="deselectAll()" class="text-sm text-gray-500 hover:text-gray-700 underline">Deselect all</button>
        </div>
        <div class="flex items-center space-x-3">
            {{-- Change Status --}}
            <div class="relative" id="bulkStatusDropdown">
                <button type="button" onclick="document.getElementById('bulkStatusMenu').classList.toggle('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-hcrg-burgundy text-white text-sm font-medium rounded-full hover:bg-[#9A1B4B] transition-colors">
                    Change Status
                    <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="bulkStatusMenu" class="hidden absolute bottom-full mb-1 right-0 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                    @foreach(\App\Models\ChangeRequest::STATUSES as $status)
                    <button type="button" onclick="bulkChangeStatus('{{ $status }}')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        {{ $status === 'requires_referral' ? 'Requires Referral' : ucfirst($status) }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Assign to --}}
            <div class="relative" id="bulkAssignDropdown">
                <button type="button" onclick="document.getElementById('bulkAssignMenu').classList.toggle('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-hcrg-burgundy text-white text-sm font-medium rounded-full hover:bg-[#9A1B4B] transition-colors">
                    Assign to
                    <svg class="w-3 h-3 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="bulkAssignMenu" class="hidden absolute bottom-full mb-1 right-0 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1 max-h-60 overflow-y-auto">
                    <button type="button" onclick="bulkAssign('')" class="block w-full text-left px-4 py-2 text-sm text-gray-400 hover:bg-gray-50">Unassign</button>
                    @foreach($adminUsers as $admin)
                    <button type="button" onclick="bulkAssign('{{ $admin->id }}')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ $admin->name }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Export selected --}}
            <button type="button" onclick="exportSelected()" class="inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy text-sm font-medium rounded-full hover:bg-hcrg-burgundy hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Selected
            </button>
        </div>
    </div>
</div>

<script>
var form = document.getElementById('filterForm');
var submitTimer = null;
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Debounced auto-submit -- waits 800ms after last checkbox change
function debouncedSubmit() {
    clearTimeout(submitTimer);
    submitTimer = setTimeout(function() { form.submit(); }, 800);
}

form.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        // Update the label immediately
        var dd = this.closest('.multi-dropdown');
        var checked = dd.querySelectorAll('input[type="checkbox"]:checked');
        var label = dd.querySelector('.multi-label');
        var nameAttr = this.name;
        var defaultLabels = { 'status[]': 'All statuses', 'priority[]': 'All priorities', 'site_id[]': 'All sites', 'tags[]': 'All tags' };
        var defaultLabel = defaultLabels[nameAttr] || 'All';
        label.textContent = checked.length === 0 ? defaultLabel : checked.length + ' selected';
        debouncedSubmit();
    });
});

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
