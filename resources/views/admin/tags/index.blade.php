@extends('layouts.admin')
@section('title', 'Tags')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Tags</h1>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Colour</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Used on</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tags as $tag)
            <tr class="hover:bg-gray-50">
                <form method="POST" action="{{ route('admin.tags.update', $tag) }}">
                    @csrf @method('PUT')
                    <td class="px-6 py-3">
                        <input type="color" name="colour" value="{{ $tag->colour }}"
                            class="w-8 h-8 rounded border border-gray-300 cursor-pointer p-0">
                    </td>
                    <td class="px-6 py-3">
                        <input type="text" name="name" value="{{ $tag->name }}" required maxlength="100"
                            class="px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy w-48">
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $tag->change_requests_count }} {{ str('request')->plural($tag->change_requests_count) }}
                    </td>
                    <td class="px-6 py-3 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-hcrg-burgundy text-white text-xs font-medium rounded-full hover:bg-[#9A1B4B] transition-colors">
                                Save
                            </button>
                </form>
                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" onsubmit="return confirm('Delete this tag? It will be removed from all requests.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-full hover:bg-red-100 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                    No tags yet. Tags are created when you tag a change request.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
