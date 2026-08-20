@php
    $settings = \App\Models\WebsiteSetting::first();
    $companyName = $settings->company_name ?? 'Grihalaxmi Finance';
    $companyLogo = $settings->logo_url ?? null;
@endphp

<!-- Full-Height Mobile Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-start border-0 shadow-lg" tabindex="-1" id="mobileAppDrawer" aria-labelledby="mobileAppDrawerLabel" style="width: 300px; max-width: 85vw;">
    <div class="offcanvas-header bg-primary text-white py-3">
        <div class="d-flex align-items-center gap-2">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="{{ $companyName }}" class="img-fluid bg-white p-1 rounded-2" style="max-height: 34px; max-width: 120px; object-fit: contain;">
            @else
                <div class="bg-white text-primary rounded-3 p-1.5 d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;">
                    <i class="bi bi-bank2 fs-6"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold font-heading text-white lh-sm">{{ $companyName }}</h6>
                    <small class="text-white-50 font-monospace" style="font-size: 0.65rem;">MOBILE ERP APP</small>
                </div>
            @endif
        </div>
        <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0 bg-light">
        <div class="accordion accordion-flush" id="mobileDrawerAccordion">
            
            <!-- 1. DASHBOARD -->
            <div class="p-2 border-bottom bg-white">
                <a href="{{ url('/admin') }}" class="d-flex align-items-center gap-2.5 text-decoration-none p-2 rounded-3 text-dark fw-bold {{ request()->is('admin') || request()->is('admin/dashboard') ? 'bg-primary-subtle text-primary' : '' }}">
                    <i class="bi bi-grid-1x2-fill text-primary fs-5"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- 2. ORGANIZATION MANAGEMENT -->
            @canany(['company.view', 'branch.view', 'customer.view', 'group.view'])
            @php
                $isOrgActive = request()->is('admin/company*') || request()->is('admin/branch*') || (request()->is('admin/customer*') && !request()->is('admin/customer-group*')) || request()->is('admin/customer-group*');
            @endphp
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingOrg">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ $isOrgActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseOrg" aria-expanded="{{ $isOrgActive ? 'true' : 'false' }}" aria-controls="mobileCollapseOrg">
                        <i class="bi bi-buildings text-primary me-2.5 fs-5"></i> Organization Management
                    </button>
                </h2>
                <div id="mobileCollapseOrg" class="accordion-collapse collapse {{ $isOrgActive ? 'show' : '' }}" aria-labelledby="mobileHeadingOrg" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('company.view')
                        <a href="{{ url('/admin/company') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/company*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-building-gear text-primary me-1"></i> Company Profile
                        </a>
                        @endcan
                        @can('branch.view')
                        <a href="{{ url('/admin/branch') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/branch*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-diagram-3 text-warning me-1"></i> Branch Management
                        </a>
                        @endcan
                        @can('customer.view')
                        <a href="{{ url('/admin/customer') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ (request()->is('admin/customer') || request()->is('admin/customer/*')) && !request()->is('admin/customer-group*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-person-badge text-success me-1"></i> Member Management
                        </a>
                        @endcan
                        @can('group.view')
                        <a href="{{ route('admin.customer-group.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/customer-group*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-people text-info me-1"></i> Customer Groups
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcanany

            <!-- 3. LOAN MANAGEMENT -->
            @canany(['loan_scheme.view', 'loan_application.view', 'loan.view', 'collection.view', 'overdue.view', 'penalty.view', 'loan_closure.view'])
            @php
                $isLoanActive = request()->is('admin/loan-scheme*') || request()->is('admin/loan-application*') || request()->is('admin/loan-account*') || request()->is('admin/emi-collection*') || request()->is('admin/overdue*') || request()->is('admin/penalties*') || request()->is('admin/loan-settlement*');
            @endphp
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingLoan">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ $isLoanActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseLoan" aria-expanded="{{ $isLoanActive ? 'true' : 'false' }}" aria-controls="mobileCollapseLoan">
                        <i class="bi bi-cash-stack text-success me-2.5 fs-5"></i> Loan Management
                    </button>
                </h2>
                <div id="mobileCollapseLoan" class="accordion-collapse collapse {{ $isLoanActive ? 'show' : '' }}" aria-labelledby="mobileHeadingLoan" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('loan_scheme.view')
                        <a href="{{ route('admin.loan-scheme.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/loan-scheme*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-journal-bookmark text-primary me-1"></i> Loan Schemes
                        </a>
                        @endcan
                        @can('loan_application.view')
                        <a href="{{ route('admin.loan-application.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/loan-application*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-file-earmark-spreadsheet text-success me-1"></i> Loan Applications
                        </a>
                        @endcan
                        @can('loan.view')
                        <a href="{{ route('admin.loan-account.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/loan-account*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-wallet2 text-warning me-1"></i> Loan Accounts
                        </a>
                        @endcan
                        @can('collection.view')
                        <a href="{{ route('admin.emi-collection.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/emi-collection*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-cash-coin text-danger me-1"></i> EMI Collection
                        </a>
                        @endcan
                        @can('overdue.view')
                        <a href="{{ route('admin.overdue.dashboard') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/overdue*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-clock-history text-danger me-1"></i> Overdue & DPD
                        </a>
                        @endcan
                        @can('penalty.view')
                        <a href="{{ route('admin.penalties.ledger') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/penalties*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-exclamation-triangle text-warning me-1"></i> Penalty Management
                        </a>
                        @endcan
                        @can('loan_closure.view')
                        <a href="{{ route('admin.loan-settlement.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/loan-settlement*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-file-earmark-check text-info me-1"></i> Settlements & Foreclosures
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcanany

            <!-- 4. PRODUCTS & INVENTORY -->
            @canany(['product.view', 'product_brand.view', 'product_category.view', 'inventory.view', 'inventory.transfer.view', 'purchase.view', 'supplier.view'])
            @php
                $isProductActive = request()->is('admin/product*') || request()->is('admin/inventory*') || request()->is('admin/suppliers*');
            @endphp
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingProducts">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ $isProductActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseProducts" aria-expanded="{{ $isProductActive ? 'true' : 'false' }}" aria-controls="mobileCollapseProducts">
                        <i class="bi bi-boxes text-warning me-2.5 fs-5"></i> Products & Inventory
                    </button>
                </h2>
                <div id="mobileCollapseProducts" class="accordion-collapse collapse {{ $isProductActive ? 'show' : '' }}" aria-labelledby="mobileHeadingProducts" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('product.view')
                        <a href="{{ route('admin.product.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ (request()->is('admin/product') || request()->is('admin/product/*')) && !request()->is('admin/product-brand*') && !request()->is('admin/product-category*') && !request()->is('admin/product-purchase*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-box-seam text-info me-1"></i> Product Catalog
                        </a>
                        @endcan
                        @can('product_brand.view')
                        <a href="{{ route('admin.product-brand.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/product-brand*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-tag text-primary me-1"></i> Product Brands
                        </a>
                        @endcan
                        @can('product_category.view')
                        <a href="{{ route('admin.product-category.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/product-category*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-grid-3x3-gap text-success me-1"></i> Product Categories
                        </a>
                        @endcan
                        @can('inventory.view')
                        <a href="{{ route('admin.inventory.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/inventory') || (request()->is('admin/inventory/*') && !request()->is('admin/inventory/transfers*') && !request()->is('admin/inventory/purchases*')) ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-stack text-warning me-1"></i> Branch Inventory
                        </a>
                        @endcan
                        @can('inventory.transfer.view')
                        <a href="{{ route('admin.inventory-transfer.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/inventory/transfers*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-arrow-left-right text-danger me-1"></i> Stock Transfers
                        </a>
                        @endcan
                        @can('purchase.view')
                        <a href="{{ route('admin.product-purchase.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/inventory/purchases*') || request()->is('admin/product-purchase*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-cart-check text-primary me-1"></i> Product Purchases
                        </a>
                        @endcan
                        @canany(['supplier.view', 'suppliers.view'])
                        <a href="{{ route('admin.suppliers.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/suppliers*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-truck text-info me-1"></i> Suppliers / Vendors
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
            @endcanany

            <!-- 5. ACCOUNTING & FINANCE -->
            @canany(['accounting.view', 'reports.view'])
            @php
                $isAccountingActive = request()->is('admin/accounting*') || request()->is('admin/reports*');
            @endphp
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingAccounting">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ $isAccountingActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseAccounting" aria-expanded="{{ $isAccountingActive ? 'true' : 'false' }}" aria-controls="mobileCollapseAccounting">
                        <i class="bi bi-calculator text-primary me-2.5 fs-5"></i> Accounting & Finance
                    </button>
                </h2>
                <div id="mobileCollapseAccounting" class="accordion-collapse collapse {{ $isAccountingActive ? 'show' : '' }}" aria-labelledby="mobileHeadingAccounting" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('accounting.view')
                        <a href="{{ route('admin.accounting.dashboard') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/accounting*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-journal-text text-primary me-1"></i> Accounting
                        </a>
                        @endcan
                        @can('reports.view')
                        <a href="{{ url('/admin/reports') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/reports*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-bar-chart-line text-success me-1"></i> Financial Reports
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcanany

            <!-- 6. ENTERPRISE HRM -->
            @canany(['employee.view', 'department.view', 'designation.view', 'attendance.view', 'leave.view', 'payroll.view', 'hr_letter.view', 'hr_reports.view'])
            @php
                $isHrmActive = request()->is('admin/employee*') || request()->is('admin/department*') || request()->is('admin/designation*') || request()->is('admin/hrm*');
            @endphp
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingHrm">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ $isHrmActive ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseHrm" aria-expanded="{{ $isHrmActive ? 'true' : 'false' }}" aria-controls="mobileCollapseHrm">
                        <i class="bi bi-people text-info me-2.5 fs-5"></i> Enterprise HRM
                    </button>
                </h2>
                <div id="mobileCollapseHrm" class="accordion-collapse collapse {{ $isHrmActive ? 'show' : '' }}" aria-labelledby="mobileHeadingHrm" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('employee.view')
                        <a href="{{ route('admin.employee.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/employee*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-person-lines-fill me-1"></i> Employees
                        </a>
                        @endcan
                        @can('department.view')
                        <a href="{{ route('admin.department.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/department*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-diagram-2 me-1"></i> Departments
                        </a>
                        @endcan
                        @can('attendance.view')
                        <a href="{{ route('admin.hrm.attendance.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/hrm/attendance*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-calendar-check me-1"></i> Attendance
                        </a>
                        @endcan
                        @can('payroll.view')
                        <a href="{{ route('admin.hrm.payroll.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold {{ request()->is('admin/hrm/payroll*') ? 'bg-white text-primary fw-bold shadow-sm' : '' }}">
                            <i class="bi bi-cash-stack me-1"></i> Payroll & Slips
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcanany

            <!-- 7. WEBSITE CMS -->
            @can('website.manage')
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingCms">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ request()->is('admin/cms*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseCms" aria-expanded="{{ request()->is('admin/cms*') ? 'true' : 'false' }}" aria-controls="mobileCollapseCms">
                        <i class="bi bi-globe text-primary me-2.5 fs-5"></i> Website CMS
                    </button>
                </h2>
                <div id="mobileCollapseCms" class="accordion-collapse collapse {{ request()->is('admin/cms*') ? 'show' : '' }}" aria-labelledby="mobileHeadingCms" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        <a href="{{ url('/admin/cms/settings') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold">
                            <i class="bi bi-sliders me-1"></i> Website Settings
                        </a>
                        <a href="{{ url('/admin/cms/banners') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold">
                            <i class="bi bi-images me-1"></i> Banners
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- 8. SYSTEM / SETTINGS -->
            @canany(['settings.manage', 'users.view', 'roles.view', 'audit.view'])
            <div class="accordion-item border-0 border-bottom bg-white">
                <h2 class="accordion-header" id="mobileHeadingSettings">
                    <button class="accordion-button py-3 px-3 fw-bold text-dark {{ request()->is('admin/system*') ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#mobileCollapseSettings" aria-expanded="{{ request()->is('admin/system*') ? 'true' : 'false' }}" aria-controls="mobileCollapseSettings">
                        <i class="bi bi-gear-fill text-secondary me-2.5 fs-5"></i> System & Settings
                    </button>
                </h2>
                <div id="mobileCollapseSettings" class="accordion-collapse collapse {{ request()->is('admin/system*') ? 'show' : '' }}" aria-labelledby="mobileHeadingSettings" data-bs-parent="#mobileDrawerAccordion">
                    <div class="accordion-body p-2 bg-light">
                        @can('users.view')
                        <a href="{{ route('admin.system.users.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold">
                            <i class="bi bi-people me-1"></i> Users Management
                        </a>
                        @endcan
                        @can('roles.view')
                        <a href="{{ route('admin.system.roles.index') }}" class="d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark small fw-semibold">
                            <i class="bi bi-shield-check me-1"></i> Roles & Permissions
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            @endcanany

        </div>
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
