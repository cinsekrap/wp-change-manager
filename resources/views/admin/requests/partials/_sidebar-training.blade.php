{{-- Training panel (access requests only) --}}
<div class="bg-white rounded-lg shadow p-4">
    <h2 class="text-sm font-semibold text-gray-900 mb-3">Training</h2>

    @php $trainingUrl = $changeRequest->cptType?->training_url; @endphp

    @if(!$trainingUrl)
        <div class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
            No training video URL is configured for this content type, so the training email cannot be sent.
            @if($changeRequest->cptType && auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.cpts.edit', $changeRequest->cptType) }}" class="font-semibold underline hover:no-underline">Configure it here.</a>
            @endif
        </div>
    @else
        <div class="mb-3 text-sm">
            <span class="text-gray-500 text-xs">Training video</span>
            <a href="{{ $trainingUrl }}" target="_blank" rel="noopener" class="block text-hcrg-burgundy hover:underline truncate">{{ $trainingUrl }}</a>
        </div>
    @endif

    <div class="space-y-2 text-sm mb-3">
        <div class="flex items-center justify-between">
            <span class="text-gray-500">Training email</span>
            @if($changeRequest->training_sent_at)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800" title="{{ $changeRequest->training_sent_at->format('d M Y H:i') }}">
                    Sent {{ $changeRequest->training_sent_at->format('d M Y') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not sent</span>
            @endif
        </div>
        <div class="flex items-center justify-between">
            <span class="text-gray-500">Competence confirmed</span>
            @if($changeRequest->training_confirmed_at)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800" title="{{ $changeRequest->training_confirmed_at->format('d M Y H:i') }}">
                    {{ $changeRequest->training_confirmed_at->format('d M Y') }}
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Pending</span>
            @endif
        </div>
    </div>

    @if(!$changeRequest->training_confirmed_at && in_array($changeRequest->status, ['approved', 'training']))
        <form method="POST" action="{{ route('admin.requests.training.send', $changeRequest) }}">
            @csrf
            <button type="submit" class="w-full bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                {{ $changeRequest->training_sent_at ? 'Resend training email' : 'Send training email' }}
            </button>
        </form>
    @elseif($changeRequest->training_confirmed_at)
        <p class="text-xs text-gray-500">{{ $changeRequest->access_recipient_name }} has confirmed they watched the training and feel competent. Grant access in WordPress, then mark the request as done.</p>
    @endif
</div>
