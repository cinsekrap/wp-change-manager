@extends('layouts.admin')
@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password @if($user->exists) <span class="text-gray-400 font-normal">(leave blank to keep current)</span> @endif
            </label>
            <input type="password" name="password" id="password" {{ $user->exists ? '' : 'required' }}
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
        </div>

        <div class="flex items-center space-x-6">
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
            <div class="flex items-center">
                <input type="hidden" name="is_admin" value="0">
                <input type="checkbox" name="is_admin" id="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_admin" class="ml-2 text-sm text-gray-700">Admin access</label>
            </div>
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                {{ $user->exists ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>

    {{-- Reset MFA section (only for existing users with MFA enabled) --}}
    @if($user->exists && $user->hasMfaEnabled())
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-bold text-gray-900 mb-2">Two-factor authentication</h2>
            <p class="text-sm text-gray-600 mb-4">This user has MFA enabled (set up {{ $user->mfa_confirmed_at->diffForHumans() }}). Resetting will require them to set up a new authenticator app on their next login.</p>
            <form method="POST" action="{{ route('admin.users.reset-mfa', $user) }}" onsubmit="return confirm('Are you sure? This user will need to set up MFA again on their next login.')">
                @csrf
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 text-sm font-medium">
                    Reset two-factor authentication
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
