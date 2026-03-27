@extends('layouts.public')
@section('title', 'Tracking: ' . $changeRequest->reference)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $changeRequest->reference }}</h1>
        @php
            $statusColors = [
                'requested' => 'bg-amber-100 text-amber-800',
                'requires_referral' => 'bg-red-100 text-red-800',
                'referred' => 'bg-orange-100 text-orange-800',
                'approved' => 'bg-hcrg-burgundy/20 text-hcrg-burgundy',
                'scheduled' => 'bg-purple-100 text-purple-800',
                'done' => 'bg-emerald-100 text-emerald-800',
                'declined' => 'bg-red-100 text-red-800',
                'cancelled' => 'bg-gray-200 text-gray-600',
            ];
            $statusLabels = [
                'requires_referral' => 'Requires Referral',
            ];
            $badgeColor = $statusColors[$changeRequest->status] ?? 'bg-gray-100 text-gray-800';
            $badgeLabel = $statusLabels[$changeRequest->status] ?? ucfirst($changeRequest->status);
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badgeColor }}">
            {{ $badgeLabel }}
        </span>
    </div>

    {{-- Details card --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Site</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->site->name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Page</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Submitted</dt>
                <dd class="text-gray-900">
                    {{ $changeRequest->created_at->format('j M Y, g:ia') }}
                    <span class="text-gray-400">({{ $changeRequest->created_at->diffForHumans() }})</span>
                </dd>
            </div>
            @if ($changeRequest->deadline_date)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Deadline</dt>
                    <dd class="{{ $changeRequest->deadline_date->isPast() ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                        {{ $changeRequest->deadline_date->format('j M Y') }}
                        <span class="{{ $changeRequest->deadline_date->isPast() ? 'text-red-400' : 'text-gray-400' }}">({{ $changeRequest->deadline_date->diffForHumans() }})</span>
                    </dd>
                </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">Change items</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->items_count }}</dd>
            </div>
        </dl>
    </div>

    {{-- Rejection reason --}}
    @if (in_array($changeRequest->status, ['declined', 'cancelled']) && $changeRequest->rejection_reason)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-sm font-medium text-red-800 mb-1">Reason</p>
            <p class="text-sm text-red-700">{{ $changeRequest->rejection_reason }}</p>
        </div>
    @endif

    {{-- Status timeline --}}
    @if ($changeRequest->statusLogs->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Status History</h2>
            <ol class="relative border-l border-gray-200 ml-2 space-y-4">
                @foreach ($changeRequest->statusLogs as $log)
                    @php
                        $logColor = $statusColors[$log->new_status] ?? 'bg-gray-100 text-gray-800';
                        $logLabel = $statusLabels[$log->new_status] ?? ucfirst($log->new_status);
                    @endphp
                    <li class="ml-4">
                        <div class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border-2 border-white bg-gray-300"></div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $logColor }}">
                                {{ $logLabel }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $log->created_at->format('j M Y, g:ia') }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-center">
        <a href="{{ route('tracking') }}" class="text-sm text-hcrg-burgundy hover:underline font-medium">
            Track another request
        </a>
        <span class="hidden sm:inline text-gray-300">|</span>
        <a href="{{ route('wizard') }}" class="text-sm text-hcrg-burgundy hover:underline font-medium">
            Submit a new request
        </a>
    </div>
</div>
@endsection
