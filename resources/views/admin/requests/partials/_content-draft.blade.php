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

    @php $locked = $changeRequest->hasBoundApproval() && !request()->boolean('unlock_draft'); @endphp

    @if($locked)
        {{-- A sign-off is a clinician putting their name to this exact text. --}}
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm font-semibold text-green-800 mb-1">Approved and locked</p>
            <p class="text-sm text-green-700">
                @php $approval = $changeRequest->approvers->firstWhere('approved_content_hash', $changeRequest->draftContentHash()); @endphp
                Clinically approved{{ $approval?->responded_at ? ' on ' . $approval->responded_at->format('j M Y') : '' }}. Editing this copy withdraws that approval and sends it back for re-approval.
            </p>
        </div>

        <div class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-hcrg-grey-100 text-hcrg-charcoal whitespace-pre-wrap">{{ $changeRequest->draft_content }}</div>

        <a href="{{ route('admin.requests.show', $changeRequest) }}?unlock_draft=1#draft"
           class="mt-3 inline-block border border-hcrg-burgundy text-hcrg-burgundy px-6 py-2 rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">
            Unlock for editing
        </a>
    @else
        @if($changeRequest->hasBoundApproval())
            <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm font-semibold text-amber-800 mb-1">Unlocked — the approval will be withdrawn</p>
                <p class="text-sm text-amber-700">Saving a change here withdraws {{ $changeRequest->approvers->firstWhere('approved_content_hash', $changeRequest->draftContentHash())?->name ?? 'the approver' }}'s sign-off and returns this to Awaiting Clinical Approval. Leave without saving and nothing changes.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.requests.draft', $changeRequest) }}" id="draft"
            @if($changeRequest->hasBoundApproval()) data-confirm="This withdraws the clinical approval and sends the copy back for re-approval. Continue?" @endif>
            @csrf @method('PATCH')
            @if($changeRequest->hasBoundApproval())
                <input type="hidden" name="void_approval" value="1">
            @endif
            <textarea name="draft_content" rows="10"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('draft_content', $changeRequest->draft_content) }}</textarea>
            @error('draft_content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="mt-3 bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Save draft</button>
        </form>
    @endif
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
                    @if($site->id !== $changeRequest->site_id)
                        <button type="submit" name="remove_site_id" value="{{ $site->id }}"
                            class="mt-2 text-xs text-hcrg-grey-400 underline hover:text-hcrg-burgundy">Remove this site</button>
                    @endif
                </div>
            @endforeach
        </div>

        @php $unlisted = \App\Models\Site::where('is_active', true)->whereNotIn('id', $changeRequest->allSites()->pluck('id'))->orderBy('name')->get(); @endphp
        @if($unlisted->isNotEmpty())
            <div class="mt-4 p-4 bg-hcrg-grey-100 rounded-lg">
                <label for="addSite" class="block text-sm font-medium text-gray-700 mb-1">Also went live somewhere else?</label>
                <p class="text-xs text-gray-500 mb-2">The requester's list was a suggestion — add any site this actually landed on.</p>
                <div class="flex flex-wrap gap-2">
                    <select name="add_site_id" id="addSite" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        <option value="">Choose a site...</option>
                        @foreach($unlisted as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="border border-hcrg-burgundy text-hcrg-burgundy px-5 py-2 rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">Add site</button>
                </div>
            </div>
        @endif

        <p class="mt-4 text-xs text-gray-500">
            Adding a site does not affect clinical approval — one sign-off covers every site. Only changing the copy voids it, so if a site needs its own wording that is a separate request.
        </p>

        <button type="submit" class="mt-4 bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Save addresses</button>
    </form>
</div>
@endif
