@extends('layouts.public')
@section('title', 'No Response Needed')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">No response needed</h1>
    <p class="text-sm text-gray-600 mb-6">
        Request <span class="font-mono font-semibold">{{ $changeRequest->reference }}</span> isn't waiting on a response from you — it may already have been answered, or the team has moved it on.
    </p>
    <a href="{{ \App\Http\Controllers\PublicSite\TrackingController::signedUrl($changeRequest) }}" class="text-sm text-hcrg-burgundy hover:underline font-medium">
        Track your request
    </a>
</div>
@endsection
