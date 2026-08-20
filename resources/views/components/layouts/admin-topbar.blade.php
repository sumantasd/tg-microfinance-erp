@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'Grihalaxmi Finance';
    $companyLogo = $settings->logo_url ?? null;
    
    $authUser = auth()->user();
    $companyId = $authUser ? $authUser->resolveScopedCompanyId() : 1;
    $scopedBranchId = $authUser ? $authUser->resolveScopedBranchId() : null;
    $branches = \App\Models\Branch::where('company_id', $companyId)->where('is_active', true)->get();
    $currentBranchName = $scopedBranchId ? ($branches->firstWhere('id', $scopedBranchId)?->name ?? 'Assigned Branch') : 'All Branches (Consolidated)';
@endphp

<!-- Admin ERP Light SaaS Topbar Header -->
<header id="admin-topbar">
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <!-- Sidebar / Mobile Drawer Toggle Button -->
        <button class="btn btn-light d-lg-none p-1.5 border" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileAppDrawer" aria-controls="mobileAppDrawer" aria-label="Open Navigation Menu">
            <i class="bi bi-list fs-5 text-dark"></i>
        </button>

        <!-- Real Company Brand Logo on Mobile -->
        <a href="{{ url('/admin') }}" class="d-lg-none text-decoration-none d-flex align-items-center my-auto me-auto">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" style="max-height: 34px; width: auto; max-width: 160px; object-fit: contain;">
            @else
                <span class="fw-bold font-heading text-dark fs-6 text-truncate">{{ $companyName }}</span>
            @endif
        </a>

        <!-- Active Branch Selector Dropdown -->
        <div class="dropdown d-none d-sm-block">
            <button class="btn btn-light btn-sm rounded-pill border dropdown-toggle fw-semibold d-flex align-items-center gap-2" type="button" id="branchSelectorDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-building-gear text-primary"></i>
                <span class="text-truncate" style="max-width: 140px;">{{ $currentBranchName }}</span>
            </button>
            <ul class="dropdown-menu shadow-lg border-0 rounded-3 mt-1" aria-labelledby="branchSelectorDropdown">
                <li><h6 class="dropdown-header">Active Branch Scope</h6></li>
                @if($authUser && ($authUser->isSuperAdmin() || $authUser->isCompanyAdmin()))
                    <li><a class="dropdown-item {{ !$scopedBranchId ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-globe me-2"></i>All Branches (Consolidated)</a></li>
                @endif
                @foreach($branches as $b)
                    <li>
                        <a class="dropdown-item {{ $scopedBranchId == $b->id ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard', ['branch_id' => $b->id]) }}">
                            <i class="bi bi-building me-2"></i>{{ $b->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Global Search Input & Live Autocomplete (Desktop) -->
        <div class="topbar-search position-relative d-none d-md-block" style="min-width: 340px;">
            <form method="GET" action="{{ route('admin.search') }}" id="topbar-search-form" class="m-0 position-relative">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="global-search-input" name="q" class="form-control bg-light border-0" placeholder="Search members, loans, applications, products..." autocomplete="off">
                <span class="position-absolute end-0 top-50 translate-middle-y me-2 badge bg-white text-muted border font-monospace small" style="pointer-events: none;">Ctrl K</span>
            </form>

            <!-- Autocomplete Suggestions Dropdown -->
            <div id="search-autocomplete-dropdown" class="dropdown-menu shadow-lg border-0 rounded-3 p-0 mt-1 position-absolute w-100" style="display: none; max-height: 420px; overflow-y: auto; z-index: 1060; left: 0; right: 0;">
                <div id="search-results-container" class="p-2"></div>
                <div id="search-view-all-container" class="p-2 border-top bg-light text-center" style="display: none;">
                    <a href="#" id="search-view-all-link" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none">View all results <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Controls & Profile -->
    <div class="d-flex align-items-center gap-2">
        <!-- Mobile Search Trigger Button -->
        <button type="button" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center border d-md-none" data-bs-toggle="modal" data-bs-target="#mobileSearchModal" title="Search" style="width: 36px; height: 36px;">
            <i class="bi bi-search fs-6 text-dark"></i>
        </button>

        <!-- Fullscreen Button (Desktop) -->
        <button type="button" class="btn btn-light rounded-circle p-2 d-none d-md-flex align-items-center justify-content-center border" id="btn-fullscreen-toggle" title="Toggle Fullscreen" style="width: 38px; height: 38px;">
            <i class="bi bi-arrows-fullscreen fs-6 text-secondary"></i>
        </button>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; background-color: #2563eb !important;">
                    {{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 2)) : 'SA' }}
                </div>
                <div class="d-none d-md-block text-start">
                    <div class="fw-bold small text-dark lh-1">{{ auth()->check() ? auth()->user()->name : 'Super Admin' }}</div>
                    <small class="text-muted" style="font-size: 0.725rem;">{{ auth()->check() && auth()->user()->roles->first() ? auth()->user()->roles->first()->name : 'Staff' }}</small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2 mt-1" aria-labelledby="userProfileDropdown">
                <li><a class="dropdown-item rounded-2 py-1.5" href="{{ route('admin.profile.show') }}"><i class="bi bi-person me-2 text-primary"></i> My Profile</a></li>
                @can('users.view')
                    <li><a class="dropdown-item rounded-2 py-1.5" href="{{ route('admin.system.users.index') }}"><i class="bi bi-people me-2 text-primary"></i> User Management</a></li>
                @endcan
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('global-search-input');
    const dropdown = document.getElementById('search-autocomplete-dropdown');
    const resultsContainer = document.getElementById('search-results-container');
    const viewAllContainer = document.getElementById('search-view-all-container');
    const viewAllLink = document.getElementById('search-view-all-link');

    if (!searchInput || !dropdown) return;

    let debounceTimer = null;

    // Keyboard shortcut Ctrl + K
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    // Close dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    searchInput.addEventListener('input', function () {
        const query = searchInput.value.trim();
        clearTimeout(debounceTimer);

        if (query.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchResults(query);
        }, 250);
    });

    function fetchResults(query) {
        resultsContainer.innerHTML = '<div class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Searching...</div>';
        dropdown.style.display = 'block';

        fetch(`{{ route('admin.search') }}?q=${encodeURIComponent(query)}&format=json`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.total_results === 0) {
                    resultsContainer.innerHTML = '<div class="text-center py-3 text-muted small"><i class="bi bi-search me-1"></i> No matching records found.</div>';
                    viewAllContainer.style.display = 'none';
                    return;
                }

                let html = '';
                for (const [category, items] of Object.entries(data.categories)) {
                    html += `<div class="px-2 py-1 small fw-bold text-uppercase text-muted" style="font-size: 0.675rem; letter-spacing: 0.5px;">${category}</div>`;
                    items.forEach(item => {
                        html += `
                            <a href="${item.url}" class="d-flex justify-content-between align-items-center p-2 rounded text-decoration-none text-dark search-item-hover">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi ${item.icon || 'bi-dot'} text-primary fs-6"></i>
                                    <div>
                                        <strong class="d-block small lh-1 text-dark">${item.title}</strong>
                                        <small class="text-muted" style="font-size: 0.7rem;">${item.subtitle}</small>
                                    </div>
                                </div>
                                ${item.badge ? `<span class="badge ${item.badge_class || 'bg-secondary'} rounded-pill" style="font-size: 0.65rem;">${item.badge}</span>` : ''}
                            </a>
                        `;
                    });
                }

                resultsContainer.innerHTML = html;
                viewAllLink.href = `{{ route('admin.search') }}?q=${encodeURIComponent(query)}`;
                viewAllContainer.style.display = 'block';
            })
            .catch(err => {
                resultsContainer.innerHTML = '<div class="text-center py-2 text-danger small">Error loading results.</div>';
                viewAllContainer.style.display = 'none';
            });
    }
});
</script>

<style>
.search-item-hover:hover {
    background-color: #f8fafc;
}
</style>
