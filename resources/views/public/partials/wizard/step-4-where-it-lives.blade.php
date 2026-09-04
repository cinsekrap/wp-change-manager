{{-- Content lane, step 4: what kind of content it is, and where it needs to appear. --}}
<div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="8" data-panel="where">
    <h2 class="text-xl font-bold text-gray-900 mb-2">What it is, and where it lives</h2>
    <p class="text-sm text-gray-500 mb-6">Two things left: the job this content does, and where it needs to appear.</p>

    <div class="pb-6 mb-6 border-b border-gray-100">
        <span class="block text-sm font-medium text-gray-700 mb-1">What kind of content is this? <span class="text-red-500">*</span></span>
        <p class="text-xs text-gray-500 mb-3">Not what it looks like — what job it does for the person reading it.</p>

        <div class="space-y-2">
            @foreach ($contentTypes as $key => $type)
                <label class="content-type-option flex items-start gap-3 p-4 border border-gray-300 rounded-lg cursor-pointer">
                    <input type="radio" name="content_type" value="{{ $key }}" class="mt-1 h-4 w-4 text-hcrg-burgundy border-gray-300">
                    <span class="flex-1">
                        <span class="block text-sm font-semibold text-gray-900">{{ $type['label'] }}</span>
                        <span class="block text-xs text-hcrg-grey-400 mt-1">{{ $type['help'] }}</span>
                    </span>
                    <span class="shrink-0 self-center text-xs text-hcrg-grey-400 border border-hcrg-grey-200 rounded-full px-3 py-1 whitespace-nowrap">
                        {{ isset($type['tag_article']) ? 'This is' : 'This is a' }}
                        <strong class="font-bold text-hcrg-charcoal">{{ $type['tag'] }}</strong>
                    </span>
                </label>
            @endforeach
        </div>

        <p class="text-xs text-gray-500 mt-3">Pick the main one. If it honestly does two of these jobs, that's usually a sign it should be two pieces of content — your content designer will talk to you about it rather than sending you back here.</p>
    </div>

    <div class="mb-6">
        <span class="block text-sm font-medium text-gray-700 mb-1">Its main home <span class="text-red-500">*</span></span>
        <p class="text-xs text-gray-500 mb-2">Where it gets written, reviewed and kept up to date. You picked this at step 1 — change it if this isn't really its home.</p>
        <div id="contentHomeSite" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white"></div>
    </div>

    <div class="mb-6">
        <span class="block text-sm font-medium text-gray-700 mb-1">Might also work on <span class="text-gray-400">(optional)</span></span>
        <p class="text-xs text-gray-500 mb-3">Could this content work on another website too?</p>
        <div id="additionalSites" class="space-y-2"></div>
    </div>

    <div id="sharedContentNotice" class="hidden p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm font-semibold text-blue-700 mb-1">This will be shared content</p>
        <p class="text-sm text-blue-700">Written once and published to <span id="sharedSiteCount">2</span> sites. Our content designer will check whether any site needs its own version — local contact details or service names often differ even when the rest is identical.</p>
    </div>
</div>
