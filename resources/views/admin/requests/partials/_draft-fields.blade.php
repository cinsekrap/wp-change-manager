@php
    $fields = $changeRequest->contentFields();
    $values = $changeRequest->draft_fields ?? [];

    // A field renders one of three ways: a single input, a group of sub-fields,
    // or a group that repeats.
    $inputTypes = ['text' => 'text', 'date' => 'date'];
@endphp

@foreach($fields as $field)
    @php
        $name = $field['name'];
        $type = $field['type'] ?? 'text';
        $repeats = ! empty($field['repeatable']);
        $subs = $field['sub_fields'] ?? [];
        $id = 'f'.\Illuminate\Support\Str::slug($name);
        $value = $values[$name] ?? null;
    @endphp

    <div class="mb-6" data-field="{{ $name }}">
        <label class="field-label" @if(! $subs) for="{{ $id }}" @endif>
            {{ $name }}
            @if(! empty($field['required'])) <span class="text-status-error">*</span> @endif
        </label>
        @if(! empty($field['help']))
            <p class="field-help">{{ $field['help'] }}</p>
        @endif

        @if($type === 'file')
            <p class="text-xs text-hcrg-grey-400">Attach files to the request rather than here.</p>

        @elseif($subs && $repeats)
            @php $rows = collect($value)->filter()->values(); @endphp
            <div data-repeat-list="{{ $name }}" class="space-y-3">
                @foreach($rows->isEmpty() ? [[]] : $rows as $i => $row)
                    <div class="card card-body relative" data-repeat-row>
                        @foreach($subs as $sub)
                            @php $sn = $sub['name']; @endphp
                            <div class="field {{ $loop->last ? 'mb-0' : '' }}">
                                <label class="field-label text-xs">{{ $sn }}</label>
                                @if(($sub['type'] ?? 'text') === 'text')
                                    <input type="text" name="fields[{{ $name }}][{{ $i }}][{{ $sn }}]"
                                        value="{{ $row[$sn] ?? '' }}" class="field-input">
                                @else
                                    <textarea rows="2" name="fields[{{ $name }}][{{ $i }}][{{ $sn }}]"
                                        class="field-input">{{ $row[$sn] ?? '' }}</textarea>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" data-repeat-remove
                            class="absolute top-3 right-3 text-xs text-hcrg-grey-400 hover:text-status-error">Remove</button>
                    </div>
                @endforeach
            </div>
            {{-- The visible label leans on the button sitting under its own list;
                 the accessible name says which list, since a page has several. --}}
            <button type="button" data-repeat-add="{{ $name }}" class="btn btn-secondary btn-sm mt-3"
                aria-label="Add another entry to {{ $name }}">
                Add another
            </button>

        @elseif($subs)
            <div class="card card-body">
                @foreach($subs as $sub)
                    @php $sn = $sub['name']; @endphp
                    <div class="field {{ $loop->last ? 'mb-0' : '' }}">
                        <label class="field-label text-xs">{{ $sn }}</label>
                        @if(($sub['type'] ?? 'text') === 'text')
                            <input type="text" name="fields[{{ $name }}][{{ $sn }}]"
                                value="{{ is_array($value) ? ($value[$sn] ?? '') : '' }}" class="field-input">
                        @else
                            <textarea rows="2" name="fields[{{ $name }}][{{ $sn }}]"
                                class="field-input">{{ is_array($value) ? ($value[$sn] ?? '') : '' }}</textarea>
                        @endif
                    </div>
                @endforeach
            </div>

        @elseif(isset($inputTypes[$type]))
            <input type="{{ $inputTypes[$type] }}" id="{{ $id }}" name="fields[{{ $name }}]"
                value="{{ is_array($value) ? '' : $value }}"
                @if(! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                class="field-input">

        @else
            <textarea id="{{ $id }}" name="fields[{{ $name }}]" rows="5"
                @if(! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
                class="field-input">{{ is_array($value) ? '' : $value }}</textarea>
            @if(! empty($field['word_limit']))
                <p class="mt-1 text-xs text-hcrg-grey-400 text-right">Aim for about {{ $field['word_limit'] }} words.</p>
            @endif
            @if(! empty($field['reading_age']))
                @include('partials.reading-age', ['field' => $id])
            @endif
        @endif
    </div>
@endforeach

<script>
(function () {
    // Indexes are rewritten across the whole list after every change. Numbering a
    // new row by the row count collides after a middle row is removed — two rows
    // share an index and one is silently lost on save.
    function renumber(list) {
        list.querySelectorAll('[data-repeat-row]').forEach(function (row, i) {
            row.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace(/\[\d+\]/, '[' + i + ']');
            });
        });
    }

    // A row is cloned from one that is already there, so its markup lives in one
    // place rather than being written twice.
    document.querySelectorAll('[data-repeat-add]').forEach(function (button) {
        button.addEventListener('click', function () {
            var list = document.querySelector('[data-repeat-list="' + button.dataset.repeatAdd + '"]');
            var rows = list.querySelectorAll('[data-repeat-row]');
            var copy = rows[rows.length - 1].cloneNode(true);

            copy.querySelectorAll('[name]').forEach(function (input) {
                if (input.tagName === 'TEXTAREA') { input.textContent = ''; }
                input.value = '';
            });

            list.appendChild(copy);
            renumber(list);
        });
    });

    // Removing the only row would leave nothing to clone from, so it empties.
    document.addEventListener('click', function (e) {
        var remove = e.target.closest('[data-repeat-remove]');
        if (!remove) return;

        var row = remove.closest('[data-repeat-row]');
        var list = row.parentElement;

        if (list.querySelectorAll('[data-repeat-row]').length > 1) {
            row.remove();
            renumber(list);
        } else {
            row.querySelectorAll('[name]').forEach(function (input) { input.value = ''; });
        }
    });
})();
</script>
