@extends('layouts.public')
@section('title', 'Content suggestions')

@section('content')
<div class="card card-body mb-6">
    <h2 class="text-xl font-bold text-gray-900 mb-2">Content suggestions</h2>
    <p class="text-sm text-gray-500 mb-4">
        Everything that has been suggested, and where it has got to. New content goes through a funding decision before anyone writes it, so some of these will sit here a while — that is the process working, not a request being ignored.
    </p>

    <form method="GET" action="{{ route('suggestions') }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search suggestions..."
            class="field-input">
    </form>
</div>

@if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">{{ session('success') }}</div>
@endif

@forelse($entries as $entry)
    <div class="card card-body mb-4">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
            <div>
                <h3 class="card-title">{{ $entry->public_title }}</h3>
                <p class="text-xs text-hcrg-grey-400 mt-1">{{ $entry->reference }} · suggested {{ $entry->created_at->diffForHumans() }}</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-hcrg-grey-100 text-hcrg-charcoal whitespace-nowrap">
                {{ \App\Models\ChangeRequest::statusLabel($entry->status) }}
            </span>
        </div>

        <p class="text-sm text-gray-600">
            @if(isset($contentTypes[$entry->content_type]))
                {{ $contentTypes[$entry->content_type]['label'] }}
            @endif
        </p>

        <p class="text-sm text-gray-600 mt-1">
            <span class="font-semibold">Sites:</span>
            {{ $entry->allSites()->pluck('name')->join(', ') }}
        </p>

        <form method="POST" action="{{ route('suggestions.watch', $entry->reference) }}" class="mt-4 flex flex-wrap gap-2 items-center">
            @csrf
            <label for="watch-{{ $entry->id }}" class="text-sm text-gray-700">Get updates:</label>
            <input type="email" id="watch-{{ $entry->id }}" name="email" required placeholder="your@email"
                class="field-input">
            <button type="submit" class="btn btn-primary">Notify me</button>
        </form>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>
@empty
    <div class="card card-body text-sm text-gray-500">
        Nothing here yet. Suggestions appear once a content designer has looked at them.
    </div>
@endforelse

<div class="mt-6">{{ $entries->links() }}</div>
@endsection
