@extends('layouts.admin')
@section('title', 'Content types')

@section('content')
<x-admin.page-header title="Content types" lede="The kinds of page each site has, and how requests for them behave.">
    <a href="{{ route('admin.cpts.create') }}" class="btn btn-primary">Add content type</a>
</x-admin.page-header>

<div class="card overflow-hidden">
    <table class="table">
        <thead class="bg-gray-50">
            <tr>
                <th>Order</th>
                <th>Slug</th>
                <th>Name</th>
                <th>Content Areas</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cpts as $cpt)
            <tr class="hover:bg-gray-50">
                <td class="text-gray-600">{{ $cpt->sort_order }}</td>
                <td class="font-mono text-gray-900">{{ $cpt->slug }}</td>
                <td class="text-gray-900">{{ $cpt->name }}</td>
                <td class="text-gray-600">
                    @php $areaCount = count($cpt->form_config['content_areas'] ?? []); @endphp
                    {{ $areaCount > 0 ? $areaCount . ' ' . Str::plural('area', $areaCount) : '—' }}
                </td>
                <td class="space-x-1">
                    @include('admin.partials.active-badge', ['active' => $cpt->is_active])
                    @if($cpt->request_mode === 'blocked')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Blocked</span>
                    @elseif($cpt->request_mode === 'self_service')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Self-service</span>
                    @endif
                </td>
                <td class="text-right space-x-2">
                    <a href="{{ route('admin.cpts.edit', $cpt) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    <form method="POST" action="{{ route('admin.cpts.destroy', $cpt) }}" class="inline" data-confirm="Delete this CPT type?">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="p-0">
                <x-admin.empty-state message="No content types yet. These are the kinds of page people can request changes to.">
                    <a href="{{ route('admin.cpts.create') }}" class="btn btn-primary btn-sm">Add content type</a>
                </x-admin.empty-state>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $cpts->links() }}</div>
@endsection
