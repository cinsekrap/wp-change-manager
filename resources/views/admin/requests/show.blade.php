@extends('layouts.admin')
@section('title', $changeRequest->reference)

@section('content')
@include('admin.requests.partials._header')

@include('admin.requests.partials._alerts')

@php
    // A brief belongs to content. Change and access requests get two tabs, not an
    // empty third one.
    $tabs = $changeRequest->isContentRequest()
        ? ['work' => 'The work', 'brief' => 'Brief', 'history' => 'History']
        : ['work' => 'The work', 'history' => 'History'];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-admin.tabs :tabs="$tabs">
            <div data-tab-panel="work" class="space-y-6">
                @if($changeRequest->isAccessRequest())
                    @include('admin.requests.partials._summary-card-access')
                @else
                    @include('admin.requests.partials._summary-card')

                    @include('admin.requests.partials._content-draft')

                    @unless($changeRequest->isContentRequest())
                        @include('admin.requests.partials._items')
                        @include('admin.requests.partials._check-answers')
                    @endunless
                @endif

                @include('admin.requests.partials._add-note')
            </div>

            @if($changeRequest->isContentRequest())
                <div data-tab-panel="brief" class="space-y-6" hidden>
                    @include('admin.requests.partials._content-brief')
                    @include('admin.requests.partials._check-answers')
                </div>
            @endif

            <div data-tab-panel="history" class="space-y-6" hidden>
                @include('admin.requests.partials._activity')

                @include('admin.requests.partials._audit-trail')

                @unless($changeRequest->isAccessRequest())
                    @include('admin.requests.partials._sidebar-page-history')
                @endunless
            </div>
        </x-admin.tabs>
    </div>

    {{-- Only what you change from this page. Everything read rather than edited
         lives in the tabs. --}}
    <div class="space-y-4">
        @include('admin.requests.partials._sidebar-status')

        @if($changeRequest->isAccessRequest())
            @include('admin.requests.partials._sidebar-training')
        @endif

        @include('admin.requests.partials._sidebar-approvals')
    </div>
</div>
@endsection
