@extends('layouts.admin')
@section('title', 'Change Password')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Change Password</h1>

    <form method="POST" action="{{ route('admin.password.update') }}" class="bg-white rounded-lg shadow p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
            <input type="password" name="current_password" id="current_password" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <input type="password" name="password" id="password" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Update Password</button>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>

    {{-- Disable MFA section (only for non-SSO users with MFA enabled) --}}
    @if(auth()->user()->hasMfaEnabled() && auth()->user()->provider !== 'microsoft')
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-bold text-gray-900 mb-2">Two-factor authentication</h2>
            <p class="text-sm text-gray-600 mb-4">MFA is currently enabled on your account (set up {{ auth()->user()->mfa_confirmed_at->diffForHumans() }}). You can disable it below, but you will be required to set it up again on your next login.</p>
            <form method="POST" action="{{ route('mfa.disable') }}" onsubmit="return confirm('Are you sure? You will need to set up MFA again on your next login.')">
                @csrf
                <div class="mb-4">
                    <label for="disable_current_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm your current password</label>
                    <input type="password" name="current_password" id="disable_current_password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                </div>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 text-sm font-medium">
                    Disable two-factor authentication
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
