<script>
(function() {
    const STORAGE_KEY = 'acme_change_wizard';
    const cptTypesData = @json($cptTypes->keyBy('slug'));

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function stripTags(str) {
        return str ? str.replace(/<[^>]*>/g, '') : str;
    }

    function countWords(str) {
        if (!str || !str.trim()) return 0;
        return str.trim().split(/\s+/).length;
    }

    function countSyllables(word) {
        word = word.toLowerCase().replace(/[^a-z]/g, '');
        if (!word) return 1;
        if (word.length <= 3) return 1;
        word = word.replace(/(?:[^laeiouy]es|ed|[^laeiouy]e)$/, '');
        word = word.replace(/^y/, '');
        const matches = word.match(/[aeiouy]{1,2}/g);
        return matches ? Math.max(matches.length, 1) : 1;
    }

    function calculateReadingAge(text) {
        if (!text || !text.trim()) return null;
        const words = text.trim().split(/\s+/).filter(w => w.length > 0);
        if (words.length < 30) return null;
        const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
        const sentenceCount = Math.max(sentences.length, 1);
        let totalSyllables = 0;
        words.forEach(w => { totalSyllables += countSyllables(w); });
        const gradeLevel = 0.39 * (words.length / sentenceCount) + 11.8 * (totalSyllables / words.length) - 15.59;
        return Math.max(Math.round(gradeLevel + 5), 5);
    }

    function readingAgeBadgeHtml(age) {
        if (age === null) return '<span class="text-gray-400">Reading age: not enough text</span>';
        let colour, dotColour;
        if (age <= 11) { colour = 'text-green-600'; dotColour = 'bg-green-500'; }
        else if (age <= 13) { colour = 'text-amber-600'; dotColour = 'bg-amber-500'; }
        else { colour = 'text-red-600'; dotColour = 'bg-red-500'; }
        return `<span class="${colour} font-medium inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full ${dotColour} inline-block"></span>Reading age: ${age}</span>`;
    }

    let readingAgeBypass = false;

    function checkReadingAgeFields() {
        const offending = [];
        document.querySelectorAll('.structured-field[data-reading-age="1"]').forEach(field => {
            const input = field.querySelector('.area-form-fields .sf-input');
            if (input && input.value) {
                const age = calculateReadingAge(input.value);
                if (age !== null && age > 13) {
                    offending.push({ name: field.dataset.areaName, age });
                }
            }
        });
        return offending;
    }

    function getCurrentCptSlug() {
        const isNew = document.getElementById('isNewPage').checked;
        if (isNew) return document.getElementById('newPageCpt').value;
        if (selectedPage) return selectedPage.cpt_slug;
        // If a CPT tab is selected but no page yet, use the tab's CPT
        if (selectedCpt) return selectedCpt;
        return null;
    }

    function getCurrentCptRequestMode() {
        const slug = getCurrentCptSlug();
        if (!slug || !cptTypesData[slug]) return 'normal';
        return cptTypesData[slug].request_mode || 'normal';
    }

    function isCurrentCptBlocked() {
        return getCurrentCptRequestMode() === 'blocked';
    }

    function isCurrentCptSelfService() {
        return getCurrentCptRequestMode() === 'self_service';
    }

    function getModeMessage() {
        const slug = getCurrentCptSlug();
        if (!slug || !cptTypesData[slug]) return '';
        return cptTypesData[slug].mode_message || '';
    }

    function updateBlockedState() {
        const mode = getCurrentCptRequestMode();
        const blockedEl = document.getElementById('blockedCptMessage');
        const blockedTextEl = document.getElementById('blockedCptText');
        const selfServiceEl = document.getElementById('selfServiceCptMessage');
        const selfServiceTextEl = document.getElementById('selfServiceCptText');

        // Hide both by default
        blockedEl.classList.add('hidden');
        selfServiceEl.classList.add('hidden');

        if (mode === 'blocked') {
            blockedTextEl.textContent = getModeMessage() || 'Requests cannot be submitted for this content type.';
            blockedEl.classList.remove('hidden');
        } else if (mode === 'self_service') {
            selfServiceTextEl.textContent = getModeMessage() || 'This content type is managed via a self-service tool.';
            selfServiceEl.classList.remove('hidden');
            initSelfServiceForm();
        }
        checkStepValid();
    }

    function initSelfServiceForm() {
        const ssName = document.getElementById('ssName');
        const ssEmail = document.getElementById('ssEmail');
        const ssReason = document.getElementById('ssReason');
        const ssBtn = document.getElementById('ssSubmitBtn');

        function checkSsValid() {
            const valid = ssName.value.trim() !== '' && ssEmail.value.trim() !== '' && ssEmail.value.includes('@') && ssReason.value.trim() !== '';
            ssBtn.disabled = !valid;
        }

        ssName.removeEventListener('input', checkSsValid);
        ssEmail.removeEventListener('input', checkSsValid);
        ssReason.removeEventListener('input', checkSsValid);
        ssName.addEventListener('input', checkSsValid);
        ssEmail.addEventListener('input', checkSsValid);
        ssReason.addEventListener('input', checkSsValid);

        // Pre-fill from stored requester details if available
        const storedName = document.getElementById('requesterName').value;
        const storedEmail = document.getElementById('requesterEmail').value;
        if (storedName && !ssName.value) ssName.value = storedName;
        if (storedEmail && !ssEmail.value) ssEmail.value = storedEmail;
        checkSsValid();
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

    function buildStructuredForm() {
        const accordion = document.getElementById('areaAccordion');
        accordion.innerHTML = '';
        structuredUploadedFiles = {};

        const areas = getContentAreas();
        if (areas.length === 0) return;

        areas.forEach((area, idx) => {
            const areaObj = typeof area === 'string'
                ? { name: area, type: 'textarea', required: false, help: '', placeholder: '', options: [], word_limit: null, sub_fields: [], reading_age: true }
                : area;

            const typeLabels = { text: 'Text', textarea: 'Text', richtext: 'Rich text', select: 'Dropdown', checkbox: 'Checkbox', date: 'Date', file: 'File upload', group: 'Group' };
            const typeLabel = typeLabels[areaObj.type] || areaObj.type;

            // Accordion item wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'area-accordion-item';
            wrapper.dataset.areaIdx = idx;

            // Checkbox header row
            const header = document.createElement('label');
            header.className = 'flex items-center space-x-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors';
            header.innerHTML = `<input type="checkbox" class="area-checkbox h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy" data-area-idx="${idx}">` +
                `<span class="flex-1"><span class="text-sm font-medium text-gray-900">${esc(areaObj.name)}</span>` +
                `<span class="ml-2 text-xs text-gray-400">${esc(typeLabel)}</span></span>`;

            // Collapsible panel (hidden by default)
            const panel = document.createElement('div');
            panel.className = 'area-accordion-panel hidden mt-1 ml-4 border-l-2 border-hcrg-burgundy/20 pl-4';

            const checkbox = header.querySelector('.area-checkbox');
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    header.classList.remove('border-gray-200');
                    header.classList.add('border-hcrg-burgundy', 'bg-hcrg-burgundy/5');
                    buildAreaForm(areaObj, idx, panel);
                    panel.classList.remove('hidden');
                } else {
                    header.classList.remove('border-hcrg-burgundy', 'bg-hcrg-burgundy/5');
                    header.classList.add('border-gray-200');
                    removeAreaForm(idx);
                    panel.classList.add('hidden');
                }
                checkStepValid();
            });

            wrapper.appendChild(header);
            wrapper.appendChild(panel);
            accordion.appendChild(wrapper);
        });
    }

    function removeAreaForm(idx) {
        const wrapper = document.querySelector(`#areaAccordion .area-accordion-item[data-area-idx="${idx}"]`);
        if (wrapper) {
            const panel = wrapper.querySelector('.area-accordion-panel');
            if (panel) panel.innerHTML = '';
        }
        delete structuredUploadedFiles[idx];
    }

    function buildAreaForm(area, idx, panel) {
        // Clear panel content before rebuilding
        if (panel) panel.innerHTML = '';

        const container = panel || document.querySelector(`#areaAccordion .area-accordion-item[data-area-idx="${idx}"] .area-accordion-panel`);
        const card = document.createElement('div');
        card.className = 'structured-field border-2 border-gray-200 shadow-sm rounded-lg p-5 transition-colors';
        card.dataset.areaIndex = idx;
        card.dataset.areaName = area.name;
        card.dataset.areaType = area.type || 'textarea';
        card.dataset.areaRequired = area.required ? '1' : '0';
        card.dataset.areaRepeatable = area.repeatable ? '1' : '0';
        card.dataset.actionType = '';
        if (area.word_limit) card.dataset.wordLimit = area.word_limit;
        if (area.reading_age) card.dataset.readingAge = '1';

        let html = '';
        if (area.help) {
            html += `<div class="mb-4 flex gap-2.5 p-3 bg-amber-50 border border-amber-200 rounded-lg">`;
            html += `<svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>`;
            html += `<p class="text-sm text-amber-800">${esc(area.help)}</p>`;
            html += `</div>`;
        }

        // Action type selector — depends on context
        const isNewPage = document.getElementById('isNewPage').checked;
        const allowAdd = area.allow_add !== false; // default true if not set

        if (isNewPage) {
            // New page — everything is "add", no picker needed
            html += `<input type="hidden" class="area-action-preset" value="add">`;
        } else {
            html += `<div class="mb-4 pt-3 border-t border-gray-100">`;
            html += `<p class="text-xs font-medium text-gray-500 mb-2">What do you want to do?</p>`;
            html += `<div class="flex flex-wrap gap-2">`;
            if (allowAdd) {
                html += `<button type="button" class="area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-green-300 text-green-700 hover:bg-green-50 transition-colors" data-action="add">Add</button>`;
            }
            html += `<button type="button" class="area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-[#B52159]/30 text-hcrg-burgundy hover:bg-[#B52159]/5 transition-colors" data-action="change">Change</button>`;
            html += `<button type="button" class="area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-red-300 text-red-700 hover:bg-red-50 transition-colors" data-action="delete">Delete</button>`;
            html += `</div>`;
            html += `</div>`;
        }

        // Placeholder for fields
        html += `<div class="area-form-fields"></div>`;

        card.innerHTML = html;

        // Insert card into its accordion panel
        container.appendChild(card);

        // Attach action button listeners
        card.querySelectorAll('.area-action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action;
                card.dataset.actionType = action;

                // Update button styles — reset all, then highlight selected
                card.querySelectorAll('.area-action-btn').forEach(b => {
                    b.classList.remove('bg-green-500', 'text-white', 'border-green-500',
                                       'bg-[#B52159]', 'border-[#B52159]',
                                       'bg-red-500', 'border-red-500');
                    // Reset to default styles
                    const a = b.dataset.action;
                    if (a === 'add') { b.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-green-300 text-green-700 hover:bg-green-50 transition-colors'; }
                    else if (a === 'change') { b.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-[#B52159]/30 text-hcrg-burgundy hover:bg-[#B52159]/5 transition-colors'; }
                    else if (a === 'delete') { b.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-red-300 text-red-700 hover:bg-red-50 transition-colors'; }
                });

                // Fill selected button
                if (action === 'add') {
                    this.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-green-500 bg-green-500 text-white transition-colors';
                    card.classList.remove('border-gray-200', 'border-[#B52159]/30', 'border-red-300');
                    card.classList.add('border-green-300');
                } else if (action === 'change') {
                    this.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-[#B52159] bg-[#B52159] text-white transition-colors';
                    card.classList.remove('border-gray-200', 'border-green-300', 'border-red-300');
                    card.classList.add('border-[#B52159]/30');
                } else if (action === 'delete') {
                    this.className = 'area-action-btn px-3 py-1 text-xs font-medium rounded-full border-2 border-red-500 bg-red-500 text-white transition-colors';
                    card.classList.remove('border-gray-200', 'border-green-300', 'border-[#B52159]/30');
                    card.classList.add('border-red-300');
                }

                renderAreaFields(card, area, action);
                checkStepValid();
            });
        });

        // For new pages, auto-trigger "add" action immediately
        const preset = card.querySelector('.area-action-preset');
        if (preset) {
            card.dataset.actionType = 'add';
            card.classList.add('border-green-300');
            renderAreaFields(card, area, 'add');
        }
    }

    function renderAreaFields(card, area, action) {
        const fieldsContainer = card.querySelector('.area-form-fields');
        fieldsContainer.innerHTML = '';
        const idx = parseInt(card.dataset.areaIndex);
        const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy';
        const areaObj = typeof area === 'string'
            ? { name: area, type: 'textarea', required: false, help: '', placeholder: '', options: [], word_limit: null, sub_fields: [], repeatable: false, reading_age: true }
            : area;

        // Repeatable group: delegate to dedicated renderer
        if (areaObj.type === 'group' && areaObj.repeatable && action !== 'delete') {
            renderRepeatableGroup(fieldsContainer, areaObj, action, idx);
            return;
        }

        let fieldHtml = '';

        if (action === 'delete') {
            // Delete: what should be removed + optional reason
            fieldHtml += `<label class="block text-sm font-medium text-gray-700 mb-1">What should be removed? <span class="text-red-500">*</span></label>`;
            fieldHtml += `<p class="text-xs text-gray-400 mb-2">Copy/paste the content or describe what needs to be removed.</p>`;
            fieldHtml += `<textarea class="sf-input ${inputClass}" rows="4" placeholder="Paste or describe the content to remove..."></textarea>`;
            fieldHtml += `<label class="block text-sm font-medium text-gray-700 mb-1 mt-3">Reason for removal <span class="text-gray-400 font-normal">(optional)</span></label>`;
            fieldHtml += `<input type="text" class="sf-delete-reason ${inputClass}" placeholder="e.g. Outdated, no longer relevant...">`;

            fieldsContainer.innerHTML = fieldHtml;
            fieldsContainer.querySelectorAll('input, textarea').forEach(el => {
                el.addEventListener('input', checkStepValid);
            });
            return;
        }

        if (action === 'change') {
            // Change: "current content" box + typed field for new content
            if (areaObj.type === 'group') {
                fieldHtml += `<div class="mb-3 p-3 bg-red-50 border-2 border-red-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-red-700 mb-1">What's currently on the page?</label>`;
                fieldHtml += `<textarea class="sf-current ${inputClass} bg-white" rows="2" placeholder="Describe what's currently on the page..."></textarea>`;
                fieldHtml += `</div>`;
                fieldHtml += `<div class="p-3 bg-green-50 border-2 border-green-200 rounded-lg space-y-3">`;
                fieldHtml += `<label class="block text-xs font-medium text-green-700 mb-1">What should it be?</label>`;
                (areaObj.sub_fields || []).forEach(sf => {
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
            } else {
                fieldHtml += `<div class="mb-3 p-3 bg-red-50 border-2 border-red-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-red-700 mb-1">What's currently on the page?</label>`;
                fieldHtml += `<textarea class="sf-current ${inputClass} bg-white" rows="2" placeholder="Paste or describe what's currently on the page..."></textarea>`;
                fieldHtml += `</div>`;
                fieldHtml += `<div class="p-3 bg-green-50 border-2 border-green-200 rounded-lg">`;
                fieldHtml += `<label class="block text-xs font-medium text-green-700 mb-1">What should it be?</label>`;
                fieldHtml += renderTypedField(areaObj, inputClass, true);
                fieldHtml += `</div>`;
            }
        } else {
            // Add: just the typed field
            if (areaObj.type === 'group') {
                fieldHtml += `<div class="border-2 border-gray-200 rounded-lg p-4 space-y-3 sf-group-card">`;
                (areaObj.sub_fields || []).forEach(sf => {
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
                fieldHtml += renderTypedField(areaObj, inputClass, false);
            }
        }

        fieldsContainer.innerHTML = fieldHtml;

        // Attach validation listeners
        fieldsContainer.querySelectorAll('.sf-input').forEach(input => {
            if (input.tagName === 'SELECT' || input.type === 'checkbox') {
                input.addEventListener('change', checkStepValid);
            } else {
                input.addEventListener('input', checkStepValid);
            }
        });

        fieldsContainer.querySelectorAll('.sf-current').forEach(el => {
            el.addEventListener('input', checkStepValid);
        });

        fieldsContainer.querySelectorAll('.sf-group-input').forEach(gInput => {
            gInput.addEventListener('input', checkStepValid);
        });

        fieldsContainer.querySelectorAll('.sf-delete-reason').forEach(el => {
            el.addEventListener('input', checkStepValid);
        });

        // Word counter listener
        const wordCounter = fieldsContainer.querySelector('.sf-word-counter');
        const mainInput = fieldsContainer.querySelector('.sf-input');
        if (wordCounter && mainInput) {
            mainInput.addEventListener('input', function() {
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

        // Reading age listener
        const readingAgeBadge = fieldsContainer.querySelector('.sf-reading-age-badge');
        const readingAgeInput = readingAgeBadge ? (fieldsContainer.querySelector('.sf-input')) : null;
        if (readingAgeBadge && readingAgeInput) {
            readingAgeInput.addEventListener('input', function() {
                const age = calculateReadingAge(this.value);
                readingAgeBadge.innerHTML = readingAgeBadgeHtml(age);
            });
        }

        // File upload for structured fields
        const fileInput = fieldsContainer.querySelector('.sf-file-input');
        if (fileInput) {
            structuredUploadedFiles[idx] = structuredUploadedFiles[idx] || [];
            fileInput.addEventListener('change', function() {
                handleStructuredFileUpload(this, idx);
            });
        }
    }

    /**
     * Render a repeatable group field (multiple instances of sub-fields).
     */
    function renderRepeatableGroup(fieldsContainer, areaObj, action, areaIndex) {
        const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy';
        let instanceCount = 0;

        const instancesContainer = document.createElement('div');
        instancesContainer.className = 'sf-instances space-y-3';
        fieldsContainer.appendChild(instancesContainer);

        function renumberInstances() {
            const instances = instancesContainer.querySelectorAll('.sf-group-instance');
            instances.forEach((inst, i) => {
                const label = inst.querySelector('.sf-instance-number');
                if (label) label.textContent = '#' + (i + 1);
                inst.dataset.instance = i + 1;
                // Show remove button for all except the first
                const removeBtn = inst.querySelector('.remove-instance');
                if (removeBtn) removeBtn.style.display = i === 0 ? 'none' : '';
            });
        }

        function addInstance() {
            instanceCount++;
            const instance = document.createElement('div');
            instance.className = 'sf-group-instance border-2 border-gray-200 rounded-lg p-4 space-y-3';
            instance.dataset.instance = instanceCount;

            let html = `<div class="flex items-center justify-between mb-2">
                <span class="sf-instance-number text-xs font-medium text-gray-500">#${instanceCount}</span>
                <button type="button" class="remove-instance text-red-500 hover:text-red-700 text-xs" style="${instanceCount === 1 ? 'display:none' : ''}">Remove</button>
            </div>`;

            if (action === 'change') {
                html += `<div class="mb-3 p-3 bg-red-50 border-2 border-red-200 rounded-lg">`;
                html += `<label class="block text-xs font-medium text-red-700 mb-1">What's currently on the page?</label>`;
                html += `<textarea class="sf-current ${inputClass} bg-white" rows="2" placeholder="Describe what's currently on the page..."></textarea>`;
                html += `</div>`;
                html += `<div class="p-3 bg-green-50 border-2 border-green-200 rounded-lg space-y-3">`;
                html += `<label class="block text-xs font-medium text-green-700 mb-1">What should it be?</label>`;
                (areaObj.sub_fields || []).forEach(sf => {
                    html += `<div>`;
                    html += `<label class="block text-xs font-medium text-gray-600 mb-1">${esc(sf.name)}</label>`;
                    if (sf.type === 'textarea') {
                        html += `<textarea class="sf-group-input ${inputClass} bg-white" data-sf-name="${esc(sf.name)}" rows="3" placeholder="${esc(sf.placeholder || '')}"></textarea>`;
                    } else {
                        html += `<input type="text" class="sf-group-input ${inputClass} bg-white" data-sf-name="${esc(sf.name)}" placeholder="${esc(sf.placeholder || '')}">`;
                    }
                    html += `</div>`;
                });
                html += `</div>`;
            } else {
                // Add action
                (areaObj.sub_fields || []).forEach(sf => {
                    html += `<div>`;
                    html += `<label class="block text-xs font-medium text-gray-600 mb-1">${esc(sf.name)}</label>`;
                    if (sf.type === 'textarea') {
                        html += `<textarea class="sf-group-input ${inputClass}" data-sf-name="${esc(sf.name)}" rows="3" placeholder="${esc(sf.placeholder || '')}"></textarea>`;
                    } else {
                        html += `<input type="text" class="sf-group-input ${inputClass}" data-sf-name="${esc(sf.name)}" placeholder="${esc(sf.placeholder || '')}">`;
                    }
                    html += `</div>`;
                });
            }

            instance.innerHTML = html;
            instancesContainer.appendChild(instance);

            // Attach remove handler
            const removeBtn = instance.querySelector('.remove-instance');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    instance.remove();
                    renumberInstances();
                    checkStepValid();
                });
            }

            // Attach validation listeners
            instance.querySelectorAll('.sf-group-input').forEach(gInput => {
                gInput.addEventListener('input', checkStepValid);
            });
            instance.querySelectorAll('.sf-current').forEach(el => {
                el.addEventListener('input', checkStepValid);
            });

            renumberInstances();
            checkStepValid();
        }

        addInstance(); // First instance (always present)

        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'mt-3 inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors';
        addBtn.innerHTML = `<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Add another ${esc(areaObj.name)}`;
        addBtn.addEventListener('click', addInstance);
        fieldsContainer.appendChild(addBtn);
    }

    /**
     * Render the typed input field HTML for a content area (non-group types).
     */
    function renderTypedField(areaObj, inputClass, isChange) {
        let html = '';
        const bgClass = isChange ? ' bg-white' : '';
        switch (areaObj.type) {
            case 'text':
                html += `<input type="text" class="sf-input ${inputClass}${bgClass}" placeholder="${esc(areaObj.placeholder || '')}">`;
                break;
            case 'textarea':
            case 'richtext':
                const rows = areaObj.type === 'richtext' ? 6 : 3;
                html += `<textarea class="sf-input ${inputClass}${bgClass}" rows="${rows}" placeholder="${esc(areaObj.placeholder || '')}"></textarea>`;
                break;
            case 'select':
                html += `<select class="sf-input ${inputClass}${bgClass}"><option value="">Select...</option>`;
                (areaObj.options || []).forEach(opt => { html += `<option value="${esc(opt)}">${esc(opt)}</option>`; });
                html += `</select>`;
                break;
            case 'checkbox':
                html += `<label class="flex items-center space-x-2 cursor-pointer mt-1"><input type="checkbox" class="sf-input sf-checkbox h-4 w-4 text-hcrg-burgundy border-gray-300 rounded"><span class="text-sm text-gray-700">Yes</span></label>`;
                break;
            case 'date':
                html += `<input type="date" class="sf-input ${inputClass}${bgClass}">`;
                break;
            case 'file':
                html += `<input type="file" class="sf-file-input text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-hcrg-burgundy/10 file:text-hcrg-burgundy hover:file:bg-hcrg-burgundy/20 cursor-pointer" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.pptx">`;
                html += `<div class="sf-file-list mt-3 space-y-3"></div>`;
                break;
        }

        // Word counter for text/textarea/richtext with word_limit
        if (areaObj.word_limit && ['text', 'textarea', 'richtext'].includes(areaObj.type)) {
            html += `<div class="sf-word-counter text-xs text-gray-400 mt-1" data-limit="${areaObj.word_limit}">0 / ${areaObj.word_limit} words</div>`;
        }

        // Reading age indicator for textarea/richtext with reading_age enabled
        if (areaObj.reading_age && ['textarea', 'richtext'].includes(areaObj.type)) {
            html += `<div class="sf-reading-age text-xs mt-1 text-right">`;
            html += `<span class="sf-reading-age-badge text-gray-400">Reading age: not enough text</span>`;
            html += `<div class="mt-0.5"><a href="https://readability.ncldata.dev/" target="_blank" rel="noopener noreferrer" class="text-hcrg-burgundy hover:underline inline-flex items-center gap-1">NHS Readability Tool <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg></a></div>`;
            html += `</div>`;
        }

        return html;
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
                    data.title = '';
                    data.description = '';
                    structuredUploadedFiles[areaIndex].push(data);
                    const fileEl = document.createElement('div');
                    fileEl.className = 'bg-gray-50 rounded-lg p-3 border border-gray-200';
                    fileEl.dataset.filename = data.filename;
                    fileEl.innerHTML = `<div class="flex items-center justify-between mb-2">` +
                        `<span class="text-sm text-gray-700 truncate mr-2 font-medium">${esc(data.original_name)}</span>` +
                        `<button type="button" class="text-red-500 hover:text-red-700 text-xs font-medium flex-shrink-0">Remove</button>` +
                        `</div>` +
                        `<div class="space-y-2">` +
                        `<input type="text" placeholder="Document title (required)" class="sf-file-title w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#B52159] focus:border-[#B52159]">` +
                        `<textarea placeholder="Description (required)" rows="2" class="sf-file-desc w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#B52159] focus:border-[#B52159]"></textarea>` +
                        `</div>`;

                    fileEl.querySelector('.sf-file-title').addEventListener('input', function() {
                        const f = structuredUploadedFiles[areaIndex].find(f => f.filename === data.filename);
                        if (f) f.title = this.value;
                        checkStepValid();
                    });
                    fileEl.querySelector('.sf-file-desc').addEventListener('input', function() {
                        const f = structuredUploadedFiles[areaIndex].find(f => f.filename === data.filename);
                        if (f) f.description = this.value;
                        checkStepValid();
                    });
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
            subtitle.textContent = 'Select the content areas you\'d like to change.';
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

        document.querySelectorAll('#areaAccordion .structured-field').forEach(section => {
            const areaName = section.dataset.areaName;
            const areaType = section.dataset.areaType;
            const action = section.dataset.actionType;
            const idx = parseInt(section.dataset.areaIndex);
            let description = '';
            let currentContent = null;
            let files = [];

            if (!action) return; // no action selected for this area

            if (action === 'delete') {
                const input = section.querySelector('.area-form-fields .sf-input');
                description = input ? input.value : '';
                const reason = section.querySelector('.area-form-fields .sf-delete-reason');

                if (description) {
                    items.push({
                        action_type: 'delete',
                        content_area: areaName,
                        description: description,
                        current_content: reason ? reason.value || null : null,
                        files: [],
                    });
                }
                return;
            }

            // Add or Change flow
            if (areaType === 'group') {
                // Check for repeatable group instances
                const instances = section.querySelectorAll('.area-form-fields .sf-group-instance');
                if (instances.length > 0) {
                    // Repeatable group: one item per instance
                    const totalInstances = instances.length;
                    instances.forEach((inst, instIdx) => {
                        const groupInputs = inst.querySelectorAll('.sf-group-input');
                        const parts = [];
                        groupInputs.forEach(gi => {
                            const sfName = gi.dataset.sfName;
                            const sfVal = gi.value.trim();
                            if (sfVal) {
                                parts.push('**' + sfName + ':** ' + sfVal);
                            }
                        });
                        const instDescription = parts.join('\n');
                        if (!instDescription) return; // skip empty instances

                        let instCurrentContent = null;
                        if (action === 'change') {
                            const currentEl = inst.querySelector('.sf-current');
                            instCurrentContent = currentEl ? currentEl.value || null : null;
                        }

                        const instanceLabel = totalInstances > 1 ? areaName + ' #' + (instIdx + 1) : areaName;

                        items.push({
                            action_type: action,
                            content_area: instanceLabel,
                            description: instDescription,
                            current_content: instCurrentContent,
                            files: [],
                        });
                    });
                } else {
                    // Non-repeatable group: single item
                    const groupInputs = section.querySelectorAll('.area-form-fields .sf-group-input');
                    const parts = [];
                    groupInputs.forEach(gi => {
                        const sfName = gi.dataset.sfName;
                        const sfVal = gi.value.trim();
                        if (sfVal) {
                            parts.push('**' + sfName + ':** ' + sfVal);
                        }
                    });
                    description = parts.join('\n');

                    if (action === 'change') {
                        const currentEl = section.querySelector('.area-form-fields .sf-current');
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
                }
            } else if (areaType === 'file') {
                const uploaded = structuredUploadedFiles[idx] || [];
                description = uploaded.map(f => f.title || f.original_name).join(', ');
                files = uploaded.map(f => ({
                    filename: f.filename,
                    original_name: f.original_name,
                    title: f.title || '',
                    description: f.description || '',
                    mime_type: f.mime_type,
                    file_size: f.file_size,
                }));

                if (uploaded.length > 0) {
                    items.push({
                        action_type: action,
                        content_area: areaName,
                        description: description,
                        current_content: currentContent,
                        files: files,
                    });
                }
            } else if (areaType === 'checkbox') {
                const cb = section.querySelector('.area-form-fields .sf-checkbox');
                description = cb && cb.checked ? 'Yes' : 'No';

                items.push({
                    action_type: action,
                    content_area: areaName,
                    description: description,
                    current_content: currentContent,
                    files: [],
                });
            } else {
                const input = section.querySelector('.area-form-fields .sf-input');
                description = input ? input.value : '';

                if (action === 'change') {
                    const currentEl = section.querySelector('.area-form-fields .sf-current');
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
    nextBtn.addEventListener('click', () => {
        if (!validateStep(currentStep) || currentStep >= totalSteps) return;
        // Reading age warning on leaving step 3
        if (currentStep === 3 && !readingAgeBypass) {
            const offending = checkReadingAgeFields();
            if (offending.length > 0) {
                const list = document.getElementById('readingAgeWarningList');
                list.innerHTML = offending.map(f => `<li>${esc(f.name)} (reading age: ${f.age})</li>`).join('');
                document.getElementById('navButtonGroup').classList.add('hidden');
                document.getElementById('readingAgeWarning').classList.remove('hidden');
                return;
            }
        }
        readingAgeBypass = false;
        currentStep++;
        updateUI();
        saveState();
    });
    submitBtn.addEventListener('click', submitForm);
    document.getElementById('addItemBtn').addEventListener('click', addLineItem);

    // Reading age: "Continue anyway" bypass
    const raSubmitAnyway = document.getElementById('readingAgeSubmitAnyway');
    if (raSubmitAnyway) {
        raSubmitAnyway.addEventListener('click', function() {
            readingAgeBypass = true;
            document.getElementById('readingAgeWarning').classList.add('hidden');
            document.getElementById('navButtonGroup').classList.remove('hidden');
            currentStep++;
            updateUI();
            saveState();
        });
    }

    // Self-service access request handler
    document.getElementById('ssSubmitBtn').addEventListener('click', submitSelfServiceRequest);

    async function submitSelfServiceRequest() {
        const ssBtn = document.getElementById('ssSubmitBtn');
        const ssError = document.getElementById('ssError');
        const ssSuccess = document.getElementById('ssSuccess');
        ssBtn.disabled = true;
        ssBtn.textContent = 'Submitting...';
        ssError.classList.add('hidden');
        ssSuccess.classList.add('hidden');

        const isNew = document.getElementById('isNewPage').checked;
        const pageUrl = isNew ? 'new-page' : (selectedPage ? selectedPage.url : '');
        const pageTitle = isNew ? document.getElementById('newPageTitle').value : (selectedPage ? selectedPage.title : '');
        const cptSlug = isNew ? document.getElementById('newPageCpt').value : (selectedPage ? selectedPage.cpt_slug : '');

        const payload = {
            site_id: parseInt(document.getElementById('siteSelect').value),
            page_url: pageUrl || 'self-service-access-request',
            page_title: pageTitle || null,
            cpt_slug: cptSlug,
            is_new_page: false,
            requester_name: document.getElementById('ssName').value.trim(),
            requester_email: document.getElementById('ssEmail').value.trim(),
            requester_phone: null,
            requester_role: null,
            priority: 'normal',
            check_answers: [],
            deadline_date: null,
            deadline_reason: null,
            items: [{
                action_type: 'access_request',
                content_area: 'Access Request',
                description: document.getElementById('ssReason').value.trim(),
                files: [],
            }],
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
                ssError.textContent = msgs;
                ssError.classList.remove('hidden');
            } else {
                throw new Error(data.message || 'Submission failed.');
            }
        } catch (err) {
            ssError.textContent = err.message;
            ssError.classList.remove('hidden');
        } finally {
            ssBtn.disabled = false;
            ssBtn.textContent = 'Request Access';
        }
    }

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
        updateBlockedState();
    });

    document.getElementById('newPageCpt').addEventListener('change', function() {
        refreshAllContentAreaFields();
        updateBlockedState();
    });

    document.getElementById('newPageTitle').addEventListener('input', checkStepValid);
    document.getElementById('pageSearch').addEventListener('input', filterPages);

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

        // Reset reading age bypass when navigating between steps
        readingAgeBypass = false;
        const raWarning = document.getElementById('readingAgeWarning');
        if (raWarning) raWarning.classList.add('hidden');
        const navBtnGroup = document.getElementById('navButtonGroup');
        if (navBtnGroup) navBtnGroup.classList.remove('hidden');

        checkStepValid();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(step) {
        switch(step) {
            case 1:
                return document.getElementById('siteSelect').value !== '' && !siteLoadError;
            case 2:
                if (isCurrentCptBlocked() || isCurrentCptSelfService()) return false;
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
        // At least one area must be checked
        const checkedBoxes = document.querySelectorAll('#areaAccordion .area-checkbox:checked');
        if (checkedBoxes.length === 0) return false;

        // Each checked area must have a card with an action selected
        const fields = document.querySelectorAll('#areaAccordion .structured-field');
        if (fields.length === 0) return false;

        for (const field of fields) {
            const action = field.dataset.actionType;
            if (!action) return false; // no action selected

            const isRequired = field.dataset.areaRequired === '1';
            const type = field.dataset.areaType;
            const idx = parseInt(field.dataset.areaIndex);

            if (action === 'delete') {
                // Delete: "what to remove" is required
                const input = field.querySelector('.area-form-fields .sf-input');
                if (!input || !input.value.trim()) return false;
                continue;
            }

            // Add or Change
            if (type === 'group') {
                // Check for repeatable group instances
                const instances = field.querySelectorAll('.area-form-fields .sf-group-instance');
                if (instances.length > 0) {
                    // Repeatable group: at least one instance must have content if required
                    if (isRequired) {
                        let hasAnyInstanceContent = false;
                        instances.forEach(inst => {
                            const groupInputs = inst.querySelectorAll('.sf-group-input');
                            groupInputs.forEach(gi => { if (gi.value.trim()) hasAnyInstanceContent = true; });
                        });
                        if (!hasAnyInstanceContent) return false;
                    }
                } else {
                    // Non-repeatable group
                    if (isRequired) {
                        const groupInputs = field.querySelectorAll('.area-form-fields .sf-group-input');
                        let hasAnyValue = false;
                        groupInputs.forEach(gi => { if (gi.value.trim()) hasAnyValue = true; });
                        if (!hasAnyValue) return false;
                    }
                }
                continue;
            }

            if (!isRequired) {
                const wordLimit = field.dataset.wordLimit ? parseInt(field.dataset.wordLimit) : null;
                if (wordLimit) {
                    const input = field.querySelector('.area-form-fields .sf-input');
                    if (input && input.value.trim() && countWords(input.value) > wordLimit) return false;
                }
                continue;
            }

            if (type === 'file') {
                if (!structuredUploadedFiles[idx] || structuredUploadedFiles[idx].length === 0) return false;
                if (structuredUploadedFiles[idx].some(f => !f.title || !f.title.trim() || !f.description || !f.description.trim())) return false;
            } else if (type === 'checkbox') {
                continue;
            } else {
                const input = field.querySelector('.area-form-fields .sf-input');
                if (!input || !input.value.trim()) return false;

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
                    siteLoadError = refreshData.message || 'Could not load site data. Please contact the marketing team.';
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
                siteLoadError = 'No pages found for this site. The sitemap may not be configured correctly. Please contact the marketing team.';
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
            siteLoadError = 'Something went wrong loading site data. Please try again or contact the marketing team.';
            showSiteError(siteLoadError);
        }

        await minDelay;
        overlay.classList.add('hidden');
        checkStepValid();

        // Auto-advance to step 2 after successful site load
        if (!silent && !siteLoadError && currentStep === 1) {
            currentStep = 2;
            updateUI();
            saveState();
        }
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
        updateBlockedState();
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
                updateBlockedState();
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
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="1">Edit</button>
                </div>
                <p class="text-sm text-gray-900">${esc(siteName)}</p>
                <p class="text-sm text-gray-600">${isNew ? 'New ' + esc(document.getElementById('newPageCpt').selectedOptions[0].text.toLowerCase()) + ': ' + esc(document.getElementById('newPageTitle').value) : (selectedPage ? esc(selectedPage.title) + ' — ' + esc(selectedPage.url) : '')}</p>
            </div>`;

        if (isStructured) {
            // Structured form review
            const structuredItems = getStructuredItems();
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Content Changes (${structuredItems.length})</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="3">Edit</button>
                </div>`;

            structuredItems.forEach(item => {
                const fileCount = (item.files || []).length;
                const actionBadge = item.action_type === 'add'
                    ? 'bg-green-100 text-green-800'
                    : item.action_type === 'delete'
                        ? 'bg-red-100 text-red-800'
                        : 'bg-[#B52159]/10 text-[#B52159]';
                html += `<div class="pl-3 border-l-2 border-gray-200">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${actionBadge}">${esc(item.action_type)}</span>
                    <span class="text-xs font-medium text-gray-500 ml-1">${esc(item.content_area)}</span>
                    ${item.current_content ? `<p class="text-xs text-gray-400 mt-1">Current: ${esc((item.current_content || '').substring(0, 100))}${(item.current_content || '').length > 100 ? '...' : ''}</p>` : ''}
                    <p class="text-sm text-gray-700 mt-1">${esc((item.description || '').substring(0, 200))}${(item.description || '').length > 200 ? '...' : ''}</p>
                    ${fileCount > 0 ? `<div class="mt-2 space-y-1">${(item.files || []).map(f => `<div class="text-xs text-gray-500"><span class="font-medium text-gray-600">${esc(f.title || f.original_name)}</span> — ${esc((f.description || '').substring(0, 80))}${(f.description || '').length > 80 ? '...' : ''}</div>`).join('')}</div>` : ''}
                </div>`;
            });
            html += `</div>`;
        } else {
            // Generic line items review
            const items = document.querySelectorAll('.line-item');
            html += `<div class="border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">Changes (${items.length})</h3>
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="3">Edit</button>
                </div>`;

            items.forEach((item, i) => {
                const action = item.querySelector('.action-type:checked')?.value || '';
                const area = item.querySelector('.content-area').value;
                const desc = item.querySelector('.item-description').value;
                const fileCount = (uploadedFiles[item.dataset.itemIndex] || []).length;

                html += `<div class="pl-3 border-l-2 border-gray-200">
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${action === 'add' ? 'bg-green-100 text-green-800' : action === 'delete' ? 'bg-red-100 text-red-800' : 'bg-[#B52159]/10 text-[#B52159]'}">${esc(action)}</span>
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
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="4">Edit</button>
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
                    <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="4">Edit</button>
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
                <button type="button" class="edit-step text-xs text-hcrg-burgundy hover:underline px-2 py-1 rounded hover:bg-hcrg-burgundy/5" data-goto="5">Edit</button>
            </div>
            <p class="text-sm text-gray-900">${esc(document.getElementById('requesterName').value)}</p>
            <p class="text-sm text-gray-600">${esc(document.getElementById('requesterEmail').value)}</p>
            ${document.getElementById('requesterPhone').value ? `<p class="text-sm text-gray-600">${esc(document.getElementById('requesterPhone').value)}</p>` : ''}
            ${document.getElementById('requesterRole').value ? `<p class="text-sm text-gray-600">${esc(document.getElementById('requesterRole').value)}</p>` : ''}
            <p class="text-sm text-gray-600">Priority: <strong>${esc((document.querySelector('input[name="priority"]:checked')?.value || 'normal').charAt(0).toUpperCase() + (document.querySelector('input[name="priority"]:checked')?.value || 'normal').slice(1))}</strong></p>
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
        document.getElementById('submitError').classList.remove('bg-amber-50', 'border-amber-200', 'text-amber-700');

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
            priority: document.querySelector('input[name="priority"]:checked')?.value || 'normal',
            check_answers: checkAnswers,
            deadline_date: document.querySelector('input[name="has_deadline"]:checked')?.value === 'yes' ? document.getElementById('deadlineDate').value : null,
            deadline_reason: document.querySelector('input[name="has_deadline"]:checked')?.value === 'yes' ? stripTags(document.getElementById('deadlineReason').value) : null,
            items: items.map(item => ({
                ...item,
                description: stripTags(item.description),
                current_content: stripTags(item.current_content),
                content_area: stripTags(item.content_area),
                files: (item.files || []).map(f => ({
                    ...f,
                    title: stripTags(f.title),
                    description: stripTags(f.description),
                })),
            })),
        };

        try {
            // Refresh CSRF token in case the session has expired
            let freshToken = csrfToken;
            try {
                const tokenRes = await fetch('{{ route("csrf-token") }}', { headers: { 'Accept': 'application/json' } });
                if (tokenRes.ok) {
                    const tokenData = await tokenRes.json();
                    freshToken = tokenData.token;
                }
            } catch (e) {}

            const res = await fetch('{{ route("submit") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': freshToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (res.status === 429) {
                const retryAfter = res.headers.get('Retry-After');
                const minutes = retryAfter ? Math.ceil(parseInt(retryAfter) / 60) : 1;
                const msg = minutes <= 1
                    ? 'You\'ve made several submissions recently. Please wait about a minute and try again.'
                    : 'You\'ve made several submissions recently. Please wait about ' + minutes + ' minutes and try again.';
                const errEl = document.getElementById('submitError');
                errEl.textContent = msg;
                errEl.classList.remove('hidden');
                errEl.classList.add('bg-amber-50', 'border-amber-200', 'text-amber-700');
                errEl.classList.remove('bg-red-50', 'border-red-200', 'text-red-700');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
                return;
            }

            if (res.status === 419) {
                throw new Error('Your session has expired. Please refresh the page and try again.');
            }

            const responseText = await res.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseErr) {
                const contentType = res.headers.get('content-type') || '';
                const errEl = document.getElementById('submitError');
                errEl.innerHTML = '<p>Something went wrong. This may be caused by your network or firewall. Please try again, or contact your IT team if the problem persists.</p>'
                    + '<p class="mt-2 text-sm">If the problem continues, please take a screenshot of the details below and send it to the marketing team.</p>'
                    + '<details class="mt-2 text-xs"><summary class="cursor-pointer text-gray-500 hover:text-gray-700">Technical details</summary>'
                    + '<pre class="mt-1 p-2 bg-gray-100 rounded overflow-auto max-h-40 text-gray-600">HTTP ' + esc(String(res.status)) + ' — ' + esc(contentType) + '\n\n' + esc(responseText.substring(0, 2000)) + '</pre></details>';
                errEl.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
                return;
            }

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
                priority: document.querySelector('input[name="priority"]:checked')?.value || 'normal',
            };
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function loadState() {
        try {
            const saved = sessionStorage.getItem(STORAGE_KEY);
            if (!saved) return;
            const state = JSON.parse(saved);

            // Site and page selection are NOT restored — user always starts fresh
            // to ensure sitemap data is checked for staleness

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

            if (state.priority) {
                const priorityRadio = document.querySelector(`input[name="priority"][value="${state.priority}"]`);
                if (priorityRadio) priorityRadio.checked = true;
            }
        } catch (e) {}
    }

    // Save on input change
    document.addEventListener('input', () => setTimeout(saveState, 100));
})();
</script>
