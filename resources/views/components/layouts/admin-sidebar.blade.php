<!-- Admin ERP Dark Sidebar Navigation -->
<aside id="admin-sidebar">
    <!-- Brand Header -->
    <div class="sidebar-brand">
        <div class="bg-primary text-white rounded-circle p-1 me-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
            <i class="bi bi-bank2 fs-6"></i>
        </div>
        <span>TG Microfinance</span>
    </div>

    <nav class="nav flex-column py-3">
        <!-- Dashboard Link -->
        <a class="sidebar-nav-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('/admin') }}">
            <i class="bi bi-speedometer2 text-primary"></i>
            <span>Dashboard</span>
        </a>

        <!-- ============================================== -->
        <!-- ERP CORE MODULES (Expanded by Default) -->
        <!-- ============================================== -->
        <div class="px-3 pt-3 pb-1 text-uppercase small fw-bold text-muted border-bottom border-secondary mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">
            ERP Core Modules
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/company*') ? 'active' : '' }}" href="{{ url('/admin/company') }}">
            <i class="bi bi-building"></i>
            <span>Company</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/branch*') ? 'active' : '' }}" href="{{ url('/admin/branch') }}">
            <i class="bi bi-diagram-3"></i>
            <span>Branches</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/employee*') ? 'active' : '' }}" href="{{ url('/admin/employee') }}">
            <i class="bi bi-people"></i>
            <span>Employees</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/customer*') ? 'active' : '' }}" href="{{ url('/admin/customer') }}">
            <i class="bi bi-person-lines-fill"></i>
            <span>Customers</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/loan*') ? 'active' : '' }}" href="{{ url('/admin/loan') }}">
            <i class="bi bi-cash-stack"></i>
            <span>Loan Management</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/savings*') ? 'active' : '' }}" href="{{ url('/admin/savings') }}">
            <i class="bi bi-piggy-bank"></i>
            <span>Savings</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/collection*') ? 'active' : '' }}" href="{{ url('/admin/collection') }}">
            <i class="bi bi-journal-check"></i>
            <span>Collection</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/accounting*') ? 'active' : '' }}" href="{{ url('/admin/accounting') }}">
            <i class="bi bi-calculator"></i>
            <span>Accounting</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ url('/admin/reports') }}">
            <i class="bi bi-bar-chart-line"></i>
            <span>Reports</span>
        </a>

        <!-- ============================================== -->
        <!-- WEBSITE CMS (Collapsed by Default Accordion) -->
        <!-- ============================================== -->
        <div class="px-3 pt-3 pb-1 text-uppercase small fw-bold text-muted border-bottom border-secondary mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">
            Website CMS
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/cms*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#sidebarCmsCollapse" role="button" aria-expanded="{{ request()->is('admin/cms*') ? 'true' : 'false' }}" aria-controls="sidebarCmsCollapse">
            <i class="bi bi-globe text-info"></i>
            <span>Website CMS</span>
            <i class="bi bi-chevron-right accordion-arrow"></i>
        </a>

        <div class="collapse sidebar-submenu {{ request()->is('admin/cms*') ? 'show' : '' }}" id="sidebarCmsCollapse">
            <a class="sidebar-nav-link {{ request()->is('admin/cms/settings*') ? 'active' : '' }}" href="{{ url('/admin/cms/settings') }}">
                <i class="bi bi-sliders"></i>
                <span>Website Settings</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/homepage*') ? 'active' : '' }}" href="{{ url('/admin/cms/homepage') }}">
                <i class="bi bi-house-gear"></i>
                <span>Homepage</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/pages*') ? 'active' : '' }}" href="{{ url('/admin/cms/pages') }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>Pages</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/banners*') ? 'active' : '' }}" href="{{ url('/admin/cms/banners') }}">
                <i class="bi bi-images"></i>
                <span>Banner</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/loan-products*') ? 'active' : '' }}" href="{{ url('/admin/cms/loan-products') }}">
                <i class="bi bi-box-seam"></i>
                <span>Loan Products</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/savings-products*') ? 'active' : '' }}" href="{{ url('/admin/cms/savings-products') }}">
                <i class="bi bi-piggy-bank"></i>
                <span>Savings Products</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/news*') ? 'active' : '' }}" href="{{ url('/admin/cms/news') }}">
                <i class="bi bi-newspaper"></i>
                <span>News</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/gallery*') ? 'active' : '' }}" href="{{ url('/admin/cms/gallery') }}">
                <i class="bi bi-image"></i>
                <span>Gallery</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/downloads*') ? 'active' : '' }}" href="{{ url('/admin/cms/downloads') }}">
                <i class="bi bi-download"></i>
                <span>Downloads</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/faq*') ? 'active' : '' }}" href="{{ url('/admin/cms/faq') }}">
                <i class="bi bi-question-circle"></i>
                <span>FAQ</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/why-choose-us*') ? 'active' : '' }}" href="{{ url('/admin/cms/why-choose-us') }}">
                <i class="bi bi-patch-check"></i>
                <span>Why Choose Us</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/team*') ? 'active' : '' }}" href="{{ url('/admin/cms/team') }}">
                <i class="bi bi-people"></i>
                <span>Team Members</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/interest-rates*') ? 'active' : '' }}" href="{{ url('/admin/cms/interest-rates') }}">
                <i class="bi bi-percent"></i>
                <span>Interest Rates</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/services*') ? 'active' : '' }}" href="{{ url('/admin/cms/services') }}">
                <i class="bi bi-gear"></i>
                <span>Services</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/careers*') ? 'active' : '' }}" href="{{ url('/admin/cms/careers') }}">
                <i class="bi bi-briefcase"></i>
                <span>Careers</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/contact*') ? 'active' : '' }}" href="{{ url('/admin/cms/contact') }}">
                <i class="bi bi-envelope"></i>
                <span>Contact</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/footer*') ? 'active' : '' }}" href="{{ url('/admin/cms/footer') }}">
                <i class="bi bi-layout-sidebar-reverse"></i>
                <span>Footer</span>
            </a>
            <a class="sidebar-nav-link {{ request()->is('admin/cms/seo*') ? 'active' : '' }}" href="{{ url('/admin/cms/seo') }}">
                <i class="bi bi-search"></i>
                <span>SEO</span>
            </a>
        </div>

        <!-- ============================================== -->
        <!-- SYSTEM (Expanded by Default) -->
        <!-- ============================================== -->
        <div class="px-3 pt-3 pb-1 text-uppercase small fw-bold text-muted border-bottom border-secondary mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">
            System & Administration
        </div>

        <a class="sidebar-nav-link {{ request()->is('admin/system/users*') ? 'active' : '' }}" href="{{ url('/admin/system/users') }}">
            <i class="bi bi-person-gear"></i>
            <span>Users</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/roles*') ? 'active' : '' }}" href="{{ url('/admin/system/roles') }}">
            <i class="bi bi-shield-check"></i>
            <span>Roles</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/permissions*') ? 'active' : '' }}" href="{{ url('/admin/system/permissions') }}">
            <i class="bi bi-key"></i>
            <span>Permissions</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/settings*') ? 'active' : '' }}" href="{{ url('/admin/system/settings') }}">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/media*') ? 'active' : '' }}" href="{{ url('/admin/system/media') }}">
            <i class="bi bi-folder2-open"></i>
            <span>Media Library</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/notifications*') ? 'active' : '' }}" href="{{ url('/admin/system/notifications') }}">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/system/audit-logs') }}">
            <i class="bi bi-journal-text"></i>
            <span>Audit Logs</span>
        </a>

        <a class="sidebar-nav-link {{ request()->is('admin/system/backup*') ? 'active' : '' }}" href="{{ url('/admin/system/backup') }}">
            <i class="bi bi-database-up"></i>
            <span>Backup</span>
        </a>
    </nav>
</aside>
