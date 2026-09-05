@extends('layouts.admin')
@section('title', $approver->exists ? 'Edit approver' : 'Add approver')

@section('content')
<x-admin.page-header
    :title="$approver->exists ? 'Edit approver' : 'Add approver'"
    lede="Someone who can sign off content as clinically safe." />

<form method="POST" action="{{ $approver->exists ? route('admin.clinical-approvers.update', $approver) : route('admin.clinical-approvers.store') }}" class="card max-w-3xl">
    @csrf
    @if($approver->exists) @method('PUT') @endif

    <x-admin.form-section title="Who they are" help="Their name and role appear on the record of what they signed off.">
        <div class="field">
            <label for="name" class="field-label">Name <span class="text-status-error">*</span></label>
            <input type="text" name="name" id="name" required value="{{ old('name', $approver->name) }}" class="field-input">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="email" class="field-label">Email <span class="text-status-error">*</span></label>
            <input type="email" name="email" id="email" required value="{{ old('email', $approver->email) }}" class="field-input">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="field mb-0">
            <label for="job_title" class="field-label">Job title</label>
            <input type="text" name="job_title" id="job_title" value="{{ old('job_title', $approver->job_title) }}"
                placeholder="e.g. Clinical Lead, Community Nursing" class="field-input">
            @error('job_title') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="Their expertise" help="Shown when someone picks an approver, so they can tell this is the right person to ask.">
        <div class="field mb-0">
            <label for="areas_of_expertise" class="field-label">Areas of expertise</label>
            <textarea name="areas_of_expertise" id="areas_of_expertise" rows="3" placeholder="e.g. Continence, wound care, long-term conditions in older adults"
                class="field-input">{{ old('areas_of_expertise', $approver->areas_of_expertise) }}</textarea>
            @error('areas_of_expertise') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </x-admin.form-section>

    <x-admin.form-section title="Availability">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approver->is_active) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">Available to be sent content for approval</span>
        </label>
    </x-admin.form-section>

    <x-admin.form-actions>
        <button type="submit" class="btn btn-primary">{{ $approver->exists ? 'Save changes' : 'Add approver' }}</button>
        <a href="{{ route('admin.clinical-approvers.index') }}" class="btn btn-quiet">Cancel</a>
    </x-admin.form-actions>
</form>
@endsection
