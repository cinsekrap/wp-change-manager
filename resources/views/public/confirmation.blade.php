@extends('layouts.public')
@section('title', 'Request Submitted')

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Request Submitted</h1>
    <p class="text-gray-600 mb-6">Your website change request has been received.</p>

    <div class="bg-gray-50 rounded-lg p-4 inline-block mb-6">
        <p class="text-sm text-gray-500">Your reference number</p>
        <p class="text-2xl font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
    </div>

    <div class="text-sm text-gray-500 space-y-1 mb-8">
        <p><strong>Site:</strong> {{ $changeRequest->site->name }}</p>
        <p><strong>Page:</strong> {{ $changeRequest->page_title ?: $changeRequest->page_url }}</p>
        <p><strong>Changes:</strong> {{ $changeRequest->items->count() }} item(s)</p>
    </div>

    <p class="text-sm text-gray-400 mb-6">Please keep your reference number for your records. The marketing team will review your request shortly.</p>

    <p class="text-sm text-gray-500 mb-6">You can <a href="{{ route('tracking') }}" class="text-hcrg-burgundy hover:underline font-medium">track the status of your request</a> at any time.</p>

    <a href="{{ route('wizard') }}" class="inline-block bg-hcrg-burgundy text-white px-6 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
        Submit Another Request
    </a>
</div>
@endsection
