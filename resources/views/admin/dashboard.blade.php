@extends('layouts.admin')

@section('title', 'Admin ERP Dashboard - TG Microfinance ERP')

@section('content')

<!-- 1. ENTERPRISE KPI CARDS (9 REQUIRED FINANCIAL & OPERATIONAL MODULE CARDS) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Members -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
        <x-ui.kpi-card 
            title="Total Members"
            value="50,420"
            icon="bi-people"
            iconBg="primary"
            badgeText="+8.4% MoM"
            badgeType="success"
            subtitle="Registered Customers"
        />
    </div>

    <!-- 2. Active Loans -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
        <x-ui.kpi-card 
            title="Active Loans"
            value="₹12,450,000"
            icon="bi-cash-stack"
            iconBg="success"
            badgeText="+12.1%"
            badgeType="success"
            subtitle="Portfolio Value"
        />
    </div>

    <!-- 3. Today's Collection -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
        <x-ui.kpi-card 
            title="Today's Collection"
            value="₹485,000"
            icon="bi-journal-check"
            iconBg="warning"
            badgeText="98.4%"
            badgeType="success"
            subtitle="Target Recovery"
        />
    </div>

    <!-- 4. Today's Disbursement -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
        <x-ui.kpi-card 
            title="Today's Disbursement"
            value="₹320,000"
            icon="bi-box-arrow-up-right"
            iconBg="info"
            badgeText="14 Loans"
            badgeType="info"
            subtitle="Counter & Mobile Field"
        />
    </div>

    <!-- 5. Savings -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
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

    <!-- 6. Cash Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
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

    <!-- 7. Bank Balance -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
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

    <!-- 8. Stock / Inventory -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
        <x-ui.kpi-card 
            title="Total Stock"
            value="1,450 Units"
            icon="bi-box-seam"
            iconBg="info"
            badgeText="In Stock"
            badgeType="info"
            subtitle="Stationery & Devices"
        />
    </div>

    <!-- 9. Profit / Loss -->
    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
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

<!-- 2. ANALYTICS CHARTS (4 REQUIRED ERP ANALYTICS PLACEHOLDERS) -->
<div class="row g-3 mb-4">
    <!-- Chart 1: Collection Analytics -->
    <div class="col-12 col-lg-6">
        <x-ui.chart-card 
            title="Collection Analytics"
            subtitle="Target vs Actual Daily Collections"
            badgeText="Monthly Trend"
            badgeType="warning"
        />
    </div>

    <!-- Chart 2: Loan Growth -->
    <div class="col-12 col-lg-6">
        <x-ui.chart-card 
            title="Loan Growth & Disbursement"
            subtitle="Disbursement Velocity Across Quarters"
            badgeText="Portfolio Growth"
            badgeType="success"
        />
    </div>

    <!-- Chart 3: Branch Performance -->
    <div class="col-12 col-lg-6">
        <x-ui.chart-card 
            title="Branch Performance Comparison"
            subtitle="Top Performing Regional Branches"
            badgeText="Branch Ranking"
            badgeType="primary"
        />
    </div>

    <!-- Chart 4: Savings Growth -->
    <div class="col-12 col-lg-6">
        <x-ui.chart-card 
            title="Savings Growth & Liquidity"
            subtitle="Deposit Net Inflow & Accumulation"
            badgeText="Vault Liquidity"
            badgeType="info"
        />
    </div>
</div>

<!-- 3. QUICK ACTIONS & OPERATIONAL PANELS -->
<div class="row g-3 mb-4">
    <!-- Quick Actions Panel -->
    <div class="col-lg-8">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-lightning-charge text-primary me-2"></i>Quick Actions</h5>
                <span class="badge bg-light text-muted border">Frequent Tasks</span>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="{{ url('/admin/customer') }}" class="btn btn-outline-primary p-3 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        <i class="bi bi-person-plus fs-2 mb-2"></i>
                        <span class="small fw-bold">New Member</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ url('/admin/loan') }}" class="btn btn-outline-success p-3 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        <i class="bi bi-file-earmark-plus fs-2 mb-2"></i>
                        <span class="small fw-bold">Issue Loan</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ url('/admin/collection') }}" class="btn btn-outline-info p-3 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        <i class="bi bi-receipt-cutoff fs-2 mb-2"></i>
                        <span class="small fw-bold">Post Collection</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ url('/admin/reports') }}" class="btn btn-outline-secondary p-3 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        <i class="bi bi-file-earmark-bar-graph fs-2 mb-2"></i>
                        <span class="small fw-bold">Generate Report</span>
                    </a>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Pending Approvals Widget -->
    <div class="col-lg-4">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-clock-history text-warning me-2"></i>Pending Approvals</h5>
                <span class="badge bg-warning text-dark fw-bold">5 Action Required</span>
            </div>
            <div class="list-group list-group-flush small">
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2026-092</strong>
                        <span class="text-muted">Micro-Enterprise (₹50,000)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2026-094</strong>
                        <span class="text-muted">Group Solidarity (₹25,000)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2026-098</strong>
                        <span class="text-muted">SME Expansion (₹150,000)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- 4. RECENT MEMBERS & AUDIT LOG TIMELINE -->
<div class="row g-3">
    <!-- Recent Members Table -->
    <div class="col-lg-7">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-person-check text-primary me-2"></i>Recent Members</h5>
                <a href="{{ url('/admin/customer') }}" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
            </div>
            <x-ui.data-table :headers="['Member Name', 'Account #', 'Branch', 'Status']">
                <tr>
                    <td class="fw-bold text-dark">Robert Vance</td>
                    <td><span class="font-monospace text-muted">MEM-1049</span></td>
                    <td>Commercial Market</td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">Active</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Sarah Jenkins</td>
                    <td><span class="font-monospace text-muted">MEM-1050</span></td>
                    <td>Head Office</td>
                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">Active</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Elena Rostova</td>
                    <td><span class="font-monospace text-muted">MEM-1051</span></td>
                    <td>Eastern Agricultural</td>
                    <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending KYC</span></td>
                </tr>
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <!-- Recent Audit Log Activities -->
    <div class="col-lg-5">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-heading"><i class="bi bi-activity text-info me-2"></i>Audit Logs</h5>
                <a href="{{ url('/admin/system/audit-logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill">View Log</a>
            </div>
            <div class="timeline small">
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
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
