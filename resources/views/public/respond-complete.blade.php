@extends('layouts.public')
@section('title', 'Response Sent')

@section('content')
<div class="card p-8 text-center">
    <div class="mx-auto w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="page-title mb-2">Thank you — response sent</h1>
    <p class="text-sm text-gray-600 mb-6">
        Your response to request <span class="font-mono font-semibold">{{ $changeRequest->reference }}</span> has been passed to the team, and the request is back in the queue. You'll receive an email when there's another update.
    </p>
    <a href="{{ \App\Http\Controllers\PublicSite\TrackingController::signedUrl($changeRequest) }}" class="text-sm text-hcrg-burgundy hover:underline font-medium">
        Track your request
    </a>
</div>
@endsection
