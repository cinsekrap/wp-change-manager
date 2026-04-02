@if($changeRequest->status === 'requested')
    @php
        $allPassed = collect($changeRequest->check_answers ?? [])->every(fn($a) => !empty($a['pass']));
        $siteRequiresApproval = $changeRequest->site->requires_approval ?? false;
    @endphp
    @if(!$allPassed)
        <div class="mb-6 p-4 bg-amber-50 border-2 border-amber-200 rounded-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.58 14.86A1 1 0 002.56 20h18.88a1 1 0 00.85-1.28L13.71 3.86a1 1 0 00-1.42 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Pre-submission checks did not all pass</p>
                <p class="text-sm text-amber-700 mt-1">This request has not been automatically sent for approval. Review the pre-submission checks below and decide whether to send for approval or decline.</p>
            </div>
        </div>
    @elseif($siteRequiresApproval)
        <div class="mb-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-xl flex items-start space-x-3">
            <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-blue-800">This site requires manual approval for all requests</p>
                <p class="text-sm text-blue-700 mt-1">Review the request and send for approval when ready, or decline if not appropriate.</p>
            </div>
        </div>
    @endif
@endif
