{{-- Step 5: Your details --}}
<div class="wizard-step bg-white rounded-lg shadow p-6 hidden" data-step="5">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Your details</h2>
    <p class="text-sm text-gray-500 mb-4">So we know who's requesting this change and how to contact you.</p>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" id="requesterName" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" id="requesterEmail" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-gray-400">(optional)</span></label>
            <input type="tel" id="requesterPhone"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Job title / role <span class="text-gray-400">(optional)</span></label>
            <input type="text" id="requesterRole"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        {{-- Priority selector --}}
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="p-4 bg-gray-50 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-1">How urgent is this?</label>
                <p class="text-xs text-gray-400 mb-3">This helps us prioritise your request. If you're unsure, leave it as Normal.</p>
                <div class="space-y-2.5">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="priority" value="low" class="priority-radio h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm text-gray-700"><strong>Low</strong> — No rush</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="priority" value="normal" class="priority-radio h-4 w-4 text-hcrg-burgundy border-gray-300" checked>
                        <span class="text-sm text-gray-700"><strong>Normal</strong> — Standard turnaround</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="priority" value="high" class="priority-radio h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm text-gray-700"><strong>High</strong> — Needed soon</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" name="priority" value="urgent" class="priority-radio h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm text-gray-700"><strong>Urgent</strong> — Critical &mdash; blocking other work</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
