@extends('layouts.admin')

@section('title', 'SaaS Finance ERP Dashboard - TG Microfinance ERP')

@section('content')

<!-- 1. TOP HEADER BANNER: GREETING, BRANCH SELECTOR, SYSTEM STATUS & ACTIONS -->
<div class="card border-0 shadow-sm rounded-4 p-3.5 mb-3 bg-white">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-clock me-1"></i>{{ date('l, F j, Y') }}
                </span>
                <span class="badge bg-success-subtle text-success rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-building me-1"></i>Head Office Branch
                </span>
                <span class="badge bg-info-subtle text-info rounded-pill font-monospace" style="font-size: 0.725rem;">
                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>System Live
                </span>
            </div>
            <h3 class="fw-bold text-dark mb-0 font-heading">
                Good {{ date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->check() ? auth()->user()->name : 'Super Admin' }} 👋
            </h3>
            <small class="text-muted">Here is your daily microfinance portfolio overview, vault liquidity, and operational metrics.</small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Export Report Button -->
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-download"></i>
                <span>Export Report</span>
            </button>

            <!-- New Application Button -->
            <a href="{{ url('/admin/loan') }}" class="btn btn-primary rounded-pill px-3 py-1.5 btn-sm fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>New Application</span>
            </a>
        </div>
    </div>
</div>

<!-- 2. ERP QUICK ACTIONS RIBBON (6 DIRECT SHORTCUTS) -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-uppercase small fw-bold text-muted font-monospace" style="letter-spacing: 0.8px;">ERP Quick Action Ribbon</span>
        <small class="text-primary fw-semibold opacity-75">Frequent Workflows</small>
    </div>
    <div class="row g-2">
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('/admin/customer') }}" class="tg-quick-action-btn">
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
            <a href="{{ url('/admin/loan') }}" class="tg-quick-action-btn">
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
            <a href="{{ url('/admin/collection') }}" class="tg-quick-action-btn">
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
            <a href="{{ url('/admin/billing') }}" class="tg-quick-action-btn">
                <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-receipt fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Product Billing</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Counter Invoice</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('/admin/inventory') }}" class="tg-quick-action-btn">
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-box-seam fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Stock Transfer</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">Vault & Inventory</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ url('/admin/accounting') }}" class="tg-quick-action-btn">
                <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                    <i class="bi bi-calculator fs-6"></i>
                </div>
                <div>
                    <strong class="d-block text-dark small lh-1">Expense Entry</strong>
                    <small class="text-muted opacity-75" style="font-size: 0.675rem;">General Ledger</small>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- 3. 10 SAAS FINANCIAL KPI CARDS GRID -->
<div class="row g-2.5 mb-4">
    <!-- 1. Total Members -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Total Members"
            value="50,420"
            icon="bi-people"
            iconBg="primary"
            badgeText="+8.4% MoM"
            badgeType="success"
            subtitle="Registered Borrowers"
        />
    </div>

    <!-- 2. Active Loans -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Active Loans"
            value="₹12,450,000"
            icon="bi-cash-stack"
            iconBg="success"
            badgeText="1,240 Active"
            badgeType="success"
            subtitle="Portfolio Outstanding"
        />
    </div>

    <!-- 3. Today's Collection -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Today's Collection"
            value="₹485,000"
            icon="bi-journal-check"
            iconBg="warning"
            badgeText="98.4% Target"
            badgeType="success"
            subtitle="Daily Recovery Rate"
        />
    </div>

    <!-- 4. Today's Disbursement -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Today's Disbursement"
            value="₹320,000"
            icon="bi-box-arrow-up-right"
            iconBg="info"
            badgeText="14 Disbursed"
            badgeType="info"
            subtitle="Counter & Field Credit"
        />
    </div>

    <!-- 5. Pending EMI -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Pending EMI"
            value="₹142,000"
            icon="bi-clock-history"
            iconBg="warning"
            badgeText="38 Due"
            badgeType="warning"
            subtitle="Today's Uncollected EMI"
        />
    </div>

    <!-- 6. Outstanding Amount -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Outstanding Loans"
            value="₹1,850,000"
            icon="bi-exclamation-triangle"
            iconBg="danger"
            badgeText="3.2% PAR"
            badgeType="danger"
            subtitle="Overdue Principal"
        />
    </div>

    <!-- 7. Total Savings -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Total Savings"
            value="₹4,820,300"
            icon="bi-piggy-bank"
            iconBg="primary"
            badgeText="Liquidity OK"
            badgeType="success"
            subtitle="Member Deposits"
        />
    </div>

    <!-- 8. Cash Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Cash Balance"
            value="₹1,245,600"
            icon="bi-safe"
            iconBg="warning"
            badgeText="Vault Limit"
            badgeType="warning"
            subtitle="Branch Vault Reserves"
        />
    </div>

    <!-- 9. Bank Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Bank Balance"
            value="₹18,920,400"
            icon="bi-building-check"
            iconBg="success"
            badgeText="Reconciled"
            badgeType="success"
            subtitle="Commercial Accounts"
        />
    </div>

    <!-- 10. Profit / Loss -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-2.4">
        <x-ui.kpi-card 
            title="Profit / Loss (YTD)"
            value="+ ₹2,140,800"
            icon="bi-graph-up-arrow"
            iconBg="success"
            badgeText="Net Yield"
            badgeType="success"
            subtitle="Audited Financials"
        />
    </div>
</div>

<!-- 4. 5 DEDICATED MODULE OVERVIEW CARDS (HIGH DENSITY MICROFINANCE ERP CARDS) -->
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
                <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">View All</a>
            </div>

            <div class="row g-2 mb-3 text-center">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Active Portfolio</small>
                        <strong class="text-dark font-heading">₹1.24 Cr</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Approvals</small>
                        <strong class="text-warning font-heading">5 Apps</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Overdue Principal</small>
                        <strong class="text-danger font-heading">₹18.5 L</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's EMI Due</small>
                        <strong class="text-primary font-heading">₹4.85 L</strong>
                    </div>
                </div>
            </div>

            <div class="small fw-semibold text-secondary mb-1">Portfolio Product Breakdown</div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 45%" title="Micro-Enterprise (45%)"></div>
                <div class="progress-bar bg-success" role="progressbar" style="width: 30%" title="Group Credit (30%)"></div>
                <div class="progress-bar bg-warning" role="progressbar" style="width: 15%" title="SME Expansion (15%)"></div>
                <div class="progress-bar bg-info" role="progressbar" style="width: 10%" title="Agri Credit (10%)"></div>
            </div>
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
                <a href="{{ url('/admin/collection') }}" class="btn btn-sm btn-link text-warning text-decoration-none fw-semibold">View Sheet</a>
            </div>

            <div class="row g-2 mb-3 text-center">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's Target</small>
                        <strong class="text-dark font-heading">₹4.92 L</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Recovery Rate</small>
                        <strong class="text-success font-heading">98.4%</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Pending Collection</small>
                        <strong class="text-danger font-heading">₹1.42 L</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Doorstep vs Counter</small>
                        <strong class="text-info font-heading">72 / 28 %</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small text-muted">
                <span>Field Collection Posted: <strong>₹3.62 L</strong></span>
                <span class="badge bg-success-subtle text-success">Balanced</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Savings Overview -->
    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-piggy-bank fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">Savings Accounts</h6>
                </div>
                <a href="{{ url('/admin/savings') }}" class="btn btn-sm btn-link text-info text-decoration-none fw-semibold">Manage</a>
            </div>

            <div class="row g-2 mb-3 text-center">
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Total Deposits</small>
                        <strong class="text-dark font-heading">₹48.2 L</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Active Accounts</small>
                        <strong class="text-primary font-heading">12,420</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Monthly Growth</small>
                        <strong class="text-success font-heading">+14.2%</strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Liquidity Ratio</small>
                        <strong class="text-info font-heading">38.5%</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center small text-muted">
                <span>Monthly Interest Yield: <strong>₹32,400</strong></span>
                <span class="badge bg-info-subtle text-info">Capitalized</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Inventory Overview -->
    <div class="col-12 col-lg-6 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-box-seam fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">Inventory & Device Terminals</h6>
                </div>
                <a href="{{ url('/admin/inventory') }}" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold">Stock Ledger</a>
            </div>

            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Total Stock Value</small>
                        <strong class="text-dark font-heading">₹14.5 L</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Low Stock Reorder</small>
                        <strong class="text-danger font-heading">3 Items</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Device POS Active</small>
                        <strong class="text-success font-heading">42 Terminals</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Accounting Overview -->
    <div class="col-12 col-lg-6 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 p-3.5 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-danger-subtle text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                        <i class="bi bi-calculator fs-6"></i>
                    </div>
                    <h6 class="fw-bold mb-0 font-heading text-dark">General Ledger & Accounting</h6>
                </div>
                <a href="{{ url('/admin/accounting') }}" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold">GL Ledger</a>
            </div>

            <div class="row g-2 text-center">
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's Income</small>
                        <strong class="text-success font-heading">₹4.85 L</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Today's Expense</small>
                        <strong class="text-danger font-heading">₹1.24 L</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Net Cash Flow</small>
                        <strong class="text-primary font-heading">+ ₹3.61 L</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. RECENT TRANSACTIONS & RECENT ACTIVITIES TIMELINE -->
<div class="row g-3">
    <!-- Recent Member Transactions Table -->
    <div class="col-lg-7">
        <x-ui.card class="p-3.5 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-receipt text-primary me-2"></i>Recent Transactions</h6>
                <a href="{{ url('/admin/collection') }}" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
            </div>
            <x-ui.data-table :headers="['Member / Voucher', 'Transaction Type', 'Amount', 'Status']">
                <tr>
                    <td class="fw-bold text-dark">
                        <div>Robert Vance</div>
                        <small class="text-muted font-monospace">VOUCH-1049</small>
                    </td>
                    <td><span class="badge bg-success-subtle text-success">EMI Repayment</span></td>
                    <td class="fw-bold text-dark">₹4,454</td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">Success</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">
                        <div>Sarah Jenkins</div>
                        <small class="text-muted font-monospace">VOUCH-1050</small>
                    </td>
                    <td><span class="badge bg-info-subtle text-info">Savings Deposit</span></td>
                    <td class="fw-bold text-dark">₹1,500</td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">Success</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">
                        <div>Elena Rostova</div>
                        <small class="text-muted font-monospace">VOUCH-1051</small>
                    </td>
                    <td><span class="badge bg-warning-subtle text-warning">Loan Disbursement</span></td>
                    <td class="fw-bold text-dark">₹50,000</td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Disbursed</span></td>
                </tr>
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <!-- Recent System Activities Timeline -->
    <div class="col-lg-5">
        <x-ui.card class="p-3.5 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-activity text-info me-2"></i>Recent Activities</h6>
                <a href="{{ url('/admin/system/audit-logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Audit Log</a>
            </div>
            <div class="timeline small">
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0; background-color: #2563eb !important;">
                        <i class="bi bi-journal-plus"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">Collection Sheet Posted</strong>
                        <span class="text-muted d-block">Officer John posted ₹12,500 collection sheet</span>
                        <small class="text-muted" style="font-size: 0.7rem;">10 minutes ago</small>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">Loan Disbursed</strong>
                        <span class="text-muted d-block">Disbursed ₹50,000 for Loan #LN-2026-088</span>
                        <small class="text-muted" style="font-size: 0.7rem;">42 minutes ago</small>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div class="bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">New Member Enrolled</strong>
                        <span class="text-muted d-block">Enrolled member MEM-1051 at Eastern Branch</span>
                        <small class="text-muted" style="font-size: 0.7rem;">1 hour ago</small>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
