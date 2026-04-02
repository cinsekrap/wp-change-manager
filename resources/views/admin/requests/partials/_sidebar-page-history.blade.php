{{-- Page history --}}
@if($pageHistory->isNotEmpty())
<div class="bg-white rounded-lg shadow p-4">
    <h2 class="text-sm font-semibold text-gray-900 mb-2">Page History</h2>
    <div class="space-y-2">
        @foreach($pageHistory as $prev)
        <a href="{{ route('admin.requests.show', $prev) }}" class="block px-3 py-2 bg-gray-50 rounded-lg hover:bg-gray-100">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-hcrg-burgundy">{{ $prev->reference }}</span>
                @include('partials.status-badge', ['status' => $prev->status])
            </div>
            <p class="text-xs text-gray-500 mt-0.5">{{ $prev->requester_name }} &middot; {{ $prev->created_at->format('d M Y') }} ({{ $prev->created_at->diffForHumans() }})</p>
        </a>
        @endforeach
    </div>
</div>
@endif
