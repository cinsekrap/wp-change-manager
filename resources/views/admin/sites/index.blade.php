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
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap w-16">Pages</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignee</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-48">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($sites as $site)
            <tr class="hover:bg-gray-50 even:bg-gray-50/50">
                <td class="px-4 py-4 font-medium text-gray-900 truncate max-w-[180px]">{{ $site->name }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 truncate max-w-[160px]">{{ $site->domain }}</td>
                <td class="px-3 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $site->sitemap_pages_count }}</td>
                <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $site->defaultAssignee?->name ?? '—' }}</td>
                <td class="px-3 py-4">
                    @include('admin.partials.active-badge', ['active' => $site->is_active])
                </td>
                <td class="px-4 py-4 text-right whitespace-nowrap">
                    <div class="flex flex-col items-end space-y-1">
                        <button type="button" onclick="refreshSitemap(this, {{ $site->id }})" class="refresh-btn inline-flex items-center text-xs text-green-600 hover:text-green-800 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1 refresh-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="refresh-label">Refresh Sitemap ({{ $site->sitemap_pages_count }} pages)</span>
                        </button>
                        <a href="{{ route('admin.sites.edit', $site) }}" class="inline-flex items-center text-xs text-hcrg-burgundy hover:text-[#9A1B4B] transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.sites.destroy', $site) }}" onsubmit="return confirm('Delete this site?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center text-xs text-red-600 hover:text-red-800 transition-colors">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No sites yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $sites->links() }}</div>

<script>
async function refreshSitemap(btn, siteId) {
    const label = btn.querySelector('.refresh-label');
    const icon = btn.querySelector('.refresh-icon');
    const originalText = label.textContent;

    btn.disabled = true;
    btn.classList.add('opacity-50');
    icon.classList.add('animate-spin');
    label.textContent = 'Refreshing...';

    try {
        const res = await fetch(`/admin/sites/${siteId}/refresh`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();

        if (data.success) {
            label.textContent = data.message;
            btn.classList.remove('text-green-600', 'hover:text-green-800');
            btn.classList.add('text-emerald-600');
            setTimeout(() => location.reload(), 1500);
        } else {
            label.textContent = data.message || 'Refresh failed';
            btn.classList.remove('text-green-600');
            btn.classList.add('text-red-600');
        }
    } catch (e) {
        label.textContent = 'Error — try again';
        btn.classList.remove('text-green-600');
        btn.classList.add('text-red-600');
    } finally {
        icon.classList.remove('animate-spin');
        btn.disabled = false;
        btn.classList.remove('opacity-50');
    }
}
</script>
@endsection
