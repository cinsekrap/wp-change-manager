@extends('layouts.admin')
@section('title', 'Audit log')

@section('content')
<x-admin.page-header title="Audit log" lede="Every change made in the admin, newest first." />

{{-- Filters --}}
<div class="card card-body mb-6">
    <form method="GET" action="{{ route('admin.audit-log') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">User</label>
            <select name="user_id" class="field-input">
                <option value="">All users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
            <select name="action" class="field-input">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($action)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="field-input">
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="field-input">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request()->hasAny(['user_id', 'action', 'date_from', 'date_to']))
                <a href="{{ route('admin.audit-log') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Date/Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="text-gray-500 whitespace-nowrap">
                    {{ $log->created_at->format('d M Y H:i:s') }}
                </td>
                <td class="text-gray-900 whitespace-nowrap">
                    {{ $log->user->name ?? 'System' }}
                </td>
                <td class="whitespace-nowrap">
                    @php
                        $actionColors = [
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
                            'deleted' => 'bg-red-100 text-red-700',
                            'status_changed' => 'bg-amber-100 text-amber-700',
                            'login' => 'bg-emerald-100 text-emerald-700',
                            'login_failed' => 'bg-red-100 text-red-700',
                            'sso_login' => 'bg-emerald-100 text-emerald-700',
                            'mfa_setup' => 'bg-purple-100 text-purple-700',
                            'mfa_disabled' => 'bg-orange-100 text-orange-700',
                            'mfa_reset' => 'bg-orange-100 text-orange-700',
                            'assigned' => 'bg-cyan-100 text-cyan-700',
                            'password_changed' => 'bg-yellow-100 text-yellow-700',
                        ];
                        $color = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                        {{ str_replace('_', ' ', ucfirst($log->action)) }}
                    </span>
                </td>
                <td class="text-gray-700 max-w-md truncate" title="{{ $log->description }}">
                    {{ \Illuminate\Support\Str::limit($log->description, 80) }}
                </td>
                <td class="text-xs text-gray-400 whitespace-nowrap">
                    {{ $log->ip_address }}
                </td>
                <td class="text-right whitespace-nowrap">
                    @if($log->old_values || $log->new_values)
                        <button type="button" onclick="toggleAuditDetail({{ $log->id }})" class="text-xs text-hcrg-burgundy hover:text-[#9A1B4B] font-medium">
                            Details
                        </button>
                    @endif
                </td>
            </tr>
            @if($log->old_values || $log->new_values)
            <tr id="audit-detail-{{ $log->id }}" class="hidden">
                <td colspan="6" class="px-6 py-4 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        @if($log->old_values)
                        <div>
                            <p class="text-xs font-medium text-red-600 mb-1">Old Values</p>
                            <pre class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-gray-700 overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        @endif
                        @if($log->new_values)
                        <div>
                            <p class="text-xs font-medium text-green-600 mb-1">New Values</p>
                            <pre class="bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-gray-700 overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="6" class="p-0">
                    <x-admin.empty-state message="Nothing recorded for these filters." />
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>

<script>
function toggleAuditDetail(id) {
    var row = document.getElementById('audit-detail-' + id);
    if (row) {
        row.classList.toggle('hidden');
    }
}
</script>
@endsection
