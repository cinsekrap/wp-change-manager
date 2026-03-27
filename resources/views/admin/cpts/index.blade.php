@extends('layouts.admin')
@section('title', 'CPT Types')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">CPT Types</h1>
    <a href="{{ route('admin.cpts.create') }}" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add CPT Type</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Content Areas</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($cpts as $cpt)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-6 py-4 text-sm text-gray-600">{{ $cpt->sort_order }}</td>
                <td class="px-6 py-4 font-mono text-sm text-gray-900">{{ $cpt->slug }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ $cpt->name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    @php $areaCount = count($cpt->form_config['content_areas'] ?? []); @endphp
                    {{ $areaCount > 0 ? $areaCount . ' ' . Str::plural('area', $areaCount) : '—' }}
                </td>
                <td class="px-6 py-4">
                    @include('admin.partials.active-badge', ['active' => $cpt->is_active])
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="{{ route('admin.cpts.edit', $cpt) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    <form method="POST" action="{{ route('admin.cpts.destroy', $cpt) }}" class="inline" onsubmit="return confirm('Delete this CPT type?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No CPT types yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $cpts->links() }}</div>
@endsection
