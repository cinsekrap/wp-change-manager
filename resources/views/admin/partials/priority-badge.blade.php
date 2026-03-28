@php
$priorityColors = [
    'low' => 'bg-gray-100 text-gray-600',
    'normal' => 'bg-hcrg-burgundy/10 text-hcrg-burgundy',
    'high' => 'bg-amber-100 text-amber-800',
    'urgent' => 'bg-red-100 text-red-800',
];
$color = $priorityColors[$priority ?? 'normal'] ?? 'bg-gray-100 text-gray-600';
@endphp
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    {{ ucfirst($priority ?? 'normal') }}
</span>
