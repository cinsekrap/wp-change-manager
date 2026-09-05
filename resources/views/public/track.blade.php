@extends('layouts.public')
@section('title', 'Track Your Request')

@section('content')
<div class="card p-8 max-w-lg mx-auto">
    <h1 class="page-title mb-2">Track Your Request</h1>
    <p class="text-gray-600 mb-6">Enter your reference number and email to check the status of your request.</p>

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tracking.show') }}">
        @csrf

        <div class="mb-4">
            <label for="reference" class="field-label">Reference number</label>
            <input
                type="text"
                id="reference"
                name="reference"
                value="{{ old('reference') }}"
                placeholder="e.g. WCR-20260327-001"
                class="field-input"
                required
            >
            @error('reference')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email" class="field-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="your.email@example.com"
                class="field-input"
                required
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-full">
            Look up request
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('wizard') }}" class="text-hcrg-burgundy hover:underline">Submit a new request</a>
    </p>
</div>
@endsection
