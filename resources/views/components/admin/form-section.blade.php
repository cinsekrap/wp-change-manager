@props(['title', 'help' => null])

{{-- Section name and its explanation beside the fields, so the shape of a form is
     visible before any of it is filled in and a section can be skipped whole. --}}
<div class="flex flex-col sm:flex-row border-b border-hcrg-grey-100 last:border-b-0">
    <div class="sm:w-56 shrink-0 p-5 sm:pr-4">
        <div class="text-sm font-bold text-gray-900">{{ $title }}</div>
        @if($help)
            <p class="mt-1 text-xs text-hcrg-grey-400 leading-relaxed">{{ $help }}</p>
        @endif
    </div>
    <div class="flex-1 p-5 sm:pl-0">{{ $slot }}</div>
</div>
