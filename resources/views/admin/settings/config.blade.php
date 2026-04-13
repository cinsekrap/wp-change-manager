@extends('layouts.admin')
@section('title', 'Import / Export Configuration')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Import / Export Configuration</h1>
    <p class="mt-1 text-sm text-gray-500">Transfer configuration between instances. Export settings from one environment and import them on another.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Export --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Export Configuration</h2>
        <p class="text-sm text-gray-500 mb-5">Download a JSON file containing your selected configuration. Sensitive data (passwords, secrets) is never included.</p>

        <form method="POST" action="{{ route('admin.settings.config.export') }}">
            @csrf

            <div class="space-y-3 mb-6">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="sections[]" value="cpt_types" checked
                        class="w-4 h-4 rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">
                    <span class="text-sm text-gray-700">CPT Types <span class="text-gray-400">({{ $counts['cpt_types'] }})</span></span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="sections[]" value="check_questions" checked
                        class="w-4 h-4 rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">
                    <span class="text-sm text-gray-700">Check Questions <span class="text-gray-400">({{ $counts['check_questions'] }})</span></span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="sections[]" value="settings" checked
                        class="w-4 h-4 rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">
                    <span class="text-sm text-gray-700">Settings <span class="text-gray-400">(non-sensitive)</span></span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="sections[]" value="email_templates" checked
                        class="w-4 h-4 rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">
                    <span class="text-sm text-gray-700">Email Templates <span class="text-gray-400">(customised only)</span></span>
                </label>

            </div>

            <button type="submit" class="inline-flex items-center bg-hcrg-burgundy text-white px-5 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Download Export
            </button>
        </form>
    </div>

    {{-- Import --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Import Configuration</h2>
        <p class="text-sm text-gray-500 mb-5">Upload a previously exported JSON file. Existing items will be updated and new ones created. Nothing will be deleted.</p>

        <form method="POST" action="{{ route('admin.settings.config.import') }}" enctype="multipart/form-data" id="importForm">
            @csrf

            <div class="mb-5">
                <label for="config_file" class="block text-sm font-medium text-gray-700 mb-1">Configuration File</label>
                <input type="file" name="config_file" id="config_file" accept=".json" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-hcrg-burgundy/10 file:text-hcrg-burgundy hover:file:bg-hcrg-burgundy/20 focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy"
                    onchange="parseImportFile(this)">
                @error('config_file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div id="importPreview" class="hidden mb-5">
                <p class="text-sm font-medium text-gray-700 mb-2">File contains:</p>
                <div class="space-y-3" id="importSections"></div>
                <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500" id="importMeta"></p>
                </div>
            </div>

            <button type="submit" id="importBtn" disabled
                class="inline-flex items-center bg-hcrg-burgundy text-white px-5 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                onclick="return confirm('Are you sure you want to import this configuration? Existing items with matching identifiers will be updated.')">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Configuration
            </button>
        </form>
    </div>
</div>

<script>
function parseImportFile(input) {
    var preview = document.getElementById('importPreview');
    var sections = document.getElementById('importSections');
    var meta = document.getElementById('importMeta');
    var btn = document.getElementById('importBtn');

    if (!input.files || !input.files[0]) {
        preview.classList.add('hidden');
        btn.disabled = true;
        return;
    }

    var reader = new FileReader();
    reader.onload = function(e) {
        try {
            var data = JSON.parse(e.target.result);
        } catch (err) {
            preview.classList.remove('hidden');
            sections.innerHTML = '<p class="text-sm text-red-600">Invalid JSON file.</p>';
            meta.textContent = '';
            btn.disabled = true;
            return;
        }

        if (!data.version) {
            preview.classList.remove('hidden');
            sections.innerHTML = '<p class="text-sm text-red-600">Not a valid configuration file (missing version).</p>';
            meta.textContent = '';
            btn.disabled = true;
            return;
        }

        var html = '';
        var hasContent = false;

        if (data.cpt_types && data.cpt_types.length) {
            html += sectionCheckbox('cpt_types', 'CPT Types', data.cpt_types.length + ' type(s)');
            hasContent = true;
        }
        if (data.check_questions && data.check_questions.length) {
            html += sectionCheckbox('check_questions', 'Check Questions', data.check_questions.length + ' question(s)');
            hasContent = true;
        }
        if (data.settings && Object.keys(data.settings).length) {
            html += sectionCheckbox('settings', 'Settings', Object.keys(data.settings).length + ' setting(s)');
            hasContent = true;
        }
        if (data.email_templates && Object.keys(data.email_templates).length) {
            html += sectionCheckbox('email_templates', 'Email Templates', Object.keys(data.email_templates).length + ' template(s)');
            hasContent = true;
        }
        if (!hasContent) {
            html = '<p class="text-sm text-gray-500">No importable data found in file.</p>';
        }

        sections.innerHTML = html;

        var metaParts = ['Version: ' + data.version];
        if (data.exported_at) {
            metaParts.push('Exported: ' + new Date(data.exported_at).toLocaleString());
        }
        meta.textContent = metaParts.join(' | ');

        preview.classList.remove('hidden');
        btn.disabled = !hasContent;
    };
    reader.readAsText(input.files[0]);
}

function sectionCheckbox(value, label, detail) {
    return '<label class="flex items-center space-x-3 cursor-pointer">' +
        '<input type="checkbox" name="import_sections[]" value="' + value + '" checked ' +
        'class="w-4 h-4 rounded border-gray-300 text-hcrg-burgundy focus:ring-hcrg-burgundy">' +
        '<span class="text-sm text-gray-700">' + label + ' <span class="text-gray-400">(' + detail + ')</span></span>' +
        '</label>';
}
</script>
@endsection
