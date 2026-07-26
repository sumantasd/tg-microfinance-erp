@props([
    'type' => 'primary', // primary, secondary, success, danger, warning, info, dark
    'pill' => true,
    'icon' => null
])

@php
$badgeClasses = [
    'primary' => 'bg-primary-subtle text-primary border border-primary-subtle',
    'secondary' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
    'success' => 'bg-success-subtle text-success border border-success-subtle',
    'danger' => 'bg-danger-subtle text-danger border border-danger-subtle',
    'warning' => 'bg-warning-subtle text-warning border border-warning-subtle',
    'info' => 'bg-info-subtle text-info border border-info-subtle',
    'dark' => 'bg-dark-subtle text-dark border border-dark-subtle',
];
$class = $badgeClasses[$type] ?? $badgeClasses['primary'];
$pillClass = $pill ? 'rounded-pill' : 'rounded';
@endphp

<span {{ $attributes->merge(['class' => 'badge px-2.5 py-1.5 fw-semibold ' . $class . ' ' . $pillClass]) }} style="font-size: 0.725rem;">
    @if($icon)
        <i class="bi {{ $icon }} me-1"></i>
    @endif
    {{ $slot }}
</span>
