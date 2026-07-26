@props([
    'title' => 'Metric Title',
    'value' => '0',
    'subtext' => null,
    'icon' => 'bi-graph-up',
    'type' => 'primary', // primary, success, warning, danger, info
])

@php
$borderColors = [
    'primary' => 'border-primary',
    'success' => 'border-success',
    'warning' => 'border-warning',
    'danger' => 'border-danger',
    'info' => 'border-info',
];
$bgSubtleColors = [
    'primary' => 'bg-primary-subtle text-primary',
    'success' => 'bg-success-subtle text-success',
    'warning' => 'bg-warning-subtle text-warning',
    'danger' => 'bg-danger-subtle text-danger',
    'info' => 'bg-info-subtle text-info',
];

$borderColor = $borderColors[$type] ?? 'border-primary';
$bgSubtle = $bgSubtleColors[$type] ?? 'bg-primary-subtle text-primary';
@endphp

<div class="card border-0 shadow-sm p-3 rounded-3 bg-white border-start border-4 {{ $borderColor }} tg-hover-lift">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div class="text-uppercase small fw-bold text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">{{ $title }}</div>
            <h3 class="fw-bold mb-0 text-dark">{{ $value }}</h3>
            @if($subtext)
                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">{{ $subtext }}</small>
            @endif
        </div>
        <div class="p-3 rounded-circle d-flex align-items-center justify-content-center {{ $bgSubtle }}" style="width: 48px; height: 48px;">
            <i class="bi {{ $icon }} fs-4"></i>
        </div>
    </div>
</div>
