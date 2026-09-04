@extends('layouts.public')
@section('title', 'Request Submitted')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $changeRequest->isContentRequest() ? 'Suggestion Received' : 'Request Submitted' }}</h1>
    <p class="text-gray-600 mb-6">
        @if($changeRequest->isAccessRequest())
            Your access request has been received.
        @elseif($changeRequest->isContentRequest())
            Thanks — your content suggestion has been received.
        @else
            Your website change request has been received.
        @endif
    </p>

    <div class="bg-gray-50 rounded-lg p-4 inline-block mb-6">
        <p class="text-sm text-gray-500">Your reference number</p>
        <p class="text-2xl font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
    </div>

    <div class="text-sm text-gray-500 space-y-1 mb-8">
        <p><strong>Site:</strong> {{ $changeRequest->site?->name ?? 'Not yet decided' }}</p>
        @if($changeRequest->isAccessRequest())
            <p><strong>Access to:</strong> {{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</p>
            <p><strong>Access for:</strong> {{ $changeRequest->access_recipient_name }}</p>
        @elseif($changeRequest->isContentRequest())
            <p><strong>Content type:</strong> {{ config("content-types.{$changeRequest->content_type}.label", 'New content') }}</p>
            <p><strong>Sites:</strong> {{ $changeRequest->allSites()->pluck('name')->join(', ') }}</p>
        @else
            <p><strong>Page:</strong> {{ $changeRequest->page_title ?: $changeRequest->page_url }}</p>
            <p><strong>Changes:</strong> {{ $changeRequest->items->count() }} item(s)</p>
        @endif
    </div>

    <p class="text-sm text-gray-400 mb-6">
        @if($changeRequest->isAccessRequest())
            Please keep your reference number for your records. Once the request is approved, {{ $changeRequest->access_recipient_name }} will receive a training email — access is granted after they confirm they've completed the training.
        @elseif($changeRequest->isContentRequest())
            Please keep your reference number. A content designer will read your brief and work out what it needs. New content goes through a funding decision before anyone writes it, so this usually takes a while — we'll email you when it moves forward.
        @else
            Please keep your reference number for your records. The marketing team will review your request shortly.
        @endif
    </p>

    <p class="text-sm text-gray-500 mb-6">You can <a href="{{ route('tracking') }}" class="text-hcrg-burgundy hover:underline font-medium">track the status of your request</a> at any time.</p>

    @if($changeRequest->isContentRequest())
    {{-- The one moment someone cares that the queue exists. It does not appear
         there until we have written a public title for it, so this promises a
         list to look at, not that theirs is on it yet. --}}
    <p class="text-sm text-gray-500 mb-6">
        Suggestions are added to our
        <a href="{{ route('suggestions') }}" class="text-hcrg-burgundy hover:underline font-medium">public list of content suggestions</a>
        once we have written yours up, so you can see where it has got to alongside everything else people have asked for.
    </p>
    @endif

    <a href="{{ route('wizard') }}" class="inline-block bg-hcrg-burgundy text-white px-6 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
        Submit Another Request
    </a>
</div>
@endsection
