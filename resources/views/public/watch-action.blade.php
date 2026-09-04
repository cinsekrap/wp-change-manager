@extends('layouts.public')
@section('title', $heading)

@section('content')
<div class="bg-white rounded-lg shadow p-8 text-center max-w-lg mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $heading }}</h1>
    <p class="text-sm text-gray-600 mb-6">{{ $body }}</p>

    <form method="POST" action="{{ $action }}">
        @csrf
        <button type="submit" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
            {{ $button }}
        </button>
    </form>

    <a href="{{ route('suggestions') }}" class="inline-block mt-4 text-sm text-gray-500 hover:underline">Cancel</a>
</div>
@endsection
