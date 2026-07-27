@props([
    'title',
    'subtitle' => null,
    'chartId' => 'chartCanvas',
    'badgeText' => 'Live Analytics',
    'badgeType' => 'primary',
    'height' => '220px'
])

<div class="tg-chart-card h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark font-heading">{{ $title }}</h6>
            @if($subtitle)
                <small class="text-muted" style="font-size: 0.75rem;">{{ $subtitle }}</small>
            @endif
        </div>
        <span class="badge bg-{{ $badgeType }}-subtle text-{{ $badgeType }} border border-{{ $badgeType }}-subtle rounded-pill font-monospace" style="font-size: 0.7rem;">
            {{ $badgeText }}
        </span>
    </div>

    <!-- Chart Canvas Container -->
    <div class="chart-placeholder-box" style="height: {{ $height }};">
        <i class="bi bi-bar-chart-line fs-2 text-primary opacity-50 mb-2"></i>
        <span class="small fw-semibold text-secondary">{{ $title }} Visualizer</span>
        <small class="text-muted font-monospace opacity-75 mt-1" style="font-size: 0.7rem;">Backend Chart Integration Ready</small>
    </div>
</div>
