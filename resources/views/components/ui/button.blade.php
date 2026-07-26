@props([
    'variant' => 'primary', // primary, secondary, success, danger, warning, info, outline-primary, etc.
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'icon' => null,
    'pill' => false,
])

@php
$sizeClasses = [
    'sm' => 'btn-sm px-2.5 py-1',
    'md' => 'px-3 py-2',
    'lg' => 'btn-lg px-4 py-2.5',
];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
$pillClass = $pill ? 'rounded-pill' : 'rounded-3';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn btn-' . $variant . ' ' . $sizeClass . ' ' . $pillClass . ' fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5 shadow-sm']) }}>
    @if($icon)
        <i class="bi {{ $icon }}"></i>
    @endif
    <span>{{ $slot }}</span>
</button>
