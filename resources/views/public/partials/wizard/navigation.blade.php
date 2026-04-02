{{-- Navigation buttons --}}
<div class="flex flex-col-reverse sm:flex-row sm:justify-between gap-3 mt-6">
    <button type="button" id="prevBtn" class="hidden px-6 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 hover:bg-gray-50">
        &larr; Back
    </button>
    <div class="sm:ml-auto">
        <button type="button" id="nextBtn" class="w-full sm:w-auto px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            Next &rarr;
        </button>
        <button type="button" id="submitBtn" class="hidden w-full sm:w-auto px-6 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B]">
            Submit Request
        </button>
    </div>
</div>
