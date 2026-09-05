{{-- Writing a note is something you do while working on a request. Reading what
     has already happened is not, so the timeline stays under History. --}}
<div class="card card-body">
    <h2 class="card-title mb-1">Notes</h2>
    <p class="text-xs text-hcrg-grey-400 mb-4">
        For anyone picking this up later. Notes appear in the request's history.
    </p>

    <form method="POST" action="{{ route('admin.requests.notes', $changeRequest) }}">
        @csrf
        <div class="flex items-start gap-3">
            <textarea name="note" rows="2" required placeholder="Add a note..." class="field-input flex-1"></textarea>
            <button type="submit" class="btn btn-primary shrink-0">Add</button>
        </div>
        @error('note') <p class="field-error">{{ $message }}</p> @enderror
    </form>
</div>
