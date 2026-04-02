@extends('layouts.public')
@section('title', 'Approval No Longer Required')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-blue-100">
        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Approval No Longer Required</h1>
    <p class="text-gray-600 mb-6">Hi {{ $approver->name }}, {{ $changeRequest->approvalOverriddenByUser->name ?? 'the marketing team' }} has progressed this change request ({{ $changeRequest->reference }}), so your approval is no longer needed.</p>

    <p class="text-sm text-gray-400">You can close this page.</p>

    @include('public.partials.approval-queue')
</div>
@endsection
