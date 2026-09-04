@extends('layouts.public')
@section('title', 'Tracking: ' . $changeRequest->reference)

@section('content')
<div class="max-w-lg mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $changeRequest->reference }}</h1>
        @php
                                    $badgeColor = \App\Models\ChangeRequest::statusColor($changeRequest->status);
            $badgeLabel = \App\Models\ChangeRequest::statusLabel($changeRequest->status);
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $badgeColor }}">
            {{ $badgeLabel }}
        </span>
    </div>

    {{-- Status explainer card --}}
    @php
        $clock = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        $statusExplainers = [
            // Content lane. Without these the whole card silently disappeared —
            // on the very page the first two content emails link to.
            'suggested' => [
                'icon' => $clock,
                'bg' => 'bg-hcrg-grey-100 border-hcrg-grey-200',
                'iconColor' => 'text-hcrg-grey-400',
                'title' => 'Your suggestion has been received',
                'text' => 'A content designer will read it and work out what it needs. Nothing is committed yet.',
            ],
            'scoped' => [
                'icon' => $clock,
                'bg' => 'bg-hcrg-grey-100 border-hcrg-grey-200',
                'iconColor' => 'text-hcrg-grey-400',
                'title' => 'A content designer has looked at it',
                'text' => 'They have worked out what it needs and roughly how long it will take. It now goes for a decision on whether to fund the work.',
            ],
            'awaiting_funding' => [
                'icon' => $clock,
                'bg' => 'bg-amber-50 border-amber-200',
                'iconColor' => 'text-amber-500',
                'title' => 'Agreed — waiting for the work to be funded',
                'text' => 'This has been agreed in principle and is waiting for the hours to be funded, which happens outside this tool. It can sit here for a while: that is the process working, not your request being forgotten.',
            ],
            'in_progress' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
                'bg' => 'bg-sky-50 border-sky-200',
                'iconColor' => 'text-sky-500',
                'title' => 'Being written',
                'text' => 'A content designer is writing this now.',
            ],
            'awaiting_approval' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                'bg' => 'bg-fuchsia-50 border-fuchsia-200',
                'iconColor' => 'text-fuchsia-500',
                'title' => 'With a clinical approver',
                'text' => 'The copy has been written and is being checked for clinical safety before it goes live.',
            ],
            'requested' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-amber-50 border-amber-200',
                'iconColor' => 'text-amber-500',
                'title' => 'Your request has been received',
                'text' => 'Our marketing team is reviewing your submission. You\'ll receive an email when there\'s an update.',
            ],
            'requires_referral' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-amber-50 border-amber-200',
                'iconColor' => 'text-amber-500',
                'title' => 'Awaiting referral',
                'text' => 'Your request needs approval from a service lead before work can begin. Our team is arranging this.',
            ],
            'referred' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                'bg' => 'bg-orange-50 border-orange-200',
                'iconColor' => 'text-orange-500',
                'title' => 'Waiting for approval',
                'text' => 'Your request has been referred for approval. We\'re waiting on a response from the relevant approver(s) before we can proceed.',
            ],
            'approved' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-hcrg-burgundy/5 border-hcrg-burgundy/20',
                'iconColor' => 'text-hcrg-burgundy',
                'title' => 'Approved',
                'text' => 'Your request has been approved and is in the queue to be actioned. Requests are scheduled based on priority and team capacity.',
            ],
            'training' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-sky-50 border-sky-200',
                'iconColor' => 'text-sky-500',
                'title' => 'Awaiting training',
                'text' => 'The access request has been approved and a training email has been sent to the person who needs access. Access will be set up once they confirm they\'ve completed the training.',
            ],
            'trained' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-teal-50 border-teal-200',
                'iconColor' => 'text-teal-500',
                'title' => 'Training confirmed',
                'text' => 'Training has been confirmed. Our team is now setting up the access and will be in touch once it\'s ready.',
            ],
            'scheduled' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                'bg' => 'bg-purple-50 border-purple-200',
                'iconColor' => 'text-purple-500',
                'title' => 'Scheduled',
                'text' => 'Your request has been scheduled for implementation. We\'ll update you once the changes have been made.',
            ],
            'on_hold' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-amber-50 border-amber-200',
                'iconColor' => 'text-amber-500',
                'title' => 'On hold',
                'text' => 'Your request is temporarily on hold — please see the reason below. While on hold, our usual turnaround targets and any requested deadline are paused. We\'ll email you as soon as work resumes.',
            ],
            'awaiting_user' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
                'bg' => 'bg-blue-50 border-blue-200',
                'iconColor' => 'text-blue-500',
                'title' => 'We need a response from you',
                'text' => 'Our team has asked you a question and the request is paused until you reply. Turnaround targets and any requested deadline are on hold while we wait — please respond using the button below to get things moving again.',
            ],
            'done' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                'bg' => 'bg-emerald-50 border-emerald-200',
                'iconColor' => 'text-emerald-500',
                'title' => 'Complete',
                'text' => 'The changes you requested have been made. Please check the page and let us know if anything doesn\'t look right.',
            ],
            'declined' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'bg' => 'bg-red-50 border-red-200',
                'iconColor' => 'text-red-500',
                'title' => 'Declined',
                'text' => 'Unfortunately this request has been declined. Please see the reason below for more information.',
            ],
            'cancelled' => [
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>',
                'bg' => 'bg-gray-50 border-gray-200',
                'iconColor' => 'text-gray-400',
                'title' => 'Cancelled',
                'text' => 'This request has been cancelled and will not be actioned.',
            ],
        ];
        $explainer = $statusExplainers[$changeRequest->status] ?? null;
    @endphp
    @if($explainer)
    <div data-status-explainer class="rounded-lg border p-5 mb-6 {{ $explainer['bg'] }}">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 {{ $explainer['iconColor'] }} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $explainer['icon'] !!}</svg>
            <div>
                <h2 class="text-base font-bold text-gray-900">{{ $explainer['title'] }}</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $explainer['text'] }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Details card --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Site</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->site->name }}</dd>
            </div>
            @if($changeRequest->isAccessRequest())
            <div class="flex justify-between">
                <dt class="text-gray-500">Access to</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Access for</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->access_recipient_name }}</dd>
            </div>
            @elseif ($changeRequest->isContentRequest())
            <div class="flex justify-between">
                <dt class="text-gray-500">Content</dt>
                <dd class="text-gray-900 font-medium text-right">
                    {{ $changeRequest->public_title ?: 'New content' }}
                    @if ($changeRequest->content_type && config("content-types.{$changeRequest->content_type}"))
                        <span class="block text-xs text-gray-400 font-normal">{{ config("content-types.{$changeRequest->content_type}.label") }}</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Sites</dt>
                <dd class="text-gray-900 font-medium text-right">{{ $changeRequest->allSites()->pluck('name')->join(', ') }}</dd>
            </div>
            @else
            <div class="flex justify-between">
                <dt class="text-gray-500">Page</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</dd>
            </div>
            @endif
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
            @unless ($changeRequest->isContentRequest())
            <div class="flex justify-between">
                <dt class="text-gray-500">Change items</dt>
                <dd class="text-gray-900 font-medium">{{ $changeRequest->items_count }}</dd>
            </div>
            @endunless
        </dl>
    </div>

    @php
    $publishedAnywhere = $changeRequest->isContentRequest()
        && $changeRequest->allSites()->contains(fn ($s) => $changeRequest->publishedFor($s->id)['published_url']);
@endphp
@if ($publishedAnywhere)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Where it went live</h3>
        <ul class="space-y-2">
            @foreach ($changeRequest->allSites() as $site)
                @php $published = $changeRequest->publishedFor($site->id); @endphp
                @if ($published['published_url'])
                    <li class="text-sm">
                        <a href="{{ $published['published_url'] }}" class="text-hcrg-burgundy underline">{{ $published['published_title'] ?: $published['published_url'] }}</a>
                        <span class="text-gray-400">— {{ $site->name }}</span>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Changes requested --}}
    @if ($changeRequest->items->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Changes Requested</h2>
            <div class="space-y-3">
                @foreach ($changeRequest->items as $item)
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
                            @if ($item->content_area)
                                <span class="text-sm font-medium text-gray-700">{{ $item->content_area }}</span>
                            @endif
                        </div>
                        @if ($item->current_content)
                            <p class="text-sm text-gray-500 mb-1"><span class="font-medium">Currently:</span> {{ Str::limit($item->current_content, 200) }}</p>
                        @endif
                        <p class="text-sm text-gray-700">{!! nl2br(e(Str::limit($item->description, 300))) !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Clarification question + respond button --}}
    @if ($changeRequest->status === 'awaiting_user')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            @if ($changeRequest->clarification_message)
                <p class="text-sm font-medium text-blue-800 mb-1">Our question</p>
                <p class="text-sm text-gray-700 mb-3">{!! nl2br(e($changeRequest->clarification_message)) !!}</p>
            @endif
            <a href="{{ \App\Http\Controllers\PublicSite\ClarificationController::respondUrl($changeRequest) }}"
                class="inline-flex items-center justify-center w-full bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium transition-colors">
                Respond to This Request
            </a>
        </div>
    @endif

    {{-- Hold reason --}}
    @if ($changeRequest->status === 'on_hold' && $changeRequest->hold_reason)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
            <p class="text-sm font-medium text-amber-800 mb-1">Reason</p>
            <p class="text-sm text-amber-700">{{ $changeRequest->hold_reason }}</p>
        </div>
    @endif

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
                        $logColor = \App\Models\ChangeRequest::statusColor($log->new_status);
                        $logLabel = \App\Models\ChangeRequest::statusLabel($log->new_status);
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
