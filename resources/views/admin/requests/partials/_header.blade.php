{{-- What this is and where it has got to, answered before any scrolling. --}}
<div class="mb-6">
    <a href="{{ route('admin.requests.index') }}" class="text-sm text-hcrg-grey-400 hover:text-hcrg-charcoal">&larr; Back to requests</a>

    <div class="flex flex-wrap items-start justify-between gap-3 mt-1">
        <div>
            <p class="text-xs text-hcrg-grey-400 tracking-wide">
                {{ $changeRequest->reference }}
                &middot; {{ ucfirst($changeRequest->request_type ?? 'change') }}
                @if($changeRequest->site) &middot; {{ $changeRequest->site->name }} @endif
            </p>
            <h1 class="page-title mt-0.5">{{ $changeRequest->subjectDescription() }}</h1>
        </div>
        <div class="flex items-center gap-2">
            @include('admin.partials.priority-badge', ['priority' => $changeRequest->priority ?? 'normal'])
            @include('partials.status-badge', ['status' => $changeRequest->status])
        </div>
    </div>

    <p class="mt-2 text-sm text-hcrg-grey-400">
        Raised {{ $changeRequest->created_at->diffForHumans() }}
        @if($changeRequest->requester_name)
            by {{ $changeRequest->requester_name }}
        @else
            by the content team
        @endif
        @if($changeRequest->assignee)
            &middot; Owner: {{ $changeRequest->assignee->name }}
        @else
            &middot; Unassigned
        @endif
        @if($changeRequest->deadline_date)
            &middot; Needed by {{ $changeRequest->deadline_date->format('j M Y') }}
        @endif
    </p>
</div>
