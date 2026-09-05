@props(['message'])

{{-- Says what would be here, and offers the way to add it. --}}
<div class="empty-state">
    <p>{{ $message }}</p>
    {{ $slot }}
</div>
