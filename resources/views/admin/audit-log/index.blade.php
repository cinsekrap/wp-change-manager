@extends('layouts.admin')
@section('title', 'Audit Log')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
</div>

{{-- Filters --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.audit-log') }}" class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">User</label>
            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <option value="">All users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
            <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <option value="">All actions</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($action)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div class="flex-1 min-w-[140px]">
            <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Filter</button>
            @if(request()->hasAny(['user_id', 'action', 'date_from', 'date_to']))
                <a href="{{ route('admin.audit-log') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                    {{ $log->created_at->format('d M Y H:i:s') }}
                </td>
                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">
                    {{ $log->user->name ?? 'System' }}
                </td>
                <td class="px-6 py-3 whitespace-nowrap">
                    @php
                        $actionColors = [
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-blue-100 text-blue-700',
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
                <td class="px-6 py-3 text-sm text-gray-700 max-w-md truncate" title="{{ $log->description }}">
                    {{ \Illuminate\Support\Str::limit($log->description, 80) }}
                </td>
                <td class="px-6 py-3 text-xs text-gray-400 whitespace-nowrap">
                    {{ $log->ip_address }}
                </td>
                <td class="px-6 py-3 text-right whitespace-nowrap">
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
                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">No audit log entries found.</td>
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
