@extends('layouts.admin')

@section('title', 'Central Reports Center - TG Microfinance ERP')

@section('content')
<!-- Header Strip -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill small fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i> Executive Analytics & Reporting
            </span>
            <span class="text-muted small">&bull;</span>
            <span class="text-muted small font-monospace">v2.5 Enterprise</span>
        </div>
        <h4 class="fw-bold text-dark font-heading mb-1">Central Reports Center</h4>
        <p class="text-muted small mb-0">Select from pre-configured operational, financial, compliance, and management report registers.</p>
    </div>

    <!-- Quick Search Filter -->
    <div class="d-flex gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="reportSearchInput" class="form-control border-start-0" placeholder="Search reports..." onkeyup="filterReports()">
        </div>
    </div>
</div>

<!-- Report Categories Grid -->
<div class="row g-4">
    @forelse($categories as $catKey => $category)
        <div class="col-12 category-section" data-category="{{ strtolower($category['title']) }}">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <!-- Category Header -->
                <div class="card-header bg-light py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 p-2 bg-{{ $category['color'] }}-subtle text-{{ $category['color'] }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi {{ $category['icon'] }} fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 font-heading">{{ $category['title'] }}</h6>
                            <small class="text-muted">{{ $category['description'] }}</small>
                        </div>
                    </div>
                    <span class="badge bg-white text-dark border px-2.5 py-1 rounded-pill small fw-bold font-monospace">
                        {{ count($category['reports']) }} Reports
                    </span>
                </div>

                <!-- Category Reports Sub-Grid -->
                <div class="card-body p-4 bg-white">
                    <div class="row g-3">
                        @foreach($category['reports'] as $typeKey => $rep)
                            <div class="col-md-6 col-lg-4 report-item" data-title="{{ strtolower($rep['title']) }}" data-desc="{{ strtolower($rep['desc']) }}">
                                <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between hover-shadow transition-all bg-light bg-opacity-25">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <h6 class="fw-bold text-dark mb-0 font-heading text-truncate" style="font-size: 0.925rem;">
                                                {{ $rep['title'] }}
                                            </h6>
                                        </div>
                                        <p class="text-muted small mb-0 line-clamp-2" style="font-size: 0.8rem; min-height: 38px;">
                                            {{ $rep['desc'] }}
                                        </p>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top gap-2">
                                        <a href="{{ route('admin.reports.show', ['category' => $catKey, 'type' => $typeKey]) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold flex-grow-1">
                                            <i class="bi bi-eye me-1"></i> View Report
                                        </a>

                                        @can('reports.export')
                                        <a href="{{ route('admin.reports.export', ['category' => $catKey, 'type' => $typeKey]) }}" class="btn btn-sm btn-outline-secondary rounded-circle p-1.5" title="Quick CSV Export" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm p-5 text-center">
                <i class="bi bi-shield-lock fs-1 text-muted mb-2"></i>
                <h5 class="fw-bold text-dark">No Report Categories Accessible</h5>
                <p class="text-muted small mb-0">Your user role does not currently have permissions to view reporting modules. Please contact an administrator.</p>
            </div>
        </div>
    @endforelse
</div>

<!-- Search Filter Script -->
<script>
function filterReports() {
    const input = document.getElementById('reportSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.report-item');
    const sections = document.querySelectorAll('.category-section');

    items.forEach(item => {
        const title = item.getAttribute('data-title');
        const desc = item.getAttribute('data-desc');
        if (title.includes(input) || desc.includes(input)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    sections.forEach(section => {
        const visibleItems = section.querySelectorAll('.report-item:not([style*="display: none"])');
        if (visibleItems.length === 0 && input.trim() !== '') {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    });
}
</script>

<style>
.hover-shadow:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1 !important;
    background-color: #ffffff !important;
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endsection
