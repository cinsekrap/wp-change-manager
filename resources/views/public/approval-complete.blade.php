@extends('layouts.public')
@section('title', 'Approval Submitted')

@section('content')
<div class="card p-8 text-center">
    <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4
        @if($status === 'approved') bg-green-100 @else bg-red-100 @endif
    ">
        @if($status === 'approved')
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        @else
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        @endif
    </div>

    <h1 class="page-title mb-2">Thank You</h1>
    <p class="text-gray-600 mb-6">Your response has been recorded.</p>

    <div class="bg-gray-50 rounded-lg p-4 inline-block mb-6">
        <p class="text-sm text-gray-500">Your decision</p>
        <p class="text-xl font-bold @if($status === 'approved') text-status-success @else text-status-error @endif">
            {{ ucfirst($status) }}
        </p>
        <p class="text-sm text-gray-400 mt-1">Ref: {{ $changeRequest->reference }}</p>
    </div>

    @if($status === 'rejected' && $changeRequest->isContentRequest())
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
            Your notes have gone to the person writing this copy. They will revise it and ask you again once it is ready.
        </p>
    @endif

    <p class="text-sm text-gray-400">You can close this page.</p>

    @include('public.partials.approval-queue')
</div>
@endsection
