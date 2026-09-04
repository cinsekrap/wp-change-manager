{{-- Content lane: the draft copy, and where it went live. --}}
@if($changeRequest->isContentRequest())
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-900 mb-1">Draft copy</h2>
    <p class="text-sm text-gray-500 mb-4">
        Clinical approval binds to this text. Editing it after sign-off voids the approval and sends it back for re-approval.
    </p>

    @if($changeRequest->staleApprovals()->isNotEmpty())
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800">This copy has changed since it was approved, so the sign-off no longer applies.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.requests.draft', $changeRequest) }}">
        @csrf @method('PATCH')
        <textarea name="draft_content" rows="10"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('draft_content', $changeRequest->draft_content) }}</textarea>
        @error('draft_content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        <button type="submit" class="mt-3 bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Save draft</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-900 mb-1">Where it went live</h2>
    <p class="text-sm text-gray-500 mb-4">
        One address per site, recorded as you publish. The suggester gets these by email; the public queue shows only the site titles.
    </p>

    <form method="POST" action="{{ route('admin.requests.published', $changeRequest) }}">
        @csrf @method('PATCH')
        <div class="space-y-4">
            @foreach($changeRequest->allSites() as $site)
                @php $pivot = $changeRequest->publishedFor($site->id); @endphp
                <div class="p-4 border border-gray-200 rounded-lg">
                    <p class="text-sm font-semibold text-hcrg-charcoal mb-2">
                        {{ $site->name }}
                        @if($site->id === $changeRequest->site_id)
                            <span class="ml-2 text-xs font-normal text-hcrg-grey-400">main home</span>
                        @endif
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="url" name="published[{{ $site->id }}][url]" placeholder="https://..."
                            value="{{ $pivot['published_url'] ?? '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        <input type="text" name="published[{{ $site->id }}][title]" placeholder="Page title"
                            value="{{ $pivot['published_title'] ?? '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    </div>
                </div>
            @endforeach
        </div>
        <button type="submit" class="mt-4 bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Save addresses</button>
    </form>
</div>
@endif
