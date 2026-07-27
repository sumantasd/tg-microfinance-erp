@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'TG Microfinance';
    $companyLogo = $settings->logo_url ?? null;
@endphp

<!-- Admin ERP Light SaaS Topbar Header -->
<header id="admin-topbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Sidebar Toggle Mobile Button -->
        <button class="btn btn-light d-lg-none p-1.5 border" type="button" id="sidebar-toggler" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5 text-dark"></i>
        </button>

        <!-- Active Branch Selector Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light btn-sm rounded-pill border dropdown-toggle fw-semibold d-flex align-items-center gap-2" type="button" id="branchSelectorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-building-gear text-primary"></i>
                <span>Head Office Branch</span>
            </button>
            <ul class="dropdown-menu shadow-lg border-0 rounded-3 mt-1" aria-labelledby="branchSelectorDropdown">
                <li><h6 class="dropdown-header">Switch Active Branch</h6></li>
                <li><a class="dropdown-item active fw-bold" href="#"><i class="bi bi-check2 text-primary me-2"></i>Head Office Branch</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-building me-2"></i>Commercial Market Branch</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-building me-2"></i>Eastern Agricultural Branch</a></li>
            </ul>
        </div>

        <!-- Global Search Input -->
        <div class="topbar-search position-relative d-none d-md-block">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="global-search-input" class="form-control bg-light border-0" placeholder="Search members, loans, collections, branches...">
            <span class="position-absolute end-0 top-50 translate-middle-y me-2 badge bg-white text-muted border font-monospace small">Ctrl K</span>
        </div>
    </div>

    <!-- Right Side Controls & Profile -->
    <div class="d-flex align-items-center gap-2">
        <!-- Fullscreen Button -->
        <button type="button" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center border" id="btn-fullscreen-toggle" title="Toggle Fullscreen" style="width: 38px; height: 38px;">
            <i class="bi bi-arrows-fullscreen fs-6 text-secondary"></i>
        </button>

        <!-- Theme Toggle Button -->
        <button type="button" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center border" title="Toggle SaaS Theme" style="width: 38px; height: 38px;">
            <i class="bi bi-moon-stars fs-6 text-secondary"></i>
        </button>

        <!-- System Notifications Dropdown -->
        <div class="dropdown">
            <button type="button" class="btn btn-light rounded-circle p-2 position-relative d-flex align-items-center justify-content-center border" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 38px;">
                <i class="bi bi-bell fs-6 text-secondary"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                    <span class="visually-hidden">New notifications</span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-3 mt-1" aria-labelledby="notificationsDropdown" style="width: 320px;">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <h6 class="fw-bold mb-0 font-heading">Notifications</h6>
                    <span class="badge bg-primary-subtle text-primary">3 New</span>
                </div>
                <div class="list-group list-group-flush small">
                    <a href="#" class="list-group-item list-group-item-action px-0 py-2 border-0">
                        <div class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-check text-success me-1"></i> Loan Application Approved</div>
                        <small class="text-muted">Loan #LN-2026-089 approved by Branch Manager</small>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action px-0 py-2 border-0">
                        <div class="fw-bold text-dark mb-0"><i class="bi bi-journal-plus text-primary me-1"></i> Field Collection Posted</div>
                        <small class="text-muted">Officer John posted ₹12,500 collection sheet</small>
                    </a>
                </div>
            </ul>
        </div>

        <div class="vr mx-1 opacity-25"></div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; background-color: #2563eb !important;">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'SA' }}
                </div>
                <div class="d-none d-md-block text-start">
                    <div class="fw-bold small text-dark lh-1">{{ auth()->check() ? auth()->user()->name : 'Super Admin' }}</div>
                    <small class="text-muted" style="font-size: 0.725rem;">Administrator</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2 mt-1" aria-labelledby="userProfileDropdown">
                <li><a class="dropdown-item rounded-2 py-1.5" href="{{ url('/admin/system/users') }}"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                <li><a class="dropdown-item rounded-2 py-1.5" href="{{ url('/admin/system/settings') }}"><i class="bi bi-gear me-2 text-primary"></i> Account Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item rounded-2 py-1.5 text-danger border-0 bg-transparent w-100 text-start">
                            <i class="bi bi-box-arrow-right me-2"></i> Staff Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
