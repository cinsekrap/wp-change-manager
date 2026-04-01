@extends('layouts.public')
@section('title', 'Please wait')

@section('content')
<div class="max-w-md mx-auto text-center py-16">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-100 mb-6">
        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900 mb-2">Too many requests</h2>
    <p class="text-gray-600 mb-6">
        You've made several requests in a short time. Please wait
        @if($exception->getHeaders()['Retry-After'] ?? null)
            @php $minutes = (int) ceil((int) $exception->getHeaders()['Retry-After'] / 60); @endphp
            about {{ $minutes }} {{ Str::plural('minute', $minutes) }}
        @else
            a few minutes
        @endif
        and try again.
    </p>
    <a href="{{ url()->previous() }}" class="inline-flex items-center px-5 py-2 bg-hcrg-burgundy text-white rounded-full text-sm font-medium hover:bg-[#9A1B4B] transition-colors">
        Go back
    </a>
</div>
@endsection
