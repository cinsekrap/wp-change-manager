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

    <div id="blockedCptMessage" class="hidden mt-4 p-5 bg-amber-50 border-2 border-amber-200 rounded-xl">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="text-sm font-semibold text-amber-800 mb-2">Requests are not available for this content type</p>
                <div id="blockedCptText" class="text-sm text-amber-700 prose prose-sm max-w-none"></div>
            </div>
        </div>
    </div>

    <div id="selfServiceCptMessage" class="hidden mt-4">
        <div class="p-5 bg-blue-50 border-2 border-blue-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <div id="selfServiceCptText" class="text-sm text-blue-700 prose prose-sm max-w-none mb-2"></div>
                    <p class="text-sm text-blue-700">If you need access, complete the form below.</p>
                </div>
            </div>
        </div>

        <div class="mt-4 p-5 bg-white border-2 border-blue-200 rounded-xl">
            <h3 class="text-sm font-bold text-gray-900 mb-1">Request access</h3>
            <p class="text-xs text-gray-500 mb-4">If you need access to manage this content, tell us why and we'll review your request.</p>

            <div class="space-y-3">
                <div>
                    <label for="ssReason" class="block text-xs font-medium text-gray-700 mb-1">Reason for needing access <span class="text-red-500">*</span></label>
                    <textarea id="ssReason" rows="3" placeholder="Describe why you need access to manage this content..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="ssName" class="block text-xs font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                        <input type="text" id="ssName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    </div>
                    <div>
                        <label for="ssEmail" class="block text-xs font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="ssEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    </div>
                </div>
                <div id="ssError" class="hidden p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>
                <div id="ssSuccess" class="hidden p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg"></div>
                <button type="button" id="ssSubmitBtn" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B] disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Request Access
                </button>
            </div>
        </div>
    </div>

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
