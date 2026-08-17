@extends('layouts.admin')

@section('title', 'Grihalaxmi Finance ERP')

@section('content')

<!-- 1. TOP HEADER BANNER: GREETING, BRANCH SELECTOR & OPERATIONAL CONTEXT -->
<div class="card border-0 shadow-sm rounded-4 p-3.5 mb-3 bg-white">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-clock me-1"></i>{{ date('l, F j, Y') }}
                </span>
                <span class="badge bg-success-subtle text-success rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-building me-1"></i>{{ $activeBranchName }}
                </span>
                <span class="badge bg-info-subtle text-info rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>System Live
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 font-heading">
                Good {{ date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->check() ? auth()->user()->name : 'User' }} 👋
            </h3>
            <small class="text-muted">Here is your daily microfinance portfolio overview, vault liquidity, and operational metrics.</small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @can('reports.view')
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary rounded-pill px-3 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-download"></i>
                    <span>Reports Center</span>
                </a>
            @endcan

            @can('loan_application.create')
                <a href="{{ route('admin.loan-application.create') }}" class="btn btn-primary rounded-pill px-3 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="bi bi-plus-circle"></i>
                    <span>New Application</span>
                </a>
            @endcan
        </div>
    </div>
</div>

<!-- 2. ERP QUICK ACTIONS RIBBON -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-uppercase small fw-bold text-muted font-monospace" style="letter-spacing: 0.8px;">ERP Quick Action Ribbon</span>
        <small class="text-primary fw-semibold opacity-75">Frequent Workflows</small>
    </div>
    <div class="row g-2">
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.customer.create') }}" class="tg-quick-action-btn">
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-person-plus fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">New Member</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">KYC Enrollment</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.loan-application.create') }}" class="tg-quick-action-btn">
                <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-file-earmark-plus fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Loan Application</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Credit Proposal</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.emi-collection.index') }}" class="tg-quick-action-btn">
                <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-journal-check fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">EMI Collection</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Post Recovery</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.product.index') }}" class="tg-quick-action-btn">
                <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-receipt fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Products</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Catalog & Items</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.inventory.index') }}" class="tg-quick-action-btn">
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-box-seam fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Stock Ledger</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Vault & Inventory</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('admin.accounting.vouchers.create') }}" class="tg-quick-action-btn">
                <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-calculator fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Voucher Entry</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">General Ledger</small>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- 3. REAL FINANCIAL KPI CARDS GRID -->
<div class="row g-2.5 mb-4">
    <!-- 1. Total Members -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Total Members"
            :value="number_format($kpis['total_customers'])"
            icon="bi-people"
            iconBg="primary"
            :badgeText="number_format($kpis['active_customers']) . ' Active'"
            badgeType="success"
            subtitle="Registered Borrowers"
        />
    </div>

    <!-- 2. Active Loans -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Active Loans"
            :value="'₹' . number_format($kpis['active_portfolio_amount'], 2)"
            icon="bi-cash-stack"
            iconBg="success"
            :badgeText="number_format($kpis['active_loans_count']) . ' Active'"
            badgeType="success"
            subtitle="Portfolio Principal"
        />
    </div>

    <!-- 3. Today's Collection -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Today's Collection"
            :value="'₹' . number_format($kpis['today_collection'], 2)"
            icon="bi-journal-check"
            iconBg="warning"
            :badgeText="'₹' . number_format($kpis['month_collection'], 2) . ' MTD'"
            badgeType="success"
            subtitle="Daily Recovery Total"
        />
    </div>

    <!-- 4. Today's Disbursement -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Today's Disbursement"
            :value="'₹' . number_format($kpis['today_disbursement'], 2)"
            icon="bi-box-arrow-up-right"
            iconBg="info"
            :badgeText="number_format($kpis['today_disbursed_count']) . ' Disbursed'"
            badgeType="info"
            subtitle="Credit Issued Today"
        />
    </div>

    <!-- 5. Pending EMI Due -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Pending EMI Due"
            :value="'₹' . number_format($kpis['today_pending_emi_amount'], 2)"
            icon="bi-clock-history"
            iconBg="warning"
            :badgeText="number_format($kpis['today_pending_emi_count']) . ' Installments'"
            badgeType="warning"
            subtitle="Today's Uncollected Due"
        />
    </div>

    <!-- 6. Total Overdue Principal -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Total Overdue"
            :value="'₹' . number_format($kpis['total_overdue_principal'], 2)"
            icon="bi-exclamation-triangle"
            iconBg="danger"
            :badgeText="number_format($kpis['par_30_rate'], 1) . '% PAR 30'"
            badgeType="danger"
            subtitle="Delinquent Principal"
        />
    </div>

    <!-- 7. Total Outstanding Demand -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Total Outstanding"
            :value="'₹' . number_format($kpis['total_outstanding'], 2)"
            icon="bi-pie-chart"
            iconBg="primary"
            :badgeText="number_format($kpis['pending_applications_count']) . ' Pending Apps'"
            badgeType="primary"
            subtitle="Gross Loan Demand"
        />
    </div>

    <!-- 8. Vault Cash Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Vault Cash"
            :value="'₹' . number_format($kpis['total_vault_cash'], 2)"
            icon="bi-safe"
            iconBg="warning"
            badgeText="In Vault"
            badgeType="warning"
            subtitle="Branch Vault Reserves"
        />
    </div>

    <!-- 9. Bank Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Bank Balance"
            :value="'₹' . number_format($kpis['total_bank_balance'], 2)"
            icon="bi-building-check"
            iconBg="success"
            badgeText="GL 1130"
            badgeType="success"
            subtitle="Commercial Accounts"
        />
    </div>

    <!-- 10. Net Yield (YTD Profit/Loss) -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Profit / Loss (YTD)"
            :value="($kpis['ytd_profit_loss'] >= 0 ? '+ ' : '') . '₹' . number_format($kpis['ytd_profit_loss'], 2)"
            icon="bi-graph-up-arrow"
            :iconBg="$kpis['ytd_profit_loss'] >= 0 ? 'success' : 'danger'"
            badgeText="Audited GL"
            :badgeType="$kpis['ytd_profit_loss'] >= 0 ? 'success' : 'danger'"
            subtitle="Revenue vs Expense"
        />
    </div>
</div>

<!-- 4. DEDICATED MODULE OVERVIEW CARDS -->
<div class="row g-3 mb-4">
    <!-- Card 1: Loan Management Overview -->
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">Loan Management</h6>
                </div>
                <a href="{{ route('admin.loan-account.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">View All</a>
            </div>

            <div class="row g-2 mb-3 text-center">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Active Portfolio</small>
                        <strong class="text-dark font-heading">₹{{ number_format($loanOverview['active_portfolio'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Approvals</small>
                        <strong class="text-warning font-heading">{{ $loanOverview['pending_approvals'] }} Apps</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Overdue Principal</small>
                        <strong class="text-danger font-heading">₹{{ number_format($loanOverview['overdue_principal'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's EMI Due</small>
                        <strong class="text-primary font-heading">₹{{ number_format($loanOverview['today_emi_due'], 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="small fw-semibold text-secondary mb-1">Portfolio Scheme Distribution</div>
            @if(count($loanOverview['product_breakdown']) > 0 && $loanOverview['total_scheme_portfolio'] > 0)
                <div class="progress" style="height: 10px;">
                    @php
                        $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger'];
                    @endphp
                    @foreach($loanOverview['product_breakdown'] as $idx => $item)
                        @if($item['percentage'] > 0)
                            <div class="progress-bar {{ $colors[$idx % count($colors)] }}" role="progressbar" style="width: {{ $item['percentage'] }}%" title="{{ $item['scheme_name'] }} ({{ $item['percentage'] }}%)"></div>
                        @endif
                    @endforeach
                </div>
                <div class="mt-2 small text-muted d-flex flex-wrap gap-2" style="font-size: 0.75rem;">
                    @foreach($loanOverview['product_breakdown'] as $idx => $item)
                        @if($item['percentage'] > 0)
                            <span><span class="badge {{ $colors[$idx % count($colors)] }} p-1 me-1"> </span>{{ $item['scheme_name'] }}: {{ $item['percentage'] }}%</span>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-muted small py-2 text-center bg-light rounded">No active loans found</div>
            @endif
        </div>
    </div>

    <!-- Card 2: Collection Overview -->
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-journal-check fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">Collection Performance</h6>
                </div>
                <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-sm btn-link text-warning text-decoration-none fw-semibold">View Sheet</a>
            </div>

            <div class="row g-2 mb-3 text-center">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's Target</small>
                        <strong class="text-dark font-heading">₹{{ number_format($collectionOverview['today_target'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Recovery Rate</small>
                        <strong class="text-success font-heading">{{ $collectionOverview['recovery_rate'] }}%</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Collection</small>
                        <strong class="text-danger font-heading">₹{{ number_format($collectionOverview['pending_collection'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Cash vs Digital</small>
                        <strong class="text-info font-heading">{{ $collectionOverview['cash_percentage'] }} / {{ $collectionOverview['digital_percentage'] }} %</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small text-muted">
                <span>Month Collection Total: <strong>₹{{ number_format($collectionOverview['month_collected'], 2) }}</strong></span>
                <span class="badge bg-success-subtle text-success">Verified</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Inventory & Goods Overview -->
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-box-seam fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">Branch Inventory</h6>
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">Stock Ledger</a>
            </div>

            <div class="row g-2 text-center mb-3">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Total Stock Valuation</small>
                        <strong class="text-dark font-heading">₹{{ number_format($inventoryOverview['total_stock_value'], 2) }}</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Low Stock Alerts</small>
                        <strong class="{{ $inventoryOverview['low_stock_count'] > 0 ? 'text-danger' : 'text-success' }} font-heading">{{ $inventoryOverview['low_stock_count'] }} Items</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small text-muted">
                <span>Active Products in Catalog: <strong>{{ $inventoryOverview['active_products_count'] }}</strong></span>
                <a href="{{ route('admin.product.index') }}" class="text-decoration-none small">Manage Items</a>
            </div>
        </div>
    </div>
</div>

<!-- 5. RECENT REAL TRANSACTIONS & RECENT ACTIVITIES -->
<div class="row g-3 mb-4">
    <!-- Recent Member Collections Table -->
    <div class="col-lg-7">
        <x-ui.card class="p-3.5 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-receipt text-primary me-2"></i>Recent Collections</h6>
                <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
            </div>
            
            @if($recentRepayments->isNotEmpty())
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
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2 text-secondary d-block mb-1"></i>
                    No collections recorded
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Recent System Activities Timeline -->
    <div class="col-lg-5">
        <x-ui.card class="p-3.5 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-activity text-info me-2"></i>Recent Activities</h6>
                <span class="badge bg-light text-muted border">System Audit</span>
            </div>
            
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
        </x-ui.card>
    </div>
</div>

<!-- 6. RECENT LOAN APPLICATIONS -->
@if($recentApplications->isNotEmpty())
<div class="card border-0 shadow-sm rounded-4 p-3.5 bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <h6 class="fw-bold mb-0 font-heading text-dark"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Recent Loan Applications</h6>
        <a href="{{ route('admin.loan-application.index') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">View All Applications</a>
    </div>
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
@endif

@endsection
