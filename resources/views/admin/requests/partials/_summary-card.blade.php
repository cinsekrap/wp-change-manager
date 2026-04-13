{{-- Combined request + requester info --}}
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex flex-wrap items-start gap-x-8 gap-y-2 text-sm">
        <div class="flex items-center">
            <span class="text-gray-500">Site:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->site->name ?? '—' }}</span>
            @if($changeRequest->site)
                <a href="https://{{ $changeRequest->site->domain }}" target="_blank" rel="noopener" title="View site" class="ml-1.5 text-gray-400 hover:text-hcrg-burgundy transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                </a>
                <a href="https://{{ $changeRequest->site->domain }}/wp-admin" target="_blank" rel="noopener" title="wp-admin" class="ml-1 text-gray-400 hover:text-hcrg-burgundy transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </a>
            @endif
        </div>
        <div>
            <span class="text-gray-500">Type:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->cpt_slug }}</span>
        </div>
        <div>
            <span class="text-gray-500">Submitted:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->created_at->format('d M Y H:i') }}</span>
            <span class="text-gray-400 ml-1">({{ $changeRequest->created_at->diffForHumans() }})</span>
        </div>
        @if($changeRequest->deadline_date)
        <div>
            @php $overdue = $changeRequest->deadline_date->isPast(); @endphp
            <span class="text-gray-500">Deadline:</span>
            <span class="font-medium ml-1 {{ $overdue ? 'text-red-600' : 'text-gray-900' }}">{{ $changeRequest->deadline_date->format('d M Y') }}</span>
            <span class="{{ $overdue ? 'text-red-400' : 'text-gray-400' }} ml-1">({{ $changeRequest->deadline_date->diffForHumans() }})</span>
        </div>
        @endif
    </div>
    @if($changeRequest->deadline_reason)
    <div class="mt-1 text-sm">
        <span class="text-gray-500">Deadline reason:</span>
        <span class="text-gray-700 ml-1">{{ $changeRequest->deadline_reason }}</span>
    </div>
    @endif

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
        @if($slaStatus === 'overdue')
            <span class="font-medium {{ $slaColors[$slaStatus] }}">Overdue by {{ abs($slaHoursRemaining) }} hours</span>
        @elseif($slaStatus === 'at_risk')
            <span class="font-medium {{ $slaColors[$slaStatus] }}">Due in {{ $slaHoursRemaining }} hours</span>
        @else
            <span class="font-medium {{ $slaColors[$slaStatus] }}">Due in {{ $slaHoursRemaining }} hours</span>
        @endif
    </div>
    @endif

    <div class="mt-2 text-sm flex items-center">
        <span class="text-gray-500">Page:</span>
        @if($changeRequest->is_new_page)
            <span class="text-orange-600 font-medium ml-1">New page:</span>
            <span class="text-gray-900 ml-1">{{ $changeRequest->page_title }}</span>
        @else
            <a href="{{ $changeRequest->page_url }}" target="_blank" rel="noopener" class="text-hcrg-burgundy hover:underline ml-1 inline-flex items-center">
                {{ $changeRequest->page_title ?: $changeRequest->page_url }}
                <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        @endif
    </div>

    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm">
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
