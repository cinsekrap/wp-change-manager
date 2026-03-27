@extends('layouts.admin')
@section('title', 'Sites')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Sites</h1>
    <a href="{{ route('admin.sites.create') }}" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">Add Site</a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pages</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($sites as $site)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-6 py-4 font-medium text-gray-900">{{ $site->name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $site->domain }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ $site->sitemap_pages_count }}</td>
                <td class="px-6 py-4">
                    @include('admin.partials.active-badge', ['active' => $site->is_active])
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                    @if($site->sitemap_url)
                    <form method="POST" action="{{ route('admin.sites.refresh', $site) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-green-600 hover:text-green-800">Refresh Sitemap</button>
                    </form>
                    @endif
                    <a href="{{ route('admin.sites.edit', $site) }}" class="text-sm text-hcrg-burgundy hover:text-[#9A1B4B]">Edit</a>
                    <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" class="inline" onsubmit="return confirm('Delete this site?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No sites yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $sites->links() }}</div>
@endsection
