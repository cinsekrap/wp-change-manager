@php
$colors = [
    'requested' => 'bg-amber-100 text-amber-800',
    'requires_referral' => 'bg-pink-100 text-pink-800',
    'referred' => 'bg-orange-100 text-orange-800',
    'approved' => 'bg-hcrg-burgundy/20 text-hcrg-burgundy',
    'scheduled' => 'bg-purple-100 text-purple-800',
    'done' => 'bg-emerald-100 text-emerald-800',
    'declined' => 'bg-red-100 text-red-800',
    'cancelled' => 'bg-gray-200 text-gray-600',
];
$labels = [
    'requires_referral' => 'Requires Referral',
];
$color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
$label = $labels[$status] ?? ucfirst($status);
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    {{ $label }}
</span>
