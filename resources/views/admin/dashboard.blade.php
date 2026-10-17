@extends('layouts.admin')

@section('title', 'Grihalaxmi Finance ERP')

@section('content')

@if(auth()->user()->hasRole('Branch Manager') && !auth()->user()->branch_id)
    <div class="alert alert-danger shadow-sm rounded-4 p-3 mb-4 border border-danger-subtle" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
            <div>
                <h6 class="fw-bold mb-0 text-danger">Mandatory Branch Assignment Required</h6>
                <p class="mb-0 small text-dark opacity-75">Your account is designated as a <strong>Branch Manager</strong>, but no operational branch is currently assigned to your profile. All branch-specific operations remain restricted until a System Administrator assigns a branch to your user account.</p>
            </div>
        </div>
    </div>
@endif

<!-- 1. TOP HEADER BANNER: GREETING, BRANCH SELECTOR & OPERATIONAL CONTEXT -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white" style="padding: var(--dashboard-card-padding-y) var(--dashboard-card-padding-x);">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace px-2.5 py-1" style="font-size: 0.725rem;">
                    <i class="bi bi-clock me-1"></i>{{ date('l, F j, Y') }}
                </span>
                <span class="badge bg-success-subtle text-success rounded-pill font-monospace px-2.5 py-1" style="font-size: 0.725rem;">
                    <i class="bi bi-building me-1"></i>{{ $activeBranchName }}
                </span>
                @if(!empty($isHeadOfficeAdmin) && count($branches) > 1)
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-xs btn-outline-secondary dropdown-toggle rounded-pill px-2.5 py-0.5 shadow-xs" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.725rem;">
                            <i class="bi bi-funnel me-1"></i>Filter Branch
                        </button>
                        <ul class="dropdown-menu shadow border-0 small">
                            <li><a class="dropdown-item {{ empty($branchId) ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard', array_merge(request()->query(), ['branch_id' => ''])) }}">All Branches (Consolidated)</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($branches as $b)
                                <li><a class="dropdown-item {{ (!empty($branchId) && $branchId == $b->id) ? 'active fw-bold' : '' }}" href="{{ route('admin.dashboard', array_merge(request()->query(), ['branch_id' => $b->id])) }}">{{ $b->name }} ({{ $b->code }})</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <span class="badge bg-info-subtle text-info rounded-pill font-monospace px-2.5 py-1" style="font-size: 0.725rem;">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>System Live
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-1 font-heading">
                Good {{ date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->check() ? auth()->user()->name : 'User' }} 👋
            </h3>
            <small class="text-muted">Here is your daily microfinance portfolio overview, vault liquidity, and operational metrics.</small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary rounded-pill px-3.5 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-download"></i>
                    <span>Reports Center</span>
                </a>
            @endcan

            @can('loan_application.create')
                <a href="{{ route('admin.loan-application.create') }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-plus-circle"></i>
                    <span>New Application</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<!-- 1.1 EMPLOYEE DUTY & REAL-TIME ATTENDANCE TRACKER -->
@if(!empty($dutyInfo))
    @include('admin.dashboard._duty_tracker')
@endif

<!-- 2. ERP QUICK ACTIONS RIBBON -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
        <span class="text-uppercase small fw-bold text-muted font-monospace" style="letter-spacing: 0.8px;">ERP Quick Action Ribbon</span>
        <small class="text-primary fw-semibold opacity-75">Frequent Workflows</small>
    </div>
    <div class="row g-2.5">
        @can('customer.create')
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.customer.create') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-person-plus fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">New Member</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">KYC Enrollment</small>
                    </div>
                </a>
            </div>
        @endcan

        @can('loan_application.create')
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.loan-application.create') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-file-earmark-plus fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">Loan Application</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">Credit Proposal</small>
                    </div>
                </a>
            </div>
        @endcan

        @canany(['collection.view', 'loan.collection.view', 'loan.collection.create'])
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.emi-collection.index') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-journal-check fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">EMI Collection</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">Post Recovery</small>
                    </div>
                </a>
            </div>
        @endcanany

        @can('product.view')
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.product.index') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-receipt fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">Products</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">Catalog & Items</small>
                    </div>
                </a>
            </div>
        @endcan

        @can('inventory.view')
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.inventory.index') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-box-seam fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">Stock Ledger</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">Vault & Inventory</small>
                    </div>
                </a>
            </div>
        @endcan

        @can('voucher.create')
            <div class="col-6 col-md-4 col-lg-2">
                <a href="{{ route('admin.accounting.vouchers.create') }}" class="tg-quick-action-btn h-100">
                    <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-calculator fs-6"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark small lh-1">Voucher Entry</strong>
                        <small class="text-muted opacity-75" style="font-size: 0.675rem;">General Ledger</small>
                    </div>
                </a>
            </div>
        @endcan
    </div>
</div>

<!-- 2.1 GLOBAL DASHBOARD REPORTING PERIOD FILTER -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-3">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <strong class="text-dark small d-block">Dashboard Reporting Period</strong>
                    <span class="text-muted small" style="font-size: 0.775rem;">
                        Active: <strong class="text-primary">{{ $periodLabel }}</strong>
                        <span class="text-secondary font-monospace">({{ $periodRangeText }})</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Presets and Custom Trigger -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-1.5 flex-wrap m-0">
                @if(!empty($branchId) && $branchId > 0)
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                @endif
                @if(!empty($companyId))
                    <input type="hidden" name="company_id" value="{{ $companyId }}">
                @endif

                <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border" role="group" aria-label="Date Presets">
                    <button type="submit" name="date_filter" value="today" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold {{ $dateFilter === 'today' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                        Today
                    </button>
                    <button type="submit" name="date_filter" value="yesterday" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold {{ $dateFilter === 'yesterday' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                        Yesterday
                    </button>
                    <button type="submit" name="date_filter" value="this_month" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold {{ $dateFilter === 'this_month' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                        This Month
                    </button>
                    <button type="submit" name="date_filter" value="this_year" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold {{ $dateFilter === 'this_year' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}">
                        This Year
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-semibold {{ $dateFilter === 'custom' ? 'btn-primary shadow-xs' : 'btn-light text-secondary' }}" data-bs-toggle="collapse" data-bs-target="#customDateFilterCollapse" aria-expanded="{{ $dateFilter === 'custom' ? 'true' : 'false' }}">
                        <i class="bi bi-sliders me-1"></i>Custom Range
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Collapsible Custom Date Range Form -->
    <div class="collapse {{ $dateFilter === 'custom' || !empty($dateFilterError) ? 'show' : '' }} mt-3 pt-3 border-top" id="customDateFilterCollapse">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-2 align-items-center">
            <input type="hidden" name="date_filter" value="custom">
            @if(!empty($branchId) && $branchId > 0)
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
            @endif
            @if(!empty($companyId))
                <input type="hidden" name="company_id" value="{{ $companyId }}">
            @endif

            <div class="col-12 col-sm-auto">
                <span class="small fw-bold text-muted text-uppercase font-monospace" style="font-size: 0.7rem;">Select Custom Window:</span>
            </div>
            <div class="col-6 col-sm-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar-event"></i></span>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-auto text-muted small fw-bold px-1">to</div>
            <div class="col-6 col-sm-auto">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar-check"></i></span>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required max="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-12 col-sm-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                    <i class="bi bi-check-lg me-1"></i>Apply Filter
                </button>
                <a href="{{ route('admin.dashboard', array_filter(['branch_id' => $branchId > 0 ? $branchId : null, 'date_filter' => 'today'])) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    Reset
                </a>
            </div>

            @if(!empty($dateFilterError))
                <div class="col-12 mt-2">
                    <div class="alert alert-warning py-1.5 px-3 mb-0 small d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                        <span>{{ $dateFilterError }}</span>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- 3. REAL FINANCIAL KPI CARDS GRID -->
<div class="row g-3 mb-4">
    <!-- 1. Total Members -->
    @can('customer.view')
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Total Members"
                :value="number_format($kpis['total_customers'])"
                icon="bi-people"
                iconBg="primary"
                :badgeText="number_format($kpis['active_customers']) . ' Active' . (($kpis['period_registered_members'] ?? 0) > 0 && $dateFilter !== 'today' ? ' · +' . $kpis['period_registered_members'] . ' in period' : '')"
                badgeType="success"
                subtitle="Registered Borrowers (Lifetime)"
            />
        </div>
    @endcan

    <!-- 2. Active Loans -->
    @can('loan.view')
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Active Loans"
                :value="'₹' . number_format($kpis['active_portfolio_amount'], 2)"
                icon="bi-cash-stack"
                iconBg="success"
                :badgeText="number_format($kpis['active_loans_count']) . ' Active'"
                badgeType="success"
                subtitle="Current Portfolio Principal"
            />
        </div>
    @endcan

    <!-- 3. Today's / Period Collection -->
    @canany(['collection.view', 'loan.collection.view', 'loan.view'])
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                :title="$dateFilter === 'today' ? 'Today\'s Collection' : 'Period Collection'"
                :value="'₹' . number_format($kpis['period_collection'] ?? $kpis['today_collection'], 2)"
                icon="bi-journal-check"
                iconBg="warning"
                :badgeText="$dateFilter === 'today' ? ('₹' . number_format($kpis['month_collection'], 2) . ' MTD') : (number_format($kpis['period_collection_count'] ?? 0) . ' Receipts')"
                badgeType="success"
                :subtitle="$dateFilter === 'today' ? 'Daily Recovery Total' : ('Recovery (' . $periodRangeText . ')')"
            />
        </div>
    @endcanany

    <!-- 4. Today's / Period Disbursement -->
    @can('loan.view')
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                :title="$dateFilter === 'today' ? 'Today\'s Disbursement' : 'Period Disbursement'"
                :value="'₹' . number_format($kpis['period_disbursement'] ?? $kpis['today_disbursement'], 2)"
                icon="bi-box-arrow-up-right"
                iconBg="info"
                :badgeText="number_format($kpis['period_disbursed_count'] ?? $kpis['today_disbursed_count']) . ' Disbursed'"
                badgeType="info"
                :subtitle="$dateFilter === 'today' ? 'Credit Issued Today' : ('Credit Disbursed (' . $periodRangeText . ')')"
            />
        </div>
    @endcan

    <!-- 5. Pending EMI Due -->
    @can('loan.view')
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                :title="$dateFilter === 'today' ? 'Pending EMI Due' : 'Period EMI Due'"
                :value="'₹' . number_format($kpis['period_pending_emi_amount'] ?? $kpis['today_pending_emi_amount'], 2)"
                icon="bi-clock-history"
                iconBg="warning"
                :badgeText="number_format($kpis['period_pending_emi_count'] ?? $kpis['today_pending_emi_count']) . ' Installments'"
                badgeType="warning"
                :subtitle="$dateFilter === 'today' ? 'Today\'s Uncollected Due' : ('Uncollected Due (' . $periodRangeText . ')')"
            />
        </div>
    @endcan

    <!-- 6. Total Overdue Principal -->
    @canany(['overdue.view', 'loan.view'])
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Total Overdue"
                :value="'₹' . number_format($kpis['total_overdue_principal'], 2)"
                icon="bi-exclamation-triangle"
                iconBg="danger"
                :badgeText="number_format($kpis['par_30_rate'], 1) . '% PAR 30'"
                badgeType="danger"
                subtitle="Current Delinquent Balance"
            />
        </div>
    @endcanany

    <!-- 7. Total Outstanding Demand -->
    @can('loan.view')
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Total Outstanding"
                :value="'₹' . number_format($kpis['total_outstanding'], 2)"
                icon="bi-pie-chart"
                iconBg="primary"
                :badgeText="number_format($kpis['pending_applications_count']) . ' Pending Apps'"
                badgeType="primary"
                subtitle="Current Gross Loan Demand"
            />
        </div>
    @endcan

    <!-- 8. Vault Cash Balance -->
    @canany(['cashbook.view', 'branch.view', 'accounting.view'])
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Vault Cash"
                :value="'₹' . number_format($kpis['total_vault_cash'], 2)"
                icon="bi-safe"
                iconBg="warning"
                badgeText="In Vault"
                badgeType="warning"
                subtitle="Current Vault Reserves"
            />
        </div>
    @endcanany

    <!-- 9. Bank Balance (Audited Ledger) -->
    @if(!empty($canViewBankBalance) && isset($kpis['total_bank_balance']) && $kpis['total_bank_balance'] !== null)
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Bank Balance"
                :value="'₹' . number_format($kpis['total_bank_balance'], 2)"
                icon="bi-building-check"
                iconBg="success"
                badgeText="GL 1130"
                badgeType="success"
                subtitle="Current Ledger Balance (GL 1130)"
            />
        </div>
    @endif

    <!-- 10. Net Profit / Loss (Admin-Only, Strictly Suppressed for Branch Managers) -->
    @if(!empty($canViewProfitLoss) && isset($kpis['period_profit_loss']) && $kpis['period_profit_loss'] !== null)
        <div class="col-12 col-md-6 col-lg-4">
            <x-ui.kpi-card
                title="Net Profit / Loss (YTD)"
                :value="($kpis['period_profit_loss'] >= 0 ? '+ ' : '') . '₹' . number_format($kpis['period_profit_loss'], 2)"
                icon="bi-graph-up-arrow"
                :iconBg="$kpis['period_profit_loss'] >= 0 ? 'success' : 'danger'"
                badgeText="Audited P&L"
                :badgeType="$kpis['period_profit_loss'] >= 0 ? 'success' : 'danger'"
                :subtitle="$dateFilter === 'this_year' ? ('Fiscal Year Recognized (' . $periodRangeText . ')') : ('Period: ₹' . number_format($kpis['period_profit_loss'], 2) . ' · YTD: ₹' . number_format($kpis['ytd_profit_loss'] ?? 0, 2))"
            />
        </div>
    @endif
</div>

<!-- 4. DEDICATED MODULE OVERVIEW CARDS -->
<div class="row g-3 mb-4">
    <!-- Card 1: Loan Management Overview -->
    @can('loan.view')
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="bi bi-cash-stack fs-6"></i>
                        </div>
                        <h6 class="fw-bold mb-0 font-heading text-dark">Loan Management</h6>
                    </div>
                    <a href="{{ route('admin.loan-account.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold p-0">View All</a>
                </div>

                <div class="dashboard-card-body d-flex flex-column justify-content-between">
                    <div class="dashboard-stat-grid">
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Active Portfolio</small>
                            <strong class="text-dark font-heading">₹{{ number_format($loanOverview['active_portfolio'], 2) }}</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Approvals</small>
                            <strong class="text-warning font-heading">{{ $loanOverview['pending_approvals'] }} Apps</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Overdue Principal</small>
                            <strong class="text-danger font-heading">₹{{ number_format($loanOverview['overdue_principal'], 2) }}</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Today's EMI Due</small>
                            <strong class="text-primary font-heading">₹{{ number_format($loanOverview['today_emi_due'], 2) }}</strong>
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="small fw-semibold text-secondary mb-1.5">Portfolio Scheme Distribution</div>
                        @if(count($loanOverview['product_breakdown']) > 0 && $loanOverview['total_scheme_portfolio'] > 0)
                            <div class="progress mb-2" style="height: 8px;">
                                @php
                                    $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger'];
                                @endphp
                                @foreach($loanOverview['product_breakdown'] as $idx => $item)
                                    @if($item['percentage'] > 0)
                                        <div class="progress-bar {{ $colors[$idx % count($colors)] }}" role="progressbar" style="width: {{ $item['percentage'] }}%" title="{{ $item['scheme_name'] }} ({{ $item['percentage'] }}%)"></div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="small text-muted d-flex flex-wrap gap-2" style="font-size: 0.75rem;">
                                @foreach($loanOverview['product_breakdown'] as $idx => $item)
                                    @if($item['percentage'] > 0)
                                        <span><span class="badge {{ $colors[$idx % count($colors)] }} p-1 me-1"> </span>{{ $item['scheme_name'] }}: {{ $item['percentage'] }}%</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small py-2 text-center bg-light rounded-3 border">No active loans found</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <!-- Card 2: Collection Overview -->
    @canany(['collection.view', 'loan.collection.view', 'loan.view'])
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="bi bi-journal-check fs-6"></i>
                        </div>
                        <h6 class="fw-bold mb-0 font-heading text-dark">Collection Performance</h6>
                    </div>
                    <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-sm btn-link text-warning text-decoration-none fw-semibold p-0">View Sheet</a>
                </div>

                <div class="dashboard-card-body d-flex flex-column justify-content-between">
                    <div class="dashboard-stat-grid">
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Today's Target</small>
                            <strong class="text-dark font-heading">₹{{ number_format($collectionOverview['today_target'], 2) }}</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Recovery Rate</small>
                            <strong class="text-success font-heading">{{ $collectionOverview['recovery_rate'] }}%</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Collection</small>
                            <strong class="text-danger font-heading">₹{{ number_format($collectionOverview['pending_collection'], 2) }}</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Cash vs Digital</small>
                            <strong class="text-info font-heading">{{ $collectionOverview['cash_percentage'] }} / {{ $collectionOverview['digital_percentage'] }} %</strong>
                        </div>
                    </div>

                    <div class="dashboard-card-footer-bar small text-muted border-top pt-2.5">
                        <span>Month Collection Total: <strong class="text-dark">₹{{ number_format($collectionOverview['month_collected'], 2) }}</strong></span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Verified</span>
                    </div>
                </div>
            </div>
        </div>
    @endcanany

    <!-- Card 3: Inventory & Goods Overview -->
    @can('inventory.view')
        <div class="col-12 col-lg-6 col-xl-4">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="bi bi-box-seam fs-6"></i>
                        </div>
                        <h6 class="fw-bold mb-0 font-heading text-dark">Branch Inventory</h6>
                    </div>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold p-0">Stock Ledger</a>
                </div>

                <div class="dashboard-card-body d-flex flex-column justify-content-between">
                    <div class="dashboard-stat-grid">
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Total Stock Valuation</small>
                            <strong class="text-dark font-heading">₹{{ number_format($inventoryOverview['total_stock_value'], 2) }}</strong>
                        </div>
                        <div class="dashboard-stat-box">
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Low Stock Alerts</small>
                            <strong class="{{ $inventoryOverview['low_stock_count'] > 0 ? 'text-danger' : 'text-success' }} font-heading">{{ $inventoryOverview['low_stock_count'] }} Items</strong>
                        </div>
                    </div>

                    <div class="dashboard-card-footer-bar small text-muted border-top pt-2.5">
                        <span>Active Products: <strong class="text-dark">{{ $inventoryOverview['active_products_count'] }}</strong></span>
                        @can('product.view')
                            <a href="{{ route('admin.product.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1 text-decoration-none small">Manage Items</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>

<!-- 4.1 COLLECTION TRENDS & PORTFOLIO AT RISK ANALYTICS -->
@canany(['loan.view', 'collection.view', 'overdue.view'])
<div class="row g-3 mb-4">
    <!-- 6-Month Collection Trend Chart -->
    <div class="col-12 col-lg-7">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-bar-chart-line fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">6-Month Collection Trend</h6>
                </div>
                <span class="badge bg-primary-subtle text-primary font-monospace rounded-pill">Actual Realized</span>
            </div>
            <div class="dashboard-card-body">
                @php
                    $trendData = $charts['collection_trend']['data'] ?? [];
                    $trendLabels = $charts['collection_trend']['labels'] ?? [];
                    $maxCollection = max(array_merge([1], $trendData));
                @endphp
                <div class="d-flex align-items-end justify-content-between gap-2 pt-3 pb-2" style="min-height: 180px;">
                    @foreach($trendLabels as $idx => $label)
                        @php
                            $val = $trendData[$idx] ?? 0;
                            $pct = $maxCollection > 0 ? max(8, round(($val / $maxCollection) * 100)) : 8;
                        @endphp
                        <div class="text-center flex-fill d-flex flex-column align-items-center justify-content-end h-100">
                            <span class="small font-monospace fw-bold text-dark mb-1" style="font-size: 0.7rem;">₹{{ number_format($val) }}</span>
                            <div class="w-100 rounded-top bg-primary" style="height: {{ $pct }}%; min-height: 12px; background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%); transition: height 0.3s ease;"></div>
                            <span class="text-muted text-truncate mt-2" style="font-size: 0.7rem; max-width: 60px;">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Portfolio At Risk & Delinquency Aging -->
    <div class="col-12 col-lg-5">
        <div class="dashboard-card h-100">
            <div class="dashboard-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-exclamation-triangle fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">PAR & Overdue Aging</h6>
                </div>
                <span class="badge bg-danger-subtle text-danger font-monospace rounded-pill">{{ number_format($charts['par_30_pct'] ?? 0, 1) }}% PAR 30</span>
            </div>
            <div class="dashboard-card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-column gap-2.5">
                    @php
                        $bColors = [
                            '1-30 Days' => ['badge' => 'bg-warning-subtle text-warning-emphasis', 'bar' => 'bg-warning'],
                            '31-60 Days' => ['badge' => 'bg-warning text-dark', 'bar' => 'bg-warning'],
                            '61-90 Days' => ['badge' => 'bg-danger-subtle text-danger', 'bar' => 'bg-danger'],
                            '90+ Days (NPA)' => ['badge' => 'bg-danger text-white', 'bar' => 'bg-danger'],
                        ];
                        $totalAgingOverdue = array_sum($charts['overdue_buckets'] ?? []);
                    @endphp
                    @foreach($charts['overdue_buckets'] as $bLabel => $bAmount)
                        @php
                            $bPct = $totalAgingOverdue > 0 ? round(($bAmount / $totalAgingOverdue) * 100, 1) : 0;
                            $style = $bColors[$bLabel] ?? ['badge' => 'bg-secondary', 'bar' => 'bg-secondary'];
                        @endphp
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge {{ $style['badge'] }} px-2 py-0.5 rounded-pill font-monospace" style="font-size: 0.7rem;">{{ $bLabel }}</span>
                                <span class="fw-bold font-monospace text-dark" style="font-size: 0.8rem;">₹{{ number_format($bAmount, 2) }}</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar {{ $style['bar'] }}" role="progressbar" style="width: {{ $bPct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="pt-3 border-top mt-2 d-flex justify-content-between align-items-center small text-muted">
                    <span>Delinquent Overdue: <strong class="text-danger font-monospace">₹{{ number_format($totalAgingOverdue, 2) }}</strong></span>
                    @can('overdue.view')
                        <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-xs btn-outline-danger rounded-pill px-2.5">Detailed PAR</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endcanany

<!-- 5. RECENT REAL TRANSACTIONS & RECENT ACTIVITIES -->
<div class="row g-3 mb-4">
    <!-- Recent Member Collections Table -->
    @canany(['collection.view', 'loan.collection.view', 'loan.view'])
        <div class="col-lg-7">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-receipt text-primary me-2"></i>Recent Collections</h6>
                    <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All</a>
                </div>

                @if($recentRepayments->isNotEmpty())
                    <div class="dashboard-table-container py-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Borrower / Loan #</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentRepayments as $rep)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $rep->loanAccount->customer->full_name ?? 'N/A' }}</div>
                                                <small class="text-muted font-monospace">{{ $rep->loanAccount->loan_number ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border text-uppercase">{{ str_replace('_', ' ', $rep->payment_method) }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-success font-monospace">₹{{ number_format($rep->amount, 2) }}</td>
                                            <td class="text-end text-muted">{{ $rep->payment_date ? \Carbon\Carbon::parse($rep->payment_date)->format('d M Y') : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="dashboard-card-body text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 text-secondary d-block mb-1"></i>
                        No collections recorded
                    </div>
                @endif
            </div>
        </div>
    @endcanany

    <!-- Recent System Activities Timeline -->
    @canany(['audit_logs.view', 'company.view', 'branch.view'])
        <div class="col-lg-5">
            <div class="dashboard-card h-100">
                <div class="dashboard-card-header">
                    <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-activity text-info me-2"></i>Recent Activities</h6>
                    <span class="badge bg-light text-muted border">System Audit</span>
                </div>

                <div class="dashboard-card-body">
                    @if($recentActivities->isNotEmpty())
                        <div class="timeline small">
                            @foreach($recentActivities as $act)
                                <div class="d-flex gap-3 mb-3 pb-2 border-bottom">
                                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0; background-color: #2563eb !important;">
                                        <i class="bi bi-check2"></i>
                                    </div>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark">{{ ucwords(str_replace('_', ' ', $act->event)) }}</strong>
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $act->created_at->diffForHumans() }}</small>
                                        </div>
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">By: {{ $act->user->name ?? 'System' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-journal-x fs-2 text-secondary d-block mb-1"></i>
                            No recent activity
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endcanany
</div>

<!-- 6. RECENT LOAN APPLICATIONS -->
@can('loan_application.view')
    @if($recentApplications->isNotEmpty())
    <div class="dashboard-card mb-4">
        <div class="dashboard-card-header">
            <h6 class="fw-bold mb-0 font-heading text-dark"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Recent Loan Applications</h6>
            <a href="{{ route('admin.loan-application.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold p-0">View All Applications</a>
        </div>
        <div class="dashboard-table-container py-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>App Number</th>
                            <th>Applicant Name</th>
                            <th>Scheme</th>
                            <th class="text-end">Requested Amount</th>
                            <th>Status</th>
                            <th class="text-end">Applied Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentApplications as $app)
                            <tr>
                                <td class="font-monospace fw-bold">
                                    <a href="{{ route('admin.loan-application.show', $app->id) }}" class="text-decoration-none">{{ $app->application_number }}</a>
                                </td>
                                <td>{{ $app->customer->full_name ?? 'N/A' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $app->loanScheme->name ?? 'N/A' }}</span></td>
                                <td class="text-end font-monospace fw-bold">₹{{ number_format($app->requested_amount, 2) }}</td>
                                <td>
                                    @php
                                        $bClass = match($app->status) {
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            'under_review', 'submitted' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $bClass }} text-capitalize">{{ str_replace('_', ' ', $app->status) }}</span>
                                </td>
                                <td class="text-end text-muted">{{ $app->application_date ? \Carbon\Carbon::parse($app->application_date)->format('d M Y') : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endcan

@endsection
