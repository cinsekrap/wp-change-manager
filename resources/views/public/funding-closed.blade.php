@extends('layouts.public')
@section('title', 'Funding decision')

@section('content')
<div class="card p-8 text-center max-w-lg mx-auto">
    @if($round->status === 'approved')
        <h1 class="text-xl font-bold text-gray-900 mb-2">Funding approved</h1>
        <p class="text-sm text-gray-600">
            {{ rtrim(rtrim(number_format((float) $round->total_hours, 1), '0'), '.') }} hours across
            {{ $round->items->count() }} {{ \Illuminate\Support\Str::plural('piece', $round->items->count()) }} of content.
            The content team can start writing.
        </p>
    @else
        <h1 class="text-xl font-bold text-gray-900 mb-2">Funding declined</h1>
        <p class="text-sm text-gray-600">The content team will follow up.</p>
    @endif

    <p class="text-xs text-gray-400 mt-4">
        {{ $round->reference }}{{ $round->responded_at ? ' · ' . $round->responded_at->format('j M Y') : '' }}
    </p>
</div>
@endsection
