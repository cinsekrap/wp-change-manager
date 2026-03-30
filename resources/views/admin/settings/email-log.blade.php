@extends('layouts.admin')
@section('title', 'Email Log')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Email Log</h1>
    <a href="{{ route('admin.settings.notifications') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Notifications</a>
</div>

{{-- Search --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('admin.settings.email-log') }}" class="flex items-end gap-4">
        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Recipient email or subject..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.settings.email-log') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($logs->isEmpty())
        <div class="p-8 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <p class="text-sm">No emails sent yet.</p>
        </div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('j M Y H:i') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                        @php
                            $typeLabels = [
                                'RequestSubmitted' => 'Submitted',
                                'RequestStatusChanged' => 'Status Changed',
                                'RequestAssigned' => 'Assigned',
                                'ApprovalRequested' => 'Approval',
                                'RequestChase' => 'Chase',
                                'NewRequestAlert' => 'New Alert',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $typeLabels[$log->mailable_class] ?? $log->mailable_class }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $log->recipient_email }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{{ $log->subject }}</td>
                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                        @if($log->changeRequest)
                            <a href="{{ route('admin.requests.show', $log->changeRequest) }}" class="text-hcrg-burgundy hover:underline">{{ $log->changeRequest->reference }}</a>
                        @else
                            <span class="text-gray-300">&mdash;</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                        @if($log->status === 'sent')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Sent</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700" title="{{ $log->error_message }}">Failed</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                        @if($log->message_id || $log->smtp_debug || $log->error_message)
                            <button type="button" onclick="toggleDetail({{ $log->id }})"
                                class="text-sm text-gray-400 hover:text-gray-600">Detail</button>
                        @endif
                        <button type="button" onclick="previewEmail({{ $log->id }})"
                            class="text-sm text-hcrg-burgundy hover:underline">View</button>
                    </td>
                </tr>
                {{-- Expandable SMTP detail row --}}
                <tr id="detail-{{ $log->id }}" class="hidden">
                    <td colspan="7" class="px-4 py-3 bg-gray-50">
                        <dl class="text-xs space-y-1.5">
                            @if($log->message_id)
                                <div class="flex gap-2">
                                    <dt class="font-medium text-gray-500 w-24 shrink-0">Message ID</dt>
                                    <dd class="text-gray-700 font-mono break-all">{{ $log->message_id }}</dd>
                                </div>
                            @endif
                            @if($log->smtp_debug)
                                <div class="flex gap-2">
                                    <dt class="font-medium text-gray-500 w-24 shrink-0">SMTP response</dt>
                                    <dd class="text-gray-700 font-mono whitespace-pre-wrap break-all">{{ $log->smtp_debug }}</dd>
                                </div>
                            @endif
                            @if($log->error_message)
                                <div class="flex gap-2">
                                    <dt class="font-medium text-gray-500 w-24 shrink-0">Error</dt>
                                    <dd class="text-red-600">{{ $log->error_message }}</dd>
                                </div>
                            @endif
                        </dl>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    @endif
</div>

{{-- Preview modal --}}
<div id="emailModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEmailModal()"></div>
    <div class="absolute inset-4 md:inset-12 bg-white rounded-lg shadow-xl flex flex-col">
        <div class="flex items-center justify-between px-5 py-3 border-b">
            <h3 class="text-sm font-semibold text-gray-900">Email Preview</h3>
            <button onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <iframe id="emailFrame" class="flex-1 w-full" sandbox="allow-same-origin"></iframe>
    </div>
</div>

<script>
function toggleDetail(id) {
    document.getElementById('detail-' + id).classList.toggle('hidden');
}
function previewEmail(id) {
    const modal = document.getElementById('emailModal');
    const frame = document.getElementById('emailFrame');
    frame.src = '{{ url("admin/settings/email-log") }}/' + id;
    modal.classList.remove('hidden');
}
function closeEmailModal() {
    const modal = document.getElementById('emailModal');
    modal.classList.add('hidden');
    document.getElementById('emailFrame').src = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEmailModal();
});
</script>
@endsection
