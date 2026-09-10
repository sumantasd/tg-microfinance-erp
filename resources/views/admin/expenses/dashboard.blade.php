@extends('layouts.admin')

@section('title', 'Expense Management Dashboard - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-receipt text-danger me-2"></i>Expense Management Dashboard
        </h4>
        <p class="text-muted small mb-0">Track, analyze, approve, and audit operational expenditure across all retail branches.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('expense.create')
        <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Record New Expense
        </a>
        @endcan
        @can('expense.category.manage')
        <a href="{{ route('admin.expenses.categories.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-tags me-1"></i> Categories
        </a>
        @endcan
        @can('expense.report.view')
        <a href="{{ route('admin.expenses.reports.index') }}" class="btn btn-outline-info rounded-pill px-3">
            <i class="bi bi-bar-chart-line me-1"></i> Reports
        </a>
        @endcan
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-dark rounded-pill px-3">
            <i class="bi bi-list-ul me-1"></i> Expense List
        </a>
    </div>
</div>

<!-- Date & Branch Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.expenses.dashboard') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Branch Scope</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Permitted Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">From Date</label>
                <input type="date" name="date_from" class="form-select form-select-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">To Date</label>
                <input type="date" name="date_to" class="form-select form-select-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1 mt-4">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.expenses.dashboard') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KPI Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Active Expenses Total</span>
                    <div class="bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-currency-rupee fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">₹{{ number_format($stats->active_total_amount ?? 0, 2) }}</h3>
                <small class="text-muted">Total Active Expenses Recorded</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Paid Amount</span>
                    <div class="bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-success mb-1">₹{{ number_format($stats->paid_amount ?? 0, 2) }}</h3>
                <small class="text-muted">{{ $stats->paid_count ?? 0 }} Paid Expenses</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Outstanding Balance</span>
                    <div class="bg-danger-subtle text-danger p-2 rounded-3">
                        <i class="bi bi-exclamation-circle fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-danger mb-1">₹{{ number_format($stats->outstanding_amount ?? 0, 2) }}</h3>
                <small class="text-muted">Approved & Unpaid/Partial</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pending Approval</span>
                    <div class="bg-warning-subtle text-warning p-2 rounded-3">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-warning mb-1">{{ $stats->pending_approval_count ?? 0 }}</h3>
                <small class="text-muted">Requires Management Review</small>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Banner -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-gradient text-white" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 text-uppercase small fw-bold">This Month Expenditure</span>
                    <h2 class="fw-bold text-white mb-0 mt-1">₹{{ number_format($thisMonthStats, 2) }}</h2>
                </div>
                <div class="bg-white bg-opacity-10 p-3 rounded-circle text-white">
                    <i class="bi bi-calendar-month fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-gradient text-white" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 text-uppercase small fw-bold">This Financial Year Expenditure</span>
                    <h2 class="fw-bold text-white mb-0 mt-1">₹{{ number_format($thisYearStats, 2) }}</h2>
                </div>
                <div class="bg-white bg-opacity-10 p-3 rounded-circle text-white">
                    <i class="bi bi-graph-up-arrow fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Categorical & Branch Breakdown Tables -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart text-primary me-2"></i>Expense by Category</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryBreakdown as $catItem)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $catItem->category->category_name ?? 'Uncategorized' }}</td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $catItem->count }}</span></td>
                                <td class="text-end fw-bold">₹{{ number_format($catItem->total_sum, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No expense data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-diagram-3 text-warning me-2"></i>Expense by Branch</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branchBreakdown as $bItem)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $bItem->branch->name ?? 'N/A' }}</td>
                                <td class="text-center"><span class="badge bg-secondary rounded-pill">{{ $bItem->count }}</span></td>
                                <td class="text-end fw-bold">₹{{ number_format($bItem->total_sum, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No branch data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Approvals Queue -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-split text-warning me-2"></i>Recent Pending Approvals Queue</h6>
        <a href="{{ route('admin.expenses.index', ['status' => 'PENDING_APPROVAL']) }}" class="btn btn-sm btn-link text-decoration-none">View All Pending</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Expense #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Requested By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPending as $exp)
                    <tr>
                        <td class="fw-bold font-monospace"><a href="{{ route('admin.expenses.show', $exp->id) }}" class="text-decoration-none">{{ $exp->expense_number }}</a></td>
                        <td>{{ $exp->expense_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $exp->branch->name }}</span></td>
                        <td>{{ $exp->category->category_name }}</td>
                        <td>{{ $exp->requestedBy->name ?? 'N/A' }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($exp->total_amount, 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.expenses.show', $exp->id) }}" class="btn btn-sm btn-warning fw-semibold rounded-pill px-3">
                                Review
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-check-circle text-success me-1"></i> No pending expense approvals.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
