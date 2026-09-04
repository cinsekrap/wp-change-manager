@extends('layouts.admin')
@section('title', $approver->exists ? 'Edit Clinical Approver' : 'Add Clinical Approver')

@section('content')
<a href="{{ route('admin.clinical-approvers.index') }}" class="text-sm text-hcrg-burgundy hover:underline">← Back to clinical approvers</a>
<h1 class="text-2xl font-bold text-gray-900 mt-2 mb-6">{{ $approver->exists ? 'Edit' : 'Add' }} Clinical Approver</h1>

<form method="POST" action="{{ $approver->exists ? route('admin.clinical-approvers.update', $approver) : route('admin.clinical-approvers.store') }}" class="bg-white rounded-lg shadow p-6 max-w-2xl">
    @csrf
    @if($approver->exists) @method('PUT') @endif

    <div class="space-y-5">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" required value="{{ old('name', $approver->name) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" id="email" required value="{{ old('email', $approver->email) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1">Job title</label>
            <input type="text" name="job_title" id="job_title" value="{{ old('job_title', $approver->job_title) }}"
                placeholder="e.g. Clinical Lead, Community Nursing"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">
            @error('job_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="areas_of_expertise" class="block text-sm font-medium text-gray-700 mb-1">Areas of expertise</label>
            <p class="text-xs text-gray-500 mb-2">
                Shown to the content designer when they pick an approver, so they can tell whether this is the
                right person to ask. Write it however is most useful — services, conditions, age groups.
            </p>
            <textarea name="areas_of_expertise" id="areas_of_expertise" rows="3"
                placeholder="e.g. Continence, wound care, long-term conditions in older adults"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-hcrg-burgundy focus:border-hcrg-burgundy">{{ old('areas_of_expertise', $approver->areas_of_expertise) }}</textarea>
            @error('areas_of_expertise') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approver->is_active) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">Available to be sent content for approval</span>
        </label>
    </div>

    <div class="mt-6 pt-5 border-t border-gray-100">
        <button type="submit" class="bg-hcrg-burgundy text-white px-6 py-2 rounded-full text-sm font-medium hover:bg-[#9A1B4B]">
            {{ $approver->exists ? 'Save changes' : 'Add approver' }}
        </button>
    </div>
</form>
@endsection
