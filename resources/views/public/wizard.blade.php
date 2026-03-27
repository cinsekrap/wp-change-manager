@extends('layouts.public')
@section('title', 'Submit a Change Request')

@section('content')
{{-- Progress bar --}}
<div class="mb-8">
    <div class="flex items-center justify-between mb-2">
        <span id="stepLabel" class="text-sm font-medium text-gray-700">Step 1 of 6</span>
        <span id="stepTitle" class="text-sm text-gray-500">Select site</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2">
        <div id="progressBar" class="bg-hcrg-burgundy h-2 rounded-full transition-all duration-300" style="width: 16.66%"></div>
    </div>
</div>

<form id="wizardForm" class="space-y-6">
    @csrf

    {{-- Step 1: Select site --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6" data-step="1">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Select a website</h2>
        <p class="text-sm text-gray-500 mb-4">Choose the website you'd like to request a change for.</p>

        <input type="hidden" id="siteSelect" value="">
        <div class="relative" id="siteDropdown">
            <input type="text" id="siteSearch" placeholder="Start typing to search..." autocomplete="off"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            <div id="siteOptions" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                @foreach($sites as $site)
                <div class="site-option px-3 py-2 cursor-pointer hover:bg-hcrg-burgundy/10 text-sm" data-value="{{ $site->id }}" data-domain="{{ $site->domain }}" data-label="{{ $site->name }} ({{ $site->domain }})">
                    <span class="font-medium text-gray-900">{{ $site->name }}</span>
                    <span class="text-gray-400 ml-1">{{ $site->domain }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Step 2: Select page --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="2">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Select a page</h2>
        <p class="text-sm text-gray-500 mb-4">Choose the content type and then the page you want to change.</p>

        <div id="cptTabs" class="flex flex-wrap gap-2 mb-4"></div>

        <div class="mb-4">
            <input type="text" id="pageSearch" placeholder="Search pages..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        <div id="pageList" class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100"></div>

        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" id="isNewPage" class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
                <span class="text-sm text-gray-700">This is a <strong>new page</strong> that doesn't exist yet</span>
            </label>
            <div id="newPageFields" class="hidden mt-3 space-y-3">
                <div>
                    <label for="newPageCpt" class="block text-xs font-medium text-gray-500 mb-1">Content type</label>
                    <select id="newPageCpt" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        @foreach($cptTypes as $cpt)
                            <option value="{{ $cpt->slug }}">{{ $cpt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="newPageTitle" class="block text-xs font-medium text-gray-500 mb-1">Proposed page title</label>
                    <input type="text" id="newPageTitle" placeholder="e.g. Our New Service" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                </div>
            </div>
        </div>
    </div>

    {{-- Step 3: What do you want to do? --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="3">
        <h2 class="text-xl font-bold text-gray-900 mb-2">What changes do you need?</h2>
        <p id="step3Subtitle" class="text-sm text-gray-500 mb-4">Describe each change you need. You can add multiple changes.</p>

        {{-- Generic line items (used when no rich content areas) --}}
        <div id="genericFlow">
            <div id="lineItems" class="space-y-4"></div>

            <button type="button" id="addItemBtn" class="mt-4 inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add another change
            </button>
        </div>

        {{-- Structured form (used when CPT has rich content areas) --}}
        <div id="structuredFlow" class="hidden">
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">What do you want to do? <span class="text-red-500">*</span></label>
                <div class="flex space-x-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="structured_action" value="add" class="sr-only peer">
                        <div class="text-center px-4 py-3 border-2 border-gray-200 rounded-lg text-sm font-medium text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 transition-colors">
                            Add new content
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="structured_action" value="change" class="sr-only peer">
                        <div class="text-center px-4 py-3 border-2 border-gray-200 rounded-lg text-sm font-medium text-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 transition-colors">
                            Change existing content
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="structured_action" value="delete" class="sr-only peer">
                        <div class="text-center px-4 py-3 border-2 border-gray-200 rounded-lg text-sm font-medium text-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 transition-colors">
                            Remove content
                        </div>
                    </label>
                </div>
            </div>
            <div id="structuredFields" class="space-y-5"></div>
        </div>
    </div>

    {{-- Step 4: Check questions --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="4">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Before you submit</h2>
        <p class="text-sm text-gray-500 mb-4">Please answer the following questions.</p>

        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-700 mb-2">Is this needed by a specific date? <span class="text-red-500">*</span></p>
            <div class="flex space-x-4 mb-3">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="has_deadline" value="yes" id="hasDeadlineYes" class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                    <span class="text-sm text-gray-700">Yes</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="has_deadline" value="no" id="hasDeadlineNo" class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                    <span class="text-sm text-gray-700">No</span>
                </label>
            </div>
            <div id="deadlineFields" class="hidden space-y-3">
                <div>
                    <label for="deadlineDate" class="block text-xs font-medium text-gray-500 mb-1">When is this needed by?</label>
                    <input type="date" id="deadlineDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                </div>
                <div>
                    <label for="deadlineReason" class="block text-xs font-medium text-gray-500 mb-1">Why is this date important?</label>
                    <input type="text" id="deadlineReason" placeholder="e.g. Service launch, event date, campaign go-live..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                </div>
            </div>
        </div>

        <div id="checkQuestions" class="space-y-6">
            @foreach($questions as $question)
            <div class="question-group" data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? '1' : '0' }}">
                <p class="text-sm font-medium text-gray-700 mb-2">
                    {{ $question->question_text }}
                    @if($question->is_required) <span class="text-red-500">*</span> @endif
                </p>
                <div class="space-y-2">
                    @foreach($question->options as $option)
                    @php $optLabel = is_array($option) ? $option['label'] : $option; @endphp
                    @php $optPass = is_array($option) ? (!empty($option['pass']) ? '1' : '0') : '0'; @endphp
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="check_q_{{ $question->id }}" value="{{ $optLabel }}" data-pass="{{ $optPass }}"
                            class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm text-gray-700">{{ $optLabel }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Step 5: Your details --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="5">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Your details</h2>
        <p class="text-sm text-gray-500 mb-4">So we know who's requesting this change and how to contact you.</p>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="requesterName" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" id="requesterEmail" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-400">(optional)</span></label>
                <input type="tel" id="requesterPhone"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Job title / role <span class="text-gray-400">(optional)</span></label>
                <input type="text" id="requesterRole"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>
        </div>
    </div>

    {{-- Step 6: Review & submit --}}
    <div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="6">
        <h2 class="text-xl font-bold text-gray-900 mb-2">Review your request</h2>
        <p class="text-sm text-gray-500 mb-4">Please check everything looks correct before submitting.</p>

        <div id="reviewContent" class="space-y-4"></div>

        <div id="submitError" class="hidden mt-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>
    </div>
</form>

{{-- Full-screen loading overlay --}}
<div id="loadingOverlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
        <svg class="animate-spin h-10 w-10 text-hcrg-burgundy mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Updating site data</h3>
        <p class="text-sm text-gray-500">This may take a moment&hellip;</p>
    </div>
</div>

{{-- Navigation buttons --}}
<div class="flex justify-between mt-6">
    <button type="button" id="prevBtn" class="hidden px-6 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-50">
        &larr; Back
    </button>
    <div class="ml-auto">
        <button type="button" id="nextBtn" class="px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Next &rarr;
        </button>
        <button type="button" id="submitBtn" class="hidden px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B]">
            Submit Request
        </button>
    </div>
</div>

{{-- Line item template --}}
<template id="lineItemTemplate">
    <div class="line-item border border-gray-200 rounded-lg p-4" data-item-index="">
        <div class="flex justify-between items-start mb-3">
            <span class="text-sm font-medium text-gray-500 item-number">Change #1</span>
            <button type="button" class="remove-item text-red-500 hover:text-red-700 text-sm">&times; Remove</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Action type <span class="text-red-500">*</span></label>
                <div class="flex space-x-4">
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="add" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Add</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="change" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Change</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="delete" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Delete</span>
                    </label>
                </div>
            </div>
            <div class="content-area-container">
                <label class="block text-xs font-medium text-gray-500 mb-1">Content area <span class="text-gray-400">(optional)</span></label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Description <span class="text-red-500">*</span></label>
                <textarea class="item-description w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                    rows="3" placeholder="Describe the change you need in detail..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Attachments <span class="text-gray-400">(max 5 files, 10MB each)</span></label>
                <input type="file" class="file-input text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-hcrg-burgundy/10 file:text-hcrg-burgundy hover:file:bg-hcrg-burgundy/20 cursor-pointer" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.pptx">
                <div class="file-list mt-2 space-y-1"></div>
            </div>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
(function() {
    const STORAGE_KEY = 'acme_change_wizard';
    const cptTypesData = @json($cptTypes->keyBy('slug'));

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function countWords(str) {
        if (!str || !str.trim()) return 0;
        return str.trim().split(/\s+/).length;
    }

    function getCurrentCptSlug() {
        const isNew = document.getElementById('isNewPage').checked;
        if (isNew) return document.getElementById('newPageCpt').value;
        if (selectedPage) return selectedPage.cpt_slug;
        return null;
    }

    /**
     * Returns the raw content_areas from form_config for the current CPT.
     * May be an array of strings (legacy) or array of objects (rich).
     */
    function getContentAreas() {
        const slug = getCurrentCptSlug();
        if (!slug || !cptTypesData[slug]) return [];
        const config = cptTypesData[slug].form_config;
        if (!config || !config.content_areas) return [];
        return config.content_areas;
    }

    /**
     * Determine if the current CPT has rich (structured) content areas.
     * Rich = array of objects with a "type" property.
     * Legacy = array of strings.
     */
    function hasRichContentAreas() {
        const areas = getContentAreas();
        if (areas.length === 0) return false;
        return typeof areas[0] === 'object' && areas[0] !== null && 'type' in areas[0];
    }

    /**
     * For the legacy generic flow: return just the area names (strings).
     */
    function getContentAreaNames() {
        const areas = getContentAreas();
        if (areas.length === 0) return [];
        // Legacy format: already strings
        if (typeof areas[0] === 'string') return areas;
        // Rich format: extract names
        return areas.map(a => a.name);
    }

    function buildContentAreaField(container, existingValue) {
        const areas = getContentAreaNames();
        // Remove any existing input/select after the label
        const label = container.querySelector('label');
        const existing = container.querySelector('input.content-area, select.content-area');
        if (existing) existing.remove();

        if (areas.length > 0) {
            const select = document.createElement('select');
            select.className = 'content-area w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy';
            let html = '<option value="">Select content area...</option>';
            areas.forEach(area => {
                const selected = existingValue === area ? ' selected' : '';
                html += `<option value="${esc(area)}"${selected}>${esc(area)}</option>`;
            });
            select.innerHTML = html;
            container.appendChild(select);
        } else {
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'content-area w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy';
            input.placeholder = 'e.g. Main content, Sidebar, Hero image...';
            if (existingValue) input.value = existingValue;
            container.appendChild(input);
        }
    }

    function refreshAllContentAreaFields() {
        document.querySelectorAll('.line-item').forEach(item => {
            const container = item.querySelector('.content-area-container');
            const existingField = container.querySelector('.content-area');
            const currentValue = existingField ? existingField.value : '';
            buildContentAreaField(container, currentValue);
        });
    }

    // ---- Structured form helpers ----
    let structuredUploadedFiles = {}; // keyed by area index

    function getStructuredAction() {
        const checked = document.querySelector('input[name="structured_action"]:checked');
        return checked ? checked.value : null;
    }

    function buildStructuredForm() {
        const container = document.getElementById('structuredFields');
        container.innerHTML = '';
        structuredUploadedFiles = {};
        const areas = getContentAreas();
        const action = getStructuredAction();

        if (!action) {
            container.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">Select an action above to continue.</p>';
            return;
        }

        const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy';

        if (action === 'delete') {
            // Delete: simple form — which area and what to remove
            const section = document.createElement('div');
            section.className = 'structured-field border border-red-200 bg-red-50/50 rounded-lg p-4 space-y-3';
            section.dataset.areaIndex = '0';
            section.dataset.areaName = '';
            section.dataset.areaType = 'textarea';
            section.dataset.areaRequired = '1';
            section.dataset.actionType = 'delete';

            let html = `<label class="block text-sm font-medium text-gray-700 mb-1">Which content area? <span class="text-red-500">*</span></label>`;
            // If we have content areas, show as dropdown, otherwise free text
            if (areas.length > 0) {
                html += `<select class="sf-delete-area ${inputClass} mb-3"><option value="">Select area...</option>`;
                areas.forEach(a => { html += `<option value="${esc(typeof a === 'string' ? a : a.name)}">${esc(typeof a === 'string' ? a : a.name)}</option>`; });
                html += `</select>`;
            } else {
                html += `<input type="text" class="sf-delete-area ${inputClass} mb-3" placeholder="e.g. Sidebar, Hero image...">`;
            }
            html += `<label class="block text-sm font-medium text-gray-700 mb-1">What should be removed? <span class="text-red-500">*</span></label>`;
            html += `<p class="text-xs text-gray-400 mb-2">Copy/paste the content or describe what needs to be removed.</p>`;
            html += `<textarea class="sf-input ${inputClass}" rows="4" placeholder="Paste or describe the content to remove..."></textarea>`;
            html += `<label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Reason for removal <span class="text-gray-400 font-normal">(optional)</span></label>`;
            html += `<input type="text" class="sf-delete-reason ${inputClass}" placeholder="e.g. Outdated, no longer relevant...">`;

            section.innerHTML = html;
            container.appendChild(section);

            section.querySelectorAll('input, textarea, select').forEach(el => {
                el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', checkStepValid);
            });
            return;
        }

        // Add or Change: render per content area
        areas.forEach((area, idx) => {
            const areaObj = typeof area === 'string' ? { name: area, type: 'textarea', required: false, help: '', placeholder: '', options: [], word_limit: null, sub_fields: [] } : area;
            const section = document.createElement('div');
            const borderColor = action === 'change' ? 'border-blue-200' : 'border-green-200';
            const bgColor = action === 'change' ? 'bg-blue-50/30' : 'bg-green-50/30';
            section.className = `structured-field border ${borderColor} ${bgColor} rounded-lg p-4`;
            section.dataset.areaIndex = idx;
            section.dataset.areaName = areaObj.name;
            section.dataset.areaType = areaObj.type;
            section.dataset.areaRequired = areaObj.required ? '1' : '0';
            section.dataset.actionType = action;
            if (areaObj.word_limit) {
                section.dataset.wordLimit = areaObj.word_limit;
            }

            let fieldHtml = '';
            const requiredMark = areaObj.required ? ' <span class="text-red-500">*</span>' : '';

            fieldHtml += `<label class="block text-sm font-medium text-gray-700 mb-1">${esc(areaObj.name)}${requiredMark}</label>`;
            if (areaObj.help) {
                fieldHtml += `<p class="text-xs text-gray-400 mb-2">${esc(areaObj.help)}</p>`;
            }

            if (action === 'change' && areaObj.type !== 'group') {
                // Current content field
                fieldHtml += `<div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-red-700 mb-1">Current content</label>`;
                fieldHtml += `<textarea class="sf-current ${inputClass} bg-white" rows="2" placeholder="Paste or describe what's currently on the page..."></textarea>`;
                fieldHtml += `</div>`;
                fieldHtml += `<div class="p-3 bg-green-50 border border-green-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-green-700 mb-1">Replace with</label>`;
            }

            if (action === 'change' && areaObj.type === 'group') {
                // Group in change mode: current content textarea then sub-fields for new content
                fieldHtml += `<div class="mb-3 p-3 bg-red-50 border border-red-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-red-700 mb-1">Current content</label>`;
                fieldHtml += `<textarea class="sf-current ${inputClass} bg-white" rows="2" placeholder="Describe what's currently on the page..."></textarea>`;
                fieldHtml += `</div>`;
                fieldHtml += `<div class="p-3 bg-green-50 border border-green-200 rounded-lg space-y-3">`;
                fieldHtml += `<label class="block text-xs font-medium text-green-700 mb-1">Replace with</label>`;
                (areaObj.sub_fields || []).forEach((sf, sfIdx) => {
                    fieldHtml += `<div>`;
                    fieldHtml += `<label class="block text-xs font-medium text-gray-600 mb-1">${esc(sf.name)}</label>`;
                    if (sf.type === 'textarea') {
                        fieldHtml += `<textarea class="sf-group-input ${inputClass} bg-white" data-sf-name="${esc(sf.name)}" rows="3" placeholder="${esc(sf.placeholder || '')}"></textarea>`;
                    } else {
                        fieldHtml += `<input type="text" class="sf-group-input ${inputClass} bg-white" data-sf-name="${esc(sf.name)}" placeholder="${esc(sf.placeholder || '')}">`;
                    }
                    fieldHtml += `</div>`;
                });
                fieldHtml += `</div>`;
            } else if (areaObj.type === 'group') {
                // Group in add mode: bordered card with sub-fields
                fieldHtml += `<div class="border border-gray-200 rounded-lg p-4 space-y-3 sf-group-card">`;
                (areaObj.sub_fields || []).forEach((sf, sfIdx) => {
                    fieldHtml += `<div>`;
                    fieldHtml += `<label class="block text-xs font-medium text-gray-600 mb-1">${esc(sf.name)}</label>`;
                    if (sf.type === 'textarea') {
                        fieldHtml += `<textarea class="sf-group-input ${inputClass}" data-sf-name="${esc(sf.name)}" rows="3" placeholder="${esc(sf.placeholder || '')}"></textarea>`;
                    } else {
                        fieldHtml += `<input type="text" class="sf-group-input ${inputClass}" data-sf-name="${esc(sf.name)}" placeholder="${esc(sf.placeholder || '')}">`;
                    }
                    fieldHtml += `</div>`;
                });
                fieldHtml += `</div>`;
            } else {
                // The actual input field for non-group types
                switch (areaObj.type) {
                    case 'text':
                        fieldHtml += `<input type="text" class="sf-input ${inputClass}${action === 'change' ? ' bg-white' : ''}" placeholder="${esc(areaObj.placeholder || '')}">`;
                        break;
                    case 'textarea':
                    case 'richtext':
                        const rows = areaObj.type === 'richtext' ? 6 : 3;
                        fieldHtml += `<textarea class="sf-input ${inputClass}${action === 'change' ? ' bg-white' : ''}" rows="${rows}" placeholder="${esc(areaObj.placeholder || '')}"></textarea>`;
                        break;
                    case 'select':
                        fieldHtml += `<select class="sf-input ${inputClass}${action === 'change' ? ' bg-white' : ''}"><option value="">Select...</option>`;
                        (areaObj.options || []).forEach(opt => { fieldHtml += `<option value="${esc(opt)}">${esc(opt)}</option>`; });
                        fieldHtml += `</select>`;
                        break;
                    case 'checkbox':
                        fieldHtml += `<label class="flex items-center space-x-2 cursor-pointer mt-1"><input type="checkbox" class="sf-input sf-checkbox h-4 w-4 text-hcrg-burgundy border-gray-300 rounded"><span class="text-sm text-gray-700">Yes</span></label>`;
                        break;
                    case 'date':
                        fieldHtml += `<input type="date" class="sf-input ${inputClass}${action === 'change' ? ' bg-white' : ''}">`;
                        break;
                    case 'file':
                        fieldHtml += `<input type="file" class="sf-file-input text-sm" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.pptx">`;
                        fieldHtml += `<div class="sf-file-list mt-2 space-y-1"></div>`;
                        break;
                }

                // Word counter for text/textarea/richtext with word_limit
                if (areaObj.word_limit && ['text', 'textarea', 'richtext'].includes(areaObj.type)) {
                    fieldHtml += `<div class="sf-word-counter text-xs text-gray-400 mt-1" data-limit="${areaObj.word_limit}">0 / ${areaObj.word_limit} words</div>`;
                }
            }

            if (action === 'change' && areaObj.type !== 'group') {
                fieldHtml += `</div>`; // close green box
            }

            section.innerHTML = fieldHtml;
            container.appendChild(section);

            // Attach validation listeners for non-group fields
            const input = section.querySelector('.sf-input');
            if (input) {
                if (input.tagName === 'SELECT' || input.type === 'checkbox') {
                    input.addEventListener('change', checkStepValid);
                } else {
                    input.addEventListener('input', checkStepValid);
                }
            }

            // Attach validation listeners for group sub-fields
            section.querySelectorAll('.sf-group-input').forEach(gInput => {
                gInput.addEventListener('input', checkStepValid);
            });

            // Word counter listener
            const wordCounter = section.querySelector('.sf-word-counter');
            if (wordCounter && input) {
                input.addEventListener('input', function() {
                    const wc = countWords(this.value);
                    const limit = parseInt(wordCounter.dataset.limit);
                    wordCounter.textContent = wc + ' / ' + limit + ' words';
                    if (wc > limit) {
                        wordCounter.classList.remove('text-gray-400');
                        wordCounter.classList.add('text-red-600', 'font-medium');
                    } else {
                        wordCounter.classList.remove('text-red-600', 'font-medium');
                        wordCounter.classList.add('text-gray-400');
                    }
                });
            }

            // File upload for structured fields
            const fileInput = section.querySelector('.sf-file-input');
            if (fileInput) {
                structuredUploadedFiles[idx] = [];
                fileInput.addEventListener('change', function() {
                    handleStructuredFileUpload(this, idx);
                });
            }
        });
    }

    async function handleStructuredFileUpload(input, areaIndex) {
        if (!structuredUploadedFiles[areaIndex]) structuredUploadedFiles[areaIndex] = [];
        const fileList = input.closest('.structured-field').querySelector('.sf-file-list');

        for (const file of input.files) {
            if (structuredUploadedFiles[areaIndex].length >= 5) {
                alert('Maximum 5 files per field.');
                break;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert(`${file.name} exceeds the 10MB limit.`);
                continue;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('{{ route("api.upload") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData,
                });

                const data = await res.json();

                if (data.success) {
                    structuredUploadedFiles[areaIndex].push(data);
                    const fileEl = document.createElement('div');
                    fileEl.className = 'flex items-center justify-between bg-gray-50 px-3 py-1.5 rounded text-sm';
                    fileEl.dataset.filename = data.filename;
                    fileEl.innerHTML = `<span class="text-gray-700 truncate mr-2">${esc(data.original_name)}</span>` +
                        `<button type="button" class="text-red-500 hover:text-red-700 text-xs font-medium flex-shrink-0">Remove</button>`;

                    fileEl.querySelector('button').addEventListener('click', async () => {
                        await fetch(`/api/upload/${data.filename}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                        });
                        structuredUploadedFiles[areaIndex] = structuredUploadedFiles[areaIndex].filter(f => f.filename !== data.filename);
                        fileEl.remove();
                        checkStepValid();
                    });

                    fileList.appendChild(fileEl);
                    checkStepValid();
                }
            } catch (err) {
                alert('Upload failed: ' + err.message);
            }
        }

        input.value = '';
    }

    /**
     * Decide whether to show the generic or structured flow on step 3.
     */
    function updateStep3Flow() {
        const genericFlow = document.getElementById('genericFlow');
        const structuredFlow = document.getElementById('structuredFlow');
        const subtitle = document.getElementById('step3Subtitle');

        if (hasRichContentAreas()) {
            genericFlow.classList.add('hidden');
            structuredFlow.classList.remove('hidden');
            subtitle.textContent = 'Fill in the details for each content area below.';
            buildStructuredForm();
        } else {
            genericFlow.classList.remove('hidden');
            structuredFlow.classList.add('hidden');
            subtitle.textContent = 'Describe each change you need. You can add multiple changes.';
        }
    }

    /**
     * Get structured form field values as items array for submission.
     */
    function getStructuredItems() {
        const items = [];
        const action = getStructuredAction() || 'change';

        document.querySelectorAll('#structuredFields .structured-field').forEach(section => {
            const areaName = section.dataset.areaName;
            const areaType = section.dataset.areaType;
            const idx = parseInt(section.dataset.areaIndex);
            let description = '';
            let currentContent = null;
            let files = [];

            if (action === 'delete') {
                // Delete flow: single section with area selector + content to remove
                const areaEl = section.querySelector('.sf-delete-area');
                const contentArea = areaEl ? areaEl.value : '';
                const input = section.querySelector('.sf-input');
                description = input ? input.value : '';
                const reason = section.querySelector('.sf-delete-reason');

                if (description) {
                    items.push({
                        action_type: 'delete',
                        content_area: contentArea,
                        description: description,
                        current_content: reason ? reason.value || null : null,
                        files: [],
                    });
                }
                return;
            }

            // Add or Change flow
            if (areaType === 'group') {
                // Group: collect sub-field values and format readably
                const groupInputs = section.querySelectorAll('.sf-group-input');
                const parts = [];
                groupInputs.forEach(gi => {
                    const sfName = gi.dataset.sfName;
                    const sfVal = gi.value.trim();
                    if (sfVal) {
                        parts.push('**' + sfName + ':** ' + sfVal);
                    }
                });
                description = parts.join('\n');

                // For change actions, get the current content
                if (action === 'change') {
                    const currentEl = section.querySelector('.sf-current');
                    currentContent = currentEl ? currentEl.value || null : null;
                }

                if (description) {
                    items.push({
                        action_type: action,
                        content_area: areaName,
                        description: description,
                        current_content: currentContent,
                        files: [],
                    });
                }
            } else if (areaType === 'file') {
                const uploaded = structuredUploadedFiles[idx] || [];
                description = uploaded.map(f => f.original_name).join(', ');
                files = uploaded.map(f => ({
                    filename: f.filename,
                    original_name: f.original_name,
                    mime_type: f.mime_type,
                    file_size: f.file_size,
                }));

                if (description) {
                    items.push({
                        action_type: action,
                        content_area: areaName,
                        description: description,
                        current_content: currentContent,
                        files: files,
                    });
                }
            } else if (areaType === 'checkbox') {
                const cb = section.querySelector('.sf-checkbox');
                description = cb && cb.checked ? 'Yes' : 'No';

                items.push({
                    action_type: action,
                    content_area: areaName,
                    description: description,
                    current_content: currentContent,
                    files: [],
                });
            } else {
                const input = section.querySelector('.sf-input');
                description = input ? input.value : '';

                // For change actions, get the current content
                if (action === 'change') {
                    const currentEl = section.querySelector('.sf-current');
                    currentContent = currentEl ? currentEl.value || null : null;
                }

                if (description) {
                    items.push({
                        action_type: action,
                        content_area: areaName,
                        description: description,
                        current_content: currentContent,
                        files: files,
                    });
                }
            }
        });
        return items;
    }

    // ---- End structured form helpers ----

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    let currentStep = 1;
    const totalSteps = 6;
    let siteData = { pages: [], cpts: [] };
    let selectedPage = null;
    let selectedCpt = null;
    let uploadedFiles = {}; // keyed by item index
    let siteLoadError = null;

    const stepTitles = ['Select site', 'Select page', 'Describe changes', 'Check questions', 'Your details', 'Review & submit'];

    // DOM elements
    const steps = document.querySelectorAll('.wizard-step');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const progressBar = document.getElementById('progressBar');
    const stepLabel = document.getElementById('stepLabel');
    const stepTitle = document.getElementById('stepTitle');

    // Initialize
    loadState();
    addLineItem();
    updateUI();

    // Event listeners
    prevBtn.addEventListener('click', () => { if (currentStep > 1) { currentStep--; updateUI(); } });
    nextBtn.addEventListener('click', () => { if (validateStep(currentStep) && currentStep < totalSteps) { currentStep++; updateUI(); saveState(); } });
    submitBtn.addEventListener('click', submitForm);
    document.getElementById('addItemBtn').addEventListener('click', addLineItem);

    // Searchable site dropdown
    const siteSearchInput = document.getElementById('siteSearch');
    const siteOptionsEl = document.getElementById('siteOptions');
    const siteHidden = document.getElementById('siteSelect');
    const allSiteOptions = document.querySelectorAll('.site-option');

    siteSearchInput.addEventListener('focus', () => {
        siteOptionsEl.classList.remove('hidden');
        filterSiteOptions();
    });

    siteSearchInput.addEventListener('input', () => {
        // If user edits text after selecting, clear the selection
        siteHidden.value = '';
        siteOptionsEl.classList.remove('hidden');
        filterSiteOptions();
        checkStepValid();
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('siteDropdown').contains(e.target)) {
            siteOptionsEl.classList.add('hidden');
        }
    });

    allSiteOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            siteHidden.value = opt.dataset.value;
            siteSearchInput.value = opt.dataset.label;
            siteOptionsEl.classList.add('hidden');
            loadSitePages(opt.dataset.value);
            checkStepValid();
        });
    });

    function filterSiteOptions() {
        const q = siteSearchInput.value.toLowerCase();
        allSiteOptions.forEach(opt => {
            const match = opt.dataset.label.toLowerCase().includes(q);
            opt.classList.toggle('hidden', !match);
        });
    }

    document.getElementById('isNewPage').addEventListener('change', function() {
        document.getElementById('newPageFields').classList.toggle('hidden', !this.checked);
        if (this.checked) {
            selectedPage = null;
            document.querySelectorAll('.page-option.selected').forEach(el => el.classList.remove('selected', 'bg-hcrg-burgundy/10', 'border-hcrg-burgundy'));
        }
        refreshAllContentAreaFields();
        checkStepValid();
    });

    document.getElementById('newPageCpt').addEventListener('change', function() {
        refreshAllContentAreaFields();
    });

    document.getElementById('newPageTitle').addEventListener('input', checkStepValid);
    document.getElementById('pageSearch').addEventListener('input', filterPages);

    // Structured action type toggle
    document.querySelectorAll('input[name="structured_action"]').forEach(radio => {
        radio.addEventListener('change', function() {
            buildStructuredForm();
            checkStepValid();
        });
    });

    // Step 5 inputs
    ['requesterName', 'requesterEmail'].forEach(id => {
        document.getElementById(id).addEventListener('input', checkStepValid);
    });

    // Check questions
    document.querySelectorAll('#checkQuestions input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', checkStepValid);
    });

    // Deadline toggle
    document.querySelectorAll('input[name="has_deadline"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('deadlineFields').classList.toggle('hidden', this.value !== 'yes');
            checkStepValid();
        });
    });
    document.getElementById('deadlineDate').addEventListener('input', checkStepValid);
    document.getElementById('deadlineReason').addEventListener('input', checkStepValid);

    function updateUI() {
        steps.forEach((step, i) => {
            step.classList.toggle('hidden', i !== currentStep - 1);
        });

        prevBtn.classList.toggle('hidden', currentStep === 1);
        nextBtn.classList.toggle('hidden', currentStep === totalSteps);
        submitBtn.classList.toggle('hidden', currentStep !== totalSteps);

        progressBar.style.width = ((currentStep / totalSteps) * 100) + '%';
        stepLabel.textContent = `Step ${currentStep} of ${totalSteps}`;
        stepTitle.textContent = stepTitles[currentStep - 1];

        if (currentStep === 3) {
            updateStep3Flow();
        }

        if (currentStep === totalSteps) {
            buildReview();
        }

        checkStepValid();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                return document.getElementById('siteSelect').value !== '' && !siteLoadError;
            case 2:
                const isNew = document.getElementById('isNewPage').checked;
                if (isNew) return document.getElementById('newPageTitle').value.trim() !== '';
                return selectedPage !== null;
            case 3:
                if (hasRichContentAreas()) {
                    return validateStructuredForm();
                }
                // Generic flow
                const items = document.querySelectorAll('.line-item');
                if (items.length === 0) return false;
                for (const item of items) {
                    const actionType = item.querySelector('.action-type:checked');
                    const desc = item.querySelector('.item-description').value.trim();
                    if (!actionType || !desc) return false;
                }
                return true;
            case 4:
                // Deadline question
                const deadlineChoice = document.querySelector('input[name="has_deadline"]:checked');
                if (!deadlineChoice) return false;
                if (deadlineChoice.value === 'yes') {
                    if (!document.getElementById('deadlineDate').value) return false;
                    if (!document.getElementById('deadlineReason').value.trim()) return false;
                }
                // Check questions
                const required = document.querySelectorAll('.question-group[data-required="1"]');
                for (const group of required) {
                    const qId = group.dataset.questionId;
                    const checked = group.querySelector(`input[name="check_q_${qId}"]:checked`);
                    if (!checked) return false;
                }
                return true;
            case 5:
                const name = document.getElementById('requesterName').value.trim();
                const email = document.getElementById('requesterEmail').value.trim();
                return name !== '' && email !== '' && email.includes('@');
            default:
                return true;
        }
    }

    function validateStructuredForm() {
        const action = getStructuredAction();
        if (!action) return false;

        const fields = document.querySelectorAll('#structuredFields .structured-field');
        if (fields.length === 0) return false;

        if (action === 'delete') {
            const section = fields[0];
            const areaEl = section.querySelector('.sf-delete-area');
            if (areaEl && !areaEl.value.trim()) return false;
            const input = section.querySelector('.sf-input');
            if (!input || !input.value.trim()) return false;
            return true;
        }

        for (const field of fields) {
            const isRequired = field.dataset.areaRequired === '1';
            const type = field.dataset.areaType;

            if (type === 'group') {
                // For required groups, at least one sub-field must have a value
                if (isRequired) {
                    const groupInputs = field.querySelectorAll('.sf-group-input');
                    let hasAnyValue = false;
                    groupInputs.forEach(gi => { if (gi.value.trim()) hasAnyValue = true; });
                    if (!hasAnyValue) return false;
                }
                continue;
            }

            if (!isRequired) {
                // Even if not required, check word limit if field has content
                const wordLimit = field.dataset.wordLimit ? parseInt(field.dataset.wordLimit) : null;
                if (wordLimit) {
                    const input = field.querySelector('.sf-input');
                    if (input && input.value.trim() && countWords(input.value) > wordLimit) return false;
                }
                continue;
            }

            if (type === 'file') {
                const idx = parseInt(field.dataset.areaIndex);
                if (!structuredUploadedFiles[idx] || structuredUploadedFiles[idx].length === 0) return false;
            } else if (type === 'checkbox') {
                continue;
            } else {
                const input = field.querySelector('.sf-input');
                if (!input || !input.value.trim()) return false;

                // Check word limit for required fields
                const wordLimit = field.dataset.wordLimit ? parseInt(field.dataset.wordLimit) : null;
                if (wordLimit && countWords(input.value) > wordLimit) return false;
            }
        }
        return true;
    }

    function checkStepValid() {
        const valid = validateStep(currentStep);
        nextBtn.disabled = !valid;
    }

    function addLineItem() {
        const container = document.getElementById('lineItems');
        const template = document.getElementById('lineItemTemplate');
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.line-item');
        const index = container.children.length;

        item.dataset.itemIndex = index;
        item.querySelector('.item-number').textContent = `Change #${index + 1}`;

        // Set unique radio group name
        item.querySelectorAll('.action-type').forEach(radio => {
            radio.name = `action_type_${index}`;
        });

        // Build content area field (dropdown or free text)
        buildContentAreaField(item.querySelector('.content-area-container'), '');

        // Remove button
        item.querySelector('.remove-item').addEventListener('click', function() {
            if (container.children.length <= 1) return;
            if (item.querySelector('.item-description').value.trim() && !confirm('Remove this change?')) return;
            item.remove();
            renumberItems();
            checkStepValid();
        });

        // File upload
        const fileInput = item.querySelector('.file-input');
        fileInput.addEventListener('change', function() { handleFileUpload(this, index); });

        // Validation listeners
        item.querySelectorAll('.action-type').forEach(radio => {
            radio.addEventListener('change', checkStepValid);
        });
        item.querySelector('.item-description').addEventListener('input', checkStepValid);

        container.appendChild(clone);
        checkStepValid();
    }

    function renumberItems() {
        document.querySelectorAll('.line-item').forEach((item, i) => {
            item.dataset.itemIndex = i;
            item.querySelector('.item-number').textContent = `Change #${i + 1}`;
            item.querySelectorAll('.action-type').forEach(radio => {
                radio.name = `action_type_${i}`;
            });
        });
    }

    async function handleFileUpload(input, itemIndex) {
        if (!uploadedFiles[itemIndex]) uploadedFiles[itemIndex] = [];
        const fileList = input.closest('.line-item').querySelector('.file-list');

        for (const file of input.files) {
            if (uploadedFiles[itemIndex].length >= 5) {
                alert('Maximum 5 files per change item.');
                break;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert(`${file.name} exceeds the 10MB limit.`);
                continue;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('{{ route("api.upload") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData,
                });

                const data = await res.json();

                if (data.success) {
                    uploadedFiles[itemIndex].push(data);
                    const fileEl = document.createElement('div');
                    fileEl.className = 'flex items-center justify-between bg-gray-50 px-3 py-1.5 rounded text-sm';
                    fileEl.dataset.filename = data.filename;
                    fileEl.innerHTML = `<span class="text-gray-700 truncate mr-2">${esc(data.original_name)}</span>` +
                        `<button type="button" class="text-red-500 hover:text-red-700 text-xs font-medium flex-shrink-0">Remove</button>`;

                    fileEl.querySelector('button').addEventListener('click', async () => {
                        await fetch(`/api/upload/${data.filename}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                        });
                        uploadedFiles[itemIndex] = uploadedFiles[itemIndex].filter(f => f.filename !== data.filename);
                        fileEl.remove();
                    });

                    fileList.appendChild(fileEl);
                }
            } catch (err) {
                alert('Upload failed: ' + err.message);
            }
        }

        input.value = '';
    }

    async function loadSitePages(siteId, silent = false) {
        const overlay = document.getElementById('loadingOverlay');
        if (!silent) overlay.classList.remove('hidden');
        siteLoadError = null;

        const minDelay = silent
            ? Promise.resolve()
            : new Promise(resolve => setTimeout(resolve, 3000));

        try {
            // Check status first
            const statusRes = await fetch(`/api/sitemap/status/${siteId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });
            const status = await statusRes.json();

            if (!status.has_data || status.needs_refresh) {
                const refreshRes = await fetch(`/api/sitemap/refresh/${siteId}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                });
                const refreshData = await refreshRes.json();

                if (!refreshData.success) {
                    siteLoadError = refreshData.message || 'Could not load site data. Please contact the web team.';
                    await minDelay;
                    overlay.classList.add('hidden');
                    showSiteError(siteLoadError);
                    checkStepValid();
                    return;
                }
            }

            // Fetch pages
            const pagesRes = await fetch(`/api/pages/${siteId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });
            siteData = await pagesRes.json();

            if (!siteData.pages || siteData.pages.length === 0) {
                siteLoadError = 'No pages found for this site. The sitemap may not be configured correctly. Please contact the web team.';
                await minDelay;
                overlay.classList.add('hidden');
                showSiteError(siteLoadError);
                checkStepValid();
                return;
            }

            hideSiteError();
            renderCptTabs();
            renderPages();
        } catch (err) {
            console.error('Failed to load pages:', err);
            siteLoadError = 'Something went wrong loading site data. Please try again or contact the web team.';
            showSiteError(siteLoadError);
        }

        await minDelay;
        overlay.classList.add('hidden');
        checkStepValid();
    }

    function showSiteError(message) {
        let el = document.getElementById('siteError');
        if (!el) {
            el = document.createElement('div');
            el.id = 'siteError';
            el.className = 'mt-4 p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg';
            document.getElementById('siteSelect').parentNode.appendChild(el);
        }
        el.textContent = message;
        el.classList.remove('hidden');
    }

    function hideSiteError() {
        const el = document.getElementById('siteError');
        if (el) el.classList.add('hidden');
    }

    function renderCptTabs() {
        const container = document.getElementById('cptTabs');
        container.innerHTML = '';
        selectedCpt = null;

        const allBtn = document.createElement('button');
        allBtn.type = 'button';
        allBtn.className = 'cpt-tab px-3 py-1.5 rounded-full text-sm font-medium bg-hcrg-burgundy text-white';
        allBtn.textContent = 'All';
        allBtn.dataset.cpt = '';
        allBtn.addEventListener('click', () => selectCpt(null));
        container.appendChild(allBtn);

        (siteData.cpts || []).forEach(cpt => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'cpt-tab px-3 py-1.5 rounded-full text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300';
            btn.textContent = cpt.charAt(0).toUpperCase() + cpt.slice(1);
            btn.dataset.cpt = cpt;
            btn.addEventListener('click', () => selectCpt(cpt));
            container.appendChild(btn);
        });
    }

    function selectCpt(cpt) {
        selectedCpt = cpt;
        document.querySelectorAll('.cpt-tab').forEach(tab => {
            const isActive = tab.dataset.cpt === (cpt || '');
            tab.className = `cpt-tab px-3 py-1.5 rounded-full text-sm font-medium ${isActive ? 'bg-hcrg-burgundy text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`;
        });
        renderPages();
    }

    function renderPages() {
        const container = document.getElementById('pageList');
        const search = document.getElementById('pageSearch').value.toLowerCase();

        let pages = siteData.pages || [];
        if (selectedCpt) pages = pages.filter(p => p.cpt_slug === selectedCpt);
        if (search) pages = pages.filter(p => (p.page_title || '').toLowerCase().includes(search) || (p.url || '').toLowerCase().includes(search));

        if (pages.length === 0) {
            container.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">No pages found.</div>';
            return;
        }

        container.innerHTML = pages.map(p => `
            <div class="page-option p-3 cursor-pointer hover:bg-gray-50 ${selectedPage && selectedPage.id === p.id ? 'selected bg-hcrg-burgundy/10 border-l-4 border-hcrg-burgundy' : ''}"
                 data-page-id="${p.id}" data-url="${esc(p.url)}" data-title="${esc(p.page_title || '')}" data-cpt="${esc(p.cpt_slug)}">
                <div class="text-sm font-medium text-gray-900">${esc(p.page_title || '(No title)')}</div>
                <div class="text-xs text-gray-500 truncate">${esc(p.url)}</div>
            </div>
        `).join('');

        container.querySelectorAll('.page-option').forEach(el => {
            el.addEventListener('click', function() {
                container.querySelectorAll('.page-option').forEach(o => {
                    o.classList.remove('selected', 'bg-hcrg-burgundy/10', 'border-l-4', 'border-hcrg-burgundy');
                });
                this.classList.add('selected', 'bg-hcrg-burgundy/10', 'border-l-4', 'border-hcrg-burgundy');

                selectedPage = {
                    id: parseInt(this.dataset.pageId),
                    url: this.dataset.url,
                    title: this.dataset.title,
                    cpt_slug: this.dataset.cpt,
                };

                document.getElementById('isNewPage').checked = false;
                document.getElementById('newPageTitle').classList.add('hidden');
                refreshAllContentAreaFields();
                checkStepValid();
            });
        });
    }

    function filterPages() {
        renderPages();
    }

    function buildReview() {
        const container = document.getElementById('reviewContent');
        const siteName = document.getElementById('siteSearch').value || '';
        const isNew = document.getElementById('isNewPage').checked;
        const isStructured = hasRichContentAreas();

        let html = `
            <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Website & Page</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="1">Edit</button>
                </div>
                <p class="text-sm text-gray-900">${esc(siteName)}</p>
                <p class="text-sm text-gray-600">${isNew ? 'New ' + esc(document.getElementById('newPageCpt').selectedOptions[0].text.toLowerCase()) + ': ' + esc(document.getElementById('newPageTitle').value) : (selectedPage ? esc(selectedPage.title) + ' — ' + esc(selectedPage.url) : '')}</p>
            </div>`;

        if (isStructured) {
            // Structured form review
            const structuredItems = getStructuredItems();
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Content Areas (${structuredItems.length})</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="3">Edit</button>
                </div>`;

            structuredItems.forEach(item => {
                const fileCount = (item.files || []).length;
                html += `<div class="pl-3 border-l-2 border-gray-200">
                    <span class="text-xs font-medium text-gray-500">${esc(item.content_area)}</span>
                    <p class="text-sm text-gray-700 mt-1">${esc((item.description || '').substring(0, 200))}${(item.description || '').length > 200 ? '...' : ''}</p>
                    ${fileCount > 0 ? `<p class="text-xs text-gray-400 mt-1">${fileCount} file(s) attached</p>` : ''}
                </div>`;
            });
            html += `</div>`;
        } else {
            // Generic line items review
            const items = document.querySelectorAll('.line-item');
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Changes (${items.length})</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="3">Edit</button>
                </div>`;

            items.forEach((item, i) => {
                const action = item.querySelector('.action-type:checked')?.value || '';
                const area = item.querySelector('.content-area').value;
                const desc = item.querySelector('.item-description').value;
                const fileCount = (uploadedFiles[item.dataset.itemIndex] || []).length;

                html += `<div class="pl-3 border-l-2 border-gray-200">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${action === 'add' ? 'bg-green-100 text-green-800' : action === 'delete' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">${esc(action)}</span>
                    ${area ? `<span class="text-xs text-gray-500 ml-1">${esc(area)}</span>` : ''}
                    <p class="text-sm text-gray-700 mt-1">${esc(desc.substring(0, 200))}${desc.length > 200 ? '...' : ''}</p>
                    ${fileCount > 0 ? `<p class="text-xs text-gray-400 mt-1">${fileCount} file(s) attached</p>` : ''}
                </div>`;
            });
            html += `</div>`;
        }

        // Deadline
        const dlChoice = document.querySelector('input[name="has_deadline"]:checked');
        if (dlChoice) {
            const hasDeadline = dlChoice.value === 'yes';
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Deadline</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="4">Edit</button>
                </div>`;
            if (hasDeadline) {
                html += `<p class="text-sm text-gray-900 font-medium">${esc(document.getElementById('deadlineDate').value)}</p>`;
                html += `<p class="text-sm text-gray-600">${esc(document.getElementById('deadlineReason').value)}</p>`;
            } else {
                html += `<p class="text-sm text-gray-500">No specific deadline</p>`;
            }
            html += `</div>`;
        }

        // Check answers
        const questions = document.querySelectorAll('.question-group');
        if (questions.length > 0) {
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Check Answers</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="4">Edit</button>
                </div>`;
            questions.forEach(group => {
                const qText = group.querySelector('p').textContent.trim();
                const checked = group.querySelector('input[type="radio"]:checked');
                html += `<p class="text-sm"><span class="text-gray-500">${esc(qText)}</span> <strong class="text-gray-900 ml-1">${checked ? esc(checked.value) : '—'}</strong></p>`;
            });
            html += `</div>`;
        }

        // Contact details
        html += `<div class="border border-gray-200 rounded-lg p-4 space-y-2">
            <div class="flex justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Your Details</h3>
                <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline" data-goto="5">Edit</button>
            </div>
            <p class="text-sm text-gray-900">${esc(document.getElementById('requesterName').value)}</p>
            <p class="text-sm text-gray-600">${esc(document.getElementById('requesterEmail').value)}</p>
            ${document.getElementById('requesterPhone').value ? `<p class="text-sm text-gray-600">${esc(document.getElementById('requesterPhone').value)}</p>` : ''}
            ${document.getElementById('requesterRole').value ? `<p class="text-sm text-gray-600">${esc(document.getElementById('requesterRole').value)}</p>` : ''}
        </div>`;

        container.innerHTML = html;

        // Edit buttons
        container.querySelectorAll('.edit-step').forEach(btn => {
            btn.addEventListener('click', () => {
                currentStep = parseInt(btn.dataset.goto);
                updateUI();
            });
        });
    }

    async function submitForm() {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        document.getElementById('submitError').classList.add('hidden');

        const isNew = document.getElementById('isNewPage').checked;
        const isStructured = hasRichContentAreas();
        let items = [];

        if (isStructured) {
            items = getStructuredItems();
        } else {
            document.querySelectorAll('.line-item').forEach(item => {
                const idx = item.dataset.itemIndex;
                items.push({
                    action_type: item.querySelector('.action-type:checked').value,
                    content_area: item.querySelector('.content-area').value || null,
                    description: item.querySelector('.item-description').value,
                    files: (uploadedFiles[idx] || []).map(f => ({
                        filename: f.filename,
                        original_name: f.original_name,
                        mime_type: f.mime_type,
                        file_size: f.file_size,
                    })),
                });
            });
        }

        const checkAnswers = [];
        document.querySelectorAll('.question-group').forEach(group => {
            const qId = group.dataset.questionId;
            const qText = group.querySelector('p').textContent.trim().replace(/\s*\*$/, '');
            const checked = group.querySelector('input[type="radio"]:checked');
            if (checked) {
                checkAnswers.push({ question_id: parseInt(qId), question_text: qText, answer: checked.value, pass: checked.dataset.pass === '1' });
            }
        });

        const payload = {
            site_id: parseInt(document.getElementById('siteSelect').value),
            page_url: isNew ? 'new-page' : selectedPage.url,
            page_title: isNew ? document.getElementById('newPageTitle').value : (selectedPage ? selectedPage.title : null),
            cpt_slug: isNew ? document.getElementById('newPageCpt').value : (selectedPage ? selectedPage.cpt_slug : 'page'),
            is_new_page: isNew,
            requester_name: document.getElementById('requesterName').value,
            requester_email: document.getElementById('requesterEmail').value,
            requester_phone: document.getElementById('requesterPhone').value || null,
            requester_role: document.getElementById('requesterRole').value || null,
            check_answers: checkAnswers,
            deadline_date: document.querySelector('input[name="has_deadline"]:checked')?.value === 'yes' ? document.getElementById('deadlineDate').value : null,
            deadline_reason: document.querySelector('input[name="has_deadline"]:checked')?.value === 'yes' ? document.getElementById('deadlineReason').value : null,
            items: items,
        };

        try {
            const res = await fetch('{{ route("submit") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (data.success) {
                sessionStorage.removeItem(STORAGE_KEY);
                window.location.href = data.redirect;
            } else if (data.errors) {
                const msgs = Object.values(data.errors).flat().join('\n');
                document.getElementById('submitError').textContent = msgs;
                document.getElementById('submitError').classList.remove('hidden');
            } else {
                throw new Error(data.message || 'Submission failed.');
            }
        } catch (err) {
            document.getElementById('submitError').textContent = err.message;
            document.getElementById('submitError').classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Request';
        }
    }

    // Session storage persistence
    function saveState() {
        try {
            const state = {
                siteId: document.getElementById('siteSelect').value,
                selectedPage,
                isNewPage: document.getElementById('isNewPage').checked,
                newPageTitle: document.getElementById('newPageTitle').value,
                newPageCpt: document.getElementById('newPageCpt').value,
                hasDeadline: document.querySelector('input[name="has_deadline"]:checked')?.value || null,
                deadlineDate: document.getElementById('deadlineDate').value,
                deadlineReason: document.getElementById('deadlineReason').value,
                requesterName: document.getElementById('requesterName').value,
                requesterEmail: document.getElementById('requesterEmail').value,
                requesterPhone: document.getElementById('requesterPhone').value,
                requesterRole: document.getElementById('requesterRole').value,
            };
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function loadState() {
        try {
            const saved = sessionStorage.getItem(STORAGE_KEY);
            if (!saved) return;
            const state = JSON.parse(saved);

            if (state.siteId) {
                document.getElementById('siteSelect').value = state.siteId;
                // Restore the visible text from the matching option
                const matchOpt = document.querySelector(`.site-option[data-value="${state.siteId}"]`);
                if (matchOpt) document.getElementById('siteSearch').value = matchOpt.dataset.label;
                loadSitePages(state.siteId, true);
            }

            if (state.selectedPage) selectedPage = state.selectedPage;
            if (state.isNewPage) {
                document.getElementById('isNewPage').checked = true;
                document.getElementById('newPageFields').classList.remove('hidden');
                document.getElementById('newPageTitle').value = state.newPageTitle || '';
                if (state.newPageCpt) document.getElementById('newPageCpt').value = state.newPageCpt;
            }

            if (state.hasDeadline) {
                const radio = document.querySelector(`input[name="has_deadline"][value="${state.hasDeadline}"]`);
                if (radio) radio.checked = true;
                if (state.hasDeadline === 'yes') {
                    document.getElementById('deadlineFields').classList.remove('hidden');
                    document.getElementById('deadlineDate').value = state.deadlineDate || '';
                    document.getElementById('deadlineReason').value = state.deadlineReason || '';
                }
            }

            document.getElementById('requesterName').value = state.requesterName || '';
            document.getElementById('requesterEmail').value = state.requesterEmail || '';
            document.getElementById('requesterPhone').value = state.requesterPhone || '';
            document.getElementById('requesterRole').value = state.requesterRole || '';
        } catch (e) {}
    }

    // Save on input change
    document.addEventListener('input', () => setTimeout(saveState, 100));
})();
</script>
@endsection
