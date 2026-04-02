@if(!empty($queue))
<div class="mt-8 pt-6 border-t border-gray-200">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">
        You have {{ count($queue) }} other request{{ count($queue) !== 1 ? 's' : '' }} awaiting your approval
    </h3>
    <div class="space-y-2">
        @foreach($queue as $item)
        <a href="{{ $item['url'] }}" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-hcrg-burgundy">{{ $item['reference'] }}</span>
                    <span class="text-sm text-gray-500 ml-2">{{ $item['site_name'] }}</span>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $item['page_title'] }}</p>
        </a>
        @endforeach
    </div>
</div>
@endif
