@extends('layouts.public')
@section('title', 'Request Closed')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-gray-100">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Request Closed</h1>
    <p class="text-gray-600 mb-6">Hi {{ $approver->name }}, this change request ({{ $changeRequest->reference }}) has been {{ $changeRequest->status }}, so your approval is no longer needed.</p>

    <p class="text-sm text-gray-400">You can close this page.</p>
</div>
@endsection
