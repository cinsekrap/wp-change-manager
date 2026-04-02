{{-- Line item template --}}
<template id="lineItemTemplate">
    <div class="line-item border-2 border-gray-200 rounded-lg p-4" data-item-index="">
        <div class="flex justify-between items-start mb-3">
            <span class="text-sm font-medium text-gray-500 item-number">Change #1</span>
            <button type="button" class="remove-item text-red-500 hover:text-red-700 hover:bg-red-50 text-sm px-2 py-1 rounded transition-colors">&times; Remove</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Action type <span class="text-red-500">*</span></label>
                <div class="flex space-x-4">
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="add" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Add</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="change" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Change</span>
                    </label>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="radio" name="" value="delete" class="action-type h-4 w-4 text-hcrg-burgundy border-gray-300">
                        <span class="text-sm">Delete</span>
                    </label>
                </div>
            </div>
            <div class="content-area-container">
                <label class="block text-xs font-medium text-gray-500 mb-1">Content area <span class="text-gray-400">(optional)</span></label>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Description <span class="text-red-500">*</span></label>
                <textarea class="item-description w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                    rows="3" placeholder="Describe the change you need in detail..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Attachments <span class="text-gray-400">(max 5 files, 10MB each)</span></label>
                <input type="file" class="file-input text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-hcrg-burgundy/10 file:text-hcrg-burgundy hover:file:bg-hcrg-burgundy/20 cursor-pointer" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.pptx">
                <div class="file-list mt-2 space-y-1"></div>
            </div>
        </div>
    </div>
</template>
