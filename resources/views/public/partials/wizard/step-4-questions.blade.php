{{-- Step 4: Check questions --}}
<div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="4">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Before you submit</h2>
    <p class="text-sm text-gray-500 mb-4">Please answer the following questions.</p>

    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <p class="text-sm font-medium text-gray-700 mb-2">Is this needed by a specific date? <span class="text-red-500">*</span></p>
        <div class="flex space-x-4 mb-3">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="has_deadline" value="yes" id="hasDeadlineYes" class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                <span class="text-sm text-gray-700">Yes</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="has_deadline" value="no" id="hasDeadlineNo" class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                <span class="text-sm text-gray-700">No</span>
            </label>
        </div>
        <div id="deadlineFields" class="hidden space-y-3">
            <div>
                <label for="deadlineDate" class="block text-xs font-medium text-gray-500 mb-1">When is this needed by?</label>
                <input type="date" id="deadlineDate" min="{{ now()->addDay()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <p class="mt-1 text-xs text-gray-400">Must be at least 1 day from today.</p>
            </div>
            <div>
                <label for="deadlineReason" class="block text-xs font-medium text-gray-500 mb-1">Why is this date important?</label>
                <input type="text" id="deadlineReason" placeholder="e.g. Service launch, event date, campaign go-live..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            </div>
        </div>
    </div>

    <div id="checkQuestions" class="space-y-6">
        @foreach($questions as $question)
        <div class="question-group" data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? '1' : '0' }}">
            <p class="text-sm font-medium text-gray-700 mb-2">
                {{ $question->question_text }}
                @if($question->is_required) <span class="text-red-500">*</span> @endif
            </p>
            <div class="space-y-2">
                @foreach($question->options as $option)
                @php $optLabel = is_array($option) ? $option['label'] : $option; @endphp
                @php $optPass = is_array($option) ? (!empty($option['pass']) ? '1' : '0') : '0'; @endphp
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="radio" name="check_q_{{ $question->id }}" value="{{ $optLabel }}" data-pass="{{ $optPass }}"
                        class="h-4 w-4 text-hcrg-burgundy border-gray-300">
                    <span class="text-sm text-gray-700">{{ $optLabel }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
