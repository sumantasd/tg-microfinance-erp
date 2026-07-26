@extends('layouts.admin')

@section('title', 'Admin Dashboard - TG Microfinance ERP')

@section('content')
<!-- Developer Comment: Future Module Integration Canvas -->
<!-- Future Module -->

<!-- 1. KPI STATISTICS CARDS -->
<div class="row g-3 mb-4">
    <!-- Total Customers -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-ui.card class="p-3 border-start border-4 border-primary shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase small fw-bold text-muted" style="font-size: 0.725rem;">Total Customers</span>
                <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-people fs-6"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-1 text-dark">50,420</h3>
            <div class="d-flex align-items-center small text-success fw-semibold">
                <i class="bi bi-arrow-up-right me-1"></i> +8.4% <span class="text-muted ms-1 font-normal">from last month</span>
            </div>
        </x-ui.card>
    </div>

    <!-- Active Loans -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-ui.card class="p-3 border-start border-4 border-success shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase small fw-bold text-muted" style="font-size: 0.725rem;">Active Loan Portfolio</span>
                <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-cash-stack fs-6"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-1 text-dark">$12,450,000</h3>
            <div class="d-flex align-items-center small text-success fw-semibold">
                <i class="bi bi-arrow-up-right me-1"></i> +12.1% <span class="text-muted ms-1 font-normal">disbursement growth</span>
            </div>
        </x-ui.card>
    </div>

    <!-- Total Savings -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-ui.card class="p-3 border-start border-4 border-info shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase small fw-bold text-muted" style="font-size: 0.725rem;">Savings Deposits</span>
                <div class="bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-piggy-bank fs-6"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-1 text-dark">$4,820,300</h3>
            <div class="d-flex align-items-center small text-info fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Stable Vault Liquidity
            </div>
        </x-ui.card>
    </div>

    <!-- Today's Collection -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-ui.card class="p-3 border-start border-4 border-warning shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-uppercase small fw-bold text-muted" style="font-size: 0.725rem;">Today's Collections</span>
                <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-journal-check fs-6"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-1 text-dark">$48,500</h3>
            <div class="d-flex align-items-center small text-success fw-semibold">
                <i class="bi bi-arrow-up-right me-1"></i> 98.4% <span class="text-muted ms-1 font-normal">target recovery</span>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- 2. QUICK ACTIONS & OPERATIONAL PANELS -->
<div class="row g-3 mb-4">
    <!-- Quick Actions Panel -->
    <div class="col-lg-8">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-lightning-charge text-primary me-2"></i>Quick Actions</h5>
                <span class="badge bg-light text-muted border">Frequent Tasks</span>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="{{ url('/admin/customer') }}" class="btn btn-outline-primary p-3 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        <i class="bi bi-person-plus fs-2 mb-2"></i>
                        <span class="small fw-bold">New Customer</span>
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

    <!-- Pending Loan Approvals Widget -->
    <div class="col-lg-4">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Pending Approvals</h5>
                <span class="badge bg-warning text-dark fw-bold">5 Action Required</span>
            </div>
            <div class="list-group list-group-flush small">
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2025-092</strong>
                        <span class="text-muted">Micro-Enterprise ($3,500)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2025-094</strong>
                        <span class="text-muted">Group Solidarity ($1,200)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
                <div class="list-group-item px-0 py-2.5 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-dark">Loan #LN-2025-098</strong>
                        <span class="text-muted">SME Expansion ($15,000)</span>
                    </div>
                    <a href="{{ url('/admin/loan') }}" class="btn btn-sm btn-outline-primary rounded-pill">Review</a>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- 3. RECENT CUSTOMERS & ACTIVITIES -->
<div class="row g-3">
    <!-- Recent Customers Table -->
    <div class="col-lg-7">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-check text-primary me-2"></i>Recent Customers</h5>
                <a href="{{ url('/admin/customer') }}" class="btn btn-sm btn-outline-primary rounded-pill">View All</a>
            </div>
            <x-ui.data-table :headers="['Customer Name', 'Account #', 'Branch', 'Status']">
                <tr>
                    <td class="fw-bold text-dark">Robert Vance</td>
                    <td><span class="font-monospace text-muted">CUST-1049</span></td>
                    <td>Commercial Market</td>
                    <td><span class="badge bg-success-subtle text-success">Active</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Sarah Jenkins</td>
                    <td><span class="font-monospace text-muted">CUST-1050</span></td>
                    <td>Head Office</td>
                    <td><span class="badge bg-success-subtle text-success">Active</span></td>
                </tr>
                <tr>
                    <td class="fw-bold text-dark">Elena Rostova</td>
                    <td><span class="font-monospace text-muted">CUST-1051</span></td>
                    <td>Eastern Agricultural</td>
                    <td><span class="badge bg-warning-subtle text-warning">Pending KYC</span></td>
                </tr>
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <!-- Recent System Activities Timeline -->
    <div class="col-lg-5">
        <x-ui.card class="p-4 shadow-sm h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-activity text-info me-2"></i>Recent Audit Activities</h5>
                <a href="{{ url('/admin/system/audit-logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Audit Log</a>
            </div>
            <div class="timeline small">
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-journal-plus"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">Collection Sheet Posted</strong>
                        <span class="text-muted d-block">Officer John posted $1,250 collection sheet</span>
                        <small class="text-muted" style="font-size: 0.7rem;">10 minutes ago</small>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">Loan Disbursed</strong>
                        <span class="text-muted d-block">Disbursed $5,000 for Loan #LN-2025-088</span>
                        <small class="text-muted" style="font-size: 0.7rem;">42 minutes ago</small>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div class="bg-info text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <strong class="d-block text-dark">New Customer Enrolled</strong>
                        <span class="text-muted d-block">Enrolled customer CUST-1051 at Eastern Branch</span>
                        <small class="text-muted" style="font-size: 0.7rem;">1 hour ago</small>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
