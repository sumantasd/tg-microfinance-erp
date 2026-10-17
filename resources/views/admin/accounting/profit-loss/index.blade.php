@extends('layouts.admin')

@section('title', 'Enterprise Profit & Loss Statement - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-8 fw-semibold rounded-pill">
                    <i class="bi bi-shield-lock-fill me-1"></i>Head Office Admin Only
                </span>
                <span class="text-muted small">|</span>
                <span class="text-muted small">Financial Intelligence & Margin Audit</span>
            </div>
            <h4 class="fw-bold text-dark mb-1 mt-1">
                <i class="bi bi-graph-up-arrow text-success me-2"></i>Enterprise Profit &amp; Loss Statement
            </h4>
            <p class="text-muted small mb-0">
                Audited real-time P&amp;L combining Product Loan Margin Lifecycles, Direct Sales COGS, and Double-Entry GL Ledger entries.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Statement
            </button>
            @can('profit_loss.export')
            <a href="{{ route('admin.accounting.profit-loss.export', request()->all()) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm btn-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Audited CSV
            </a>
            @endcan
            <a href="{{ route('admin.accounting.dashboard') }}" class="btn btn-primary rounded-pill px-4 shadow-sm btn-sm text-white fw-bold">
                <i class="bi bi-calculator me-1"></i> Accounting Dashboard
            </a>
        </div>
    </div>

    <!-- Filter & Scope Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="{{ route('admin.accounting.profit-loss.index') }}" id="pnlFilterForm">
                <div class="row g-3 align-items-end">
                    @if(auth()->user()->isSuperAdmin() && $companies->count() > 1)
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Company Scope</label>
                        <select name="company_id" class="form-select form-select-sm rounded-3 shadow-none">
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}" {{ $companyId == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-{{ auth()->user()->isSuperAdmin() && $companies->count() > 1 ? '3' : '4' }}">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">Branch Scope</label>
                        <select name="branch_id" class="form-select form-select-sm rounded-3 shadow-none">
                            <option value="">All Branches (Consolidated Enterprise)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }} ({{ $b->branch_code ?? 'BR-'.$b->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">From Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control form-control-sm rounded-3 shadow-none" value="{{ $fromDate }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-1">To Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control form-control-sm rounded-3 shadow-none" value="{{ $toDate }}">
                    </div>

                    <div class="col-md-{{ auth()->user()->isSuperAdmin() && $companies->count() > 1 ? '2' : '4' }} d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm w-100">
                            <i class="bi bi-funnel me-1"></i> Apply Filter
                        </button>
                        <a href="{{ route('admin.accounting.profit-loss.index') }}" class="btn btn-light btn-sm rounded-pill px-3 border shadow-sm" title="Reset Filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>

                <!-- Date Range Quick Presets -->
                <div class="d-flex gap-2 mt-3 pt-2 border-top flex-wrap align-items-center">
                    <span class="small text-muted fw-semibold me-1"><i class="bi bi-lightning-charge text-warning me-1"></i>Quick Period:</span>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2" onclick="setPresetDates('this_month')">This Month</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2" onclick="setPresetDates('last_month')">Last Month</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2" onclick="setPresetDates('this_fy')">Current FY (India)</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2" onclick="setPresetDates('all_time')">All Time</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Filter Info Banner -->
    <div class="alert alert-light border border-secondary-subtle rounded-3 py-2 px-3 mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 shadow-sm">
        <div class="small text-dark">
            <i class="bi bi-calendar-check text-primary me-2"></i>
            <strong>Active Audit Window:</strong>
            <span class="text-primary fw-semibold">{{ $fromDate ?: 'All Past' }}</span> to <span class="text-primary fw-semibold">{{ $toDate ?: 'Today' }}</span>
            <span class="mx-2 text-muted">|</span>
            <i class="bi bi-geo-alt text-danger me-1"></i>
            <strong>Scope:</strong> {{ $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? 'Branch #'.$branchId) : 'All Branches (Consolidated)' }}
        </div>
        <div class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>Double-Entry GL &amp; Margin Verified
        </div>
    </div>

    <!-- Primary KPI Hero Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- 1. Pending Profit (Product Loans) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white border-start border-warning border-4 p-3 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Pending Profit</div>
                        <div class="fs-4 fw-bold text-dark mt-1">₹{{ number_format($pnl['pending_product_profit'], 2) }}</div>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1 fs-8">
                        Unrealized Margin
                    </span>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-hourglass-split text-warning me-1"></i>{{ $pnl['product_loan']['pending_count'] }} Active Product Loans</span>
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    Held as pending until loan reaches 100% full closure.
                </div>
            </div>
        </div>

        <!-- 2. Final Profit (Product Loans) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white border-start border-success border-4 p-3 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Final Profit</div>
                        <div class="fs-4 fw-bold text-success mt-1">₹{{ number_format($pnl['final_product_profit'], 2) }}</div>
                    </div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 fs-8">
                        Realized Profit
                    </span>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-check-circle-fill text-success me-1"></i>{{ $pnl['product_loan']['finalized_count'] }} Legitimate Closures</span>
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    Transferred exactly once upon zero outstanding closure.
                </div>
            </div>
        </div>

        <!-- 3. Total Generated Margin -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white border-start border-info border-4 p-3 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Generated Margin</div>
                        <div class="fs-4 fw-bold text-info mt-1">₹{{ number_format($pnl['total_generated_margin'], 2) }}</div>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2 py-1 fs-8">
                        Margin Potential
                    </span>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center justify-content-between">
                    <span>Pending + Final Margin</span>
                    <span class="fw-semibold text-dark">Zero Double-Count</span>
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    Full margin volume across active and completed loans.
                </div>
            </div>
        </div>

        <!-- 4. Direct Sales Gross Profit -->
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white border-start border-primary border-4 p-3 position-relative overflow-hidden">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Direct Sales Gross Profit</div>
                        <div class="fs-4 fw-bold text-primary mt-1">₹{{ number_format($pnl['direct_sales_gross_profit'], 2) }}</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 fs-8">
                        Retail Margin
                    </span>
                </div>
                <div class="small text-muted mt-2 d-flex align-items-center justify-content-between">
                    <span>{{ $pnl['direct_sales']['sales_count'] }} Direct Invoices</span>
                    <span class="text-muted">COGS factored</span>
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    Actual retail sales minus purchase inventory cost.
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Summary Row: Income, Expenses & Net Profit Hero Card -->
    <div class="row g-3 mb-4">
        <!-- Cash Loan Revenue -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white p-3 border-start border-secondary border-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Cash Loan Operating Income</div>
                        <div class="fs-4 fw-bold text-dark mt-1">₹{{ number_format($pnl['cash_loan_revenue'], 2) }}</div>
                    </div>
                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-8">
                        GL 4xxx Posted
                    </span>
                </div>
                <div class="mt-2 small text-muted">
                    Interest (₹{{ number_format($pnl['accounting']['interest_cash_loan'], 2) }}), Processing (₹{{ number_format($pnl['accounting']['processing_fees'], 2) }}), Insurance, Penalties.
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check text-success me-1"></i>Principal repayments are NOT counted as revenue.
                </div>
            </div>
        </div>

        <!-- Total Operating Expenses -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 bg-white p-3 border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Operating Expenses</div>
                        <div class="fs-4 fw-bold text-danger mt-1">₹{{ number_format($pnl['total_operating_expenses'], 2) }}</div>
                    </div>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1 fs-8">
                        GL 5xxx Posted
                    </span>
                </div>
                <div class="mt-2 small text-muted">
                    Salaries, Rent, Utilities, Stationery, Travel, Bank Charges &amp; Write-Offs.
                </div>
                <div class="border-top mt-2 pt-2 text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check text-success me-1"></i>Principal disbursements are NOT counted as expenses.
                </div>
            </div>
        </div>

        <!-- Net Operating Profit / Loss Hero -->
        <div class="col-md-4">
            @php
                $isProfitable = $pnl['net_profit_or_loss'] >= 0;
            @endphp
            <div class="card h-100 border-0 shadow-sm rounded-4 text-white p-3 position-relative overflow-hidden {{ $isProfitable ? 'bg-success' : 'bg-danger' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-white-50 small fw-bold text-uppercase">Realized Net Profit / (Loss)</div>
                        <div class="fs-3 fw-bold mt-1">
                            {{ $isProfitable ? '+' : '-' }}₹{{ number_format(abs($pnl['net_profit_or_loss']), 2) }}
                        </div>
                    </div>
                    <span class="badge bg-white {{ $isProfitable ? 'text-success' : 'text-danger' }} rounded-pill px-3 py-1 fw-bold fs-7 shadow-sm">
                        {{ $isProfitable ? 'NET PROFIT' : 'NET LOSS' }}
                    </span>
                </div>
                <div class="mt-2 small text-white-75">
                    Recognized Income: ₹{{ number_format($pnl['total_recognized_income'], 2) }}<br>
                    Total Expenses: ₹{{ number_format($pnl['total_operating_expenses'], 2) }}
                </div>
                <div class="border-top border-white-50 mt-2 pt-2 text-white-50" style="font-size: 0.75rem;">
                    Pending Profit (₹{{ number_format($pnl['pending_product_profit'], 2) }}) is kept strictly unrealized.
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction-Level Details Tabs -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 border-0" id="pnlTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold py-3 px-4 border-0 border-bottom border-primary border-3" id="statement-tab" data-bs-toggle="tab" data-bs-target="#statement-pane" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-1"></i>Formal P&amp;L Statement
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-4 border-0" id="product-loans-tab" data-bs-toggle="tab" data-bs-target="#product-loans-pane" type="button" role="tab">
                        <i class="bi bi-box-seam me-1"></i>Product Loan Margins ({{ $pnl['product_loan']['pending_count'] + $pnl['product_loan']['finalized_count'] }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-4 border-0" id="direct-sales-tab" data-bs-toggle="tab" data-bs-target="#direct-sales-pane" type="button" role="tab">
                        <i class="bi bi-cart-check me-1"></i>Direct Sales Margin ({{ $pnl['direct_sales']['sales_count'] }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-4 border-0" id="gl-revenue-tab" data-bs-toggle="tab" data-bs-target="#gl-revenue-pane" type="button" role="tab">
                        <i class="bi bi-cash-coin me-1"></i>Cash Loan &amp; GL Income
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-4 border-0" id="gl-expenses-tab" data-bs-toggle="tab" data-bs-target="#gl-expenses-pane" type="button" role="tab">
                        <i class="bi bi-receipt me-1"></i>Operating Expenses
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="pnlTabContent">
                <!-- TAB 1: FORMAL P&L STATEMENT -->
                <div class="tab-pane fade show active" id="statement-pane" role="tabpanel" tabindex="0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60%;">Financial Classification / Line Item</th>
                                    <th class="text-end" style="width: 20%;">Sub-Total (INR)</th>
                                    <th class="text-end" style="width: 20%;">Total (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PART I: OPERATING REVENUE -->
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="text-uppercase text-primary">
                                        <i class="bi bi-arrow-down-right-circle me-1"></i>Part I: Recognized Operating Income
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Finalized Product Loan Margins</strong>
                                        <div class="small text-muted">Legitimately closed product loans with 100% full repayment.</div>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['final_product_profit'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Direct Product Sales Gross Profit</strong>
                                        <div class="small text-muted">Retail revenue (₹{{ number_format($pnl['direct_sales']['total_sales_revenue'], 2) }}) minus Inventory COGS (₹{{ number_format($pnl['direct_sales']['total_cogs'], 2) }}).</div>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['direct_sales_gross_profit'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Cash Loan Interest Income (GL 4110)</strong>
                                        <div class="small text-muted">Posted regular EMI interest collections on microfinance loans.</div>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['interest_cash_loan'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Product Loan EMI Interest Income (GL 4120)</strong>
                                        <div class="small text-muted">Posted EMI finance interest on product loan financing.</div>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['interest_product_loan'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Loan Processing Fees (GL 4210)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['processing_fees'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Borrower Insurance Administration Fees (GL 4220)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['insurance_fees'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Late Payment Penalties (GL 4230)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['penalty_income'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Loan Prepayment &amp; Foreclosure Charges (GL 4240)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['foreclosure_fees'], 2) }}</td>
                                    <td></td>
                                </tr>
                                @if($pnl['accounting']['other_operating_revenue'] != 0)
                                <tr>
                                    <td class="ps-4">
                                        <strong>Other Operating Revenue (GL 4xxx)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['other_operating_revenue'], 2) }}</td>
                                    <td></td>
                                </tr>
                                @endif
                                <tr class="fw-bold bg-success-subtle text-success">
                                    <td class="ps-3 text-uppercase">Total Recognized Operating Income</td>
                                    <td></td>
                                    <td class="text-end font-monospace fs-6">₹{{ number_format($pnl['total_recognized_income'], 2) }}</td>
                                </tr>

                                <!-- PART II: OPERATING EXPENSES -->
                                <tr class="table-light fw-bold">
                                    <td colspan="3" class="text-uppercase text-danger mt-3">
                                        <i class="bi bi-arrow-up-right-circle me-1"></i>Part II: Operating &amp; Business Expenses
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Personnel, Salaries &amp; Staff Welfare (GL 5210, 5220)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['personnel_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Branch &amp; Office Rent (GL 5310)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['rent_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Electricity, Utilities &amp; Internet (GL 5320)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['utilities_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Printing, Stationery &amp; Office Supplies (GL 5330)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['stationery_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Field Staff Travel &amp; Fuel Conveyance (GL 5340)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['travel_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Bank Charges, Processing Fees &amp; Gateway Costs (GL 5350)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['bank_charges_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">
                                        <strong>Loan Loss &amp; Bad Debt Write-Off Loss (GL 5120)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['write_off_expense'], 2) }}</td>
                                    <td></td>
                                </tr>
                                @if($pnl['accounting']['other_expenses'] != 0)
                                <tr>
                                    <td class="ps-4">
                                        <strong>Other General &amp; Administrative Expenses (GL 5xxx)</strong>
                                    </td>
                                    <td class="text-end font-monospace">₹{{ number_format($pnl['accounting']['other_expenses'], 2) }}</td>
                                    <td></td>
                                </tr>
                                @endif
                                <tr class="fw-bold bg-danger-subtle text-danger">
                                    <td class="ps-3 text-uppercase">Total Operating Expenses</td>
                                    <td></td>
                                    <td class="text-end font-monospace fs-6">₹{{ number_format($pnl['total_operating_expenses'], 2) }}</td>
                                </tr>

                                <!-- PART III: BOTTOM LINE -->
                                <tr class="fw-bold fs-5 {{ $pnl['net_profit_or_loss'] >= 0 ? 'table-success text-success' : 'table-danger text-danger' }}">
                                    <td class="py-3 text-uppercase">
                                        <i class="bi bi-calculator-fill me-2"></i>NET OPERATING PROFIT / (LOSS)
                                    </td>
                                    <td></td>
                                    <td class="text-end py-3 font-monospace">
                                        {{ $pnl['net_profit_or_loss'] >= 0 ? '+' : '-' }}₹{{ number_format(abs($pnl['net_profit_or_loss']), 2) }}
                                    </td>
                                </tr>

                                <!-- MEMO LINE: PENDING MARGIN -->
                                <tr class="table-warning border-top border-warning border-2">
                                    <td colspan="2" class="py-2">
                                        <i class="bi bi-info-circle-fill text-warning me-2"></i>
                                        <strong>Informational Memo: Pending Profit (Unrealized Margin on Active Product Loans)</strong>
                                        <div class="small text-muted">Excluded from Net Profit until full legitimate loan closure.</div>
                                    </td>
                                    <td class="text-end py-2 font-monospace fw-bold text-dark">
                                        ₹{{ number_format($pnl['pending_product_profit'], 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: PRODUCT LOAN MARGINS -->
                <div class="tab-pane fade" id="product-loans-pane" role="tabpanel" tabindex="0">
                    <!-- Finalized Margins Table -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-success mb-0">
                                <i class="bi bi-check2-circle me-1"></i>Finalized Product Loan Margins (Realized)
                            </h6>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                                Total Realized: ₹{{ number_format($pnl['final_product_profit'], 2) }} ({{ count($pnl['product_loan']['finalized_list']) }} Loans)
                            </span>
                        </div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan #</th>
                                        <th>Borrower</th>
                                        <th>Branch</th>
                                        <th>Financed Product(s)</th>
                                        <th class="text-end">Cost Price</th>
                                        <th class="text-end">Selling Price</th>
                                        <th class="text-end text-success">Realized Margin</th>
                                        <th>Sanction Date</th>
                                        <th>Closed Date</th>
                                        <th>Finalized By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pnl['product_loan']['finalized_list'] as $row)
                                    <tr>
                                        <td class="fw-bold font-monospace">
                                            @if($row['loan_id'])
                                            <a href="{{ route('admin.loan-account.show', $row['loan_id']) }}" class="text-decoration-none">
                                                {{ $row['loan_number'] }}
                                            </a>
                                            @else
                                                {{ $row['loan_number'] }}
                                            @endif
                                        </td>
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['branch_name'] }}</td>
                                        <td>{{ $row['products'] }}</td>
                                        <td class="text-end font-monospace">₹{{ number_format($row['cost_price'], 2) }}</td>
                                        <td class="text-end font-monospace">₹{{ number_format($row['selling_price'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-success">+₹{{ number_format($row['margin_amount'], 2) }}</td>
                                        <td>{{ $row['sanction_date'] }}</td>
                                        <td>{{ $row['closed_at'] }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $row['finalized_by'] }}</span></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                            No finalized product loans found for the selected period.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Margins Table -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-warning mb-0">
                                <i class="bi bi-hourglass-split me-1"></i>Pending Product Loan Margins (Unrealized)
                            </h6>
                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">
                                Total Pending: ₹{{ number_format($pnl['pending_product_profit'], 2) }} ({{ count($pnl['product_loan']['pending_list']) }} Loans)
                            </span>
                        </div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan #</th>
                                        <th>Borrower</th>
                                        <th>Branch</th>
                                        <th>Financed Product(s)</th>
                                        <th class="text-end">Cost Price</th>
                                        <th class="text-end">Selling Price</th>
                                        <th class="text-end text-warning">Pending Margin</th>
                                        <th>Sanction Date</th>
                                        <th>Current Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pnl['product_loan']['pending_list'] as $row)
                                    <tr>
                                        <td class="fw-bold font-monospace">
                                            @if($row['loan_id'])
                                            <a href="{{ route('admin.loan-account.show', $row['loan_id']) }}" class="text-decoration-none">
                                                {{ $row['loan_number'] }}
                                            </a>
                                            @else
                                                {{ $row['loan_number'] }}
                                            @endif
                                        </td>
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['branch_name'] }}</td>
                                        <td>{{ $row['products'] }}</td>
                                        <td class="text-end font-monospace">₹{{ number_format($row['cost_price'], 2) }}</td>
                                        <td class="text-end font-monospace">₹{{ number_format($row['selling_price'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-warning">₹{{ number_format($row['margin_amount'], 2) }}</td>
                                        <td>{{ $row['sanction_date'] }}</td>
                                        <td><span class="badge bg-warning-subtle text-warning-emphasis">Active / Repaying</span></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                            No pending product loans currently active.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: DIRECT PRODUCT SALES -->
                <div class="tab-pane fade" id="direct-sales-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="bi bi-cart3 me-1"></i>Direct Retail Product Sales Margins
                        </h6>
                        <div class="d-flex gap-2">
                            <span class="badge bg-light text-dark border px-2 py-1">Revenue: ₹{{ number_format($pnl['direct_sales']['total_sales_revenue'], 2) }}</span>
                            <span class="badge bg-light text-dark border px-2 py-1">COGS: ₹{{ number_format($pnl['direct_sales']['total_cogs'], 2) }}</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">Gross Profit: ₹{{ number_format($pnl['direct_sales']['total_gross_profit'], 2) }}</span>
                        </div>
                    </div>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Sale #</th>
                                    <th>Customer</th>
                                    <th>Branch</th>
                                    <th>Sale Date</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-end">Net Revenue</th>
                                    <th class="text-end">Inventory COGS</th>
                                    <th class="text-end text-primary">Gross Profit</th>
                                    <th class="text-center">Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pnl['direct_sales']['sales_list'] as $s)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $s['sale_number'] }}</td>
                                    <td>{{ $s['customer_name'] }}</td>
                                    <td>{{ $s['branch_name'] }}</td>
                                    <td>{{ $s['sale_date'] }}</td>
                                    <td class="text-center">{{ $s['items_count'] }}</td>
                                    <td class="text-end font-monospace">₹{{ number_format($s['net_revenue'], 2) }}</td>
                                    <td class="text-end font-monospace">₹{{ number_format($s['cogs'], 2) }}</td>
                                    <td class="text-end font-monospace fw-bold text-primary">
                                        {{ $s['gross_profit'] >= 0 ? '+' : '-' }}₹{{ number_format(abs($s['gross_profit']), 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $s['payment_status'] === 'paid' ? 'success' : ($s['payment_status'] === 'partial' ? 'warning' : 'secondary') }}-subtle text-dark">
                                            {{ ucfirst($s['payment_status'] ?? 'paid') }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        No direct product sales recorded for this period.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: GL REVENUE -->
                <div class="tab-pane fade" id="gl-revenue-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-journal-arrow-down me-1"></i>Double-Entry Posted Revenue Accounts (GL 4xxx)
                        </h6>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                            Total Cash Loan Revenue: ₹{{ number_format($pnl['cash_loan_revenue'], 2) }}
                        </span>
                    </div>
                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">GL Account Code</th>
                                    <th style="width: 50%;">Account Name</th>
                                    <th class="text-end" style="width: 30%;">Net Posted Revenue (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pnl['accounting']['revenue_details'] as $r)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $r['account_code'] }}</td>
                                    <td>{{ $r['account_name'] }}</td>
                                    <td class="text-end font-monospace fw-bold text-success">₹{{ number_format($r['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No posted revenue vouchers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        <strong>Accounting Architecture Rule:</strong> Principal loan collections are debited to Cash/Bank (GL 1110/1120) and credited directly to Loans Receivable Principal Asset (GL 1210). They do not hit 4xxx and are never counted as revenue.
                    </div>
                </div>

                <!-- TAB 5: GL EXPENSES -->
                <div class="tab-pane fade" id="gl-expenses-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-journal-arrow-up me-1"></i>Double-Entry Posted Operating Expense Accounts (GL 5xxx)
                        </h6>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1">
                            Total Operating Expenses: ₹{{ number_format($pnl['total_operating_expenses'], 2) }}
                        </span>
                    </div>
                    <div class="table-responsive border rounded-3 mb-3">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 20%;">GL Account Code</th>
                                    <th style="width: 50%;">Account Name</th>
                                    <th class="text-end" style="width: 30%;">Net Posted Expense (INR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pnl['accounting']['expense_details'] as $e)
                                <tr>
                                    <td class="font-monospace fw-bold">{{ $e['account_code'] }}</td>
                                    <td>{{ $e['account_name'] }}</td>
                                    <td class="text-end font-monospace fw-bold text-danger">₹{{ number_format($e['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No posted expense vouchers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        <strong>Accounting Architecture Rule:</strong> Cash loan principal disbursements are debited to Loans Receivable Asset (GL 1210) and credited to Cash/Bank (GL 1110/1120). They do not hit 5xxx and are never counted as expenses.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setPresetDates(preset) {
    const today = new Date();
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');

    const formatDate = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(today);
    } else if (preset === 'last_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(lastDay);
    } else if (preset === 'this_fy') {
        const currentMonth = today.getMonth() + 1; // 1-12
        const fyStartYear = currentMonth >= 4 ? today.getFullYear() : today.getFullYear() - 1;
        fromInput.value = `${fyStartYear}-04-01`;
        toInput.value = formatDate(today);
    } else if (preset === 'all_time') {
        fromInput.value = '';
        toInput.value = '';
    }

    document.getElementById('pnlFilterForm').submit();
}
</script>
@endpush
@endsection
