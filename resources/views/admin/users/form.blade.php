@extends('layouts.admin')
@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
<div class="max-w-2xl">
    <h1 class="page-title mb-6">{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card card-body space-y-5">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div>
            <label for="name" class="field-label">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                class="field-input">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                class="field-input">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">
                Password @if($user->exists) <span class="text-gray-400 font-normal">(leave blank to keep current)</span> @endif
            </label>
            <input type="password" name="password" id="password" {{ $user->exists ? '' : 'required' }}
                class="field-input">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="field-input">
        </div>

        <div class="flex items-center space-x-6">
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
        </div>

        <div>
            <label for="role" class="field-label">Role</label>
            <select name="role" id="role"
                class="field-input">
                <option value="" {{ old('role', $user->role) === null ? 'selected' : '' }}>No admin access</option>
                <option value="editor" {{ old('role', $user->role) === 'editor' ? 'selected' : '' }}>Editor</option>
                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            </select>
            @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="btn btn-primary">
                {{ $user->exists ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
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
