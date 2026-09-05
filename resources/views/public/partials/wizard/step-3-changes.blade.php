{{-- Step 3: What do you want to do? --}}
<div class="card card-body wizard-step hidden" data-step="3" data-panel="changes">
    <h2 class="text-xl font-bold text-gray-900 mb-2">What changes do you need?</h2>
    <p id="step3Subtitle" class="text-sm text-gray-500 mb-4">Describe each change you need. You can add multiple changes.</p>

    {{-- Generic line items (used when no rich content areas) --}}
    <div id="genericFlow">
        <div id="lineItems" class="space-y-4"></div>

        <button type="button" id="addItemBtn" class="btn btn-secondary mt-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add another change
        </button>
    </div>

    {{-- Structured form (used when CPT has rich content areas) --}}
    <div id="structuredFlow" class="hidden">
        <p class="text-sm text-gray-500 mb-4">Tick the sections you need to change — each one will open so you can describe what's needed.</p>

        <!-- Accordion: each area is a row with inline-expanding form -->
        <div id="areaAccordion" class="space-y-2"></div>
    </div>

</div>
