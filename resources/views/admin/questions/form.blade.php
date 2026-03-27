@extends('layouts.admin')
@section('title', $question->exists ? 'Edit Question' : 'Add Question')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $question->exists ? 'Edit Question' : 'Add Question' }}</h1>

    <form method="POST" action="{{ $question->exists ? route('admin.questions.update', $question) : route('admin.questions.store') }}" class="bg-white rounded-lg shadow p-6 space-y-5" id="questionForm">
        @csrf
        @if($question->exists) @method('PUT') @endif

        <div>
            <label for="question_text" class="block text-sm font-medium text-gray-700 mb-1">Question Text</label>
            <textarea name="question_text" id="question_text" rows="3" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('question_text', $question->question_text) }}</textarea>
            @error('question_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
            <p class="text-xs text-gray-500 mb-2">Tick "Pass" for answers that count as passing the check.</p>
            <div id="optionsList" class="space-y-2">
                @php
                    $opts = old('options', $question->options ?? [['label' => 'Yes', 'pass' => true], ['label' => 'No', 'pass' => false]]);
                    // Normalise legacy flat string options
                    $opts = array_map(fn($o) => is_array($o) ? $o : ['label' => $o, 'pass' => false], $opts);
                @endphp
                @foreach($opts as $index => $option)
                <div class="flex items-center space-x-2 option-row">
                    <input type="text" name="options[{{ $index }}][label]" value="{{ $option['label'] }}" required placeholder="Option text"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy text-sm">
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
            <button type="button" onclick="addOption()" class="mt-2 inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">+ Add option</button>
            @error('options') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $question->sort_order ?? 0) }}" min="0"
                class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        <div class="flex items-center space-x-6">
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

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                {{ $question->exists ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.questions.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>

<script>
function addOption() {
    const list = document.getElementById('optionsList');
    const idx = list.children.length;
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 option-row';
    div.innerHTML = `<input type="text" name="options[${idx}][label]" required placeholder="Option text" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy text-sm">` +
        `<label class="flex items-center space-x-1 text-sm text-gray-600 flex-shrink-0"><input type="hidden" name="options[${idx}][pass]" value="0"><input type="checkbox" name="options[${idx}][pass]" value="1" class="h-4 w-4 text-green-600 border-gray-300 rounded"><span>Pass</span></label>` +
        `<button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>`;
    list.appendChild(div);
    div.querySelector('input[type="text"]').focus();
}
</script>
@endsection
