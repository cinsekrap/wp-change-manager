{{-- Content lane: the draft copy, and where it went live. --}}
@if($changeRequest->isContentRequest())
<div class="card card-body mb-6">
    <h2 class="card-title mb-4">Draft copy</h2>
    @if($changeRequest->staleApprovals()->isNotEmpty())
        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm text-amber-800 mb-2">This copy has changed since it was approved, so the sign-off no longer applies.</p>
            <p class="text-sm text-amber-700">Nothing is sent automatically — use <strong class="font-semibold">Send for approval</strong> in the sidebar when the copy is ready to go back to the approver.</p>
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
           class="btn btn-secondary mt-3">
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
            <textarea name="draft_content" id="draftContent" rows="10"
                class="field-input">{{ old('draft_content', $changeRequest->draft_content) }}</textarea>
            @error('draft_content') <p class="field-error">{{ $message }}</p> @enderror
            @include('partials.reading-age', ['field' => 'draftContent'])
            <button type="submit" class="btn btn-primary mt-3">Save draft</button>
        </form>
    @endif
</div>

@if($changeRequest->files->isNotEmpty())
<div class="card card-body mb-6">
    <h2 class="card-title mb-1">Attached to the brief</h2>
    <p class="text-sm text-gray-500 mb-4">What the requester sent with the suggestion.</p>
    <ul class="space-y-2">
        @foreach($changeRequest->files as $file)
            <li class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-hcrg-charcoal">{{ $file->original_filename }}</p>
                    <p class="text-xs text-hcrg-grey-400">{{ number_format($file->file_size / 1024, 0) }} KB</p>
                </div>
                @if($file->purged_at)
                    <span class="text-xs text-hcrg-grey-400">removed {{ $file->purged_at->diffForHumans() }}</span>
                @else
                    <a href="{{ route('admin.requests.download', [$changeRequest, $file]) }}"
                       class="text-sm text-hcrg-burgundy underline">Download</a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card card-body mb-6">
    <h2 class="card-title mb-1">Where it went live</h2>
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
                            class="field-input">
                        <input type="text" name="published[{{ $site->id }}][title]" placeholder="Page title"
                            value="{{ $pivot['published_title'] ?? '' }}"
                            class="field-input">
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
                <label for="addSite" class="field-label">Also went live somewhere else?</label>
                <p class="field-help">The requester's list was a suggestion — add any site this actually landed on.</p>
                <div class="flex flex-wrap gap-2">
                    <select name="add_site_id" id="addSite" class="field-input">
                        <option value="">Choose a site...</option>
                        @foreach($unlisted as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary">Add site</button>
                </div>
            </div>
        @endif

        <button type="submit" class="btn btn-primary mt-4">Save addresses</button>
    </form>
</div>
@endif
