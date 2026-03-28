@extends('layouts.admin')
@section('title', 'Notifications')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
    <a href="{{ route('admin.settings.email-templates') }}" class="inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Email Templates
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left column (2/3) --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Email Templates section --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Email Templates</h2>
            <p class="text-sm text-gray-500 mb-4">Customise the subject lines and body text of automated emails sent by the system.</p>

            <a href="{{ route('admin.settings.email-templates') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Open template editor &rarr;
            </a>
        </div>

        {{-- SLA Settings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">SLA Turnaround Times</h2>
            <p class="text-sm text-gray-500 mb-4">Business hours per priority level (Mon-Fri, 8h/day).</p>

            <form method="POST" action="{{ route('admin.settings.sla.update') }}">
                @csrf @method('PUT')
                <div class="space-y-3">
                    @foreach(\App\Models\ChangeRequest::PRIORITIES as $priority)
                    @php
                        $defaultHours = config("sla.{$priority}", 40);
                        $currentHours = \App\Models\Setting::get("sla_{$priority}", $defaultHours);
                    @endphp
                    <div class="flex items-center justify-between">
                        <label for="sla_{{ $priority }}" class="text-sm font-medium text-gray-700 capitalize">{{ ucfirst($priority) }}</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="sla_{{ $priority }}" id="sla_{{ $priority }}" value="{{ $currentHours }}" min="1" max="999"
                                class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            <span class="text-xs text-gray-400">hours</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="submit" class="mt-4 bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                    Save SLA Settings
                </button>
            </form>
        </div>

        {{-- Chase Reminder Settings --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Chase Reminders</h2>
            <p class="text-sm text-gray-500 mb-4">Automatically remind assignees when a request has been inactive.</p>

            <form method="POST" action="{{ route('admin.settings.chase.update') }}">
                @csrf @method('PUT')
                <div class="space-y-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="hidden" name="chase_enabled" value="0">
                        <input type="checkbox" name="chase_enabled" value="1"
                            {{ \App\Models\Setting::get('chase_enabled') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">
                        <span class="text-sm font-medium text-gray-700">Enable chase reminders</span>
                    </label>

                    <div>
                        <label for="chase_hours" class="block text-sm font-medium text-gray-700 mb-1">Chase after inactivity</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="chase_hours" id="chase_hours"
                                value="{{ \App\Models\Setting::get('chase_hours', 48) }}" min="1" max="9999"
                                class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            <span class="text-xs text-gray-400">hours</span>
                        </div>
                        @error('chase_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="chase_unassigned_email" class="block text-sm font-medium text-gray-700 mb-1">Notify for unassigned requests</label>
                        <input type="email" name="chase_unassigned_email" id="chase_unassigned_email"
                            value="{{ \App\Models\Setting::get('chase_unassigned_email') }}" placeholder="team@example.com"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        <p class="mt-1 text-xs text-gray-500">Fallback email for stale requests with no assignee.</p>
                        @error('chase_unassigned_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="mt-4 bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                    Save Chase Settings
                </button>
            </form>

            <p class="mt-3 text-xs text-gray-400">Requires a scheduled task or cron job running <code class="bg-gray-100 px-1 py-0.5 rounded">php artisan requests:chase</code>.</p>
        </div>
    </div>

    {{-- Right column (1/3): Template previews --}}
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Template Previews</h2>
            <p class="text-sm text-gray-500 mb-4">Preview emails with sample data.</p>

            <div class="space-y-2">
                <a href="{{ route('admin.settings.mail.preview', 'request-submitted') }}" target="_blank"
                   class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Request Submitted</p>
                        <p class="text-xs text-gray-500">Confirmation to requester</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <a href="{{ route('admin.settings.mail.preview', 'status-changed') }}" target="_blank"
                   class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Status Changed</p>
                        <p class="text-xs text-gray-500">Update notification to requester</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <a href="{{ route('admin.settings.mail.preview', 'new-request-alert') }}" target="_blank"
                   class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900">New Request Alert</p>
                        <p class="text-xs text-gray-500">Notification to admin team</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <a href="{{ route('admin.settings.mail.preview', 'approval-requested') }}" target="_blank"
                   class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Approval Requested</p>
                        <p class="text-xs text-gray-500">Sent to approvers with review link</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <a href="{{ route('admin.settings.mail.preview', 'request-chase') }}" target="_blank"
                   class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Chase Reminder</p>
                        <p class="text-xs text-gray-500">Sent for stale inactive requests</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
