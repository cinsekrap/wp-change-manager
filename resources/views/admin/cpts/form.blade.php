@extends('layouts.admin')
@section('title', $cpt->exists ? 'Edit CPT Type' : 'Add CPT Type')

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $cpt->exists ? 'Edit CPT Type' : 'Add CPT Type' }}</h1>

    <form method="POST" action="{{ $cpt->exists ? route('admin.cpts.update', $cpt) : route('admin.cpts.store') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf
        @if($cpt->exists) @method('PUT') @endif

        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $cpt->slug) }}" required placeholder="e.g. services, news"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy font-mono">
            <p class="mt-1 text-xs text-gray-500">URL prefix used to match pages. Use lowercase, no spaces.</p>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $cpt->name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" id="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('description', $cpt->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $cpt->sort_order ?? 0) }}" min="0"
                class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-2">Content Areas</label>
            <p class="text-xs text-gray-500 mb-3">Define the parts of a page that users can request changes to. Each area becomes a structured field in the change request form.</p>

            <div id="contentAreasList" class="space-y-4">
                @php
                    // Normalise: support old format (array of strings) and new format (array of objects)
                    $rawAreas = old('content_areas', $cpt->form_config['content_areas'] ?? []);
                    $areas = [];
                    foreach ($rawAreas as $area) {
                        if (is_string($area)) {
                            // Legacy format — convert to new structure
                            $areas[] = ['name' => $area, 'type' => 'text', 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'word_limit' => null, 'sub_fields' => []];
                        } elseif (is_array($area)) {
                            $areas[] = $area;
                        }
                    }
                @endphp
                @foreach($areas as $index => $area)
                <div class="content-area-card border border-gray-200 rounded-lg overflow-hidden" data-index="{{ $index }}">
                    <div class="flex items-center justify-between bg-white border-b border-gray-200 px-4 py-2 cursor-pointer area-header" onclick="toggleArea(this)">
                        <div class="flex items-center space-x-3">
                            <span class="drag-handle text-gray-400 cursor-grab" title="Drag to reorder">&#x2630;</span>
                            <span class="area-summary text-sm font-medium text-gray-700">
                                {{ $area['name'] ?? 'New area' }}
                                <span class="text-xs text-gray-400 ml-1">({{ $area['type'] ?? 'text' }})</span>
                            </span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="collapse-icon text-gray-400 text-sm transition-transform duration-200">&#x25BC;</span>
                            <button type="button" onclick="event.stopPropagation(); removeArea(this)" class="text-red-500 hover:text-red-700 text-sm px-1" title="Remove">&times;</button>
                        </div>
                    </div>
                    <div class="area-body p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="content_areas[{{ $index }}][name]" value="{{ $area['name'] ?? '' }}" required placeholder="e.g. Hero image"
                                    class="area-name-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                                    oninput="updateSummary(this)">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Field type <span class="text-red-500">*</span></label>
                                <select name="content_areas[{{ $index }}][type]" required
                                    class="area-type-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                                    onchange="onTypeChange(this)">
                                    <option value="text" {{ ($area['type'] ?? 'text') === 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="textarea" {{ ($area['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                    <option value="richtext" {{ ($area['type'] ?? '') === 'richtext' ? 'selected' : '' }}>Rich text</option>
                                    <option value="select" {{ ($area['type'] ?? '') === 'select' ? 'selected' : '' }}>Select dropdown</option>
                                    <option value="checkbox" {{ ($area['type'] ?? '') === 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                    <option value="date" {{ ($area['type'] ?? '') === 'date' ? 'selected' : '' }}>Date</option>
                                    <option value="file" {{ ($area['type'] ?? '') === 'file' ? 'selected' : '' }}>File upload</option>
                                    <option value="group" {{ ($area['type'] ?? '') === 'group' ? 'selected' : '' }}>Field group</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="hidden" name="content_areas[{{ $index }}][required]" value="0">
                            <input type="checkbox" name="content_areas[{{ $index }}][required]" value="1"
                                {{ !empty($area['required']) ? 'checked' : '' }}
                                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                            <label class="ml-2 text-sm text-gray-700">Required field</label>
                        </div>

                        <div class="word-limit-row" style="{{ in_array($area['type'] ?? 'text', ['text', 'textarea', 'richtext']) ? '' : 'display:none' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Word limit</label>
                            <input type="number" name="content_areas[{{ $index }}][word_limit]" value="{{ $area['word_limit'] ?? '' }}" placeholder="No limit" min="1" max="10000"
                                class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Help text</label>
                            <input type="text" name="content_areas[{{ $index }}][help]" value="{{ $area['help'] ?? '' }}" placeholder="Guidance shown below the field label"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        </div>

                        <div class="placeholder-row" style="{{ in_array($area['type'] ?? 'text', ['checkbox', 'file', 'group']) ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Placeholder</label>
                            <input type="text" name="content_areas[{{ $index }}][placeholder]" value="{{ $area['placeholder'] ?? '' }}" placeholder="Placeholder text for the input"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        </div>

                        <div class="options-section" style="{{ ($area['type'] ?? 'text') !== 'select' ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Options</label>
                            <div class="options-list space-y-2">
                                @foreach(($area['options'] ?? []) as $optIndex => $option)
                                <div class="flex items-center space-x-2 option-row">
                                    <input type="text" name="content_areas[{{ $index }}][options][]" value="{{ $option }}" placeholder="Option value"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addOption(this)" class="mt-2 text-xs text-hcrg-burgundy hover:text-[#9A1B4B]">+ Add option</button>
                        </div>

                        <div class="sub-fields-section" style="{{ ($area['type'] ?? 'text') !== 'group' ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Sub-fields</label>
                            <div class="sub-fields-list space-y-2">
                                @foreach(($area['sub_fields'] ?? []) as $sfIndex => $subField)
                                <div class="flex items-center space-x-2 sub-field-row">
                                    <input type="text" name="content_areas[{{ $index }}][sub_fields][{{ $sfIndex }}][name]" value="{{ $subField['name'] ?? '' }}" placeholder="Field name"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                    <select name="content_areas[{{ $index }}][sub_fields][{{ $sfIndex }}][type]"
                                        class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                                        <option value="text" {{ ($subField['type'] ?? 'text') === 'text' ? 'selected' : '' }}>Text</option>
                                        <option value="textarea" {{ ($subField['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                    </select>
                                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addSubField(this)" class="mt-2 text-xs text-hcrg-burgundy hover:text-[#9A1B4B]">+ Add sub-field</button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" onclick="addContentArea()" class="mt-3 inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add content area
            </button>
            @error('content_areas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('content_areas.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center space-x-6">
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $cpt->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
            <div class="flex items-center">
                <input type="hidden" name="is_blocked" value="0">
                <input type="checkbox" name="is_blocked" id="is_blocked" value="1" {{ old('is_blocked', $cpt->is_blocked ?? false) ? 'checked' : '' }}
                    class="h-4 w-4 text-amber-500 border-gray-300 rounded" onchange="toggleBlockedMessage()">
                <label for="is_blocked" class="ml-2 text-sm text-gray-700">Block requests</label>
            </div>
        </div>

        <div id="blockedMessageSection" class="{{ old('is_blocked', $cpt->is_blocked ?? false) ? '' : 'hidden' }}">
            <label for="blocked_message" class="block text-sm font-medium text-gray-700 mb-1">
                Blocked message <span class="font-normal text-gray-400">(shown to users instead of the request form)</span>
            </label>
            <textarea name="blocked_message" id="blocked_message" rows="4" placeholder="e.g. Events can be managed directly using the self-service portal at..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('blocked_message', $cpt->blocked_message) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">This message will be displayed when a user selects a page of this content type. They will not be able to submit a request.</p>
            @error('blocked_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                {{ $cpt->exists ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.cpts.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>

<script>
let areaCounter = {{ count($areas) }};

function addContentArea() {
    const list = document.getElementById('contentAreasList');
    const idx = areaCounter++;
    const card = document.createElement('div');
    card.className = 'content-area-card border border-gray-200 rounded-lg overflow-hidden';
    card.dataset.index = idx;
    card.innerHTML = `
        <div class="flex items-center justify-between bg-white border-b border-gray-200 px-4 py-2 cursor-pointer area-header" onclick="toggleArea(this)">
            <div class="flex items-center space-x-3">
                <span class="drag-handle text-gray-400 cursor-grab" title="Drag to reorder">&#x2630;</span>
                <span class="area-summary text-sm font-medium text-gray-700">
                    New area
                    <span class="text-xs text-gray-400 ml-1">(text)</span>
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="collapse-icon text-gray-400 text-sm transition-transform duration-200">&#x25BC;</span>
                <button type="button" onclick="event.stopPropagation(); removeArea(this)" class="text-red-500 hover:text-red-700 text-sm px-1" title="Remove">&times;</button>
            </div>
        </div>
        <div class="area-body p-4 space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="content_areas[${idx}][name]" required placeholder="e.g. Hero image"
                        class="area-name-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                        oninput="updateSummary(this)">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Field type <span class="text-red-500">*</span></label>
                    <select name="content_areas[${idx}][type]" required
                        class="area-type-select w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                        onchange="onTypeChange(this)">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="richtext">Rich text</option>
                        <option value="select">Select dropdown</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                        <option value="file">File upload</option>
                        <option value="group">Field group</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center">
                <input type="hidden" name="content_areas[${idx}][required]" value="0">
                <input type="checkbox" name="content_areas[${idx}][required]" value="1"
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label class="ml-2 text-sm text-gray-700">Required field</label>
            </div>

            <div class="word-limit-row">
                <label class="block text-xs font-medium text-gray-500 mb-1">Word limit</label>
                <input type="number" name="content_areas[${idx}][word_limit]" placeholder="No limit" min="1" max="10000"
                    class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Help text</label>
                <input type="text" name="content_areas[${idx}][help]" placeholder="Guidance shown below the field label"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <div class="placeholder-row">
                <label class="block text-xs font-medium text-gray-500 mb-1">Placeholder</label>
                <input type="text" name="content_areas[${idx}][placeholder]" placeholder="Placeholder text for the input"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>

            <div class="options-section" style="display:none">
                <label class="block text-xs font-medium text-gray-500 mb-1">Options</label>
                <div class="options-list space-y-2"></div>
                <button type="button" onclick="addOption(this)" class="mt-2 text-xs text-hcrg-burgundy hover:text-[#9A1B4B]">+ Add option</button>
            </div>

            <div class="sub-fields-section" style="display:none">
                <label class="block text-xs font-medium text-gray-500 mb-1">Sub-fields</label>
                <div class="sub-fields-list space-y-2"></div>
                <button type="button" onclick="addSubField(this)" class="mt-2 text-xs text-hcrg-burgundy hover:text-[#9A1B4B]">+ Add sub-field</button>
            </div>
        </div>
    `;
    list.appendChild(card);
    card.querySelector('.area-name-input').focus();
}

function removeArea(btn) {
    const card = btn.closest('.content-area-card');
    const name = card.querySelector('.area-name-input')?.value;
    if (name && !confirm('Remove content area "' + name + '"?')) return;
    card.remove();
}

function toggleArea(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('.collapse-icon');
    if (body.style.display === 'none') {
        body.style.display = '';
        icon.style.transform = '';
    } else {
        body.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    }
}

function updateSummary(input) {
    const card = input.closest('.content-area-card');
    const summary = card.querySelector('.area-summary');
    const typeSelect = card.querySelector('.area-type-select');
    const name = input.value.trim() || 'New area';
    const type = typeSelect ? typeSelect.value : 'text';
    summary.innerHTML = escHtml(name) + ' <span class="text-xs text-gray-400 ml-1">(' + escHtml(type) + ')</span>';
}

function onTypeChange(select) {
    const card = select.closest('.content-area-card');
    const type = select.value;

    // Show/hide word limit row (text, textarea, richtext only)
    const wordLimitRow = card.querySelector('.word-limit-row');
    if (wordLimitRow) {
        wordLimitRow.style.display = ['text', 'textarea', 'richtext'].includes(type) ? '' : 'none';
    }

    // Show/hide placeholder row
    const placeholderRow = card.querySelector('.placeholder-row');
    if (placeholderRow) {
        placeholderRow.style.display = ['checkbox', 'file', 'group'].includes(type) ? 'none' : '';
    }

    // Show/hide options section
    const optionsSection = card.querySelector('.options-section');
    if (optionsSection) {
        optionsSection.style.display = type === 'select' ? '' : 'none';
    }

    // Show/hide sub-fields section
    const subFieldsSection = card.querySelector('.sub-fields-section');
    if (subFieldsSection) {
        subFieldsSection.style.display = type === 'group' ? '' : 'none';
    }

    // Update summary
    const nameInput = card.querySelector('.area-name-input');
    if (nameInput) updateSummary(nameInput);
}

function addOption(btn) {
    const section = btn.closest('.options-section');
    const list = section.querySelector('.options-list');
    const card = btn.closest('.content-area-card');
    const idx = card.dataset.index;

    const row = document.createElement('div');
    row.className = 'flex items-center space-x-2 option-row';
    row.innerHTML = `
        <input type="text" name="content_areas[${idx}][options][]" placeholder="Option value"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
    `;
    list.appendChild(row);
    row.querySelector('input').focus();
}

function addSubField(btn) {
    const section = btn.closest('.sub-fields-section');
    const list = section.querySelector('.sub-fields-list');
    const card = btn.closest('.content-area-card');
    const idx = card.dataset.index;
    const sfIdx = list.children.length;

    const row = document.createElement('div');
    row.className = 'flex items-center space-x-2 sub-field-row';
    row.innerHTML = `
        <input type="text" name="content_areas[${idx}][sub_fields][${sfIdx}][name]" placeholder="Field name"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        <select name="content_areas[${idx}][sub_fields][${sfIdx}][type]"
            class="w-32 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
        </select>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
    `;
    list.appendChild(row);
    row.querySelector('input').focus();
}

function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function toggleBlockedMessage() {
    var checked = document.getElementById('is_blocked').checked;
    document.getElementById('blockedMessageSection').classList.toggle('hidden', !checked);
}
</script>
@endsection
