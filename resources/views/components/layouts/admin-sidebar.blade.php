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

    <nav class="nav flex-column py-2">
        <!-- 1. DASHBOARD LINK -->
        <a class="sidebar-nav-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('/admin') }}">
            <i class="bi bi-grid-1x2-fill text-primary nav-icon"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. ERP MANAGEMENT (ALWAYS VISIBLE - REQUIREMENT #1) -->
        <div class="sidebar-group-header">
            ERP Management
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/company*') ? 'active' : '' }}" href="{{ url('/admin/company') }}">
            <i class="bi bi-buildings nav-icon text-primary"></i>
            <span>Company Profile</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/branch*') ? 'active' : '' }}" href="{{ url('/admin/branch') }}">
            <i class="bi bi-building nav-icon text-warning"></i>
            <span>Branch Management</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/customer') || request()->is('admin/customer/*') ? 'active' : '' }}" href="{{ url('/admin/customer') }}">
            <i class="bi bi-person-badge nav-icon text-primary"></i>
            <span>Member Management</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/customer-group*') ? 'active' : '' }}" href="{{ route('admin.customer-group.index') }}">
            <i class="bi bi-people nav-icon text-info"></i>
            <span>Customer Groups</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/loan*') ? 'active' : '' }}" href="{{ url('/admin/loan') }}">
            <i class="bi bi-cash-stack nav-icon text-success"></i>
            <span>Loan Management</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/savings*') ? 'active' : '' }}" href="{{ url('/admin/savings') }}">
            <i class="bi bi-piggy-bank nav-icon text-info"></i>
            <span>Savings</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/collection*') ? 'active' : '' }}" href="{{ url('/admin/collection') }}">
            <i class="bi bi-journal-check nav-icon text-danger"></i>
            <span>Collection</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/inventory*') ? 'active' : '' }}" href="{{ url('/admin/inventory') }}">
            <i class="bi bi-box-seam nav-icon text-warning"></i>
            <span>Inventory</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/billing*') ? 'active' : '' }}" href="{{ url('/admin/billing') }}">
            <i class="bi bi-receipt nav-icon text-info"></i>
            <span>Billing</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/accounting*') ? 'active' : '' }}" href="{{ url('/admin/accounting') }}">
            <i class="bi bi-calculator nav-icon text-primary"></i>
            <span>Accounting</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ url('/admin/reports') }}">
            <i class="bi bi-bar-chart-line nav-icon text-success"></i>
            <span>Reports</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/employee*') || request()->is('admin/department*') || request()->is('admin/designation*') || request()->is('admin/hrm*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarHrmCollapse" role="button" aria-expanded="{{ request()->is('admin/employee*') || request()->is('admin/department*') || request()->is('admin/designation*') || request()->is('admin/hrm*') ? 'true' : 'false' }}" aria-controls="sidebarHrmCollapse">
            <i class="bi bi-people nav-icon text-info"></i>
            <span>Enterprise HRM</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ request()->is('admin/employee*') || request()->is('admin/department*') || request()->is('admin/designation*') || request()->is('admin/hrm*') ? 'show' : '' }}" id="sidebarHrmCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/employee*') ? 'active' : '' }}" href="{{ route('admin.employee.index') }}">
                <i class="bi bi-person-lines-fill nav-icon"></i>
                <span>Employees</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/department*') ? 'active' : '' }}" href="{{ route('admin.department.index') }}">
                <i class="bi bi-diagram-2 nav-icon"></i>
                <span>Departments</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/designation*') ? 'active' : '' }}" href="{{ route('admin.designation.index') }}">
                <i class="bi bi-person-workspace nav-icon"></i>
                <span>Designations</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/hrm/attendance*') ? 'active' : '' }}" href="{{ route('admin.hrm.attendance.index') }}">
                <i class="bi bi-calendar-check nav-icon"></i>
                <span>Attendance</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/hrm/leave*') ? 'active' : '' }}" href="{{ route('admin.hrm.leave.index') }}">
                <i class="bi bi-calendar-minus nav-icon"></i>
                <span>Leave Management</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/hrm/payroll*') ? 'active' : '' }}" href="{{ route('admin.hrm.payroll.index') }}">
                <i class="bi bi-cash-stack nav-icon"></i>
                <span>Payroll & Slips</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/hrm/letters*') ? 'active' : '' }}" href="{{ route('admin.hrm.letters.index') }}">
                <i class="bi bi-card-heading nav-icon"></i>
                <span>HR Letters & ID Cards</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/hrm/reports*') ? 'active' : '' }}" href="{{ route('admin.hrm.reports.index') }}">
                <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
                <span>HR Reports</span>
            </a>
        </div>

        <!-- 3. WEBSITE CMS (ONLY COLLAPSIBLE MENU - REQUIREMENT #1) -->
        <div class="sidebar-group-header">
            Website CMS
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/cms*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarCmsCollapse" role="button" aria-expanded="{{ request()->is('admin/cms*') ? 'true' : 'false' }}" aria-controls="sidebarCmsCollapse">
            <i class="bi bi-globe text-primary nav-icon"></i>
            <span>Website CMS</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ request()->is('admin/cms*') ? 'show' : '' }}" id="sidebarCmsCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/cms/settings*') ? 'active' : '' }}" href="{{ url('/admin/cms/settings') }}">
                <i class="bi bi-sliders nav-icon"></i>
                <span>Website Settings</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/homepage*') ? 'active' : '' }}" href="{{ url('/admin/cms/homepage') }}">
                <i class="bi bi-house-gear nav-icon"></i>
                <span>Homepage</span>
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
                <span>Loan Products CMS</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/savings-products*') ? 'active' : '' }}" href="{{ url('/admin/cms/savings-products') }}">
                <i class="bi bi-piggy-bank nav-icon"></i>
                <span>Savings Products CMS</span>
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

        <!-- 4. SYSTEM (ALWAYS VISIBLE - REQUIREMENT #1) -->
        <div class="sidebar-group-header">
            System
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/system/users*') ? 'active' : '' }}" href="{{ url('/admin/system/users') }}">
            <i class="bi bi-person-gear nav-icon text-primary"></i>
            <span>Users</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/roles*') ? 'active' : '' }}" href="{{ url('/admin/system/roles') }}">
            <i class="bi bi-shield-check nav-icon text-success"></i>
            <span>Roles</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/permissions*') ? 'active' : '' }}" href="{{ url('/admin/system/permissions') }}">
            <i class="bi bi-key nav-icon text-warning"></i>
            <span>Permissions</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/system/audit-logs') }}">
            <i class="bi bi-journal-text nav-icon text-info"></i>
            <span>Audit Logs</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/backup*') ? 'active' : '' }}" href="{{ url('/admin/system/backup') }}">
            <i class="bi bi-database-up nav-icon text-primary"></i>
            <span>Backup</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/settings*') ? 'active' : '' }}" href="{{ url('/admin/system/settings') }}">
            <i class="bi bi-gear nav-icon text-secondary"></i>
            <span>Settings</span>
        </a>
    </nav>
</aside>
