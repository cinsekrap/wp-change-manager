@extends('layouts.admin')
@section('title', $cpt->exists ? 'Edit CPT Type' : 'Add CPT Type')

@section('content')
<div class="max-w-3xl">
    <h1 class="page-title mb-6">{{ $cpt->exists ? 'Edit CPT Type' : 'Add CPT Type' }}</h1>

    <form method="POST" action="{{ $cpt->exists ? route('admin.cpts.update', $cpt) : route('admin.cpts.store') }}" class="card card-body space-y-5">
        @csrf
        @if($cpt->exists) @method('PUT') @endif

        <div>
            <label for="slug" class="field-label">Slug</label>
            <input type="text" name="slug" id="slug" value="{{ old('slug', $cpt->slug) }}" required placeholder="e.g. services, news"
                class="field-input font-mono">
            <p class="mt-1 text-xs text-gray-500">URL prefix used to match pages. Use lowercase, no spaces.</p>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="field-label">Display Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $cpt->name) }}" required
                class="field-input">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="field-label">Description</label>
            <textarea name="description" id="description" rows="3"
                class="field-input">{{ old('description', $cpt->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sort_order" class="field-label">Sort Order</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $cpt->sort_order ?? 0) }}" min="0"
                class="field-input w-24">
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
                                    class="field-input area-name-input"
                                    oninput="updateSummary(this)">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Field type <span class="text-red-500">*</span></label>
                                <select name="content_areas[{{ $index }}][type]" required
                                    class="field-input area-type-select"
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

                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <input type="hidden" name="content_areas[{{ $index }}][required]" value="0">
                                <input type="checkbox" name="content_areas[{{ $index }}][required]" value="1"
                                    {{ !empty($area['required']) ? 'checked' : '' }}
                                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                                <label class="ml-2 text-sm text-gray-700">Required field</label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="content_areas[{{ $index }}][allow_add]" value="0">
                                <input type="checkbox" name="content_areas[{{ $index }}][allow_add]" value="1"
                                    {{ ($area['allow_add'] ?? true) ? 'checked' : '' }}
                                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                                <label class="ml-2 text-sm text-gray-700">Can add new</label>
                            </div>
                        </div>

                        <div class="word-limit-row" style="{{ in_array($area['type'] ?? 'text', ['text', 'textarea', 'richtext']) ? '' : 'display:none' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Word limit</label>
                            <input type="number" name="content_areas[{{ $index }}][word_limit]" value="{{ $area['word_limit'] ?? '' }}" placeholder="No limit" min="1" max="10000"
                                class="field-input w-32">
                        </div>

                        <div class="reading-age-row" style="{{ in_array($area['type'] ?? 'text', ['textarea', 'richtext']) ? '' : 'display:none' }}">
                            <div class="flex items-center">
                                <input type="hidden" name="content_areas[{{ $index }}][reading_age]" value="0">
                                <input type="checkbox" name="content_areas[{{ $index }}][reading_age]" value="1"
                                    {{ ($area['reading_age'] ?? true) ? 'checked' : '' }}
                                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                                <label class="ml-2 text-sm text-gray-700">Show reading age</label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Displays a Flesch-Kincaid reading age indicator below the field</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Help text</label>
                            <input type="text" name="content_areas[{{ $index }}][help]" value="{{ $area['help'] ?? '' }}" placeholder="Guidance shown below the field label"
                                class="field-input">
                        </div>

                        <div class="placeholder-row" style="{{ in_array($area['type'] ?? 'text', ['checkbox', 'file', 'group']) ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Placeholder</label>
                            <input type="text" name="content_areas[{{ $index }}][placeholder]" value="{{ $area['placeholder'] ?? '' }}" placeholder="Placeholder text for the input"
                                class="field-input">
                        </div>

                        <div class="options-section" style="{{ ($area['type'] ?? 'text') !== 'select' ? 'display:none' : '' }}">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Options</label>
                            <div class="options-list space-y-2">
                                @foreach(($area['options'] ?? []) as $optIndex => $option)
                                <div class="flex items-center space-x-2 option-row">
                                    <input type="text" name="content_areas[{{ $index }}][options][]" value="{{ $option }}" placeholder="Option value"
                                        class="field-input flex-1">
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
                                        class="field-input flex-1">
                                    <select name="content_areas[{{ $index }}][sub_fields][{{ $sfIndex }}][type]"
                                        class="field-input w-32">
                                        <option value="text" {{ ($subField['type'] ?? 'text') === 'text' ? 'selected' : '' }}>Text</option>
                                        <option value="textarea" {{ ($subField['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                    </select>
                                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addSubField(this)" class="mt-2 text-xs text-hcrg-burgundy hover:text-[#9A1B4B]">+ Add sub-field</button>

                            <div class="repeatable-row mt-3 pt-3 border-t border-gray-200">
                                <div class="flex items-center">
                                    <input type="hidden" name="content_areas[{{ $index }}][repeatable]" value="0">
                                    <input type="checkbox" name="content_areas[{{ $index }}][repeatable]" value="1"
                                        {{ !empty($area['repeatable']) ? 'checked' : '' }}
                                        class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                                    <label class="ml-2 text-sm text-gray-700">Allow multiple entries</label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Users can add multiple sets of these fields in one request (e.g. multiple Q&A pairs)</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <button type="button" onclick="addContentArea()" class="btn btn-secondary mt-3">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add content area
            </button>
            @error('content_areas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('content_areas.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $cpt->is_active ?? true) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
            <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
        </div>

        <div>
            <label class="field-label">Used for new content</label>
            <p class="field-help">
                New content of these kinds is written into the fields below. A kind no page type
                claims is written as one block of text instead.
            </p>
            @php
                $claimed = old('content_kinds', $cpt->content_kinds ?? []);
                // A kind belongs to one page type, so anything another has taken
                // is shown as taken rather than silently moving.
                $takenBy = \App\Models\CptType::whereNotNull('content_kinds')
                    ->when($cpt->exists, fn ($q) => $q->where('id', '!=', $cpt->id))
                    ->get()
                    ->flatMap(fn ($t) => collect($t->content_kinds)->mapWithKeys(fn ($k) => [$k => $t->name]));
            @endphp
            <div class="space-y-2">
                @foreach(config('content-types') as $key => $kind)
                    @php $taken = $takenBy[$key] ?? null; @endphp
                    <label class="flex items-start gap-2.5 p-2.5 border border-hcrg-grey-200 rounded-lg {{ $taken ? 'opacity-60' : 'cursor-pointer hover:border-hcrg-burgundy' }}">
                        <input type="checkbox" name="content_kinds[]" value="{{ $key }}"
                            @checked(in_array($key, (array) $claimed)) @disabled($taken)
                            class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
                        <span class="text-sm text-gray-700">
                            {{ $kind['label'] }}
                            <span class="block text-xs text-hcrg-grey-400">
                                {{ $taken ? 'Already used by '.$taken : $kind['help'] }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('content_kinds') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Request mode</label>
            @php $currentMode = old('request_mode', $cpt->request_mode ?? 'normal'); @endphp
            <div class="space-y-2">
                <label class="flex items-start space-x-3 p-3 border rounded-lg cursor-pointer transition-colors {{ $currentMode === 'normal' ? 'border-hcrg-burgundy bg-hcrg-burgundy/5' : 'border-gray-200 hover:bg-gray-50' }}" id="modeLabel_normal">
                    <input type="radio" name="request_mode" value="normal" {{ $currentMode === 'normal' ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300" onchange="updateRequestMode()">
                    <div>
                        <span class="text-sm font-medium text-gray-900">Normal</span>
                        <p class="text-xs text-gray-500">Standard change request flow</p>
                    </div>
                </label>
                <label class="flex items-start space-x-3 p-3 border rounded-lg cursor-pointer transition-colors {{ $currentMode === 'blocked' ? 'border-hcrg-burgundy bg-hcrg-burgundy/5' : 'border-gray-200 hover:bg-gray-50' }}" id="modeLabel_blocked">
                    <input type="radio" name="request_mode" value="blocked" {{ $currentMode === 'blocked' ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300" onchange="updateRequestMode()">
                    <div>
                        <span class="text-sm font-medium text-gray-900">Blocked</span>
                        <p class="text-xs text-gray-500">Requests not allowed. Show a message instead.</p>
                    </div>
                </label>
                <label class="flex items-start space-x-3 p-3 border rounded-lg cursor-pointer transition-colors {{ $currentMode === 'self_service' ? 'border-hcrg-burgundy bg-hcrg-burgundy/5' : 'border-gray-200 hover:bg-gray-50' }}" id="modeLabel_self_service">
                    <input type="radio" name="request_mode" value="self_service" {{ $currentMode === 'self_service' ? 'checked' : '' }}
                        class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300" onchange="updateRequestMode()">
                    <div>
                        <span class="text-sm font-medium text-gray-900">Self-service</span>
                        <p class="text-xs text-gray-500">This content type has a self-service tool. Users can request access instead of changes.</p>
                    </div>
                </label>
            </div>
            @error('request_mode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div id="modeMessageSection" class="{{ in_array($currentMode, ['blocked', 'self_service']) ? '' : 'hidden' }}">
            <label for="mode_message" id="modeMessageLabel" class="field-label">
                Message to display
            </label>
            <textarea name="mode_message" id="mode_message" rows="4"
                placeholder="{{ $currentMode === 'self_service' ? 'e.g. This content can be managed using the self-service tool. If you need access, submit a request below...' : 'e.g. Requests for this content type are not currently available...' }}"
                class="field-input">{{ old('mode_message', $cpt->mode_message) }}</textarea>
            <p id="modeMessageHelp" class="mt-1 text-xs text-gray-500">
                {{ $currentMode === 'self_service' ? 'This message will be shown alongside a simplified access request form.' : 'This message will be displayed when a user selects a page of this content type. They will not be able to submit a request.' }}
            </p>
            @error('mode_message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div id="trainingUrlSection" class="{{ $currentMode === 'self_service' ? '' : 'hidden' }}">
            <label for="training_url" class="field-label">
                Training video URL
            </label>
            <input type="url" name="training_url" id="training_url" value="{{ old('training_url', $cpt->training_url) }}"
                placeholder="https://..."
                class="field-input">
            <p class="mt-1 text-xs text-gray-500">
                Link to the training video the access recipient must watch. The training email cannot be sent without this.
            </p>
            @error('training_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="btn btn-primary">
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
                        class="field-input area-name-input"
                        oninput="updateSummary(this)">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Field type <span class="text-red-500">*</span></label>
                    <select name="content_areas[${idx}][type]" required
                        class="field-input area-type-select"
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

            <div class="flex items-center space-x-6">
                <div class="flex items-center">
                    <input type="hidden" name="content_areas[${idx}][required]" value="0">
                    <input type="checkbox" name="content_areas[${idx}][required]" value="1"
                        class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">Required field</label>
                </div>
                <div class="flex items-center">
                    <input type="hidden" name="content_areas[${idx}][allow_add]" value="0">
                    <input type="checkbox" name="content_areas[${idx}][allow_add]" value="1" checked
                        class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">Can add new</label>
                </div>
            </div>

            <div class="word-limit-row">
                <label class="block text-xs font-medium text-gray-500 mb-1">Word limit</label>
                <input type="number" name="content_areas[${idx}][word_limit]" placeholder="No limit" min="1" max="10000"
                    class="field-input w-32">
            </div>

            <div class="reading-age-row" style="display:none">
                <div class="flex items-center">
                    <input type="hidden" name="content_areas[${idx}][reading_age]" value="0">
                    <input type="checkbox" name="content_areas[${idx}][reading_age]" value="1" checked
                        class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">Show reading age</label>
                </div>
                <p class="mt-1 text-xs text-gray-500">Displays a Flesch-Kincaid reading age indicator below the field</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Help text</label>
                <input type="text" name="content_areas[${idx}][help]" placeholder="Guidance shown below the field label"
                    class="field-input">
            </div>

            <div class="placeholder-row">
                <label class="block text-xs font-medium text-gray-500 mb-1">Placeholder</label>
                <input type="text" name="content_areas[${idx}][placeholder]" placeholder="Placeholder text for the input"
                    class="field-input">
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

                <div class="repeatable-row mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-center">
                        <input type="hidden" name="content_areas[${idx}][repeatable]" value="0">
                        <input type="checkbox" name="content_areas[${idx}][repeatable]" value="1"
                            class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700">Allow multiple entries</label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Users can add multiple sets of these fields in one request (e.g. multiple Q&A pairs)</p>
                </div>
            </div>
        </div>
    `;
    list.appendChild(card);
    card.querySelector('.area-name-input').focus();
}

function removeArea(btn) {
    const card = btn.closest('.content-area-card');
    const name = card.querySelector('.area-name-input')?.value;
    if (name) {
        armConfirm(btn, 'Remove?', function() { card.remove(); });
        return;
    }
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

    // Show/hide reading age row (textarea, richtext only)
    const readingAgeRow = card.querySelector('.reading-age-row');
    if (readingAgeRow) {
        readingAgeRow.style.display = ['textarea', 'richtext'].includes(type) ? '' : 'none';
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
            class="field-input flex-1">
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
            class="field-input flex-1">
        <select name="content_areas[${idx}][sub_fields][${sfIdx}][type]"
            class="field-input w-32">
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

function updateRequestMode() {
    var mode = document.querySelector('input[name="request_mode"]:checked').value;
    var section = document.getElementById('modeMessageSection');
    var textarea = document.getElementById('mode_message');
    var helpText = document.getElementById('modeMessageHelp');

    // Show/hide message section
    section.classList.toggle('hidden', mode === 'normal');

    // Training URL only applies to self-service CPTs
    document.getElementById('trainingUrlSection').classList.toggle('hidden', mode !== 'self_service');

    // Update placeholder and help text based on mode
    if (mode === 'blocked') {
        textarea.placeholder = 'e.g. Requests for this content type are not currently available...';
        helpText.textContent = 'This message will be displayed when a user selects a page of this content type. They will not be able to submit a request.';
    } else if (mode === 'self_service') {
        textarea.placeholder = 'e.g. This content can be managed using the self-service tool. If you need access, submit a request below...';
        helpText.textContent = 'This message will be shown alongside a simplified access request form.';
    }

    // Update label highlight styles
    ['normal', 'blocked', 'self_service'].forEach(function(m) {
        var label = document.getElementById('modeLabel_' + m);
        if (m === mode) {
            label.classList.remove('border-gray-200', 'hover:bg-gray-50');
            label.classList.add('border-hcrg-burgundy', 'bg-hcrg-burgundy/5');
        } else {
            label.classList.remove('border-hcrg-burgundy', 'bg-hcrg-burgundy/5');
            label.classList.add('border-gray-200', 'hover:bg-gray-50');
        }
    });
}
</script>
@endsection
