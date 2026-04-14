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

{{-- Reading age warning (shown when advancing past step 3 with high reading age) --}}
<div id="readingAgeWarning" class="hidden mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
    <div class="flex gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1">
            <p class="text-sm font-medium text-amber-800">Reading age is high on the following fields:</p>
            <ul id="readingAgeWarningList" class="mt-1 text-sm text-amber-700 list-disc list-inside"></ul>
            <p class="mt-2 text-xs text-amber-600">Consider simplifying the language so it can be understood by a wider audience. You can use the <a href="https://readability.ncldata.dev/" target="_blank" rel="noopener noreferrer" class="underline font-medium text-amber-700 hover:text-amber-900">NHS Medical Document Readability Tool</a> to work on your text.</p>
            <div class="mt-3 text-right">
                <button type="button" id="readingAgeSubmitAnyway" class="px-4 py-1.5 text-xs font-medium rounded-full border border-amber-400 text-amber-700 hover:bg-amber-100 transition-colors">Continue anyway &rarr;</button>
            </div>
        </div>
    </div>
</div>
