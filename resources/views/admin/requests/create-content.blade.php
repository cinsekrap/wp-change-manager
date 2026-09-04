@extends('layouts.admin')
@section('title', 'New content')

@section('content')
<a href="{{ route('admin.requests.index') }}" class="text-sm text-hcrg-burgundy hover:underline">← Back to requests</a>
<h1 class="text-2xl font-bold text-gray-900 mt-2 mb-2">New content</h1>
<p class="text-sm text-gray-500 mb-6 max-w-3xl">
    For content the team has decided it wants, rather than content somebody asked for. It starts as a
    suggestion and goes through the same gates as anything from the request form. Only the working title
    is needed now &mdash; fill in whatever else you already know.
</p>

<form method="POST" action="{{ route('admin.requests.content.store') }}" class="max-w-3xl">
    @csrf

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">What it is</h2>

        <div class="space-y-5">
            <div>
                <label for="page_title" class="block text-sm font-medium text-gray-700 mb-1">Working title <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 mb-2">
                    How you and the team refer to it while it is being written &mdash; it also names this
                    piece in approval emails. The public title is set separately, once you are ready for it
                    to appear on the public list.
                </p>
                <input type="text" name="page_title" id="page_title" required maxlength="255"
                    value="{{ old('page_title') }}"
                    placeholder="e.g. First appointment explainer for community podiatry"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                @error('page_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="block text-sm font-medium text-gray-700 mb-1">What job does it do?</span>
                <p class="text-xs text-gray-500 mb-2">This sets the page type it lands on. Leave it if you have not decided.</p>
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
                @error('content_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Where it lives</h2>
        <p class="text-xs text-gray-500 mb-4">
            Content is approved once and can go on more than one site. If you have not decided yet, leave
            this alone &mdash; a home can be set later.
        </p>

        <div class="space-y-5">
            <div>
                <label for="site_id" class="block text-sm font-medium text-gray-700 mb-1">Main home</label>
                <select name="site_id" id="site_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <option value="">Not yet decided</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" @selected((string) old('site_id') === (string) $site->id)>{{ $site->name }}</option>
                    @endforeach
                </select>
                @error('site_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">The brief</h2>
        <p class="text-xs text-gray-500 mb-4">
            The same questions the request form asks. It is what the clinical approver reads to judge whether
            the copy does what it set out to do, so it is worth filling in even when you wrote it yourself.
        </p>

        <div class="space-y-5">
            <div>
                <label for="briefAchieve" class="block text-sm font-medium text-gray-700 mb-1">What is it trying to achieve?</label>
                <textarea name="brief[achieve]" id="briefAchieve" rows="3"
                    placeholder="e.g. Stop people ringing to ask what happens at their first appointment."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('brief.achieve') }}</textarea>
                @error('brief.achieve') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                <label for="briefKnowOrDo" class="block text-sm font-medium text-gray-700 mb-1">What should they know, or do?</label>
                <textarea name="brief[know_or_do]" id="briefKnowOrDo" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('brief.know_or_do') }}</textarea>
                @error('brief.know_or_do') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="briefMeasure" class="block text-sm font-medium text-gray-700 mb-1">How will we know it worked?</label>
                <input type="text" name="brief[measure]" id="briefMeasure" value="{{ old('brief.measure') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                @error('brief.measure') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="briefExists" class="block text-sm font-medium text-gray-700 mb-1">Does something like this already exist?</label>
                <select name="brief[already_exists]" id="briefExists"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <option value="">Not checked yet</option>
                    @foreach(['no' => 'No', 'yes' => 'Yes, something similar', 'not_sure' => 'Not sure'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('brief.already_exists') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <textarea name="brief[already_exists_detail]" rows="2" placeholder="What you found, and why this is still needed"
                    class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('brief.already_exists_detail') }}</textarea>
                @error('brief.already_exists_detail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">The copy</h2>
        <p class="text-xs text-gray-500 mb-4">
            Only if you have already written it. Editing it later voids any clinical approval it has been
            given, so the sign-off always belongs to the words that were actually read.
        </p>
        <textarea name="draft_content" id="draft_content" rows="8"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('draft_content') }}</textarea>
        @error('draft_content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        @include('partials.reading-age', ['field' => 'draft_content'])
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Who and when</h2>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                <select name="assigned_to" id="assigned_to"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    <option value="">Unassigned</option>
                    @foreach($adminUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_to', auth()->id()) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" id="priority"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                    @foreach(\App\Models\ChangeRequest::PRIORITIES as $value)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="deadline_date" class="block text-sm font-medium text-gray-700 mb-1">Needed by</label>
                <input type="date" name="deadline_date" id="deadline_date" value="{{ old('deadline_date') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                @error('deadline_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deadline_reason" class="block text-sm font-medium text-gray-700 mb-1">Why then?</label>
                <input type="text" name="deadline_reason" id="deadline_reason" value="{{ old('deadline_reason') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
                @error('deadline_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <button type="submit" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">Create content</button>
        <a href="{{ route('admin.requests.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
    </div>
</form>
@endsection
