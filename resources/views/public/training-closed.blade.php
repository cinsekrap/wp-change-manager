@extends('layouts.public')
@section('title', 'Request Closed')

@section('content')
<div class="card p-8 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-gray-100">
        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
    </div>

    <h1 class="page-title mb-2">This Request Has Been Closed</h1>
    <p class="text-gray-600 mb-6">
        The access request {{ $changeRequest->reference }} has been {{ $changeRequest->status }}, so no training confirmation is needed.
    </p>

    <p class="text-sm text-gray-400">If you think this is a mistake, please contact the marketing team. You can close this page.</p>
</div>
@endsection
