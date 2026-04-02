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
            <div class="site-option px-3 py-2 cursor-pointer hover:bg-hcrg-burgundy/10 text-sm transition-colors" data-value="{{ $site->id }}" data-domain="{{ $site->domain }}" data-label="{{ $site->name }} ({{ $site->domain }})">
                <span class="font-medium text-gray-900">{{ $site->name }}</span>
                <span class="text-gray-400 ml-1">{{ $site->domain }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
