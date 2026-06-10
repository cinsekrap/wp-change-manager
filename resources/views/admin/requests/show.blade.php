@extends('layouts.admin')
@section('title', $changeRequest->reference)

@section('content')
@include('admin.requests.partials._header')

@include('admin.requests.partials._alerts')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-6">
        @if($changeRequest->isAccessRequest())
            @include('admin.requests.partials._summary-card-access')
        @else
            @include('admin.requests.partials._summary-card')

            @include('admin.requests.partials._items')

            @include('admin.requests.partials._check-answers')
        @endif

        @include('admin.requests.partials._activity')

        @include('admin.requests.partials._audit-trail')
    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        @include('admin.requests.partials._sidebar-status')

        @if($changeRequest->isAccessRequest())
            @include('admin.requests.partials._sidebar-training')
        @endif

        @include('admin.requests.partials._sidebar-approvals')

        @unless($changeRequest->isAccessRequest())
            @include('admin.requests.partials._sidebar-page-history')
        @endunless
    </div>
</div>
@endsection
