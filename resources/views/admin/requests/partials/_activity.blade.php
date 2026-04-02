{{-- Activity timeline: notes + status changes merged --}}
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity</h2>

    <div class="relative">
        <div class="absolute left-3.5 top-2 bottom-2 w-px bg-gray-200"></div>

        <div class="space-y-4">
            @forelse($activities as $activity)
            <div class="relative flex items-start space-x-3 pl-1">
                @if($activity->type === 'created')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-hcrg-burgundy/20 flex items-center justify-center ring-4 ring-white z-10">
                        <div class="w-2 h-2 rounded-full bg-hcrg-burgundy"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700"><span class="font-medium">{{ $activity->user }}</span> submitted this request</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @elseif($activity->type === 'status')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center ring-4 ring-white z-10">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ $activity->user }}</span> changed status
                            @include('partials.status-badge', ['status' => $activity->old_status])
                            <span class="text-gray-400 mx-0.5">&rarr;</span>
                            @include('partials.status-badge', ['status' => $activity->new_status])
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @elseif($activity->type === 'approval')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full {{ $activity->approval_status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center ring-4 ring-white z-10">
                        <div class="w-2 h-2 rounded-full {{ $activity->approval_status === 'approved' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ $activity->user }}</span>
                            {{ $activity->approval_status === 'approved' ? 'approved' : 'rejected' }} this request
                        </p>
                        @if($activity->notes)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $activity->notes }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @elseif($activity->type === 'override')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center ring-4 ring-white z-10">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700"><span class="font-medium">{{ $activity->user }}</span> overrode the approval gate</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @elseif($activity->type === 'email')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center ring-4 ring-white z-10">
                        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">{{ $activity->subject }}</span>
                            <span class="text-gray-400">&rarr;</span> {{ $activity->recipient }}
                            @if($activity->status === 'failed')
                                <span class="text-xs text-red-500 font-medium ml-1">Failed</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @elseif($activity->type === 'note')
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center ring-4 ring-white z-10">
                        <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700">{{ $activity->note }}</p>
                        <p class="text-xs text-gray-400 mt-0.5"><span class="font-medium">{{ $activity->user }}</span> &middot; {{ $activity->date->format('d M Y H:i') }} &middot; {{ $activity->date->diffForHumans() }}</p>
                    </div>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-400 pl-1">No activity recorded.</p>
            @endforelse
        </div>
    </div>

    {{-- Add note form --}}
    <form method="POST" action="{{ route('admin.requests.notes', $changeRequest) }}" class="mt-6 pt-4 border-t border-gray-200">
        @csrf
        <div class="flex items-start space-x-3">
            <div class="flex-1">
                <textarea name="note" rows="2" required placeholder="Add a note..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"></textarea>
            </div>
            <button type="submit" class="flex-shrink-0 bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add</button>
        </div>
    </form>
</div>
