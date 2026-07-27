<!-- Admin ERP Dark Sidebar Navigation -->
<aside id="admin-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="bg-primary text-white rounded-circle p-1 me-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
            <i class="bi bi-bank2 fs-6"></i>
        </div>
        <div>
            <span class="d-block fw-bold text-white font-heading lh-sm">TG Microfinance</span>
            <small class="text-muted d-block font-monospace" style="font-size: 0.65rem; letter-spacing: 0.5px;">ENTERPRISE ERP v2.5</small>
        </div>
    </div>

    <nav class="nav flex-column py-2">
        <!-- 1. DASHBOARD LINK -->
        <a class="sidebar-nav-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('/admin') }}">
            <i class="bi bi-speedometer2 text-primary nav-icon"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. WEBSITE CMS ACCORDION GROUP -->
        <a class="sidebar-nav-link {{ request()->is('admin/cms*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarCmsCollapse" role="button" aria-expanded="{{ request()->is('admin/cms*') ? 'true' : 'false' }}" aria-controls="sidebarCmsCollapse">
            <i class="bi bi-globe text-info nav-icon"></i>
            <span>Website CMS</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ request()->is('admin/cms*') ? 'show' : '' }}" id="sidebarCmsCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/cms/homepage*') ? 'active' : '' }}" href="{{ url('/admin/cms/homepage') }}">
                <i class="bi bi-house-gear nav-icon"></i>
                <span>Homepage CMS</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/settings*') ? 'active' : '' }}" href="{{ url('/admin/cms/settings') }}">
                <i class="bi bi-sliders nav-icon"></i>
                <span>Website Settings</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/banners*') ? 'active' : '' }}" href="{{ url('/admin/cms/banners') }}">
                <i class="bi bi-images nav-icon"></i>
                <span>Banners</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/pages*') ? 'active' : '' }}" href="{{ url('/admin/cms/pages') }}">
                <i class="bi bi-file-earmark-text nav-icon"></i>
                <span>Pages</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/loan-products*') ? 'active' : '' }}" href="{{ url('/admin/cms/loan-products') }}">
                <i class="bi bi-box-seam nav-icon"></i>
                <span>Loan Products</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/savings-products*') ? 'active' : '' }}" href="{{ url('/admin/cms/savings-products') }}">
                <i class="bi bi-piggy-bank nav-icon"></i>
                <span>Savings Products</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/interest-rates*') ? 'active' : '' }}" href="{{ url('/admin/cms/interest-rates') }}">
                <i class="bi bi-percent nav-icon"></i>
                <span>Interest Rates</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/services*') ? 'active' : '' }}" href="{{ url('/admin/cms/services') }}">
                <i class="bi bi-gear nav-icon"></i>
                <span>Services</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/news*') ? 'active' : '' }}" href="{{ url('/admin/cms/news') }}">
                <i class="bi bi-newspaper nav-icon"></i>
                <span>News</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/gallery*') ? 'active' : '' }}" href="{{ url('/admin/cms/gallery') }}">
                <i class="bi bi-image nav-icon"></i>
                <span>Gallery</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/downloads*') ? 'active' : '' }}" href="{{ url('/admin/cms/downloads') }}">
                <i class="bi bi-download nav-icon"></i>
                <span>Downloads</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/faq*') ? 'active' : '' }}" href="{{ url('/admin/cms/faq') }}">
                <i class="bi bi-question-circle nav-icon"></i>
                <span>FAQ</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/footer*') ? 'active' : '' }}" href="{{ url('/admin/cms/footer') }}">
                <i class="bi bi-layout-sidebar-reverse nav-icon"></i>
                <span>Footer</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/seo*') ? 'active' : '' }}" href="{{ url('/admin/cms/seo') }}">
                <i class="bi bi-search nav-icon"></i>
                <span>SEO</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/contact*') ? 'active' : '' }}" href="{{ url('/admin/cms/contact') }}">
                <i class="bi bi-envelope nav-icon"></i>
                <span>Contact</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/careers*') ? 'active' : '' }}" href="{{ url('/admin/cms/careers') }}">
                <i class="bi bi-briefcase nav-icon"></i>
                <span>Career</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/team*') ? 'active' : '' }}" href="{{ url('/admin/cms/team') }}">
                <i class="bi bi-people nav-icon"></i>
                <span>Team Members</span>
            </a>
        </div>

        <!-- 3. ERP MANAGEMENT ACCORDION GROUP -->
        @php
            $isErpActive = request()->is('admin/branch*') || request()->is('admin/customer*') || request()->is('admin/loan*') || request()->is('admin/savings*') || request()->is('admin/collection*') || request()->is('admin/inventory*') || request()->is('admin/billing*') || request()->is('admin/accounting*') || request()->is('admin/employee*') || request()->is('admin/reports*');
        @endphp
        <a class="sidebar-nav-link {{ $isErpActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarErpCollapse" role="button" aria-expanded="{{ $isErpActive ? 'true' : 'false' }}" aria-controls="sidebarErpCollapse">
            <i class="bi bi-diagram-3 text-warning nav-icon"></i>
            <span>ERP Management</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ $isErpActive ? 'show' : '' }}" id="sidebarErpCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/branch*') ? 'active' : '' }}" href="{{ url('/admin/branch') }}">
                <i class="bi bi-building nav-icon"></i>
                <span>Branch Management</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/customer*') ? 'active' : '' }}" href="{{ url('/admin/customer') }}">
                <i class="bi bi-person-badge nav-icon"></i>
                <span>Member Management</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/loan*') ? 'active' : '' }}" href="{{ url('/admin/loan') }}">
                <i class="bi bi-cash-stack nav-icon"></i>
                <span>Loan Management</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/savings*') ? 'active' : '' }}" href="{{ url('/admin/savings') }}">
                <i class="bi bi-piggy-bank nav-icon"></i>
                <span>Savings</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/collection*') ? 'active' : '' }}" href="{{ url('/admin/collection') }}">
                <i class="bi bi-journal-check nav-icon"></i>
                <span>Collection</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/inventory*') ? 'active' : '' }}" href="{{ url('/admin/inventory') }}">
                <i class="bi bi-box-seam nav-icon"></i>
                <span>Inventory</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/billing*') ? 'active' : '' }}" href="{{ url('/admin/billing') }}">
                <i class="bi bi-receipt nav-icon"></i>
                <span>Billing</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/accounting*') ? 'active' : '' }}" href="{{ url('/admin/accounting') }}">
                <i class="bi bi-calculator nav-icon"></i>
                <span>Accounting</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/employee*') ? 'active' : '' }}" href="{{ url('/admin/employee') }}">
                <i class="bi bi-person-lines-fill nav-icon"></i>
                <span>HRM / Employees</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ url('/admin/reports') }}">
                <i class="bi bi-bar-chart-line nav-icon"></i>
                <span>Reports</span>
            </a>
        </div>

        <!-- 4. SYSTEM ACCORDION GROUP -->
        @php
            $isSystemActive = request()->is('admin/system*');
        @endphp
        <a class="sidebar-nav-link {{ $isSystemActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarSystemCollapse" role="button" aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" aria-controls="sidebarSystemCollapse">
            <i class="bi bi-shield-lock text-success nav-icon"></i>
            <span>System</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ $isSystemActive ? 'show' : '' }}" id="sidebarSystemCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/system/users*') ? 'active' : '' }}" href="{{ url('/admin/system/users') }}">
                <i class="bi bi-person-gear nav-icon"></i>
                <span>Users</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/system/roles*') ? 'active' : '' }}" href="{{ url('/admin/system/roles') }}">
                <i class="bi bi-shield-check nav-icon"></i>
                <span>Roles</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/system/permissions*') ? 'active' : '' }}" href="{{ url('/admin/system/permissions') }}">
                <i class="bi bi-key nav-icon"></i>
                <span>Permissions</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/system/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/system/audit-logs') }}">
                <i class="bi bi-journal-text nav-icon"></i>
                <span>Audit Logs</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/system/backup*') ? 'active' : '' }}" href="{{ url('/admin/system/backup') }}">
                <i class="bi bi-database-up nav-icon"></i>
                <span>Backup</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/system/settings*') ? 'active' : '' }}" href="{{ url('/admin/system/settings') }}">
                <i class="bi bi-gear nav-icon"></i>
                <span>System Settings</span>
            </a>
        </div>
    </nav>
</aside>
