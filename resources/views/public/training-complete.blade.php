@extends('layouts.public')
@section('title', 'Training Confirmed')

@section('content')
<div class="card p-8 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-green-100">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="page-title mb-2">
        {{ $alreadyConfirmed ? 'Already Confirmed' : 'Thank You' }}
    </h1>
    <p class="text-gray-600 mb-6">
        @if($alreadyConfirmed)
            Your training confirmation was already recorded on {{ $changeRequest->training_confirmed_at->format('j F Y') }}.
        @else
            Your training confirmation has been recorded. The team will now set up your access and you'll hear from them once it's ready.
        @endif
    </p>

    <div class="bg-gray-50 rounded-lg p-4 inline-block mb-6">
        <p class="text-sm text-gray-500">Access request</p>
        <p class="text-xl font-bold text-hcrg-burgundy">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</p>
        <p class="text-sm text-gray-400 mt-1">Ref: {{ $changeRequest->reference }}</p>
    </div>

    <p class="text-sm text-gray-400">You can close this page.</p>
</div>
@endsection
