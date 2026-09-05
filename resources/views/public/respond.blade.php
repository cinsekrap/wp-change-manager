@extends('layouts.public')
@section('title', 'Respond: ' . $changeRequest->reference)

@section('content')
<div class="bg-white rounded-lg shadow p-8">
    <h1 class="text-2xl font-bold text-hcrg-burgundy mb-6">We need some more information</h1>

    {{-- Request summary card --}}
    <div class="bg-gray-50 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Reference</p>
                <p class="text-lg font-bold text-hcrg-burgundy font-mono">{{ $changeRequest->reference }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Site</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->site?->name ?? 'Not yet decided' }}</p>
            </div>
            @if($changeRequest->isAccessRequest())
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Access to</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</p>
            </div>
            @else
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Page</p>
                <p class="text-sm text-gray-800 font-semibold">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</p>
            </div>
            @endif
        </div>
    </div>

    @if($changeRequest->clarification_message)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
        <p class="text-sm font-semibold text-blue-800 mb-1">Our question</p>
        <p class="text-sm text-gray-700">{!! nl2br(e($changeRequest->clarification_message)) !!}</p>
        @if($changeRequest->clarification_requested_at)
        <p class="text-xs text-gray-400 mt-2">Asked {{ $changeRequest->clarification_requested_at->format('j M Y, g:ia') }}</p>
        @endif
    </div>
    @else
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
        <p class="text-sm text-gray-700">Our team needs a response from you before this request can continue. Please reply below, or update your original request if anything needs correcting.</p>
    </div>
    @endif

    <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('respond.store', ['reference' => $changeRequest->reference]) }}">
        @csrf

        {{-- Comment --}}
        <div class="mb-6">
            <label for="comment" class="block text-sm font-semibold text-gray-900 mb-2">Your response</label>
            <textarea name="comment" id="comment" rows="4" placeholder="Type your reply here..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('comment') }}</textarea>
            @error('comment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Editable original request items --}}
        @if($changeRequest->items->isNotEmpty())
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Your original request</h2>
            <p class="text-xs text-gray-500 mb-4">If anything needs correcting, edit the wording below — leave items untouched if they're fine as they are.</p>
            <div class="space-y-3">
                @foreach($changeRequest->items as $item)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        @php
                            $actionColors = [
                                'add' => 'bg-green-100 text-green-700',
                                'change' => 'bg-amber-100 text-amber-700',
                                'delete' => 'bg-red-100 text-red-700',
                                'access_request' => 'bg-blue-100 text-blue-700',
                            ];
                            $actionColor = $actionColors[$item->action_type] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $actionColor }}">
                            {{ ucfirst(str_replace('_', ' ', $item->action_type)) }}
                        </span>
                        @if($item->content_area)
                            <span class="text-sm font-medium text-gray-700">{{ $item->content_area }}</span>
                        @endif
                    </div>
                    @if($item->current_content)
                        <p class="text-sm text-gray-500 mb-2"><span class="font-medium">Currently:</span> {{ Str::limit($item->current_content, 200) }}</p>
                    @endif
                    <textarea name="items[{{ $item->id }}]" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('items.' . $item->id, $item->description) }}</textarea>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <button type="submit" class="w-full bg-hcrg-burgundy text-white font-bold py-4 px-6 rounded-lg text-lg hover:bg-[#9A1B4B] transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-hcrg-burgundy">
            Send Response
        </button>
        <p class="mt-2 text-xs text-gray-500 text-center">Sending your response puts the request straight back into the team's queue.</p>
    </form>
</div>
@endsection
