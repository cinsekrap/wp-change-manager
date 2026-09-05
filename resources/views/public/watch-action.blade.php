@extends('layouts.public')
@section('title', $heading)

@section('content')
<div class="card p-8 text-center max-w-lg mx-auto">
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $heading }}</h1>
    <p class="text-sm text-gray-600 mb-6">{{ $body }}</p>

    <form method="POST" action="{{ $action }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            {{ $button }}
        </button>
    </form>

    <a href="{{ route('suggestions') }}" class="inline-block mt-4 text-sm text-gray-500 hover:underline">Cancel</a>
</div>
@endsection
