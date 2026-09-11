@props(['idPrefix' => ''])

@php
    $p = $idPrefix;
@endphp

<nav class="nav flex-column py-2">
    <!-- 1. DASHBOARD (Always accessible to all authenticated staff) -->
    <a class="sidebar-nav-link {{ request()->is('admin') || request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ url('/admin') }}">
        <i class="bi bi-grid-1x2-fill text-primary nav-icon"></i>
        <span>Dashboard</span>
    </a>

    <!-- 2. CASH BOOK REGISTER -->
    @can('cashbook.view')
    <a class="sidebar-nav-link {{ request()->is('admin/cash-book*') ? 'active' : '' }}" href="{{ route('admin.cash-book.index') }}">
        <i class="bi bi-journal-check text-success nav-icon"></i>
        <span>Cash Book</span>
    </a>
    @endcan
    @can('bank_deposit.view')
    <a class="sidebar-nav-link {{ request()->is('admin/bank-deposits*') ? 'active' : '' }}" href="{{ route('admin.bank-deposits.index') }}">
        <i class="bi bi-bank text-info nav-icon"></i>
        <span>Bank Deposits</span>
    </a>
    @endcan

    <!-- 3. ORGANIZATION MANAGEMENT -->
    @canany(['company.view', 'branch.view'])
    @php
        $isOrgActive = request()->is('admin/company*') || request()->is('admin/branch*');
        $orgTarget = '#' . $p . 'sidebarOrgCollapse';
        $orgId = $p . 'sidebarOrgCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isOrgActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $orgTarget }}" role="button" aria-expanded="{{ $isOrgActive ? 'true' : 'false' }}" aria-controls="{{ $orgId }}">
        <i class="bi bi-buildings nav-icon text-primary"></i>
        <span>Organization Management</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isOrgActive ? 'show' : '' }}" id="{{ $orgId }}">
        @can('company.view')
        <a class="sidebar-nav-link {{ request()->is('admin/company*') ? 'active' : '' }}" href="{{ url('/admin/company') }}">
            <i class="bi bi-building-gear nav-icon text-primary"></i>
            <span>Company Profile</span>
        </a>
        @endcan
        @can('branch.view')
        <a class="sidebar-nav-link {{ request()->is('admin/branch*') ? 'active' : '' }}" href="{{ url('/admin/branch') }}">
            <i class="bi bi-diagram-3 nav-icon text-warning"></i>
            <span>Branch Management</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 4. CUSTOMERS & GROUPS -->
    @canany(['customer.view', 'group.view'])
    @php
        $isCustomersGroupsActive = (request()->is('admin/customer*') && !request()->is('admin/customer-group*')) || request()->is('admin/customer-group*');
        $custTarget = '#' . $p . 'sidebarCustomersGroupsCollapse';
        $custId = $p . 'sidebarCustomersGroupsCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isCustomersGroupsActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $custTarget }}" role="button" aria-expanded="{{ $isCustomersGroupsActive ? 'true' : 'false' }}" aria-controls="{{ $custId }}">
        <i class="bi bi-people-fill nav-icon text-success"></i>
        <span>Customers & Groups</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isCustomersGroupsActive ? 'show' : '' }}" id="{{ $custId }}">
        @can('customer.view')
        <a class="sidebar-nav-link {{ (request()->is('admin/customer') || request()->is('admin/customer/*')) && !request()->is('admin/customer-group*') ? 'active' : '' }}" href="{{ url('/admin/customer') }}">
            <i class="bi bi-person-badge nav-icon text-success"></i>
            <span>Member Management</span>
        </a>
        @endcan
        @can('group.view')
        <a class="sidebar-nav-link {{ request()->is('admin/customer-group*') ? 'active' : '' }}" href="{{ route('admin.customer-group.index') }}">
            <i class="bi bi-people nav-icon text-info"></i>
            <span>Groups</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 5. LOAN MANAGEMENT -->
    @canany(['loan_scheme.view', 'loan_application.view', 'loan.view', 'collection.view', 'overdue.view', 'penalty.view', 'loan_closure.view'])
    @php
        $isLoanActive = request()->is('admin/loan-scheme*') || request()->is('admin/loan-application*') || request()->is('admin/loan-account*') || request()->is('admin/emi-collection*') || request()->is('admin/overdue*') || request()->is('admin/penalties*') || request()->is('admin/loan-settlement*');
        $loanTarget = '#' . $p . 'sidebarLoanCollapse';
        $loanId = $p . 'sidebarLoanCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isLoanActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $loanTarget }}" role="button" aria-expanded="{{ $isLoanActive ? 'true' : 'false' }}" aria-controls="{{ $loanId }}">
        <i class="bi bi-cash-stack nav-icon text-success"></i>
        <span>Loan Management</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isLoanActive ? 'show' : '' }}" id="{{ $loanId }}">
        @can('loan_scheme.view')
        <a class="sidebar-nav-link {{ request()->is('admin/loan-scheme*') ? 'active' : '' }}" href="{{ route('admin.loan-scheme.index') }}">
            <i class="bi bi-journal-bookmark nav-icon text-primary"></i>
            <span>Loan Schemes</span>
        </a>
        @endcan
        @can('loan_application.view')
        <a class="sidebar-nav-link {{ request()->is('admin/loan-application*') ? 'active' : '' }}" href="{{ route('admin.loan-application.index') }}">
            <i class="bi bi-file-earmark-spreadsheet nav-icon text-success"></i>
            <span>Loan Applications</span>
        </a>
        @endcan
        @can('loan.view')
        <a class="sidebar-nav-link {{ request()->is('admin/loan-account*') ? 'active' : '' }}" href="{{ route('admin.loan-account.index') }}">
            <i class="bi bi-wallet2 nav-icon text-warning"></i>
            <span>Loan Accounts</span>
        </a>
        @endcan
        @can('collection.view')
        <a class="sidebar-nav-link {{ request()->is('admin/emi-collection*') ? 'active' : '' }}" href="{{ route('admin.emi-collection.index') }}">
            <i class="bi bi-cash-coin nav-icon text-danger"></i>
            <span>EMI Collection</span>
        </a>
        @endcan
        @can('overdue.view')
        <a class="sidebar-nav-link {{ request()->is('admin/overdue*') ? 'active' : '' }}" href="{{ route('admin.overdue.dashboard') }}">
            <i class="bi bi-clock-history nav-icon text-danger"></i>
            <span>Overdue & DPD</span>
        </a>
        @endcan
        @can('penalty.view')
        <a class="sidebar-nav-link {{ request()->is('admin/penalties*') ? 'active' : '' }}" href="{{ route('admin.penalties.ledger') }}">
            <i class="bi bi-exclamation-triangle nav-icon text-warning"></i>
            <span>Penalty Management</span>
        </a>
        @endcan
        @can('loan_closure.view')
        <a class="sidebar-nav-link {{ request()->is('admin/loan-settlement*') ? 'active' : '' }}" href="{{ route('admin.loan-settlement.index') }}">
            <i class="bi bi-file-earmark-check nav-icon text-info"></i>
            <span>Settlements & Foreclosures</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 6. PRODUCTS & INVENTORY -->
    @canany(['product.view', 'product_brand.view', 'product_category.view', 'inventory.view', 'inventory.transfer.view'])
    @php
        $isProductActive = (request()->is('admin/product*') && !request()->is('admin/product-purchase*')) || (request()->is('admin/inventory*') && !request()->is('admin/inventory/purchases*')) || request()->is('admin/billing*');
        $prodTarget = '#' . $p . 'sidebarProductsInventoryCollapse';
        $prodId = $p . 'sidebarProductsInventoryCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isProductActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $prodTarget }}" role="button" aria-expanded="{{ $isProductActive ? 'true' : 'false' }}" aria-controls="{{ $prodId }}">
        <i class="bi bi-boxes nav-icon text-warning"></i>
        <span>Products & Inventory</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isProductActive ? 'show' : '' }}" id="{{ $prodId }}">
        @can('product.view')
        <a class="sidebar-nav-link {{ (request()->is('admin/product') || request()->is('admin/product/*')) && !request()->is('admin/product-brand*') && !request()->is('admin/product-category*') && !request()->is('admin/product-purchase*') ? 'active' : '' }}" href="{{ route('admin.product.index') }}">
            <i class="bi bi-box-seam nav-icon text-info"></i>
            <span>Product Catalog</span>
        </a>
        @endcan
        @can('product_brand.view')
        <a class="sidebar-nav-link {{ request()->is('admin/product-brand*') ? 'active' : '' }}" href="{{ route('admin.product-brand.index') }}">
            <i class="bi bi-tag nav-icon text-primary"></i>
            <span>Product Brands</span>
        </a>
        @endcan
        @can('product_category.view')
        <a class="sidebar-nav-link {{ request()->is('admin/product-category*') ? 'active' : '' }}" href="{{ route('admin.product-category.index') }}">
            <i class="bi bi-grid-3x3-gap nav-icon text-success"></i>
            <span>Product Categories</span>
        </a>
        @endcan
        @can('inventory.view')
        <a class="sidebar-nav-link {{ request()->is('admin/inventory') || (request()->is('admin/inventory/*') && !request()->is('admin/inventory/transfers*') && !request()->is('admin/inventory/purchases*')) ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}">
            <i class="bi bi-stack nav-icon text-warning"></i>
            <span>Branch Inventory</span>
        </a>
        @endcan
        @can('inventory.transfer.view')
        <a class="sidebar-nav-link {{ request()->is('admin/inventory/transfers*') ? 'active' : '' }}" href="{{ route('admin.inventory-transfer.index') }}">
            <i class="bi bi-arrow-left-right nav-icon text-danger"></i>
            <span>Stock Transfers</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 7. PROCUREMENT -->
    @canany(['purchase.view', 'supplier.view', 'suppliers.view', 'inventory.view'])
    @php
        $isProcurementActive = request()->is('admin/warehouse*') || request()->is('admin/inventory/purchases*') || request()->is('admin/product-purchase*') || request()->is('admin/suppliers*');
        $procTarget = '#' . $p . 'sidebarProcurementCollapse';
        $procId = $p . 'sidebarProcurementCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isProcurementActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $procTarget }}" role="button" aria-expanded="{{ $isProcurementActive ? 'true' : 'false' }}" aria-controls="{{ $procId }}">
        <i class="bi bi-cart3 nav-icon text-primary"></i>
        <span>Procurement</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isProcurementActive ? 'show' : '' }}" id="{{ $procId }}">
        @can('inventory.view')
        <a class="sidebar-nav-link {{ request()->is('admin/warehouse*') ? 'active' : '' }}" href="{{ route('admin.warehouse.index') }}">
            <i class="bi bi-building nav-icon text-primary"></i>
            <span>Warehouse</span>
        </a>
        @endcan
        @can('purchase.view')
        <a class="sidebar-nav-link {{ request()->is('admin/inventory/purchases*') || request()->is('admin/product-purchase*') ? 'active' : '' }}" href="{{ route('admin.product-purchase.index') }}">
            <i class="bi bi-cart-check nav-icon text-primary"></i>
            <span>Product Purchases</span>
        </a>
        @endcan
        @canany(['supplier.view', 'suppliers.view'])
        <a class="sidebar-nav-link {{ request()->is('admin/suppliers*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}">
            <i class="bi bi-truck nav-icon text-info"></i>
            <span>Suppliers / Vendors</span>
        </a>
        @endcanany
    </div>
    @endcanany

    <!-- 8. BILLING & INVOICE SYSTEM -->
    @can('billing.view')
    <a class="sidebar-nav-link {{ request()->is('admin/billing*') ? 'active' : '' }}" href="{{ route('admin.billing.invoices.index') }}">
        <i class="bi bi-receipt-cutoff nav-icon text-info"></i>
        <span>Billing & Invoices</span>
    </a>
    @endcan

    <!-- 9. ACCOUNTING & FINANCE -->
    @canany(['accounting.view', 'expense.view', 'reports.view'])
    @php
        $isAccountingActive = request()->is('admin/accounting*') || request()->is('admin/expenses*') || request()->is('admin/reports*');
        $accTarget = '#' . $p . 'sidebarAccountingFinanceCollapse';
        $accId = $p . 'sidebarAccountingFinanceCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isAccountingActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $accTarget }}" role="button" aria-expanded="{{ $isAccountingActive ? 'true' : 'false' }}" aria-controls="{{ $accId }}">
        <i class="bi bi-calculator nav-icon text-primary"></i>
        <span>Accounting & Finance</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isAccountingActive ? 'show' : '' }}" id="{{ $accId }}">
        @can('expense.view')
        <a class="sidebar-nav-link {{ request()->is('admin/expenses*') ? 'active' : '' }}" href="{{ route('admin.expenses.index') }}">
            <i class="bi bi-receipt nav-icon text-danger"></i>
            <span>Expense Management</span>
        </a>
        @endcan
        @can('accounting.view')
        <a class="sidebar-nav-link {{ request()->is('admin/accounting*') ? 'active' : '' }}" href="{{ route('admin.accounting.dashboard') }}">
            <i class="bi bi-journal-text nav-icon text-primary"></i>
            <span>Accounting</span>
        </a>
        @endcan
        @can('reports.view')
        <a class="sidebar-nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}" href="{{ url('/admin/reports') }}">
            <i class="bi bi-bar-chart-line nav-icon text-success"></i>
            <span>Reports</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 10. ENTERPRISE HRM -->
    @canany(['employee.view', 'department.view', 'designation.view', 'attendance.view', 'leave.view', 'payroll.view', 'hr_letter.view', 'hr_reports.view'])
    @php
        $isHrmActive = request()->is('admin/employee*') || request()->is('admin/department*') || request()->is('admin/designation*') || request()->is('admin/hrm*');
        $hrmTarget = '#' . $p . 'sidebarHrmCollapse';
        $hrmId = $p . 'sidebarHrmCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isHrmActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $hrmTarget }}" role="button" aria-expanded="{{ $isHrmActive ? 'true' : 'false' }}" aria-controls="{{ $hrmId }}">
        <i class="bi bi-people nav-icon text-info"></i>
        <span>Enterprise HRM</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isHrmActive ? 'show' : '' }}" id="{{ $hrmId }}">
        @can('employee.view')
        <a class="sidebar-nav-link {{ request()->is('admin/employee*') ? 'active' : '' }}" href="{{ route('admin.employee.index') }}">
            <i class="bi bi-person-lines-fill nav-icon"></i>
            <span>Employees</span>
        </a>
        @endcan
        @can('department.view')
        <a class="sidebar-nav-link {{ request()->is('admin/department*') ? 'active' : '' }}" href="{{ route('admin.department.index') }}">
            <i class="bi bi-diagram-2 nav-icon"></i>
            <span>Departments</span>
        </a>
        @endcan
        @can('designation.view')
        <a class="sidebar-nav-link {{ request()->is('admin/designation*') ? 'active' : '' }}" href="{{ route('admin.designation.index') }}">
            <i class="bi bi-person-workspace nav-icon"></i>
            <span>Designations</span>
        </a>
        @endcan
        @can('attendance.view')
        <a class="sidebar-nav-link {{ request()->is('admin/hrm/attendance*') ? 'active' : '' }}" href="{{ route('admin.hrm.attendance.index') }}">
            <i class="bi bi-calendar-check nav-icon"></i>
            <span>Attendance</span>
        </a>
        @endcan
        @can('leave.view')
        <a class="sidebar-nav-link {{ request()->is('admin/hrm/leave*') ? 'active' : '' }}" href="{{ route('admin.hrm.leave.index') }}">
            <i class="bi bi-calendar-minus nav-icon"></i>
            <span>Leave Management</span>
        </a>
        @endcan
        @can('payroll.view')
        <a class="sidebar-nav-link {{ request()->is('admin/hrm/payroll*') ? 'active' : '' }}" href="{{ route('admin.hrm.payroll.index') }}">
            <i class="bi bi-cash-stack nav-icon"></i>
            <span>Payroll & Slips</span>
        </a>
        @endcan
        @can('hr_letter.view')
        <a class="sidebar-nav-link {{ request()->is('admin/hrm/letters*') ? 'active' : '' }}" href="{{ route('admin.hrm.letters.index') }}">
            <i class="bi bi-card-heading nav-icon"></i>
            <span>HR Letters & ID Cards</span>
        </a>
        @endcan
        @can('hr_reports.view')
        <a class="sidebar-nav-link {{ request()->is('admin/hrm/reports*') ? 'active' : '' }}" href="{{ route('admin.hrm.reports.index') }}">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            <span>HR Reports</span>
        </a>
        @endcan
    </div>
    @endcanany

    <!-- 11. WEBSITE CMS -->
    @can('website.manage')
    @php
        $isCmsActive = request()->is('admin/cms*');
        $cmsTarget = '#' . $p . 'sidebarCmsCollapse';
        $cmsId = $p . 'sidebarCmsCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isCmsActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $cmsTarget }}" role="button" aria-expanded="{{ $isCmsActive ? 'true' : 'false' }}" aria-controls="{{ $cmsId }}">
        <i class="bi bi-globe text-primary nav-icon"></i>
        <span>Website CMS</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isCmsActive ? 'show' : '' }}" id="{{ $cmsId }}">
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
    @endcan

    <!-- 12. SYSTEM / SETTINGS -->
    @canany(['users.view', 'roles.view', 'permissions.view', 'settings.view'])
    @php
        $isSystemActive = request()->is('admin/system*');
        $sysTarget = '#' . $p . 'sidebarSystemCollapse';
        $sysId = $p . 'sidebarSystemCollapse';
    @endphp
    <a class="sidebar-nav-link {{ $isSystemActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="{{ $sysTarget }}" role="button" aria-expanded="{{ $isSystemActive ? 'true' : 'false' }}" aria-controls="{{ $sysId }}">
        <i class="bi bi-gear-fill nav-icon text-secondary"></i>
        <span>System / Settings</span>
        <i class="bi bi-chevron-right accordion-arrow"></i>
    </a>

    <div class="collapse sidebar-submenu {{ $isSystemActive ? 'show' : '' }}" id="{{ $sysId }}">
        @can('users.view')
        <a class="sidebar-nav-link {{ request()->is('admin/system/users*') ? 'active' : '' }}" href="{{ url('/admin/system/users') }}">
            <i class="bi bi-person-gear nav-icon text-primary"></i>
            <span>Users</span>
        </a>
        @endcan
        @can('roles.view')
        <a class="sidebar-nav-link {{ request()->is('admin/system/roles*') ? 'active' : '' }}" href="{{ url('/admin/system/roles') }}">
            <i class="bi bi-shield-check nav-icon text-success"></i>
            <span>Roles</span>
        </a>
        @endcan
        @can('permissions.view')
        <a class="sidebar-nav-link {{ request()->is('admin/system/permissions*') ? 'active' : '' }}" href="{{ url('/admin/system/permissions') }}">
            <i class="bi bi-key nav-icon text-warning"></i>
            <span>Permissions</span>
        </a>
        @endcan
        @can('settings.view')
        <a class="sidebar-nav-link {{ request()->is('admin/system/audit-logs*') ? 'active' : '' }}" href="{{ url('/admin/system/audit-logs') }}">
            <i class="bi bi-journal-text nav-icon text-info"></i>
            <span>Audit Logs</span>
        </a>
        <a class="sidebar-nav-link {{ request()->is('admin/system/backup*') ? 'active' : '' }}" href="{{ url('/admin/system/backup') }}">
            <i class="bi bi-database-up nav-icon text-primary"></i>
            <span>Backup</span>
        </a>
        <a class="sidebar-nav-link {{ request()->is('admin/system/settings*') ? 'active' : '' }}" href="{{ url('/admin/system/settings') }}">
            <i class="bi bi-sliders nav-icon text-secondary"></i>
            <span>Settings</span>
        </a>
        @endcan
    </div>
    @endcanany
</nav>
