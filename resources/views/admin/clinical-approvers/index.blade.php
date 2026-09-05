@extends('layouts.admin')
@section('title', 'Clinical Approvers')

@section('content')
<x-admin.page-header title="Clinical approvers" lede="The named people who may sign off content as clinically safe.">
    <a href="{{ route('admin.clinical-approvers.create') }}" class="btn btn-primary">Add approver</a>
</x-admin.page-header>

<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Areas of expertise</th>
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
                <td class="text-gray-600 max-w-md">{{ $approver->areas_of_expertise ?: '—' }}</td>
                <td>
                    @include('admin.partials.active-badge', ['active' => $approver->is_active])
                </td>
                <td class="text-right">
                    <a href="{{ route('admin.clinical-approvers.edit', $approver) }}" class="text-hcrg-burgundy hover:underline">Edit</a>
                    @if($approver->is_active)
                        <form method="POST" action="{{ route('admin.clinical-approvers.destroy', $approver) }}" class="inline ml-3"
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
                    No clinical approvers yet. Content cannot be sent for approval until there is at least one.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $approvers->links() }}</div>
@endsection
