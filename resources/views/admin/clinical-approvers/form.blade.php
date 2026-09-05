@extends('layouts.admin')
@section('title', $approver->exists ? 'Edit Clinical Approver' : 'Add Clinical Approver')

@section('content')
<a href="{{ route('admin.clinical-approvers.index') }}" class="text-sm text-hcrg-burgundy hover:underline">← Back to clinical approvers</a>
<h1 class="page-title mt-2 mb-6">{{ $approver->exists ? 'Edit' : 'Add' }} Clinical Approver</h1>

<form method="POST" action="{{ $approver->exists ? route('admin.clinical-approvers.update', $approver) : route('admin.clinical-approvers.store') }}" class="card card-body max-w-2xl">
    @csrf
    @if($approver->exists) @method('PUT') @endif

    <div class="space-y-5">
        <div>
            <label for="name" class="field-label">Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" id="name" required value="{{ old('name', $approver->name) }}"
                class="field-input">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" id="email" required value="{{ old('email', $approver->email) }}"
                class="field-input">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="job_title" class="field-label">Job title</label>
            <input type="text" name="job_title" id="job_title" value="{{ old('job_title', $approver->job_title) }}"
                placeholder="e.g. Clinical Lead, Community Nursing"
                class="field-input">
            @error('job_title') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="areas_of_expertise" class="field-label">Areas of expertise</label>
            <p class="field-help">
                Shown to the content designer when they pick an approver, so they can tell whether this is the
                right person to ask. Write it however is most useful — services, conditions, age groups.
            </p>
            <textarea name="areas_of_expertise" id="areas_of_expertise" rows="3"
                placeholder="e.g. Continence, wound care, long-term conditions in older adults"
                class="field-input">{{ old('areas_of_expertise', $approver->areas_of_expertise) }}</textarea>
            @error('areas_of_expertise') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approver->is_active) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">Available to be sent content for approval</span>
        </label>
    </div>

    <div class="mt-6 pt-5 border-t border-gray-100">
        <button type="submit" class="btn btn-primary">
            {{ $approver->exists ? 'Save changes' : 'Add approver' }}
        </button>
    </div>
</form>
@endsection
