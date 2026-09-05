@extends('layouts.public')
@section('title', 'Funding decision')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h1 class="text-xl font-bold text-gray-900 mb-1">Funding decision needed</h1>
    <p class="text-sm text-gray-500">
        {{ $round->items->count() }} {{ \Illuminate\Support\Str::plural('piece', $round->items->count()) }} of content,
        {{ rtrim(rtrim(number_format((float) $round->total_hours, 1), '0'), '.') }} hours in total.
        Nothing is written until this is agreed.
    </p>
</div>

<div class="space-y-4 mb-6">
    @foreach($round->items as $item)
        @php
            $cr = $item->changeRequest;
            $brief = $cr?->content_brief ?? [];
            $audiences = collect($brief['audience'] ?? [])
                ->map(fn ($a) => config("content-audiences.{$a}", $a))
                ->join(', ');
        @endphp
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex flex-wrap justify-between items-start gap-3 mb-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900">{{ $cr?->subjectDescription() ?? 'No longer in the system' }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $cr?->reference }}
                        @if($cr && $cr->allSites()->isNotEmpty())
                            &middot; {{ $cr->allSites()->pluck('name')->join(', ') }}
                        @endif
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-2xl font-bold text-hcrg-burgundy">{{ rtrim(rtrim(number_format((float) $item->estimated_hours, 1), '0'), '.') }}</span>
                    <span class="block text-xs text-gray-400">hours</span>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">What it's trying to achieve</dt>
                    <dd class="text-gray-800 mt-0.5 whitespace-pre-wrap">{{ $brief['achieve'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Who it's for</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $audiences ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">What they should know or do</dt>
                    <dd class="text-gray-800 mt-0.5 whitespace-pre-wrap">{{ $brief['know_or_do'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">How we'll know it worked</dt>
                    <dd class="text-gray-800 mt-0.5 whitespace-pre-wrap">{{ ($brief['measure'] ?? null) ?: '—' }}</dd>
                </div>
                @if(($brief['already_exists'] ?? null) === 'yes')
                {{-- Relevant to a decision about money: we are being asked to pay
                     for something the requester thought might already exist. --}}
                <div>
                    <dt class="text-xs font-medium text-amber-700 uppercase tracking-wide">Something similar may already exist</dt>
                    <dd class="text-amber-800 mt-0.5 whitespace-pre-wrap">{{ ($brief['already_exists_detail'] ?? null) ?: 'The person who asked was not sure.' }}</dd>
                </div>
                @endif
            </dl>
        </div>
    @endforeach

    <div class="bg-hcrg-grey-100 rounded-lg p-5 flex justify-between items-center">
        <span class="font-bold text-gray-900">Total</span>
        <span class="text-2xl font-bold text-hcrg-burgundy">{{ rtrim(rtrim(number_format((float) $round->total_hours, 1), '0'), '.') }} hours</span>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('funding.respond', request()->route('token')) }}" id="fundingForm">
        @csrf
        <input type="hidden" name="decision" id="decisionField" value="approved">

        <div id="declineReason" class="hidden mb-4">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Why not? <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-500 mb-2">The content team picks this up, so a sentence on what would change your mind is more use than a no.</p>
            <textarea name="notes" id="notes" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('notes') }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" id="approveBtn"
                class="flex-1 bg-status-success hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition-colors">
                Approve these hours
            </button>
            <button type="button" id="declineBtn"
                class="flex-1 border border-gray-300 text-gray-700 font-medium py-3 px-6 rounded-lg hover:bg-gray-50 transition-colors">
                Decline
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var decline = document.getElementById('declineBtn');
    var reason = document.getElementById('declineReason');
    var field = document.getElementById('decisionField');
    var approve = document.getElementById('approveBtn');
    var notes = document.getElementById('notes');

    decline.addEventListener('click', function () {
        // Two steps for a decline: the reason is what the content team acts on.
        if (reason.classList.contains('hidden')) {
            reason.classList.remove('hidden');
            field.value = 'declined';
            notes.required = true;
            approve.classList.add('hidden');
            decline.textContent = 'Confirm decline';
            decline.type = 'submit';
            notes.focus();
        }
    });
})();
</script>
@endsection
