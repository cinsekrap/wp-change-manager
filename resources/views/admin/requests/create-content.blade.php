@extends('layouts.admin')
@section('title', 'New content')

@section('content')
<x-admin.page-header title="New content"
    lede="For content the team wants, rather than something someone has asked for." />

<form method="POST" action="{{ route('admin.requests.content.store') }}" class="card max-w-3xl">
    @csrf

    <x-admin.form-section title="What it is" help="Only the working title is needed. Fill in whatever else you know.">
        <div class="space-y-5">
            <div>
                <label for="page_title" class="field-label">Working title <span class="text-red-500">*</span></label>
                <p class="field-help">
                    What the team calls it while it's being written. The public title is set later.
                </p>
                <input type="text" name="page_title" id="page_title" required maxlength="255"
                    value="{{ old('page_title') }}"
                    placeholder="e.g. First appointment explainer for community podiatry"
                    class="field-input">
                @error('page_title') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="field-label">What job does it do?</span>
                <p class="field-help">Sets the page type it lands on. Skip if you haven't decided.</p>
                <div class="space-y-2">
                    @foreach($contentTypes as $key => $type)
                        <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:border-hcrg-burgundy has-[:checked]:border-hcrg-burgundy has-[:checked]:bg-hcrg-grey-100">
                            <input type="radio" name="content_type" value="{{ $key }}" @checked(old('content_type') === $key)
                                class="mt-0.5 h-4 w-4 text-hcrg-burgundy border-gray-300 accent-hcrg-burgundy">
                            <span>
                                <span class="block text-sm text-hcrg-charcoal">{{ $type['label'] }}</span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ $type['help'] }}</span>
                            </span>
                            <span class="ml-auto text-xs text-hcrg-grey-400 border border-hcrg-grey-200 rounded-full px-3 py-1 whitespace-nowrap self-start">{{ $type['tag'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('content_type') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="Where it lives" help="Content can go on more than one site.">
        <p class="text-xs text-gray-500 mb-4">
            Content can go on more than one site. Leave blank if you haven't decided.
        </p>

        <div class="space-y-5">
            <div>
                <label for="site_id" class="field-label">Main home</label>
                <select name="site_id" id="site_id"
                    class="field-input">
                    <option value="">Not yet decided</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('site_id') === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                @error('site_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="block text-sm font-medium text-gray-700 mb-2">Also appears on</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($sites as $site)
                        <label class="cursor-pointer">
                            <input type="checkbox" class="sr-only peer" name="additional_site_ids[]" value="{{ $site->id }}"
                                @checked(in_array($site->id, old('additional_site_ids', [])))>
                            <span class="inline-block text-sm px-4 py-2 rounded-full bg-hcrg-grey-100 text-hcrg-charcoal peer-checked:bg-hcrg-burgundy peer-checked:text-white">{{ $site->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="The brief" help="What the clinical approver reads alongside the copy.">
        <p class="text-xs text-gray-500 mb-4">
            What the clinical approver reads alongside the copy.
        </p>

        <div class="space-y-5">
            <div>
                <label for="briefAchieve" class="field-label">What is it trying to achieve?</label>
                <textarea name="brief[achieve]" id="briefAchieve" rows="3"
                    placeholder="e.g. Stop people ringing to ask what happens at their first appointment."
                    class="field-input">{{ old('brief.achieve') }}</textarea>
                @error('brief.achieve') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="block text-sm font-medium text-gray-700 mb-2">Who is it for?</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($audiences as $value => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" class="sr-only peer" name="brief[audience][]" value="{{ $value }}"
                                @checked(in_array($value, old('brief.audience', [])))>
                            <span class="inline-block text-sm px-4 py-2 rounded-full bg-hcrg-grey-100 text-hcrg-charcoal peer-checked:bg-hcrg-burgundy peer-checked:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="briefKnowOrDo" class="field-label">What should they know, or do?</label>
                <textarea name="brief[know_or_do]" id="briefKnowOrDo" rows="2"
                    class="field-input">{{ old('brief.know_or_do') }}</textarea>
                @error('brief.know_or_do') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="briefMeasure" class="field-label">How will we know it worked?</label>
                <input type="text" name="brief[measure]" id="briefMeasure" value="{{ old('brief.measure') }}"
                    class="field-input">
                @error('brief.measure') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="briefExists" class="field-label">Does something like this already exist?</label>
                <select name="brief[already_exists]" id="briefExists"
                    class="field-input">
                    <option value="">Not checked yet</option>
                    @foreach(['no' => 'No', 'yes' => 'Yes, something similar', 'not_sure' => 'Not sure'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('brief.already_exists') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <textarea name="brief[already_exists_detail]" rows="2" placeholder="What you found, and why this is still needed"
                    class="field-input mt-2">{{ old('brief.already_exists_detail') }}</textarea>
                @error('brief.already_exists_detail') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="The copy" help="Only if you've already written it. Editing approved copy withdraws the approval.">
        <p class="text-xs text-gray-500 mb-4">
            Only if you've already written it. Editing approved copy withdraws the approval.
        </p>
        <textarea name="draft_content" id="draft_content" rows="8"
            class="field-input">{{ old('draft_content') }}</textarea>
        @error('draft_content') <p class="field-error">{{ $message }}</p> @enderror
        @include('partials.reading-age', ['field' => 'draft_content'])
    </x-admin.form-section>

    <x-admin.form-section title="Who and when" help="Ownership, priority and any date it is needed by.">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="assigned_to" class="field-label">Owner</label>
                <select name="assigned_to" id="assigned_to"
                    class="field-input">
                    <option value="">Unassigned</option>
                    @foreach($adminUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_to', auth()->id()) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="priority" class="field-label">Priority</label>
                <select name="priority" id="priority"
                    class="field-input">
                    @foreach(\App\Models\ChangeRequest::PRIORITIES as $value)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estimated_hours" class="field-label">Estimated hours</label>
                <input type="number" name="estimated_hours" id="estimated_hours" step="0.5" min="0" max="9999"
                    value="{{ old('estimated_hours') }}" placeholder="e.g. 8"
                    class="field-input">
                <p class="mt-1 text-xs text-gray-400">Internal only. Leave blank until you've sized it up.</p>
                @error('estimated_hours') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deadline_date" class="field-label">Needed by</label>
                <input type="date" name="deadline_date" id="deadline_date" value="{{ old('deadline_date') }}"
                    class="field-input">
                @error('deadline_date') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deadline_reason" class="field-label">Why then?</label>
                <input type="text" name="deadline_reason" id="deadline_reason" value="{{ old('deadline_reason') }}"
                    class="field-input">
                @error('deadline_reason') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-admin.form-section>

    <x-admin.form-actions>
        <button type="submit" class="btn btn-primary">Create content</button>
        <a href="{{ route('admin.requests.index') }}" class="btn btn-quiet">Cancel</a>
    </x-admin.form-actions>
</form>
@endsection
