@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'TG Microfinance';
    $companyLogo = $settings->logo_url ?? null;
@endphp

<!-- Full-Height Mobile Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-start border-0 shadow-lg" tabindex="-1" id="mobileAppDrawer" aria-labelledby="mobileAppDrawerLabel" style="width: 280px; max-width: 85vw;">
    <div class="offcanvas-header bg-white py-3 border-bottom">
        <div class="d-flex align-items-center gap-2">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="img-fluid" style="max-height: 34px; max-width: 120px; object-fit: contain;">
            @else
                <div class="bg-primary text-white rounded-3 p-1.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;">
                    <i class="bi bi-bank2 fs-6"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold font-heading text-dark lh-sm">{{ $companyName }}</h6>
                    <small class="text-muted font-monospace" style="font-size: 0.65rem;">MOBILE ERP APP</small>
                </div>
            @endif
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 bg-white" style="overflow-y: auto;">
        <x-layouts.admin-sidebar-nav id-prefix="mob-" />
    </div>

    <!-- Drawer Footer Account Info -->
    <div class="offcanvas-footer p-3 bg-white border-top">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 36px; height: 36px;">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'SA' }}
                </div>
                <div>
                    <div class="fw-bold small text-dark lh-sm">{{ auth()->check() ? auth()->user()->name : 'Staff Admin' }}</div>
                    <small class="text-muted font-monospace" style="font-size: 0.675rem;">{{ auth()->check() && auth()->user()->roles->first() ? auth()->user()->roles->first()->name : 'Staff' }}</small>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger p-1.5" title="Logout">
                    <i class="bi bi-box-arrow-right fs-6"></i>
                </button>
            </form>
        </div>
    </div>
</div>
