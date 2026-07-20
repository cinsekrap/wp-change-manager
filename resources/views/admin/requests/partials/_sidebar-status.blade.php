{{-- Status + Priority + Assignment combined card --}}
<div class="bg-white rounded-lg shadow p-4">
    <h2 class="text-sm font-semibold text-gray-900 mb-3">Status & Priority</h2>
    @php $canMovePast = $changeRequest->canMovePastReferred(); @endphp
    <form method="POST" action="{{ route('admin.requests.status', $changeRequest) }}" id="statusForm">
        @csrf @method('PATCH')
        <select name="status" id="statusSelect" onchange="toggleReasonField()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2 focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @php $statusLabels = ['requires_referral' => 'Requires Referral', 'training' => 'Awaiting Training', 'trained' => 'Training Confirmed', 'on_hold' => 'On Hold']; @endphp
            @foreach($changeRequest->statusOptions() as $status)
                @php $blocked = !$canMovePast && in_array($status, \App\Models\ChangeRequest::POST_REFERRED_STATUSES); @endphp
                <option value="{{ $status }}" {{ $changeRequest->status === $status ? 'selected' : '' }} {{ $blocked ? 'disabled' : '' }}>
                    {{ $statusLabels[$status] ?? ucfirst($status) }}{{ $blocked ? ' (approvals required)' : '' }}
                </option>
            @endforeach
        </select>
        <div id="reasonField" class="hidden mb-2">
            <textarea name="rejection_reason" rows="2" placeholder="Reason (required)..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('rejection_reason') }}</textarea>
            @error('rejection_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div id="holdReasonField" class="hidden mb-2">
            <textarea name="hold_reason" rows="2" placeholder="Reason for hold (required) — this is emailed to the requester..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('hold_reason') }}</textarea>
            @error('hold_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div id="scheduledField" class="hidden mb-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Scheduled for (required)</label>
            <input type="date" name="scheduled_date" min="{{ now()->format('Y-m-d') }}"
                value="{{ old('scheduled_date', $changeRequest->scheduled_date?->format('Y-m-d')) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('scheduled_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Update Status</button>
    </form>
    <script>
    function toggleReasonField() {
        var val = document.getElementById('statusSelect').value;
        var showReason = val === 'declined' || val === 'cancelled';
        document.getElementById('reasonField').classList.toggle('hidden', !showReason);

        var showHoldReason = val === 'on_hold';
        document.getElementById('holdReasonField').classList.toggle('hidden', !showHoldReason);

        var showScheduled = val === 'scheduled';
        var scheduledField = document.getElementById('scheduledField');
        scheduledField.classList.toggle('hidden', !showScheduled);
        scheduledField.querySelector('input[name="scheduled_date"]').required = showScheduled;
    }
    toggleReasonField();
    </script>

    @if($changeRequest->rejection_reason && in_array($changeRequest->status, ['declined', 'cancelled']))
    <div class="mt-2 p-2.5 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-xs font-medium text-red-700 mb-0.5">Reason</p>
        <p class="text-sm text-red-800">{{ $changeRequest->rejection_reason }}</p>
    </div>
    @endif

    @if($changeRequest->hold_reason && $changeRequest->status === 'on_hold')
    <div class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-xs font-medium text-amber-700 mb-0.5">On hold{{ $changeRequest->previous_status ? ' (was ' . ucfirst(str_replace('_', ' ', $changeRequest->previous_status)) . ')' : '' }}</p>
        <p class="text-sm text-amber-800">{{ $changeRequest->hold_reason }}</p>
    </div>
    @endif

    {{-- Send for approval button --}}
    @if($changeRequest->status === 'requested')
        @php
            $siteHasApprovers = !empty($changeRequest->site->default_approvers);
            $hasManualApprovers = $changeRequest->approvers->isNotEmpty();
            $allChecksPassed = collect($changeRequest->check_answers ?? [])->every(fn($a) => !empty($a['pass']));
        @endphp
        @if($siteHasApprovers || $hasManualApprovers)
            <div class="mt-3 pt-3 border-t border-gray-100">
                <form method="POST" action="{{ route('admin.requests.send-approval', $changeRequest) }}" data-confirm="This will{{ $siteHasApprovers && !$hasManualApprovers ? ' add the site\'s default approvers and' : '' }} send approval emails. Continue?">
                    @csrf
                    <button type="submit" class="w-full bg-amber-500 text-white px-4 py-2 rounded-full hover:bg-amber-600 text-sm font-medium transition-colors">
                        Send for Approval
                    </button>
                </form>
            </div>
        @endif
    @endif

    {{-- Priority --}}
    <div class="mt-3 pt-3 border-t border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500">Priority</span>
            @include('admin.partials.priority-badge', ['priority' => $changeRequest->priority ?? 'normal'])
        </div>
        <form method="POST" action="{{ route('admin.requests.priority', $changeRequest) }}">
            @csrf @method('PATCH')
            <select name="priority" onchange="this.form.submit()"
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                @foreach(\App\Models\ChangeRequest::PRIORITIES as $p)
                    <option value="{{ $p }}" {{ ($changeRequest->priority ?? 'normal') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Assignment inline --}}
    <div class="mt-3 pt-3 border-t border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-500">Assigned to</span>
            @if($changeRequest->assignee)
                <div class="flex items-center space-x-1.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-hcrg-burgundy text-white text-[10px] font-semibold flex-shrink-0">{{ strtoupper(substr($changeRequest->assignee->name, 0, 1)) }}</span>
                    <span class="text-xs font-medium text-gray-700">{{ $changeRequest->assignee->name }}</span>
                </div>
            @else
                <span class="text-xs text-gray-400">Unassigned</span>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.requests.assign', $changeRequest) }}">
            @csrf @method('PATCH')
            <select name="assigned_to" onchange="this.form.submit()"
                class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <option value="">Unassigned</option>
                @foreach($adminUsers as $admin)
                    <option value="{{ $admin->id }}" {{ $changeRequest->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
