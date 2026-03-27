@extends('layouts.admin')
@section('title', 'Change Requests')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Change Requests</h1>

@php
    $selectedStatuses = (array) request('status', []);
    $selectedSites = (array) request('site_id', []);
@endphp

{{-- Filters --}}
<form method="GET" action="{{ route('admin.requests.index') }}" class="bg-white rounded-lg shadow p-4 mb-6" id="filterForm">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
            @if(request('search') || !empty($selectedStatuses) || !empty($selectedSites) || request('date_from') || request('date_to'))
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Page</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requester</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($requests as $req)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50 cursor-pointer" onclick="window.location='{{ route('admin.requests.show', $req) }}'">
                <td class="px-6 py-4">
                    <a href="{{ route('admin.requests.show', $req) }}" class="text-hcrg-burgundy hover:underline font-medium text-sm">{{ $req->reference }}</a>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-[150px] truncate">{{ $req->site->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-[200px] truncate">{{ $req->page_title ?: $req->page_url }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $req->requester_name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $req->items_count }}</td>
                <td class="px-6 py-4">
                    @include('admin.partials.status-badge', ['status' => $req->status])
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $req->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No requests found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $requests->links() }}</div>

<script>
var form = document.getElementById('filterForm');
var submitTimer = null;

// Debounced auto-submit — waits 800ms after last checkbox change
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
        var isStatus = this.name === 'status[]';
        label.textContent = checked.length === 0 ? (isStatus ? 'All statuses' : 'All sites') : checked.length + ' selected';
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
            dd.querySelector('.absolute').classList.add('hidden');
        }
    });
});
</script>
@endsection
