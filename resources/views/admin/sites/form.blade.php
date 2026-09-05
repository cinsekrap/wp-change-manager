@extends('layouts.admin')
@section('title', $site->exists ? 'Edit site' : 'Add site')

@section('content')
<div class="max-w-2xl">
    <x-admin.page-header
        :title="$site->exists ? 'Edit site' : 'Add site'"
        lede="A website requests can be made against." />

    <form method="POST" action="{{ $site->exists ? route('admin.sites.update', $site) : route('admin.sites.store') }}" class="card">
        @csrf
        @if($site->exists) @method('PUT') @endif

        {{-- Site Details --}}
        <x-admin.form-section title="Site details" help="The name people choose from, and where its pages live.">

            <div>
                <label for="name" class="field-label">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $site->name) }}" required
                    class="field-input">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="domain" class="field-label">Domain</label>
                <input type="text" name="domain" id="domain" value="{{ old('domain', $site->domain) }}" required placeholder="example.nhs.uk"
                    class="field-input">
                @error('domain') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sitemap_url" class="field-label">
                    Sitemap URL <span class="font-normal text-gray-400">(optional — auto-detected from domain)</span>
                </label>
                <input type="url" name="sitemap_url" id="sitemap_url" value="{{ old('sitemap_url', $site->sitemap_url) }}" placeholder="Leave blank to auto-detect"
                    class="field-input">
                <p class="mt-1 text-xs text-gray-500">If left blank, we'll check for <code>/sitemap_index.xml</code> and <code>/sitemap.xml</code> automatically.</p>
                @error('sitemap_url') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </x-admin.form-section>

        {{-- Approvals --}}
        <x-admin.form-section title="Approvals" help="Who signs off wording changes on this site.">

            <div>
                <label class="field-label">Default Approvers</label>
                <p class="field-help">Automatically added to every new request for this site. Approvers sharing the same group name form a group — only one member of a group needs to approve.</p>
                @php
                    $approvers = old('default_approvers', $site->default_approvers ?? []);
                @endphp
                <div id="approversList" class="space-y-2">
                    @foreach($approvers as $index => $approver)
                    <div class="flex items-center space-x-2 approver-row">
                        <input type="text" name="default_approvers[{{ $index }}][name]" value="{{ $approver['name'] ?? '' }}" required placeholder="Name"
                            class="field-input flex-1">
                        <input type="email" name="default_approvers[{{ $index }}][email]" value="{{ $approver['email'] ?? '' }}" placeholder="Email (optional)"
                            class="field-input flex-1">
                        <input type="text" name="default_approvers[{{ $index }}][group]" value="{{ $approver['group'] ?? '' }}" placeholder="Group (optional)"
                            class="field-input w-40">
                        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" onclick="addApprover()" class="btn btn-secondary mt-2">+ Add approver</button>
            </div>

            <div class="pt-3 border-t border-gray-100">
                <div class="flex items-center">
                    <input type="hidden" name="requires_approval" value="0">
                    <input type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval', $site->requires_approval ?? false) ? 'checked' : '' }}
                        class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                    <label for="requires_approval" class="ml-2 text-sm text-gray-700">Require manual approval for all requests</label>
                </div>
                <p class="text-xs text-gray-500 mt-1 ml-6">When enabled, all requests will need admin review before being sent for approval, even if pre-submission checks pass.</p>
            </div>
        </x-admin.form-section>

        {{-- Default Assignee --}}
        <x-admin.form-section title="Default assignee" help="Who new requests for this site go to.">

            <div>
                <p class="field-help">New requests for this site will be automatically assigned to this user.</p>
                <select name="default_assignee_id" id="default_assignee_id"
                    class="field-input">
                    <option value="">None</option>
                    @foreach($adminUsers as $adminUser)
                        <option value="{{ $adminUser->id }}" {{ old('default_assignee_id', $site->default_assignee_id) == $adminUser->id ? 'selected' : '' }}>
                            {{ $adminUser->name }}
                        </option>
                    @endforeach
                </select>
                @error('default_assignee_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </x-admin.form-section>

        {{-- Site Status --}}
        <x-admin.form-section title="Availability" help="Whether this site can be chosen when making a request.">

            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $site->is_active ?? true) ? 'checked' : '' }}
                    class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Active</label>
            </div>
            <p class="text-xs text-gray-500 -mt-3 ml-6">Inactive sites won't appear in the public wizard.</p>
        </x-admin.form-section>

        <x-admin.form-actions>
            <button type="submit" class="btn btn-primary">{{ $site->exists ? 'Save changes' : 'Add site' }}</button>
            <a href="{{ route('admin.sites.index') }}" class="btn btn-quiet">Cancel</a>
        </x-admin.form-actions>
    </form>
</div>
<script>
function addApprover() {
    const list = document.getElementById('approversList');
    const idx = list.children.length;
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 approver-row';
    div.innerHTML = `<input type="text" name="default_approvers[${idx}][name]" required placeholder="Name" class="field-input flex-1">` +
        `<input type="email" name="default_approvers[${idx}][email]" placeholder="Email (optional)" class="field-input flex-1">` +
        `<input type="text" name="default_approvers[${idx}][group]" placeholder="Group (optional)" class="field-input w-40">` +
        `<button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm px-2">&times;</button>`;
    list.appendChild(div);
    div.querySelector('input').focus();
}
</script>
@endsection
