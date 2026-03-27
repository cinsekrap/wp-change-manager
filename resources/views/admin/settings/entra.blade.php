@extends('layouts.admin')
@section('title', 'SSO Settings')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">SSO Settings</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Entra configuration --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ route('admin.settings.entra.update') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Enable toggle --}}
            <div class="flex items-start space-x-3">
                <input type="hidden" name="entra_enabled" value="0">
                <input type="checkbox" name="entra_enabled" id="entra_enabled" value="1"
                    {{ old('entra_enabled', $settings->get('entra_enabled')) ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
                <div>
                    <label for="entra_enabled" class="text-sm font-medium text-gray-900">Enable Microsoft Entra SSO</label>
                    <p class="text-xs text-gray-500 mt-0.5">Allow users to sign in with their Microsoft account. You must configure the settings below and register this app in Azure first.</p>
                </div>
            </div>

            <hr class="border-gray-200">

            {{-- Tenant ID --}}
            <div>
                <label for="entra_tenant_id" class="block text-sm font-medium text-gray-700 mb-1">Tenant ID</label>
                <input type="text" name="entra_tenant_id" id="entra_tenant_id"
                    value="{{ old('entra_tenant_id', $settings->get('entra_tenant_id')) }}"
                    placeholder="your-tenant-id or common"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <p class="mt-1 text-xs text-gray-500">Find this in Azure Portal &rarr; Microsoft Entra ID &rarr; Overview. Use "common" to allow any Azure AD tenant.</p>
                @error('entra_tenant_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Client ID --}}
            <div>
                <label for="entra_client_id" class="block text-sm font-medium text-gray-700 mb-1">Client ID / Application ID</label>
                <input type="text" name="entra_client_id" id="entra_client_id"
                    value="{{ old('entra_client_id', $settings->get('entra_client_id')) }}"
                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <p class="mt-1 text-xs text-gray-500">The Application (client) ID from your App Registration in Azure Portal.</p>
                @error('entra_client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Client Secret --}}
            <div>
                <label for="entra_client_secret" class="block text-sm font-medium text-gray-700 mb-1">Client Secret</label>
                <div class="relative">
                    <input type="password" name="entra_client_secret" id="entra_client_secret"
                        placeholder="{{ $settings->get('entra_client_secret') ? '••••••••' : '' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy pr-16">
                    <button type="button" onclick="toggleSecret()" id="toggleSecretBtn"
                        class="absolute inset-y-0 right-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700">
                        Show
                    </button>
                </div>
                @if($settings->get('entra_client_secret'))
                    <p class="mt-1 text-xs text-gray-500">Leave blank to keep current secret.</p>
                @else
                    <p class="mt-1 text-xs text-gray-500">Create a client secret in Azure Portal &rarr; App Registration &rarr; Certificates &amp; secrets.</p>
                @endif
                @error('entra_client_secret') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <hr class="border-gray-200">

            {{-- Auto-provision --}}
            <div class="flex items-start space-x-3">
                <input type="hidden" name="entra_auto_provision" value="0">
                <input type="checkbox" name="entra_auto_provision" id="entra_auto_provision" value="1"
                    {{ old('entra_auto_provision', $settings->get('entra_auto_provision')) ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
                <div>
                    <label for="entra_auto_provision" class="text-sm font-medium text-gray-900">Auto-provision users</label>
                    <p class="text-xs text-gray-500 mt-0.5">Automatically create an admin account when a new user signs in via Microsoft for the first time. If disabled, users must be created manually before they can log in with SSO.</p>
                </div>
            </div>

            <div class="flex items-center space-x-3 pt-2">
                <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Right: Setup instructions --}}
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Redirect URI</h2>
            <p class="text-sm text-gray-500 mb-3">Copy this URI into your Azure App Registration under <strong>Authentication &rarr; Web &rarr; Redirect URIs</strong>.</p>

            <div class="flex items-center space-x-2">
                <input type="text" readonly value="{{ config('app.url') }}/auth/microsoft/callback" id="redirectUri"
                    class="flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-700 font-mono">
                <button type="button" onclick="copyRedirectUri()"
                    class="px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-200 transition-colors" title="Copy to clipboard">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Azure Setup Guide</h2>
            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                <li>Go to <strong>Azure Portal</strong> &rarr; <strong>Microsoft Entra ID</strong> &rarr; <strong>App registrations</strong></li>
                <li>Click <strong>New registration</strong></li>
                <li>Enter a name (e.g., "{{ config('app.name') }}")</li>
                <li>Set supported account types as needed</li>
                <li>Add the <strong>Redirect URI</strong> above as a <strong>Web</strong> platform</li>
                <li>Copy the <strong>Application (client) ID</strong> and <strong>Directory (tenant) ID</strong> from the overview page</li>
                <li>Under <strong>Certificates &amp; secrets</strong>, create a new client secret and copy the value</li>
                <li>Paste all values into the form on the left</li>
            </ol>
        </div>
    </div>
</div>

<script>
function toggleSecret() {
    var input = document.getElementById('entra_client_secret');
    var btn = document.getElementById('toggleSecretBtn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide';
    } else {
        input.type = 'password';
        btn.textContent = 'Show';
    }
}

function copyRedirectUri() {
    var input = document.getElementById('redirectUri');
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = input.nextElementSibling;
        var original = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(function() { btn.innerHTML = original; }, 2000);
    });
}
</script>
@endsection
