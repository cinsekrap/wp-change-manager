@extends('layouts.admin')
@section('title', $site->exists ? 'Edit Site' : 'Add Site')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $site->exists ? 'Edit Site' : 'Add Site' }}</h1>

    <form method="POST" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf
        @if($site->exists) @method('PUT') @endif

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $site->name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="domain" class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
            <input type="text" name="domain" id="domain" value="{{ old('domain', $site->domain) }}" required placeholder="example.nhs.uk"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('domain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="sitemap_url" class="block text-sm font-medium text-gray-700 mb-1">
                Sitemap URL <span class="font-normal text-gray-400">(optional — auto-detected from domain)</span>
            </label>
            <input type="url" name="sitemap_url" id="sitemap_url" value="{{ old('sitemap_url', $site->sitemap_url) }}" placeholder="Leave blank to auto-detect"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            <p class="mt-1 text-xs text-gray-500">If left blank, we'll check for <code>/sitemap_index.xml</code> and <code>/sitemap.xml</code> automatically.</p>
            @error('sitemap_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Default Approvers</label>
            <p class="text-xs text-gray-500 mb-2">Automatically added to every new request for this site.</p>
            @php
                $approvers = old('default_approvers', $site->default_approvers ?? []);
            @endphp
            <div id="approversList" class="space-y-2">
                @foreach($approvers as $index => $approver)
                <div class="flex items-center space-x-2 approver-row">
                    <input type="text" name="default_approvers[{{ $index }}][name]" value="{{ $approver['name'] ?? '' }}" required placeholder="Name"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <input type="email" name="default_approvers[{{ $index }}][email]" value="{{ $approver['email'] ?? '' }}" placeholder="Email (optional)"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                </div>
                @endforeach
            </div>
            <button type="button" onclick="addApprover()" class="mt-2 inline-flex items-center px-4 py-2 border border-hcrg-burgundy text-hcrg-burgundy rounded-full text-sm font-medium hover:bg-hcrg-burgundy hover:text-white transition-colors">+ Add approver</button>
        </div>

        <div>
            <label for="default_assignee_id" class="block text-sm font-medium text-gray-700 mb-1">Default Assignee</label>
            <p class="text-xs text-gray-500 mb-2">New requests for this site will be automatically assigned to this user.</p>
            <select name="default_assignee_id" id="default_assignee_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                <option value="">None</option>
                @foreach($adminUsers as $adminUser)
                    <option value="{{ $adminUser->id }}" {{ old('default_assignee_id', $site->default_assignee_id) == $adminUser->id ? 'selected' : '' }}>
                        {{ $adminUser->name }}
                    </option>
                @endforeach
            </select>
            @error('default_assignee_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $site->is_active ?? true) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
            <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
        </div>

        <div class="flex items-center space-x-3 pt-4">
            <button type="submit" class="bg-hcrg-burgundy text-white px-4 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
                {{ $site->exists ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.sites.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Cancel</a>
        </div>
    </form>
</div>
<script>
function addApprover() {
    const list = document.getElementById('approversList');
    const idx = list.children.length;
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 approver-row';
    div.innerHTML = `<input type="text" name="default_approvers[${idx}][name]" required placeholder="Name" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">` +
        `<input type="email" name="default_approvers[${idx}][email]" placeholder="Email (optional)" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">` +
        `<button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>`;
    list.appendChild(div);
    div.querySelector('input').focus();
}
</script>
@endsection
