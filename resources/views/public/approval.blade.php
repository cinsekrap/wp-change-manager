@extends('layouts.public')
@section('title', 'Approval Request')

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h1 class="text-2xl font-bold text-hcrg-burgundy mb-6">Approval Request</h1>

    {{-- Request summary card --}}
    <div class="bg-gray-50 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Reference</p>
                <p class="text-lg font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Site</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->site->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Page</p>
                <p class="text-sm text-gray-800">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Changes requested</p>
                <p class="text-sm text-gray-800">{{ $changeRequest->items->count() }} item(s)</p>
            </div>
        </div>

        @if($changeRequest->deadline_date)
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4">
                <p class="text-sm font-semibold text-amber-800">
                    Deadline: {{ $changeRequest->deadline_date->format('j F Y') }}
                </p>
                @if($changeRequest->deadline_reason)
                    <p class="text-xs text-amber-700 mt-1">{{ $changeRequest->deadline_reason }}</p>
                @endif
            </div>
        @endif

        {{-- Items overview --}}
        <div class="space-y-3">
            @foreach($changeRequest->items as $item)
                <div class="bg-white rounded border border-gray-200 p-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block px-2 py-0.5 text-xs font-semibold rounded-full
                            @if($item->action_type === 'add') bg-green-100 text-green-800
                            @elseif($item->action_type === 'change') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800
                            @endif
                        ">{{ ucfirst($item->action_type) }}</span>
                        @if($item->content_area)
                            <span class="text-xs text-gray-500">{{ $item->content_area }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-700">{{ Str::limit($item->description, 200) }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Approver info --}}
    <div class="bg-hcrg-grey-100 rounded-lg px-6 py-4 mb-6">
        <p class="text-sm text-gray-700">
            <strong>{{ $approver->name }}</strong>, you have been asked to review this change request as an approver.
        </p>
    </div>

    {{-- Approval form --}}
    <form method="POST" action="{{ route('approval.respond', $approver->token) }}">
        @csrf

        <div class="mb-6">
            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">Notes (optional)</label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                maxlength="1000"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-hcrg-burgundy focus:ring-hcrg-burgundy text-sm p-3 border"
                placeholder="Add any comments about your decision..."
            >{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @error('status')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <div class="flex flex-col sm:flex-row gap-4">
            <button
                type="submit"
                name="status"
                value="approved"
                class="flex-1 bg-status-success hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors"
            >
                Approve
            </button>
            <button
                type="submit"
                name="status"
                value="rejected"
                class="flex-1 bg-status-error hover:bg-red-800 text-white font-bold py-4 px-6 rounded-lg text-lg transition-colors"
            >
                Reject
            </button>
        </div>
    </form>
</div>
@endsection
