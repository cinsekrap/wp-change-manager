{{-- Content lane, step 3: the brief. Replaces "what changes do you need?" for new content. --}}
<div class="card card-body wizard-step hidden" data-step="7" data-panel="brief">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Tell us about the content</h2>
    <p class="text-sm text-gray-500 mb-6">You don't need to write it — that's our job. We need to understand what it's for.</p>

    <div class="space-y-6">

        <div>
            <label for="briefAchieve" class="field-label">What is this trying to achieve? <span class="text-red-500">*</span></label>
            <p class="field-help">What should be different once it's published? If nothing would change, it might not need to exist.</p>
            <textarea id="briefAchieve" rows="3" placeholder="e.g. People referred to us keep ringing to ask what happens at the first appointment..."
                class="field-input"></textarea>
        </div>

        <div>
            <span class="field-label">Who is it for? <span class="text-red-500">*</span></span>
            <p class="field-help">Pick everyone it genuinely has to serve — this usually changes how it's written.</p>
            <div id="briefAudience" class="flex flex-wrap gap-2">
                @foreach (config('content-audiences') as $value => $label)
                    <label class="cursor-pointer">
                        <input type="checkbox" class="sr-only brief-audience" value="{{ $value }}">
                        <span class="audience-chip inline-block text-sm px-4 py-2 rounded-full bg-hcrg-grey-100 text-hcrg-charcoal">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label for="briefKnowOrDo" class="field-label">What do you want them to know, or do? <span class="text-red-500">*</span></label>
            <p class="field-help">The one or two things that matter most. We'll build the content around them.</p>
            <textarea id="briefKnowOrDo" rows="2"
                class="field-input"></textarea>
        </div>

        <div>
            <label for="briefMeasure" class="field-label">How will we know it worked? <span class="text-gray-400">(optional)</span></label>
            <p class="field-help">Even a rough measure helps us write to a purpose — and tells us later whether it landed.</p>
            <input type="text" id="briefMeasure" placeholder="e.g. Fewer &quot;what happens next&quot; calls to the single point of access"
                class="field-input">
        </div>

        <div class="p-4 bg-hcrg-grey-100 rounded-lg">
            <span class="field-label">Does something like this already exist? <span class="text-red-500">*</span></span>
            <p class="text-xs text-gray-500 mb-3">On any of our sites, or as a leaflet or document. We'd rather improve one thing than publish a second version of it.</p>
            <div class="flex flex-wrap gap-5 mb-3">
                @foreach (['yes' => 'Yes, something similar', 'no' => 'No', 'not_sure' => 'Not sure'] as $value => $label)
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="brief_exists" value="{{ $value }}" class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <div id="briefDuplicateMatches" class="hidden mb-3 p-3 bg-white border border-hcrg-grey-200 rounded-lg">
                <p class="text-xs font-semibold text-hcrg-charcoal mb-2">Already suggested — is one of these what you mean?</p>
                <div id="briefDuplicateList" class="space-y-1"></div>
            </div>
            <div id="briefExistsDetailWrap" class="hidden">
                <input type="text" id="briefExistsDetail" placeholder="Where is it? A link or a description is fine."
                    class="field-input bg-white">
            </div>
        </div>

        {{-- One uploader doing both jobs: evidence for "it already exists", and
             anything else worth reading first. The prompt changes with the answer above. --}}
        <div>
            <span class="field-label">Anything we should read first? <span class="text-gray-400">(optional)</span></span>
            <p id="briefUploadHelp" class="field-help">Draft wording, a service spec, or the leaflet you're replacing.</p>

            <div id="briefDropzone" class="border-2 border-dashed border-hcrg-grey-200 rounded-lg p-6 text-center bg-white">
                <p class="text-sm text-gray-500 mb-2">Drop files here, or</p>
                <label class="btn btn-secondary cursor-pointer">
                    Choose a file
                    <input type="file" id="briefFileInput" class="sr-only" multiple
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.pptx,.mp4,.mov,.webm,.avi">
                </label>
                <p class="text-xs text-gray-400 mt-2">Images, documents and video up to 128MB each, 5 files max.</p>
            </div>

            <div id="briefFileList" class="mt-3 space-y-2"></div>
            <p id="briefFileError" class="hidden mt-2 text-xs text-red-600"></p>
        </div>

    </div>
</div>
