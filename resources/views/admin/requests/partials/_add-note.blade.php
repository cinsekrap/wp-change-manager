@php
    // A question needs somebody to ask, and an open request to ask about.
    $canAsk = $changeRequest->isActive() && filled($changeRequest->requester_email);
@endphp

{{-- Writing something down is one act with two consequences: a note changes
     nothing, a question emails the requester and stops the clock. The button
     says which you are doing, and asking confirms before it sends. --}}
<div class="card card-body">
    <h2 class="card-title mb-1">Notes and questions</h2>
    <p class="text-xs text-hcrg-grey-400 mb-4">Both appear in this request's history.</p>

    <form method="POST" action="{{ route('admin.requests.notes', $changeRequest) }}">
        @csrf
        {{-- Carried in the form rather than on the button: confirming re-submits
             without a submitter, which would drop a button's own value. --}}
        <input type="hidden" name="kind" id="noteKind" value="note">

        <textarea name="note" id="noteBody" rows="3" required placeholder="What do you want to say?"
            class="field-input mb-3">{{ old('note') }}</textarea>
        @error('note') <p class="field-error mb-2">{{ $message }}</p> @enderror

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary"
                onclick="document.getElementById('noteKind').value = 'note'; delete this.form.dataset.confirm;">
                Add note
            </button>

            @if($canAsk)
                <button type="submit" class="btn btn-secondary"
                    onclick="document.getElementById('noteKind').value = 'question';
                             this.form.dataset.confirm = 'This emails {{ addslashes($changeRequest->requester_name) }} and holds the request until they reply. Continue?';">
                    Send as a question to {{ $changeRequest->requester_name }}
                </button>
            @endif
        </div>

        @if(! $canAsk && $changeRequest->isActive())
            {{-- Explains an absence nothing else on the page accounts for. --}}
            <p class="mt-3 text-xs text-hcrg-grey-400">This has no requester, so there is nobody to put a question to.</p>
        @endif
    </form>
</div>
