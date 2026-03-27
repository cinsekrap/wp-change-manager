@extends('layouts.public')
@section('title', 'Track Your Request')

@section('content')
<div class="bg-white rounded-lg shadow p-8 max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Track Your Request</h1>
    <p class="text-gray-600 mb-6">Enter your reference number and email to check the status of your request.</p>

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tracking.show') }}">
        @csrf

        <div class="mb-4">
            <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference number</label>
            <input
                type="text"
                id="reference"
                name="reference"
                value="{{ old('reference') }}"
                placeholder="e.g. WCR-20260327-001"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-hcrg-burgundy focus:ring-hcrg-burgundy"
                required
            >
            @error('reference')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="your.email@example.com"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-hcrg-burgundy focus:ring-hcrg-burgundy"
                required
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-hcrg-burgundy text-white px-6 py-2.5 rounded-full hover:bg-[#9A1B4B] text-sm font-medium transition-colors">
            Look up request
        </button>
    </form>
</div>
@endsection
