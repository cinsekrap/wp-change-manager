{{-- Line items --}}
<div class="bg-white rounded-lg shadow p-6">
    @php
        $totalItems = $changeRequest->items->count();
        $doneItems = $changeRequest->items->where('status', 'done')->count();
        $allDone = $totalItems > 0 && $doneItems === $totalItems;
        $itemStatusColors = [
            'in_progress' => 'bg-hcrg-burgundy',
            'done' => 'bg-emerald-500',
            'not_done' => 'bg-red-500',
        ];
    @endphp

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Change Items ({{ $totalItems }})</h2>
        @if($totalItems > 0)
        <div class="flex items-center space-x-3">
            <span class="text-sm text-gray-500">{{ $doneItems }} of {{ $totalItems }} items completed</span>
            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full {{ $allDone ? 'bg-emerald-500' : 'bg-hcrg-burgundy' }} transition-all" style="width: {{ round(($doneItems / $totalItems) * 100) }}%"></div>
            </div>
        </div>
        @endif
    </div>

    <div class="space-y-4">
        @foreach($changeRequest->items as $index => $item)
        @php
            $borderColor = match($item->action_type) {
                'add' => 'border-green-200',
                'delete' => 'border-red-200',
                default => 'border-hcrg-burgundy/20',
            };
        @endphp
        <div class="border {{ $borderColor }} rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                    <span class="flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full {{ $itemStatusColors[$item->status] ?? 'bg-gray-400' }}"></span>
                        <span class="text-sm font-medium text-gray-500">#{{ $index + 1 }}</span>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $item->action_type === 'add' ? 'bg-green-100 text-green-800' : ($item->action_type === 'delete' ? 'bg-red-100 text-red-800' : 'bg-hcrg-burgundy/10 text-hcrg-burgundy') }}">
                        {{ ucfirst($item->action_type) }}
                    </span>
                    @if($item->content_area)
                        <span class="text-sm text-gray-500">{{ $item->content_area }}</span>
                    @endif
                </div>
                <div class="flex items-center space-x-1 flex-shrink-0">
                    {{-- In Progress --}}
                    <form method="POST" action="{{ route('admin.requests.items.status', [$changeRequest, $item]) }}" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button type="submit" title="In Progress"
                            class="w-7 h-7 rounded-full flex items-center justify-center transition-colors {{ $item->status === 'in_progress' ? 'bg-hcrg-burgundy text-white' : 'bg-gray-100 text-gray-400 hover:bg-hcrg-burgundy/10 hover:text-hcrg-burgundy' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </button>
                    </form>
                    {{-- Done --}}
                    <form method="POST" action="{{ route('admin.requests.items.status', [$changeRequest, $item]) }}" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="done">
                        <button type="submit" title="Done"
                            class="w-7 h-7 rounded-full flex items-center justify-center transition-colors {{ $item->status === 'done' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </form>
                    {{-- Not Done --}}
                    <form method="POST" action="{{ route('admin.requests.items.status', [$changeRequest, $item]) }}" class="inline">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="not_done">
                        <button type="submit" title="Not Done"
                            class="w-7 h-7 rounded-full flex items-center justify-center transition-colors {{ $item->status === 'not_done' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-red-50 hover:text-red-600' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            @if($item->action_type === 'change' && $item->current_content)
                {{-- Change: show before -> after --}}
                <div class="space-y-2">
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-xs font-medium text-red-700 mb-1">Current content</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->current_content }}</p>
                    </div>
                    <div class="flex justify-center">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-xs font-medium text-green-700 mb-1">Replace with</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                    </div>
                </div>
            @elseif($item->action_type === 'delete')
                {{-- Delete: show what's being removed --}}
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-xs font-medium text-red-700 mb-1">Content to remove</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                </div>
                @if($item->current_content)
                    <p class="mt-2 text-sm text-gray-500"><span class="font-medium">Reason:</span> {{ $item->current_content }}</p>
                @endif
            @elseif($item->action_type === 'add')
                {{-- Add: show what's being added --}}
                <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-xs font-medium text-green-700 mb-1">New content</p>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                </div>
            @else
                {{-- Fallback: plain text --}}
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
            @endif

            @if($item->files->isNotEmpty())
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-500 mb-2">Attachments:</p>
                <div class="space-y-1">
                    @foreach($item->files as $file)
                    <div class="mb-2">
                        <a href="{{ route('admin.requests.download', [$changeRequest, $file]) }}"
                           class="flex items-center text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">
                            <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ $file->original_filename }}
                            <span class="ml-1 text-gray-400 text-xs">({{ number_format($file->file_size / 1024, 0) }}KB)</span>
                        </a>
                        @if($file->title)
                            <p class="text-sm font-medium text-gray-700 ml-5">{{ $file->title }}</p>
                        @endif
                        @if($file->description)
                            <p class="text-xs text-gray-500 ml-5">{{ $file->description }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
