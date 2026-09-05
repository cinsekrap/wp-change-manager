@php
    // A question needs somebody to ask, and an open request to ask about.
    $canAsk = $changeRequest->isActive() && filled($changeRequest->requester_email);
@endphp

{{-- Writing something down is one act with two consequences: a note changes
     nothing, a question emails the requester and stops the clock. --}}
<div class="card card-body">
    <h2 class="card-title mb-1">Notes and questions</h2>
    <p class="text-xs text-hcrg-grey-400 mb-4">Both appear in this request's history.</p>

    <form method="POST" action="{{ route('admin.requests.notes', $changeRequest) }}" id="noteForm">
        @csrf
        <textarea name="note" id="noteBody" rows="3" required placeholder="What do you want to say?"
            class="field-input mb-3">{{ old('note') }}</textarea>
        @error('note') <p class="field-error mb-2">{{ $message }}</p> @enderror

        @if($canAsk)
            <div class="space-y-2 mb-3">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="radio" name="kind" value="note" checked
                        class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 accent-hcrg-burgundy">
                    <span class="text-sm text-gray-700">
                        A note on the job
                        <span class="block text-xs text-hcrg-grey-400">For whoever picks this up next. Nothing is sent.</span>
                    </span>
                </label>
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="radio" name="kind" value="question"
                        class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 accent-hcrg-burgundy">
                    <span class="text-sm text-gray-700">
                        A question for {{ $changeRequest->requester_name }}
                        <span class="block text-xs text-hcrg-grey-400">
                            Emailed to them. The request waits on their answer, and the SLA clock stops until it comes.
                        </span>
                    </span>
                </label>
            </div>
        @else
            <input type="hidden" name="kind" value="note">
            <p class="text-xs text-hcrg-grey-400 mb-3">
                {{ $changeRequest->isActive()
                    ? 'This has no requester, so there is nobody to put a question to.'
                    : 'This request is closed, so nobody can be asked about it.' }}
            </p>
        @endif

        <button type="submit" id="noteSubmit" class="btn btn-primary">Add note</button>
    </form>

    @if($canAsk)
    <script>
    (function () {
        var form = document.getElementById('noteForm');
        var button = document.getElementById('noteSubmit');
        if (!form || !button) return;

        // The button says what pressing it does, since one of these sends email.
        form.addEventListener('change', function () {
            var asking = form.querySelector('input[name="kind"]:checked').value === 'question';
            button.textContent = asking ? 'Send question' : 'Add note';
            form.dataset.confirm = asking
                ? 'This emails {{ addslashes($changeRequest->requester_name) }} and holds the request until they reply. Continue?'
                : '';
            if (!asking) delete form.dataset.confirm;
        });
    })();
    </script>
    @endif
</div>
