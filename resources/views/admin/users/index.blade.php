@extends('layouts.admin')
@section('title', 'Admins')

@section('content')
<x-admin.page-header title="Admins" lede="People who can sign in and work on requests.">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add admin</a>
</x-admin.page-header>

<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="font-medium text-gray-900">
                    {{ $user->name }}
                    @if($user->hasMfaEnabled())
                        <span class="inline-flex items-center ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700" title="Two-factor authentication enabled">
                            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            MFA
                        </span>
                    @endif
                </td>
                <td class="text-gray-600">{{ $user->email }}</td>
                <td>
                    @if($user->isSuperAdmin())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-hcrg-burgundy/20 text-hcrg-burgundy">Super Admin</span>
                    @elseif($user->isEditor())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-hcrg-burgundy/10 text-hcrg-burgundy">Editor</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">No Access</span>
                    @endif
                </td>
                <td>
                    @include('admin.partials.active-badge', ['active' => $user->is_active])
                </td>
                <td class="text-right space-x-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm="Delete this user?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
