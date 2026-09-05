@extends('layouts.admin')
@section('title', $approver->exists ? 'Edit Funding Approver' : 'Add Funding Approver')

@section('content')
<a href="{{ route('admin.funding-approvers.index') }}" class="text-sm text-hcrg-burgundy hover:underline">← Back to funding approvers</a>
<h1 class="page-title mt-2 mb-6">{{ $approver->exists ? 'Edit' : 'Add' }} Funding Approver</h1>

<form method="POST" action="{{ $approver->exists ? route('admin.funding-approvers.update', $approver) : route('admin.funding-approvers.store') }}" class="card card-body max-w-2xl">
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
                placeholder="e.g. Head of Communications"
                class="field-input">
            @error('job_title') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="remit" class="field-label">Remit</label>
            <p class="field-help">
                What budget they hold. Shown when someone picks an approver.
            </p>
            <textarea name="remit" id="remit" rows="3"
                placeholder="e.g. Community services content budget, BSW and Bath"
                class="field-input">{{ old('remit', $approver->remit) }}</textarea>
            @error('remit') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approver->is_active) ? 'checked' : '' }}
                class="h-4 w-4 text-hcrg-burgundy border-gray-300 rounded accent-hcrg-burgundy">
            <span class="text-sm text-gray-700">Available to be asked to fund work</span>
        </label>
    </div>

    <div class="mt-6 pt-5 border-t border-gray-100">
        <button type="submit" class="btn btn-primary">
            {{ $approver->exists ? 'Save changes' : 'Add approver' }}
        </button>
    </div>
</form>
@endsection
