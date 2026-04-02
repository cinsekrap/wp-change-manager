@extends('layouts.admin')
@section('title', $changeRequest->reference)

@section('content')
@include('admin.requests.partials._header')

@include('admin.requests.partials._alerts')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-6">
        @include('admin.requests.partials._summary-card')

        @include('admin.requests.partials._items')

        @include('admin.requests.partials._check-answers')

        @include('admin.requests.partials._activity')

        @include('admin.requests.partials._audit-trail')
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        @include('admin.requests.partials._sidebar-status')

        @include('admin.requests.partials._sidebar-approvals')

        @include('admin.requests.partials._sidebar-tags')

        @include('admin.requests.partials._sidebar-links')

        @include('admin.requests.partials._sidebar-page-history')
    </div>
</div>
@endsection
