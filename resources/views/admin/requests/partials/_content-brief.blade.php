{{-- The brief is the whole substance of a content request; without it the content
     designer has nothing to work from. --}}
@if($changeRequest->isContentRequest())
@php $brief = $changeRequest->content_brief ?? []; @endphp
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">The brief</h2>
        @if($changeRequest->content_type && config("content-types.{$changeRequest->content_type}"))
            @php $type = config("content-types.{$changeRequest->content_type}"); @endphp
            <span class="text-xs text-hcrg-grey-400 border border-hcrg-grey-200 rounded-full px-3 py-1 whitespace-nowrap">
                {{ $type['label'] }} — <strong class="font-bold text-hcrg-charcoal">{{ $type['tag'] }}</strong>
            </span>
        @endif
    </div>

    <dl class="space-y-4">
        <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">What it's trying to achieve</dt>
            <dd class="text-sm text-hcrg-charcoal whitespace-pre-wrap">{{ $brief['achieve'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Who it's for</dt>
            <dd class="text-sm text-hcrg-charcoal">
                @php
                    $audiences = [
                        'patients' => 'Patients & service users', 'families' => 'Families & carers',
                        'referrers' => 'Referrers & GPs', 'staff' => 'Our staff',
                        'commissioners' => 'Commissioners', 'public' => 'General public',
                    ];
                @endphp
                {{ collect($brief['audience'] ?? [])->map(fn ($a) => $audiences[$a] ?? $a)->join(', ') ?: '—' }}
            </dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">What they should know or do</dt>
            <dd class="text-sm text-hcrg-charcoal whitespace-pre-wrap">{{ $brief['know_or_do'] ?? '—' }}</dd>
        </div>
        @if(!empty($brief['measure']))
        <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">How we'll know it worked</dt>
            <dd class="text-sm text-hcrg-charcoal">{{ $brief['measure'] }}</dd>
        </div>
        @endif
        <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Does something like this already exist?</dt>
            <dd class="text-sm text-hcrg-charcoal">
                {{ ['yes' => 'Yes, something similar', 'no' => 'No', 'not_sure' => 'Not sure'][$brief['already_exists'] ?? ''] ?? '—' }}
                @if(!empty($brief['already_exists_detail']))
                    <span class="block text-hcrg-grey-400 mt-1">{{ $brief['already_exists_detail'] }}</span>
                @endif
            </dd>
        </div>
    </dl>

    @if($changeRequest->watchers->isNotEmpty())
    <div class="mt-6 pt-5 border-t border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Following this suggestion</h3>
        <p class="text-xs text-gray-500 mb-3">People who asked to be told when it moves. Only confirmed addresses are emailed.</p>
        <ul class="space-y-2">
            @foreach($changeRequest->watchers as $watcher)
                <li class="flex items-center justify-between p-2 border border-gray-200 rounded-lg">
                    <span class="text-sm text-hcrg-charcoal">{{ $watcher->email }}</span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs {{ $watcher->confirmed_at ? 'text-hcrg-grey-400' : 'text-amber-600' }}">
                            {{ $watcher->confirmed_at ? 'confirmed' : 'not confirmed — not emailed' }}
                        </span>
                        <form method="POST" action="{{ route('admin.requests.watcher.remove', [$changeRequest, $watcher]) }}"
                              data-confirm="Remove {{ $watcher->email }} from this suggestion?">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- The only title safe to publish: written here, not taken from the requester. --}}
    <form method="POST" action="{{ route('admin.requests.public-title', $changeRequest) }}" class="mt-6 pt-5 border-t border-gray-100">
        @csrf @method('PATCH')
        <label for="publicTitle" class="block text-sm font-medium text-gray-700 mb-1">Public title</label>
        <p class="text-xs text-gray-500 mb-2">
            Shown on the public suggestions list and used by the duplicate check. Until you set one, this suggestion does not appear publicly at all.
        </p>
        <div class="flex flex-wrap gap-2">
            <input type="text" name="public_title" id="publicTitle" maxlength="255"
                value="{{ old('public_title', $changeRequest->public_title) }}"
                placeholder="e.g. What happens at your first appointment"
                class="flex-1 min-w-64 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            <button type="submit" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Save</button>
        </div>
        @error('public_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </form>
</div>
@endif
