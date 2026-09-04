{{-- Step 2: which lane. The page picker below is unchanged; it just no longer carries
     the "this is a new page" checkbox, which the content lane replaces. --}}
<div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="2" data-panel="page">

    <div class="flex items-center gap-2 mb-5 pb-4 border-b border-gray-100">
        <span class="text-sm text-gray-500">Site</span>
        <span id="step2SiteChip" class="inline-flex items-center gap-2 bg-hcrg-grey-100 px-3 py-1 rounded-full text-sm font-semibold text-hcrg-charcoal"></span>
        <button type="button" id="step2ChangeSite" class="text-sm text-hcrg-burgundy underline">Change</button>
    </div>

    <h2 class="text-xl font-bold text-gray-900 mb-2">What are you working on?</h2>
    <p class="text-sm text-gray-500 mb-4">These ask you different things, so it's worth getting right. You can go back and switch.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <label class="lane-option flex flex-col p-5 border border-gray-300 rounded-lg cursor-pointer" data-lane="change">
            <input type="radio" name="wizard_lane" value="change" class="sr-only">
            <svg class="w-6 h-6 text-hcrg-grey-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4z"/></svg>
            <span class="lane-title text-base font-bold text-gray-900 mb-1">A page that already exists</span>
            <span class="text-sm text-hcrg-grey-400">Wording, images, contact details or links that need updating.</span>
        </label>

        <label class="lane-option flex flex-col p-5 border border-gray-300 rounded-lg cursor-pointer" data-lane="content">
            <input type="radio" name="wizard_lane" value="content" class="sr-only">
            <svg class="w-6 h-6 text-hcrg-grey-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 5v14M5 12h14"/></svg>
            <span class="lane-title text-base font-bold text-gray-900 mb-1">Something new</span>
            <span class="text-sm text-hcrg-grey-400">Content that doesn't exist yet (this might be for one website, or to be used across many).</span>
        </label>
    </div>

    {{-- Change lane: today's picker, unchanged --}}
    <div id="lanePickerWrap" class="hidden">
        <div id="cptTabs" class="flex flex-wrap gap-2 mb-4"></div>

        <div id="pageSearchWrap" class="mb-4">
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
                            <label for="ssName" class="block text-xs font-medium text-gray-700 mb-1">Your name <span class="text-red-500">*</span></label>
                            <input type="text" id="ssName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        </div>
                        <div>
                            <label for="ssEmail" class="block text-xs font-medium text-gray-700 mb-1">Your email <span class="text-red-500">*</span></label>
                            <input type="email" id="ssEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="text-xs font-bold text-gray-900">Who needs access?</h4>
                            <button type="button" id="ssSameAsMe" class="text-xs text-hcrg-burgundy hover:underline">Same as me</button>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">This person will receive the training email and be given access — it can be you or someone else.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="ssAccessName" class="block text-xs font-medium text-gray-700 mb-1">Their name <span class="text-red-500">*</span></label>
                                <input type="text" id="ssAccessName" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            </div>
                            <div>
                                <label for="ssAccessEmail" class="block text-xs font-medium text-gray-700 mb-1">Their email <span class="text-red-500">*</span></label>
                                <input type="email" id="ssAccessEmail" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            </div>
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
    </div>

    {{-- Content lane: what's coming, so a new piece of content reads as a conversation
         with a content designer rather than a form that ends in a page title. --}}
    <div id="laneContentPreview" class="hidden p-5 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm font-bold text-blue-700 mb-3">What happens next</p>
        <ol class="space-y-2 text-sm text-blue-700">
            <li><strong class="font-bold">3. A short brief.</strong> What the content is trying to achieve, who it's for, and what you want them to know or do.</li>
            <li><strong class="font-bold">4. What it is and where it lives.</strong> This site is its home — tell us if it's needed on others too.</li>
            <li><strong class="font-bold">5. Your details</strong>, then review and send. A content designer picks it up from there.</li>
        </ol>
        @if($contentWaitDays)
        <p class="text-sm font-bold text-blue-700 mt-3 pt-3 border-t border-blue-200">
            The wait for new content is currently around {{ $contentWaitDays }} {{ Str::plural('day', $contentWaitDays) }}.
        </p>
        @endif
        <p class="text-sm text-blue-700 mt-3 pt-3 border-t border-blue-200">You won't be asked to choose a content type or write a page title. What it turns out to be — a page, a leaflet, a section of an existing page — is ours to work out from the brief.</p>
    </div>
</div>
