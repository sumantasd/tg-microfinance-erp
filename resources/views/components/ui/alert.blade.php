@props([
    'type' => 'success', // success, danger, warning, info
    'dismissible' => true,
    'icon' => null
])

@php
$icons = [
    'success' => 'bi-check-circle-fill',
    'danger' => 'bi-exclamation-triangle-fill',
    'warning' => 'bi-exclamation-circle-fill',
    'info' => 'bi-info-circle-fill',
];
$selectedIcon = $icon ?? ($icons[$type] ?? 'bi-info-circle-fill');
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-' . $type . ' ' . ($dismissible ? 'alert-dismissible fade show' : '') . ' shadow-sm border-0 rounded-3 mb-4']) }} role="alert">
    <div class="d-flex align-items-center">
        <i class="bi {{ $selectedIcon }} me-2 fs-5"></i>
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
