@extends('layouts.admin')
@section('title', 'Change Password')

@section('content')
<div class="max-w-2xl">
    <h1 class="page-title mb-6">Change Password</h1>

    <form method="POST" action="{{ route('admin.password.update') }}" class="card card-body space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="field-label">Current Password</label>
            <input type="password" name="current_password" id="current_password" required
                class="field-input">
            @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">New Password</label>
            <input type="password" name="password" id="password" required
                class="field-input">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="field-input">
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="btn btn-primary">Update Password</button>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>

    @if((bool) \App\Models\Setting::get('entra_enabled'))
        <div class="card card-body mt-6">
            <h2 class="card-title mb-2">Microsoft sign-in</h2>
            @if(auth()->user()->usesSso())
                <p class="text-sm text-gray-600">Your account is linked to Microsoft sign-in. A super admin can unlink it if you need to sign in another way.</p>
            @else
                <p class="text-sm text-gray-600 mb-4">
                    Link your account to Microsoft so you sign in there instead. Your password will stop working,
                    and Microsoft's own two-factor replaces the code you enter here.
                </p>
                <a href="{{ route('auth.microsoft') }}" class="btn btn-secondary">
                    Link Microsoft sign-in
                </a>
            @endif
        </div>
    @endif

    {{-- Disable MFA section (only for non-SSO users with MFA enabled) --}}
    @if(auth()->user()->hasMfaEnabled() && ! auth()->user()->usesSso())
        <div class="card card-body mt-6">
            <h2 class="card-title mb-2">Two-factor authentication</h2>
            <p class="text-sm text-gray-600 mb-4">MFA is currently enabled on your account (set up {{ auth()->user()->mfa_confirmed_at->diffForHumans() }}). You can disable it below, but you will be required to set it up again on your next login.</p>
            <form method="POST" action="{{ route('mfa.disable') }}" data-confirm="Are you sure? You will need to set up MFA again on your next login.">
                @csrf
                <div class="mb-4">
                    <label for="disable_current_password" class="field-label">Confirm your current password</label>
                    <input type="password" name="current_password" id="disable_current_password" required
                        class="field-input">
                </div>
                <button type="submit" class="btn btn-danger">
                    Disable two-factor authentication
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
