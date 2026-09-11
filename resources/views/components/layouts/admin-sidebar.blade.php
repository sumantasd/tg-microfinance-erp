@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'TG Microfinance';
    $companyLogo = $settings->logo_url ?? null;
@endphp

<!-- Light SaaS Enterprise Sidebar Navigation (Stripe / Razorpay / Zoho Inspired) -->
<aside id="admin-sidebar">
    <!-- Brand Header with Dynamic Logo -->
    <div class="sidebar-brand">
        <a href="{{ url('/admin') }}" class="d-flex align-items-center text-decoration-none gap-2 text-dark">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="img-fluid" style="max-height: 38px; max-width: 140px; object-fit: contain;">
            @else
                <div class="bg-primary text-white rounded-3 p-1.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px; background-color: #2563eb !important;">
                    <i class="bi bi-bank2 fs-6"></i>
                </div>
                <div>
                    <span class="d-block fw-bold font-heading lh-sm text-dark">{{ $companyName }}</span>
                    <small class="text-muted d-block font-monospace" style="font-size: 0.625rem; letter-spacing: 0.5px;">SAAS ERP DASHBOARD</small>
                </div>
            @endif
        </a>
    </div>

    <x-layouts.admin-sidebar-nav />
</aside>
