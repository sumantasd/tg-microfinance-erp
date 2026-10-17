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
    @if(trim($slot) !== '')
        <div class="chart-content-box" style="min-height: {{ $height }};">
            {{ $slot }}
        </div>
    @else
        <div class="chart-canvas-box d-flex align-items-center justify-content-center text-muted" style="min-height: {{ $height }};">
            <div class="text-center">
                <i class="bi bi-bar-chart-line fs-2 text-primary opacity-50 mb-1 d-block"></i>
                <span class="small fw-semibold text-secondary">{{ $title }} Analytics</span>
            </div>
        </div>
    @endif
</div>
