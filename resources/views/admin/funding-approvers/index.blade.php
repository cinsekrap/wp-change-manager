@extends('layouts.admin')
@section('title', 'Funding Approvers')

@section('content')
<x-admin.page-header title="Funding approvers" lede="The people who can agree to spend content design hours.">
    <a href="{{ route('admin.funding-approvers.create') }}" class="btn btn-primary">Add approver</a>
</x-admin.page-header>

<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Remit</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($approvers as $approver)
            <tr class="hover:bg-gray-50">
                <td>
                    <p class="text-sm font-medium text-gray-900">{{ $approver->name }}</p>
                    @if($approver->job_title)
                        <p class="text-xs text-hcrg-grey-400">{{ $approver->job_title }}</p>
                    @endif
                </td>
                <td class="text-gray-600">{{ $approver->email }}</td>
                <td class="text-gray-600 max-w-md">{{ $approver->remit ?: '—' }}</td>
                <td>
                    @include('admin.partials.active-badge', ['active' => $approver->is_active])
                </td>
                <td class="text-right">
                    <a href="{{ route('admin.funding-approvers.edit', $approver) }}" class="text-hcrg-burgundy hover:underline">Edit</a>
                    @if($approver->is_active)
                        <form method="POST" action="{{ route('admin.funding-approvers.destroy', $approver) }}" class="inline ml-3"
                              data-confirm="Deactivate {{ $approver->name }}? They will no longer appear when requesting funding.">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Deactivate</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                    No funding approvers yet. Add one to request funding from the funding page.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $approvers->links() }}</div>
@endsection
