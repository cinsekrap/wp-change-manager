@extends('layouts.public')
@section('title', 'Submit a Change Request')

@section('content')
@include('public.partials.wizard.progress-bar')

<form id="wizardForm" class="space-y-6">
    @csrf

    @include('public.partials.wizard.step-1-site')
    @include('public.partials.wizard.step-2-page')
    @include('public.partials.wizard.step-3-changes')
    @include('public.partials.wizard.step-4-questions')
    @include('public.partials.wizard.step-5-details')
    @include('public.partials.wizard.step-6-review')
</form>

@include('public.partials.wizard.loading-overlay')
@include('public.partials.wizard.navigation')
@include('public.partials.wizard.line-item-template')
@endsection

@section('scripts')
@include('public.partials.wizard.wizard-scripts')
@endsection
