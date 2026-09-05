@extends('layouts.admin')
@section('title', $question->exists ? 'Edit question' : 'Add question')

@section('content')
<div class="max-w-3xl">
    <x-admin.page-header
        :title="$question->exists ? 'Edit question' : 'Add question'"
        lede="Asked before a request is submitted, so obvious problems are caught early." />

    <form method="POST" action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}" class="card" id="questionForm">
        @csrf
        @if($question->exists) @method('PUT') @endif

        <x-admin.form-section title="The question" help="Shown to the person making a request, with the options below.">
        <div class="field mb-0">
            <label for="question_text" class="field-label">Question <span class="text-status-error">*</span></label>
            <textarea name="question_text" id="question_text" rows="3" required
                class="field-input">{{ old('question_text', $question->question_text) }}</textarea>
            @error('question_text') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        </x-admin.form-section>

        <x-admin.form-section title="Answers" help="Tick Pass for the answers that let a request through.">
        <div class="field mb-0">
            <label class="field-label">Options</label>
            <p class="field-help">Tick "Pass" for answers that count as passing the check.</p>
            <div id="optionsList" class="space-y-2">
                @php
                    $opts = old('options', $question->options ?? [['label' => 'Yes', 'pass' => true], ['label' => 'No', 'pass' => false]]);
                    // Normalise legacy flat string options
                    $opts = array_map(fn($o) => is_array($o) ? $o : ['label' => $o, 'pass' => false], $opts);
                @endphp
                @foreach($opts as $index => $option)
                <div class="flex items-center space-x-2 option-row">
                    <input type="text" name="options[{{ $index }}][label]" value="{{ $option['label'] }}" required placeholder="Option text"
                        class="field-input flex-1">
                    <label class="flex items-center space-x-1 text-sm text-gray-600 flex-shrink-0">
                        <input type="hidden" name="options[{{ $index }}][pass]" value="0">
                        <input type="checkbox" name="options[{{ $index }}][pass]" value="1" {{ !empty($option['pass']) ? 'checked' : '' }}
                            class="h-4 w-4 text-green-600 border-gray-300 rounded">
                        <span>Pass</span>
                    </label>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                </div>
                @endforeach
            </div>
            <button type="button" onclick="addOption()" class="btn btn-secondary mt-2">+ Add option</button>
            @error('options') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        </x-admin.form-section>

        <x-admin.form-section title="When it appears" help="Order in the list, and whether it is asked at all.">
        <div class="field">
            <label for="sort_order" class="field-label">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $question->sort_order ?? 0) }}" min="0"
                class="field-input w-24">
        </div>

        <div class="field mb-0 flex items-center gap-6">
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $question->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
            <div class="flex items-center">
                <input type="hidden" name="is_required" value="0">
                <input type="checkbox" name="is_required" id="is_required" value="1" {{ old('is_required', $question->is_required ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_required" class="ml-2 text-sm text-gray-700">Required</label>
            </div>
        </div>

        </x-admin.form-section>

        <x-admin.form-actions>
            <button type="submit" class="btn btn-primary">{{ $question->exists ? 'Save changes' : 'Add question' }}</button>
            <a href="{{ route('admin.questions.index') }}" class="btn btn-quiet">Cancel</a>
        </x-admin.form-actions>
    </form>
</div>

<script>
function addOption() {
    const list = document.getElementById('optionsList');
    const idx = list.children.length;
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 option-row';
    div.innerHTML = `<input type="text" name="options[${idx}][label]" required placeholder="Option text" class="field-input flex-1">` +
        `<label class="flex items-center space-x-1 text-sm text-gray-600 flex-shrink-0"><input type="hidden" name="options[${idx}][pass]" value="0"><input type="checkbox" name="options[${idx}][pass]" value="1" class="h-4 w-4 text-green-600 border-gray-300 rounded"><span>Pass</span></label>` +
        `<button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>`;
    list.appendChild(div);
    div.querySelector('input[type="text"]').focus();
}
</script>
@endsection
