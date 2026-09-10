@extends('layouts.admin')

@section('title', 'Expense Reports & Analytics - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-bar-chart-line text-info me-2"></i>Expense Reports & Financial Analytics
        </h4>
        <p class="text-muted small mb-0">Detailed breakdown of organizational expenses by category, branch, payee, and payment state.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('expense.report.export')
        <a href="{{ route('admin.expenses.reports.export', request()->all()) }}" class="btn btn-success text-white fw-bold shadow-sm rounded-pill px-3">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        @endcan
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Expenses
        </a>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-semibold">Total Expenses</span>
                    <h3 class="fw-bold mb-0 mt-1">₹{{ number_format($totalExpense, 2) }}</h3>
                    <small class="text-white-50">{{ $totalCount }} total records</small>
                </div>
                <div class="fs-1 text-white-50"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-semibold">Paid Amount</span>
                    <h3 class="fw-bold mb-0 mt-1">₹{{ number_format($totalPaid, 2) }}</h3>
                    <small class="text-white-50">Disbursed funds</small>
                </div>
                <div class="fs-1 text-white-50"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-warning text-dark h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-dark-50 small text-uppercase fw-semibold">Outstanding Balance</span>
                    <h3 class="fw-bold mb-0 mt-1">₹{{ number_format($totalOutstanding, 2) }}</h3>
                    <small class="text-dark-50">Approved / Unpaid</small>
                </div>
                <div class="fs-1 text-dark-50"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 small text-uppercase fw-semibold">Active Filter Count</span>
                    <h3 class="fw-bold mb-0 mt-1">{{ $expenses->count() }}</h3>
                    <small class="text-white-50">Filtered expense records</small>
                </div>
                <div class="fs-1 text-white-50"><i class="bi bi-funnel"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.expenses.reports.index') }}" class="row g-2">
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Report View</label>
                <select name="report_type" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
                    <option value="summary" {{ $reportType == 'summary' ? 'selected' : '' }}>Detailed Summary</option>
                    <option value="by_category" {{ $reportType == 'by_category' ? 'selected' : '' }}>By Category</option>
                    <option value="by_branch" {{ $reportType == 'by_branch' ? 'selected' : '' }}>By Branch</option>
                    <option value="by_payee" {{ $reportType == 'by_payee' ? 'selected' : '' }}>By Payee / Vendor</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-filter me-1"></i> Apply
                </button>
                <a href="{{ route('admin.expenses.reports.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Content Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        @if($reportType === 'summary')
            <!-- Detailed Summary List -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Expense #</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Category</th>
                            <th>Payee</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Outstanding</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $exp)
                        <tr>
                            <td class="ps-4 font-monospace fw-bold">
                                <a href="{{ route('admin.expenses.show', $exp->id) }}" class="text-primary text-decoration-none">
                                    {{ $exp->expense_number }}
                                </a>
                            </td>
                            <td class="small">{{ $exp->expense_date->format('d M Y') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $exp->branch?->name }}</span></td>
                            <td class="small text-muted">{{ $exp->category?->category_name }}</td>
                            <td class="small text-dark fw-semibold">{{ $exp->payee_display_name }}</td>
                            <td class="text-end small">₹{{ number_format($exp->amount, 2) }}</td>
                            <td class="text-end small text-muted">₹{{ number_format($exp->tax_amount, 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($exp->total_amount, 2) }}</td>
                            <td class="text-end text-success small">₹{{ number_format($exp->paid_amount, 2) }}</td>
                            <td class="text-end text-danger small">₹{{ number_format($exp->outstanding_amount, 2) }}</td>
                            <td>
                                <span class="badge bg-soft-secondary text-secondary border">{{ $exp->status }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block text-muted opacity-50 mb-2"></i>
                                No expense records found for the selected criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Grouped Breakdown View (by Category, Branch, or Payee) -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">Grouping Label</th>
                            <th class="text-center">Count</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Paid Amount</th>
                            <th class="text-end">Outstanding Amount</th>
                            <th class="text-end pe-4">Share of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupedData as $group)
                        @php
                            $share = $totalExpense > 0 ? round(($group['total'] / $totalExpense) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $group['label'] }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border px-3 py-1">{{ $group['count'] }}</span></td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($group['total'], 2) }}</td>
                            <td class="text-end text-success fw-semibold">₹{{ number_format($group['paid'], 2) }}</td>
                            <td class="text-end text-danger fw-semibold">₹{{ number_format($group['outstanding'], 2) }}</td>
                            <td class="text-end pe-4">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px; min-width: 80px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $share }}%"></div>
                                    </div>
                                    <span class="small fw-bold text-muted">{{ $share }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-pie-chart display-6 d-block text-muted opacity-50 mb-2"></i>
                                No aggregated report data available.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
