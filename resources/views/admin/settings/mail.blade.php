@extends('layouts.admin')
@section('title', 'Mail Settings')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Mail Settings</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: SMTP config --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                    <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $settings->get('mail_host')) }}" required placeholder="smtp.office365.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @error('mail_host') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                        <input type="number" name="mail_port" id="mail_port" value="{{ old('mail_port', $settings->get('mail_port', '587')) }}" required placeholder="587" min="1" max="65535"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                        @error('mail_port') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                        <select name="mail_encryption" id="mail_encryption"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                            <option value="tls" {{ old('mail_encryption', $settings->get('mail_encryption', 'tls')) === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ old('mail_encryption', $settings->get('mail_encryption', 'tls')) === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ old('mail_encryption', $settings->get('mail_encryption', 'tls')) === 'none' ? 'selected' : '' }}>None</option>
                        </select>
                        @error('mail_encryption') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $settings->get('mail_username')) }}" placeholder="user@example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @error('mail_username') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" name="mail_password" id="mail_password"
                            placeholder="{{ $settings->get('mail_password') ? '••••••••' : '' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy pr-16">
                        <button type="button" onclick="togglePassword()" id="togglePasswordBtn"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700">
                            Show
                        </button>
                    </div>
                    @if($settings->get('mail_password'))
                        <p class="mt-1 text-xs text-gray-500">Leave blank to keep current.</p>
                    @endif
                    @error('mail_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1">From Address</label>
                    <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $settings->get('mail_from_address')) }}" required placeholder="noreply@example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @error('mail_from_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                    <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $settings->get('mail_from_name')) }}" required placeholder="ACME Change"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @error('mail_from_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                    Save Settings
                </button>
            </div>
        </form>

        {{-- Test --}}
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Configuration</h2>

            @if(session('test_success'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('test_success') }}</div>
            @endif
            @if(session('test_error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('test_error') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="flex items-end space-x-3">
                @csrf
                <div class="flex-1">
                    <label for="test_email" class="block text-sm font-medium text-gray-700 mb-1">Send test email to</label>
                    <input type="email" name="test_email" id="test_email" value="{{ old('test_email') }}" required placeholder="you@example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                </div>
                <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium whitespace-nowrap">
                    Send Test
                </button>
            </form>
        </div>
    </div>

    {{-- Right: Email templates --}}
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Email Templates</h2>
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
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var input = document.getElementById('mail_password');
    var btn = document.getElementById('togglePasswordBtn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide';
    } else {
        input.type = 'password';
        btn.textContent = 'Show';
    }
}
</script>
@endsection
