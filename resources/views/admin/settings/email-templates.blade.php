@extends('layouts.admin')
@section('title', 'Email Templates')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Email Templates</h1>
        <p class="mt-1 text-sm text-gray-500">Customise the subject lines and body text of automated emails. Use placeholders to insert dynamic content.</p>
    </div>
    <a href="{{ route('admin.settings.mail') }}" class="text-sm text-hcrg-burgundy hover:underline">&larr; Mail Settings</a>
</div>

<form method="POST" action="{{ route('admin.settings.email-templates.update') }}" id="templateForm">
    @csrf
    @method('PUT')

    <div class="space-y-6">
        @foreach($templates as $key => $tpl)
        <div class="bg-white rounded-lg shadow" id="panel-{{ $key }}">
            {{-- Accordion header --}}
            <button type="button" onclick="togglePanel('{{ $key }}')" class="w-full flex items-center justify-between px-6 py-4 text-left focus:outline-none">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $tpl['name'] }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $tpl['description'] }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    @if($tpl['subject'] || $tpl['body'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-hcrg-burgundy/10 text-hcrg-burgundy">Customised</span>
                    @endif
                    <svg id="chevron-{{ $key }}" class="w-5 h-5 text-gray-400 transition-transform duration-200{{ $loop->first ? ' rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>

            {{-- Accordion body --}}
            <div id="body-{{ $key }}" class="border-t border-gray-100 px-6 py-5 space-y-5{{ $loop->first ? '' : ' hidden' }}">
                {{-- Placeholders --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available placeholders</label>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($tpl['placeholders'] as $ph)
                        <button type="button"
                            onclick="insertPlaceholder('{{ $key }}', '{{ $ph }}')"
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-hcrg-burgundy/10 hover:text-hcrg-burgundy transition-colors cursor-pointer">
                            {{ '{' . $ph . '}' }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label for="subject_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">Subject line</label>
                    <input type="text"
                        name="templates[{{ $key }}][subject]"
                        id="subject_{{ $key }}"
                        data-template="{{ $key }}"
                        data-field="subject"
                        value="{{ old("templates.{$key}.subject", $tpl['subject']) }}"
                        placeholder="{{ $tpl['default_subject'] }}"
                        class="template-field w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy text-sm"
                        onfocus="setActiveField(this)">
                    <p class="mt-1 text-xs text-gray-400">Default: {{ $tpl['default_subject'] }}</p>
                </div>

                {{-- Body --}}
                <div>
                    <label for="body_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">Body text</label>
                    <textarea
                        name="templates[{{ $key }}][body]"
                        id="body_{{ $key }}"
                        data-template="{{ $key }}"
                        data-field="body"
                        rows="4"
                        placeholder="{{ $tpl['default_body'] }}"
                        class="template-field w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy text-sm"
                        onfocus="setActiveField(this)">{{ old("templates.{$key}.body", $tpl['body']) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Default: {{ $tpl['default_body'] }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <div class="flex items-center space-x-3">
                        @if($tpl['subject'] || $tpl['body'])
                        <button type="button" onclick="resetTemplate('{{ $key }}')" class="text-sm text-red-600 hover:text-red-800 font-medium">
                            Reset to default
                        </button>
                        @else
                        <span class="text-sm text-gray-400">Using default template</span>
                        @endif
                    </div>
                    <a href="{{ route('admin.settings.mail.preview', str_replace('_', '-', $key)) }}" target="_blank" class="text-sm text-hcrg-burgundy hover:underline">
                        Preview email &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6 flex items-center space-x-3">
        <button type="submit" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full hover:bg-[#9A1B4B] text-sm font-medium">
            Save All Templates
        </button>
    </div>
</form>

{{-- Hidden reset form --}}
<form method="POST" action="{{ route('admin.settings.email-templates.reset') }}" id="resetForm" class="hidden">
    @csrf
    <input type="hidden" name="template" id="resetTemplateKey">
</form>

<script>
var activeField = null;

function togglePanel(key) {
    var body = document.getElementById('body-' + key);
    var chevron = document.getElementById('chevron-' + key);
    body.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
}

function setActiveField(el) {
    activeField = el;
}

function insertPlaceholder(templateKey, placeholder) {
    var tag = '{' + placeholder + '}';

    // If no field is focused for this template, default to the subject field
    if (!activeField || activeField.dataset.template !== templateKey) {
        activeField = document.getElementById('subject_' + templateKey);
    }

    var field = activeField;
    var start = field.selectionStart;
    var end = field.selectionEnd;
    var value = field.value;

    field.value = value.substring(0, start) + tag + value.substring(end);
    field.selectionStart = field.selectionEnd = start + tag.length;
    field.focus();
}

function resetTemplate(key) {
    if (confirm('Reset this template to the default subject and body? Your customisations will be removed.')) {
        document.getElementById('resetTemplateKey').value = key;
        document.getElementById('resetForm').submit();
    }
}
</script>
@endsection
