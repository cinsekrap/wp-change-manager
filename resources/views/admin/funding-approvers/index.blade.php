@extends('layouts.admin')
@section('title', 'Funding Approvers')

@section('content')
<div class="flex justify-between items-center mb-2">
    <h1 class="text-2xl font-bold text-gray-900">Funding Approvers</h1>
    <a href="{{ route('admin.funding-approvers.create') }}" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add Approver</a>
</div>
<p class="text-sm text-gray-500 mb-6 max-w-3xl">
    The people who can agree to spend content design hours. A funding ask names one of them, and the record of what was agreed keeps their name.
</p>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remit</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($approvers as $approver)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-6 py-4">
                    <p class="text-sm font-medium text-gray-900">{{ $approver->name }}</p>
                    @if($approver->job_title)
                        <p class="text-xs text-hcrg-grey-400">{{ $approver->job_title }}</p>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $approver->email }}</td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-md">{{ $approver->remit ?: '—' }}</td>
                <td class="px-6 py-4">
                    @include('admin.partials.active-badge', ['active' => $approver->is_active])
                </td>
                <td class="px-6 py-4 text-right text-sm">
                    <a href="{{ route('admin.funding-approvers.edit', $approver) }}" class="text-hcrg-burgundy hover:underline">Edit</a>
                    @if($approver->is_active)
                        <form method="POST" action="{{ route('admin.funding-approvers.destroy', $approver) }}" class="inline ml-3"
                              data-confirm="Deactivate {{ $approver->name }}? Past approvals will still name them.">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Deactivate</button>
                        </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                    No funding approvers yet. Content cannot be sent for approval until there is at least one.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $approvers->links() }}</div>
@endsection
