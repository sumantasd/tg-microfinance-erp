@props([
    'title' => 'Dashboard',
    'breadcrumbs' => []
])

<!-- Admin Header & Mobile-Optimized Breadcrumb Bar -->
<div class="bg-white border-bottom py-2.5 py-md-3 px-3 px-md-4 shadow-2xs admin-breadcrumbs-bar">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="overflow-hidden">
            <!-- Breadcrumbs trail -->
            <nav aria-label="breadcrumb" class="mb-0.5 d-none d-sm-block">
                <ol class="breadcrumb mb-0 small text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                    <li class="breadcrumb-item">
                        <a href="{{ url('/admin') }}" class="text-decoration-none text-muted">
                            <i class="bi bi-speedometer2 me-1"></i>ERP
                        </a>
                    </li>
                    @foreach($breadcrumbs as $label => $url)
                        @if($loop->last || empty($url))
                            <li class="breadcrumb-item active text-primary" aria-current="page">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ url($url) }}" class="text-decoration-none text-muted">{{ $label }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>

            <!-- Mobile subtle back trail (Shown only on small screens) -->
            <div class="d-sm-none small text-muted mb-0.5 d-flex align-items-center gap-1 font-monospace" style="font-size: 0.68rem;">
                <a href="{{ url('/admin') }}" class="text-decoration-none text-muted d-inline-flex align-items-center">
                    <i class="bi bi-chevron-left me-0.5"></i> ERP
                </a>
                <span>/</span>
                <span class="text-primary fw-semibold text-truncate" style="max-width: 180px;">{{ $title }}</span>
            </div>

            <!-- Page Title -->
            <h4 class="fw-bold mb-0 text-dark font-heading fs-5 fs-md-4 text-truncate lh-sm">{{ $title }}</h4>
        </div>

        <!-- Action Buttons (Smooth swipeable horizontal row on mobile) -->
        @if(isset($actions))
            <div class="d-flex align-items-center gap-2 overflow-x-auto pb-1 pb-md-0 mt-1 mt-md-0 flex-nowrap" style="scrollbar-width: none; -webkit-overflow-scrolling: touch; max-width: 100%;">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
