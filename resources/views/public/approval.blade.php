@extends('layouts.public')
@section('title', 'Approval Request')

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h1 class="text-2xl font-bold text-hcrg-burgundy mb-6">{{ $changeRequest->isAccessRequest() ? 'Access Request Approval' : 'Approval Request' }}</h1>

    {{-- Request summary card --}}
    <div class="bg-gray-50 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Reference</p>
                <p class="text-lg font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Site</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->site->name }}</p>
            </div>
            @if($changeRequest->isAccessRequest())
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Access to</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Access for</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->access_recipient_name }}</p>
                <p class="text-xs text-gray-500">{{ $changeRequest->access_recipient_email }}</p>
            </div>
            @elseif($changeRequest->isContentRequest())
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Content type</p>
                <p class="text-sm text-gray-800">{{ config("content-types.{$changeRequest->content_type}.label", 'New content') }}</p>
            </div>
            @else
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Changes requested</p>
                <p class="text-sm text-gray-800">{{ $changeRequest->items->count() }} item(s)</p>
            </div>
            @endif
        </div>

        @if($changeRequest->isAccessRequest())
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 mb-4">
            <p class="text-sm text-gray-600">{{ $changeRequest->requester_name }} is asking for {{ $changeRequest->access_recipient_name === $changeRequest->requester_name ? 'access' : $changeRequest->access_recipient_name . ' to be given access' }} to manage <strong>{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</strong> on {{ $changeRequest->site->name }} directly, instead of requesting changes through the marketing team.</p>
            @if($changeRequest->items->first()?->description)
                <p class="text-xs text-gray-500 uppercase tracking-wide mt-3">Their reason</p>
                <p class="text-sm text-gray-800 mt-1 whitespace-pre-wrap">{{ $changeRequest->items->first()->description }}</p>
            @endif
            <p class="text-sm text-gray-600 mt-3">If you approve, they'll be asked to complete a short training video and confirm they're competent before access is set up.</p>
        </div>
        @elseif($changeRequest->isContentRequest())
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 mb-4">
            <p class="text-sm text-gray-600">This is new content for:</p>
            <p class="text-sm font-medium text-gray-800 mt-1">{{ $changeRequest->allSites()->pluck('name')->join(', ') }}</p>
        </div>
        @else
        <div class="bg-white border border-gray-200 rounded-lg px-4 py-3 mb-4">
            <p class="text-sm text-gray-600">The page these changes are requested for is:</p>
            @if($changeRequest->is_new_page)
                <p class="text-sm font-medium text-gray-800 mt-1">New page: {{ $changeRequest->page_title }}</p>
            @else
                <a href="{{ $changeRequest->page_url }}" target="_blank" class="inline-flex items-center mt-1 text-sm font-medium text-hcrg-burgundy hover:underline">
                    {{ $changeRequest->page_title ?: $changeRequest->page_url }}
                    <svg class="w-3.5 h-3.5 ml-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            @endif
        </div>
        @endif

        @if($changeRequest->deadline_date)
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
                <p class="text-sm font-semibold text-amber-800">
                    Deadline: {{ $changeRequest->deadline_date->format('j F Y') }}
                </p>
                @if($changeRequest->deadline_reason)
                    <p class="text-xs text-amber-700 mt-1">{{ $changeRequest->deadline_reason }}</p>
                @endif
            </div>
        @endif

        {{-- Reading-age guidance: shown when any change raises the reading age --}}
        @php
            $anyReadingAgeIncrease = $changeRequest->items->contains(fn($i) =>
                $i->action_type === 'change'
                && \App\Support\ReadingAge::increase($i->current_content, $i->description) !== null
            );
        @endphp
        @if($anyReadingAgeIncrease)
            <div class="mb-4 p-4 bg-amber-50 border-2 border-amber-400 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-bold text-amber-900 uppercase tracking-wide">Important — some of these changes raise the reading age</p>
                </div>
                <div class="space-y-2 text-sm text-amber-800">
                    <p>We often see requests where professionals prefer different wording, but many of these changes make content harder for our audience because they use more complex words or longer sentences.</p>
                    <p>We know it's important that we're taken seriously &mdash; but wording things simply is what helps our users.</p>
                    <p class="font-medium">If the meaning hasn't changed, please don't approve the change. Making changes that aren't needed still takes the team's time.</p>
                </div>
            </div>
        @endif

        {{-- The copy being signed off. The approval records a hash of exactly this
             text, so it has to be what the approver actually reads. --}}
        @if($changeRequest->isContentRequest())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-2">The copy you are approving</h2>
            @if($changeRequest->draft_content)
                <div class="p-4 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{{ $changeRequest->draft_content }}</div>
                <p class="mt-2 text-xs text-gray-500">Your approval is recorded against this exact wording. If it changes afterwards, the approval is withdrawn and you will be asked again.</p>
            @else
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    No copy has been written yet. Please do not approve until there is something to review — contact the content designer.
                </div>
            @endif
        </div>
        @endif

        {{-- Items overview --}}
        @unless($changeRequest->isAccessRequest() || $changeRequest->isContentRequest())
        <div class="space-y-3">
            @foreach($changeRequest->items as $item)
                @php
                    $borderColor = match($item->action_type) {
                        'add' => 'border-green-200',
                        'delete' => 'border-red-200',
                        default => 'border-hcrg-burgundy/20',
                    };
                @endphp
                <div class="bg-white rounded-lg border-2 {{ $borderColor }} p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full
                            @if($item->action_type === 'add') bg-green-100 text-green-800
                            @elseif($item->action_type === 'change') bg-hcrg-burgundy/10 text-hcrg-burgundy
                            @else bg-red-100 text-red-800
                            @endif
                        ">{{ ucfirst($item->action_type) }}</span>
                        @if($item->content_area)
                            <span class="text-xs text-gray-500">{{ $item->content_area }}</span>
                        @endif
                    </div>

                    @if($item->action_type === 'change' && $item->current_content)
                        @php $raIncrease = \App\Support\ReadingAge::increase($item->current_content, $item->description); @endphp
                        <div class="space-y-2">
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-xs font-medium text-gray-500 mb-1">Requested change</p>
                                <p class="text-sm text-gray-800 whitespace-pre-wrap leading-relaxed">{!! \App\Support\WordDiff::toHtml($item->current_content, $item->description) !!}</p>
                            </div>
                            <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-xs font-medium text-green-700 mb-1">Updated content</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                            </div>
                            @if($raIncrease)
                                <div class="p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800">
                                    &#9888; This change raises the reading age from {{ $raIncrease['from'] }} to {{ $raIncrease['to'] }} (the average reading age in the UK is 9&ndash;10). Check the meaning has actually changed, not just the wording.
                                </div>
                            @endif
                        </div>
                    @elseif($item->action_type === 'delete')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-xs font-medium text-red-700 mb-1">Content to remove</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                        </div>
                        @if($item->current_content)
                            <p class="mt-2 text-sm text-gray-500"><span class="font-medium">Reason:</span> {{ $item->current_content }}</p>
                        @endif
                    @elseif($item->action_type === 'add')
                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-xs font-medium text-green-700 mb-1">New content</p>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                        </div>
                    @else
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $item->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        @endunless
    </div>

    {{-- Approver info --}}
    @php
        $otherApprovers = $changeRequest->approvers->where('id', '!=', $approver->id);
        $approvalOutcome = match (true) {
            $changeRequest->isAccessRequest() => 'access will be granted',
            $changeRequest->isContentRequest() => 'this copy will be published as it stands',
            default => 'this change will be scheduled and implemented',
        };
        $approvalSubject = match (true) {
            $changeRequest->isAccessRequest() => 'access request',
            $changeRequest->isContentRequest() => 'content for clinical approval',
            default => 'change request',
        };
    @endphp
    <div class="bg-hcrg-grey-100 rounded-lg px-6 py-4 mb-6 space-y-2">
        <p class="text-sm text-gray-700">
            <strong>{{ $approver->name }}</strong>, you have been asked to review this {{ $approvalSubject }}.
        </p>
        @if($approver->group)
            @php $groupMembers = $changeRequest->approvers->where('group', $approver->group)->where('id', '!=', $approver->id); @endphp
            <p class="text-sm text-gray-600">
                You are part of the <strong>{{ $approver->group }}</strong> group — only one member needs to approve.
                @if($groupMembers->isNotEmpty())
                    We have also asked {{ $groupMembers->pluck('name')->join(', ', ' and ') }} from your group.
                @endif
            </p>
        @elseif($otherApprovers->count() > 0)
            <p class="text-sm text-gray-600">
                We have also asked {{ $otherApprovers->count() }} other {{ Str::plural('person', $otherApprovers->count()) }} for their approval. All approvers must approve before {{ $approvalOutcome }}.
            </p>
        @else
            <p class="text-sm text-gray-600">
                You are the only approver for this request. Your approval is required before {{ $approvalOutcome }}.
            </p>
        @endif
    </div>

    {{-- Approval form --}}
    <form method="POST" action="{{ route('approval.respond', $approver->token) }}" id="approvalForm">
        @csrf

        {{-- Approve notes (optional) --}}
        <div id="approveNotes" class="mb-6">
            <label for="approveNotesField" class="block text-sm font-semibold text-gray-700 mb-2">Notes <span class="font-normal text-gray-400">(optional)</span></label>
            <textarea
                id="approveNotesField"
                rows="3"
                maxlength="1000"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-hcrg-burgundy focus:ring-hcrg-burgundy text-sm p-3 border"
                placeholder="Add any comments about your approval..."
            >{{ old('notes') }}</textarea>
        </div>

        @error('status')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        {{-- Step 1: Approve / Reject buttons --}}
        <div id="decisionButtons" class="flex flex-col sm:flex-row gap-4">
            <button
                type="{{ $anyReadingAgeIncrease ? 'button' : 'submit' }}"
                id="approveBtn"
                @unless($anyReadingAgeIncrease) name="status" value="approved" onclick="document.getElementById('notesField').value = document.getElementById('approveNotesField').value;" @endunless
                class="flex-1 bg-status-success hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
                Approve
            </button>
            <button
                type="button"
                id="rejectBtn"
                class="flex-1 bg-status-error hover:bg-red-800 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
                Reject
            </button>
        </div>

        {{-- Step 2: Rejection details (hidden until Reject is clicked) --}}
        <div id="rejectPanel" class="hidden">
            <div class="p-5 bg-red-50 border border-red-200 rounded-lg space-y-4">
                <div>
                    <p class="text-sm font-semibold text-red-800 mb-1">Rejecting this request will decline it.</p>
                    <p class="text-xs text-red-700">Your comments below will be shared with the requester as the reason for the decision.</p>
                </div>

                <div>
                    <label for="rejectReason" class="block text-sm font-semibold text-gray-700 mb-1">Reason for rejection <span class="text-red-500">*</span></label>
                    <textarea
                        id="rejectReason"
                        rows="3"
                        maxlength="1000"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm p-3 border"
                        placeholder="Please explain why you are rejecting this request..."
                    ></textarea>
                    <p id="rejectReasonError" class="hidden mt-1 text-xs text-red-600">Please provide a reason for rejection.</p>
                </div>

                <label class="flex items-start space-x-2 cursor-pointer">
                    <input type="checkbox" name="share_details" value="1" class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                    <span class="text-sm text-gray-700">Share my name with the requester, so they can contact me to discuss other options</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        name="status"
                        value="rejected"
                        id="confirmRejectBtn"
                        class="flex-1 bg-status-error hover:bg-red-800 text-white font-bold py-3 px-6 rounded-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                        Confirm Rejection
                    </button>
                    <button
                        type="button"
                        id="cancelRejectBtn"
                        class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        {{-- Approve confirmation (only when a change raises the reading age) --}}
        @if($anyReadingAgeIncrease)
        <div id="approveConfirmPanel" class="hidden">
            <div class="p-5 bg-amber-50 border-2 border-amber-400 rounded-lg space-y-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-bold text-amber-900 uppercase tracking-wide">Before you approve</p>
                </div>
                <p class="text-sm text-amber-800">One or more of these changes raises the reading age, which can make the content harder for our audience to understand (the average reading age in the UK is 9&ndash;10). Please only approve if the change is genuinely needed &mdash; not just a preferred way of wording it.</p>
                <div class="flex gap-3 pt-2">
                    <button
                        type="submit"
                        name="status"
                        value="approved"
                        id="confirmApproveBtn"
                        onclick="document.getElementById('notesField').value = document.getElementById('approveNotesField').value;"
                        class="flex-1 bg-status-success hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        Yes, approve anyway
                    </button>
                    <button
                        type="button"
                        id="cancelApproveBtn"
                        class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Hidden field that carries the notes value for both flows --}}
        <input type="hidden" name="notes" id="notesField">
    </form>

    <script>
    // Approve confirmation step — only present when a change raises the reading age
    var approveBtn = document.getElementById('approveBtn');
    var approveConfirmPanel = document.getElementById('approveConfirmPanel');
    if (approveBtn && approveConfirmPanel) {
        approveBtn.addEventListener('click', function() {
            document.getElementById('decisionButtons').classList.add('hidden');
            document.getElementById('approveNotes').classList.add('hidden');
            approveConfirmPanel.classList.remove('hidden');
        });
        document.getElementById('cancelApproveBtn').addEventListener('click', function() {
            approveConfirmPanel.classList.add('hidden');
            document.getElementById('decisionButtons').classList.remove('hidden');
            document.getElementById('approveNotes').classList.remove('hidden');
        });
    }

    document.getElementById('rejectBtn').addEventListener('click', function() {
        document.getElementById('decisionButtons').classList.add('hidden');
        document.getElementById('approveNotes').classList.add('hidden');
        document.getElementById('rejectPanel').classList.remove('hidden');
        document.getElementById('rejectReason').focus();
    });

    document.getElementById('cancelRejectBtn').addEventListener('click', function() {
        document.getElementById('rejectPanel').classList.add('hidden');
        document.getElementById('decisionButtons').classList.remove('hidden');
        document.getElementById('approveNotes').classList.remove('hidden');
    });

    document.getElementById('confirmRejectBtn').addEventListener('click', function(e) {
        var textarea = document.getElementById('rejectReason');
        var errorMsg = document.getElementById('rejectReasonError');
        var reason = textarea.value.trim();
        if (!reason) {
            e.preventDefault();
            textarea.classList.add('border-red-500');
            errorMsg.classList.remove('hidden');
            textarea.focus();
            return;
        }
        textarea.classList.remove('border-red-500');
        errorMsg.classList.add('hidden');
        document.getElementById('notesField').value = reason;
    });

    document.getElementById('rejectReason').addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('border-red-500');
            document.getElementById('rejectReasonError').classList.add('hidden');
        }
    });
    </script>

    @include('public.partials.approval-queue')
</div>
@endsection
