{{-- Combined request + requester info --}}
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex flex-wrap items-start gap-x-8 gap-y-2 text-sm">
        <div>
            <span class="text-gray-500">Site:</span>
            <span class="font-medium text-gray-900 ml-1">{{ $changeRequest->site->name ?? '—' }}</span>
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

    <div class="mt-2 text-sm">
        <span class="text-gray-500">Page:</span>
        @if($changeRequest->is_new_page)
            <span class="text-orange-600 font-medium ml-1">New page:</span>
            <span class="text-gray-900 ml-1">{{ $changeRequest->page_title }}</span>
        @else
            <a href="{{ $changeRequest->page_url }}" target="_blank" class="text-hcrg-burgundy hover:underline ml-1">{{ $changeRequest->page_title ?: $changeRequest->page_url }}</a>
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
