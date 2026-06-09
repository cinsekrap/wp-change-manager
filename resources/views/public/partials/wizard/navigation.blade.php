{{-- Navigation buttons --}}
<div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 mt-6">
    <button type="button" id="prevBtn" class="hidden px-6 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-50">
        &larr; Back
    </button>
    <div class="sm:ml-auto" id="navButtonGroup">
        <button type="button" id="nextBtn" class="w-full sm:w-auto px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Next &rarr;
        </button>
        <button type="button" id="submitBtn" class="hidden w-full sm:w-auto px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B]">
            Submit Request
        </button>
    </div>
</div>

{{-- Governance acknowledgement (shown when advancing past step 2 / page selection) --}}
<div id="governanceGate" class="hidden mt-4 p-4 bg-white border border-gray-200 border-l-4 border-hcrg-burgundy rounded-lg">
    <p class="text-sm font-semibold text-gray-900">Before you continue</p>
    <p class="mt-1 text-sm text-gray-600">Website changes are a managed process. Every request is reviewed and approved before anything goes live, so it's important your request is accurate.</p>

    {{-- Existing-page acknowledgements --}}
    <div id="govExistingChecks" class="hidden mt-3 space-y-2">
        <label class="flex items-start space-x-2 cursor-pointer">
            <input type="checkbox" class="gov-check mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">I've selected the correct page. If the page I need isn't listed, I'll contact the marketing team rather than picking another one.</span>
        </label>
        <label class="flex items-start space-x-2 cursor-pointer">
            <input type="checkbox" class="gov-check mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">I'm requesting changes to this existing page &mdash; not asking for a new page to be created. <span class="text-gray-500">(Need a new page? Go back and tick &ldquo;This is a new page&rdquo;.)</span></span>
        </label>
    </div>

    {{-- New-page acknowledgement --}}
    <div id="govNewChecks" class="hidden mt-3 space-y-2">
        <label class="flex items-start space-x-2 cursor-pointer">
            <input type="checkbox" class="gov-check mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">I've checked and this page doesn't already exist on the site.</span>
        </label>
    </div>

    <div class="mt-4 flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
        <button type="button" id="govBack" class="px-6 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-50">
            &larr; Back to page
        </button>
        <button type="button" id="govContinue" class="px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Continue &rarr;
        </button>
    </div>
</div>

{{-- Reading age warning (shown when advancing past step 3 with high reading age) --}}
<div id="readingAgeWarning" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1">
            {{-- High absolute reading age --}}
            <div id="readingAgeHighSection" class="hidden">
                <p class="text-sm font-medium text-amber-800">Reading age is high on the following fields:</p>
                <ul id="readingAgeWarningList" class="mt-1 text-sm text-amber-700 list-disc list-inside"></ul>
                <p class="mt-2 text-xs text-amber-600">Consider simplifying the language so it can be understood by a wider audience. You can use the <a href="https://readability.ncldata.dev/" target="_blank" rel="noopener noreferrer" class="underline font-medium text-amber-700 hover:text-amber-900">NHS Medical Document Readability Tool</a> to work on your text.</p>
            </div>
            {{-- Reading age increased by the change --}}
            <div id="readingAgeIncreaseSection" class="hidden">
                <p class="text-sm font-medium text-amber-800">Your changes increase the reading age:</p>
                <ul id="readingAgeIncreaseList" class="mt-1 text-sm text-amber-700 list-disc list-inside"></ul>
                <p class="mt-2 text-xs text-amber-600">Are you sure these changes are needed? Rewording to sound more formal often raises the reading age without changing the meaning &mdash; which makes the content harder for people to understand.</p>
            </div>
            <div class="mt-3 text-right">
                <button type="button" id="readingAgeSubmitAnyway" class="px-4 py-1.5 text-xs font-medium rounded-full border border-amber-400 text-amber-700 hover:bg-amber-100 transition-colors">Continue anyway &rarr;</button>
            </div>
        </div>
    </div>
</div>
