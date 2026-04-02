{{-- Audit trail (collapsed by default, super_admin only) --}}
@if(auth()->user()->isSuperAdmin())
@php $auditEntries = \App\Models\AuditLog::forModel($changeRequest)->with('user')->latest()->get(); @endphp
@if($auditEntries->isNotEmpty())
<div class="bg-white rounded-lg shadow">
    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180')"
        class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 transition-colors">
        <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span class="text-sm font-semibold text-gray-700">Audit Trail</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $auditEntries->count() }}</span>
        </div>
        <svg class="chevron w-4 h-4 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div class="hidden border-t border-gray-100 p-4">
        <div class="space-y-3">
            @foreach($auditEntries as $entry)
            <div class="flex items-start justify-between gap-4 text-sm">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-700">{{ $entry->description }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $entry->user->name ?? 'System' }} &middot;
                        {{ $entry->created_at->format('d M Y H:i') }}
                        @if($entry->ip_address)
                            &middot; {{ $entry->ip_address }}
                        @endif
                    </p>
                </div>
                @php
                    $auditActionColors = [
                        'status_changed' => 'bg-amber-100 text-amber-700',
                        'assigned' => 'bg-cyan-100 text-cyan-700',
                        'note_added' => 'bg-gray-100 text-gray-700',
                        'approver_added' => 'bg-green-100 text-green-700',
                        'approver_removed' => 'bg-red-100 text-red-700',
                        'approver_updated' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
                        'sent_for_approval' => 'bg-purple-100 text-purple-700',
                        'item_status_changed' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
                        'priority_changed' => 'bg-orange-100 text-orange-700',
                        'approval_overridden' => 'bg-amber-100 text-amber-700',
                    ];
                @endphp
                <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium {{ $auditActionColors[$entry->action] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ str_replace('_', ' ', ucfirst($entry->action)) }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endif
