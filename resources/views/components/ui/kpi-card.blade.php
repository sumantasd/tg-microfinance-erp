@props([
    'title',
    'value',
    'icon' => 'bi-wallet2',
    'badgeText' => null,
    'badgeType' => 'success', // success, danger, warning, info, primary
    'iconBg' => 'primary',
    'subtitle' => null
])

@php
    $bgClasses = [
        'primary' => 'bg-primary-subtle text-primary',
        'success' => 'bg-success-subtle text-success',
        'info' => 'bg-info-subtle text-info',
        'warning' => 'bg-warning-subtle text-warning',
        'danger' => 'bg-danger-subtle text-danger',
        'dark' => 'bg-dark-subtle text-dark',
    ];
    $iconClass = $bgClasses[$iconBg] ?? 'bg-primary-subtle text-primary';
@endphp

<div class="tg-kpi-card h-100">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <span class="text-uppercase small fw-bold text-muted d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">{{ $title }}</span>
            <h3 class="fw-bold mb-0 text-dark font-heading">{{ $value }}</h3>
        </div>
        <div class="tg-kpi-icon {{ $iconClass }}">
            <i class="bi {{ $icon }}"></i>
        </div>
    </div>

    @if($badgeText || $subtitle)
        <div class="d-flex align-items-center gap-1.5 small mt-2">
            @if($badgeText)
                <span class="badge bg-{{ $badgeType }}-subtle text-{{ $badgeType }} border border-{{ $badgeType }}-subtle rounded-pill fw-semibold font-monospace" style="font-size: 0.7rem;">
                    {{ $badgeText }}
                </span>
            @endif
            @if($subtitle)
                <span class="text-muted opacity-75 font-normal" style="font-size: 0.75rem;">{{ $subtitle }}</span>
            @endif
        </div>
    @endif
</div>
