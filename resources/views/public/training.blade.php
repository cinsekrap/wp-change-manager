@extends('layouts.public')
@section('title', 'Training Confirmation')

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h1 class="text-2xl font-bold text-hcrg-burgundy mb-6">Training Confirmation</h1>

    {{-- Request summary card --}}
    <div class="bg-gray-50 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Reference</p>
                <p class="text-lg font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Site</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->site?->name ?? 'Not yet decided' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Access to</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</p>
            </div>
        </div>
    </div>

    <div class="bg-hcrg-grey-100 rounded-lg px-6 py-4 mb-6 space-y-2">
        <p class="text-sm text-gray-700">
            <strong>{{ $changeRequest->access_recipient_name }}</strong>, your access request has been approved.
        </p>
        <p class="text-sm text-gray-600">
            Before access can be granted, please watch the training video below. When you have watched it and feel confident, confirm using the form underneath.
        </p>
    </div>

    {{-- Training video link --}}
    <div class="mb-6">
        <a href="{{ $changeRequest->cptType->training_url }}" target="_blank" rel="noopener"
            class="inline-flex items-center bg-hcrg-burgundy text-white font-bold py-4 px-6 rounded-lg text-lg hover:bg-[#9A1B4B] transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-hcrg-burgundy">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Watch the training video
        </a>
        <p class="mt-2 text-xs text-gray-500">The video opens in a new tab. Come back to this page afterwards to confirm.</p>
    </div>

    {{-- Confirmation form --}}
    <form method="POST" action="{{ route('training.confirm', $changeRequest->training_token) }}">
        @csrf

        <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
            <label class="flex items-start space-x-3 cursor-pointer">
                <input type="checkbox" name="confirmed" value="1" id="confirmCheckbox"
                    class="mt-0.5 h-5 w-5 text-hcrg-burgundy border-gray-300 rounded focus:ring-hcrg-burgundy">
                <span class="text-sm text-gray-700">
                    I confirm that I have watched the training video and I am confident I can use this competently.
                </span>
            </label>

            @error('confirmed')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit" id="confirmSubmit" disabled
                class="w-full bg-status-success hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                Confirm Training Complete
            </button>
        </div>
    </form>

    <script>
    document.getElementById('confirmCheckbox').addEventListener('change', function() {
        document.getElementById('confirmSubmit').disabled = !this.checked;
    });
    </script>
</div>
@endsection
