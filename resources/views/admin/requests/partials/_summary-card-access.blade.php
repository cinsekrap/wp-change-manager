{{-- Access request ticket: site + recipient + reason --}}
<div class="card card-body">
    <div class="flex flex-wrap items-start gap-x-8 gap-y-2 text-sm">
        <div class="flex items-center">
            <span class="text-gray-500">Site:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->site->name ?? '—' }}</span>
            @if($changeRequest->site)
                <a href="https://{{ $changeRequest->site?->domain }}" target="_blank" rel="noopener" title="View site" class="ml-1.5 text-gray-400 hover:text-hcrg-burgundy transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                </a>
                <a href="https://{{ $changeRequest->site?->domain }}/wp-admin" target="_blank" rel="noopener" title="wp-admin" class="ml-1 text-gray-400 hover:text-hcrg-burgundy transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </a>
            @endif
        </div>
        <div>
            <span class="text-gray-500">Access to:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->cptType->name ?? $changeRequest->cpt_slug }}</span>
        </div>
        <div>
            <span class="text-gray-500">Submitted:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->created_at->format('d M Y H:i') }}</span>
            <span class="text-gray-400 ml-1">({{ $changeRequest->created_at->diffForHumans() }})</span>
        </div>
    </div>

    @if($changeRequest->isActive())
    @php
        $slaStatus = $changeRequest->slaStatus();
        $slaHoursRemaining = $changeRequest->slaRemainingHours();
        $slaColors = [
            'on_track' => 'text-emerald-600',
            'at_risk' => 'text-amber-600',
            'overdue' => 'text-red-600',
        ];
    @endphp
    <div class="mt-2 text-sm flex items-center gap-x-2">
        <span class="text-gray-500">SLA:</span>
        @if($changeRequest->status === 'training')
            <span class="font-medium text-sky-700">Paused — awaiting training confirmation</span>
        @elseif($slaStatus === 'overdue')
            <span class="font-medium {{ $slaColors[$slaStatus] }}">Overdue by {{ abs($slaHoursRemaining) }} hours</span>
        @else
            <span class="font-medium {{ $slaColors[$slaStatus] }}">Due in {{ $slaHoursRemaining }} hours</span>
        @endif
    </div>
    @endif

    {{-- Who gets access --}}
    <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
        <span class="text-gray-500">Access for:</span>
        <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->access_recipient_name ?: '—' }}</span>
        @if($changeRequest->access_recipient_email)
            <a href="mailto:{{ $changeRequest->access_recipient_email }}" class="text-hcrg-burgundy hover:underline ml-2">{{ $changeRequest->access_recipient_email }}</a>
        @endif
    </div>

    {{-- Reason --}}
    @if($changeRequest->items->first()?->description)
    <div class="mt-2 text-sm">
        <span class="text-gray-500">Reason:</span>
        <p class="text-gray-700 mt-1 whitespace-pre-wrap">{{ $changeRequest->items->first()->description }}</p>
    </div>
    @endif

    {{-- Requester --}}
    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
        <span class="text-gray-500">Requested by:</span>
        <span class="font-medium text-gray-900">{{ $changeRequest->requester_name }}</span>
        <a href="mailto:{{ $changeRequest->requester_email }}" class="text-hcrg-burgundy hover:underline">{{ $changeRequest->requester_email }}</a>
        @if($changeRequest->requester_phone)
            <span class="text-gray-600">{{ $changeRequest->requester_phone }}</span>
        @endif
        @if($changeRequest->requester_role)
            <span class="text-gray-400">{{ $changeRequest->requester_role }}</span>
        @endif
    </div>
</div>
