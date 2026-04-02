{{-- Full-screen loading overlay --}}
<div id="loadingOverlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center">
        <svg class="animate-spin h-12 w-12 text-hcrg-burgundy mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Updating site data</h3>
        <p class="text-sm text-gray-500">This may take a moment&hellip;</p>
    </div>
</div>
