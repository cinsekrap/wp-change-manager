@extends('layouts.admin')
@section('title', $user->exists ? 'Edit admin' : 'Add admin')

@section('content')
<div class="max-w-3xl">
    <x-admin.page-header
        :title="$user->exists ? 'Edit admin' : 'Add admin'"
        lede="Someone who can sign in and work on requests." />

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <x-admin.form-section title="Who they are">
        <div class="field">
            <label for="name" class="field-label">Name <span class="text-status-error">*</span></label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                class="field-input">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field mb-0">
            <label for="email" class="field-label">Email <span class="text-status-error">*</span></label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                class="field-input">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        </x-admin.form-section>

        <x-admin.form-section title="Password" help="Leave blank when editing to keep the current one.">
        <div class="field">
            <label for="password" class="field-label">
                Password @if($user->exists) <span class="text-gray-400 font-normal">(leave blank to keep current)</span> @endif
            </label>
            <input type="password" name="password" id="password" {{ $user->exists ? '' : 'required' }}
                class="field-input">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field mb-0">
            <label for="password_confirmation" class="field-label">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="field-input">
        </div>

        </x-admin.form-section>

        <x-admin.form-section title="Access" help="What they can reach, and whether they can sign in at all.">
        <div class="field">
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
        </div>

        <div class="field mb-0">
            <label for="role" class="field-label">Role</label>
            <select name="role" id="role"
                class="field-input">
                <option value="" {{ old('role', $user->role) === null ? 'selected' : '' }}>No admin access</option>
                <option value="editor" {{ old('role', $user->role) === 'editor' ? 'selected' : '' }}>Editor</option>
                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            </select>
            @error('role') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        </x-admin.form-section>

        <x-admin.form-actions>
            <button type="submit" class="btn btn-primary">{{ $user->exists ? 'Save changes' : 'Add admin' }}</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-quiet">Cancel</a>
        </x-admin.form-actions>
    </form>

    {{-- Reset MFA section (only for existing users with MFA enabled) --}}
    @if($user->exists && $user->usesSso())
        <div class="card card-body mt-6">
            <h2 class="card-title mb-2">Microsoft sign-in</h2>
            <p class="text-sm text-gray-600 mb-4">
                This account signs in with Microsoft and has no password sign-in. Unlink it if Microsoft
                sign-in is unavailable and they need to get in another way &mdash; they will need to set a
                password and set up two-factor authentication.
            </p>
            <form method="POST" action="{{ route('admin.users.unlink-sso', $user) }}"
                  data-confirm="Return {{ $user->name }} to password sign-in? They will need a new password and to set up two-factor authentication.">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    Unlink Microsoft sign-in
                </button>
            </form>
        </div>
    @endif

    @if($user->exists && $user->hasMfaEnabled())
        <div class="card card-body mt-6">
            <h2 class="card-title mb-2">Two-factor authentication</h2>
            <p class="text-sm text-gray-600 mb-4">This user has MFA enabled (set up {{ $user->mfa_confirmed_at->diffForHumans() }}). Resetting will require them to set up a new authenticator app on their next login.</p>
            <form method="POST" action="{{ route('admin.users.reset-mfa', $user) }}" data-confirm="Are you sure? This user will need to set up MFA again on their next login.">
                @csrf
                <button type="submit" class="btn btn-danger">
                    Reset two-factor authentication
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
