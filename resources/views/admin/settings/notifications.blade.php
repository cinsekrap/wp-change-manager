@extends('layouts.admin')
@section('title', 'Notifications')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="page-title">Notifications</h1>
    <a href="{{ route('admin.settings.email-templates') }}" class="btn btn-secondary">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Email Templates
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left column (2/3) --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Email Templates section --}}
        <div class="card card-body">
            <h2 class="card-title mb-2">Email Templates</h2>
            <p class="text-sm text-gray-500 mb-4">Customise the subject lines and body text of automated emails sent by the system.</p>

            <a href="{{ route('admin.settings.email-templates') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Open template editor &rarr;
            </a>
        </div>

        {{-- New Request Alert --}}
        <div class="card card-body">
            <h2 class="card-title mb-2">New Request Alert</h2>
            <p class="text-sm text-gray-500 mb-4">Send an email notification when a new change request is submitted.</p>

            <form method="POST" action="{{ route('admin.settings.new-request-alert.update') }}">
                @csrf @method('PUT')
                <div>
                    <label for="new_request_alert_email" class="field-label">Recipient email</label>
                    <input type="email" name="new_request_alert_email" id="new_request_alert_email"
                        value="{{ \App\Models\Setting::get('new_request_alert_email') }}" placeholder="team@example.com"
                        class="field-input">
                    <p class="mt-1 text-xs text-gray-500">Leave blank to disable. The alert uses the <em>New Request Alert</em> email template.</p>
                    @error('new_request_alert_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary mt-4">
                    Save Alert Settings
                </button>
            </form>
        </div>

        {{-- SLA Settings --}}
        <div class="card card-body">
            <h2 class="card-title mb-2">SLA Turnaround Times</h2>
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
                                class="field-input w-20 text-right">
                            <span class="text-xs text-gray-400">hours</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary mt-4">
                    Save SLA Settings
                </button>
            </form>
        </div>

        {{-- Chase Reminder Settings --}}
        <div class="card card-body">
            <h2 class="card-title mb-2">Chase Reminders</h2>
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
                        <label for="chase_hours" class="field-label">Chase after inactivity</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="chase_hours" id="chase_hours"
                                value="{{ \App\Models\Setting::get('chase_hours', 48) }}" min="1" max="9999"
                                class="field-input w-20 text-right">
                            <span class="text-xs text-gray-400">hours</span>
                        </div>
                        @error('chase_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="chase_unassigned_email" class="field-label">Notify for unassigned requests</label>
                        <input type="email" name="chase_unassigned_email" id="chase_unassigned_email"
                            value="{{ \App\Models\Setting::get('chase_unassigned_email') }}" placeholder="team@example.com"
                            class="field-input">
                        <p class="mt-1 text-xs text-gray-500">Fallback email for stale requests with no assignee.</p>
                        @error('chase_unassigned_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4">
                    Save Chase Settings
                </button>
            </form>

            <p class="mt-3 text-xs text-gray-400">Requires a scheduled task or cron job running <code class="bg-gray-100 px-1 py-0.5 rounded">php artisan requests:chase</code>.</p>
        </div>
    </div>

    {{-- Right column (1/3): Email log + Template previews --}}
    <div class="space-y-6">
        <div class="card card-body">
            <h2 class="card-title mb-2">Email Log</h2>
            <p class="text-sm text-gray-500 mb-4">View all emails sent by the system with their content.</p>
            <a href="{{ route('admin.settings.email-log') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                View email log &rarr;
            </a>
        </div>

        <div class="card card-body">
            <h2 class="card-title mb-2">Template Previews</h2>
            <p class="text-sm text-gray-500 mb-4">Preview emails with sample data.</p>

            <div class="space-y-2">
                {{-- Generated from config so this cannot drift behind the templates
                     again — it had fallen 12 behind while it was hand-written. --}}
                @foreach(config('email-templates') as $key => $tpl)
                    <a href="{{ route('admin.settings.mail.preview', str_replace('_', '-', $key)) }}" target="_blank"
                       class="flex items-center justify-between w-full px-3 py-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $tpl['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $tpl['description'] }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
