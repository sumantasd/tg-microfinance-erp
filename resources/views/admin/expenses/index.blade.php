@extends('layouts.admin')

@section('title', 'Expense Management - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-receipt text-danger me-2"></i>Expense Management
        </h4>
        <p class="text-muted small mb-0">Record, filter, approve, and track operational expenditure records.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        @can('expense.create')
        <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> New Expense
        </a>
        @endcan
        <a href="{{ route('admin.expenses.dashboard') }}" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-grid-1x2 me-1"></i> Dashboard
        </a>
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
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filters -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.expenses.index') }}" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Expense #, payee, notes..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Workflow States</option>
                    <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Draft</option>
                    <option value="PENDING_APPROVAL" {{ request('status') == 'PENDING_APPROVAL' ? 'selected' : '' }}>Pending Approval</option>
                    <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>Approved</option>
                    <option value="PARTIALLY_PAID" {{ request('status') == 'PARTIALLY_PAID' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="PAID" {{ request('status') == 'PAID' ? 'selected' : '' }}>Paid</option>
                    <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Payment States</option>
                    <option value="UNPAID" {{ request('payment_status') == 'UNPAID' ? 'selected' : '' }}>Unpaid</option>
                    <option value="PARTIALLY_PAID" {{ request('payment_status') == 'PARTIALLY_PAID' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="PAID" {{ request('payment_status') == 'PAID' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Main Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Expense #</th>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Category</th>
                        <th>Payee / Vendor</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-center">Workflow</th>
                        <th class="text-center">Payment</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $exp)
                    <tr>
                        <td class="fw-bold font-monospace">
                            <a href="{{ route('admin.expenses.show', $exp->id) }}" class="text-decoration-none text-primary">
                                {{ $exp->expense_number }}
                            </a>
                        </td>
                        <td>{{ $exp->expense_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $exp->branch->name }}</span></td>
                        <td><span class="fw-semibold text-dark">{{ $exp->category->category_name }}</span></td>
                        <td class="small">{{ $exp->payee_display_name }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($exp->total_amount, 2) }}</td>
                        <td class="text-end text-success">₹{{ number_format($exp->paid_amount, 2) }}</td>
                        <td class="text-end text-danger fw-bold">₹{{ number_format($exp->outstanding_amount, 2) }}</td>
                        <td class="text-center">
                            @if($exp->status === 'DRAFT')
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($exp->status === 'PENDING_APPROVAL')
                                <span class="badge bg-warning text-dark">Pending Approval</span>
                            @elseif($exp->status === 'APPROVED')
                                <span class="badge bg-info text-dark">Approved</span>
                            @elseif($exp->status === 'PARTIALLY_PAID')
                                <span class="badge bg-primary">Partially Paid</span>
                            @elseif($exp->status === 'PAID')
                                <span class="badge bg-success">Paid</span>
                            @elseif($exp->status === 'REJECTED')
                                <span class="badge bg-danger">Rejected</span>
                            @elseif($exp->status === 'CANCELLED')
                                <span class="badge bg-dark">Cancelled</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($exp->payment_status === 'UNPAID')
                                <span class="badge bg-outline-danger border border-danger text-danger">Unpaid</span>
                            @elseif($exp->payment_status === 'PARTIALLY_PAID')
                                <span class="badge bg-outline-warning border border-warning text-warning">Partial</span>
                            @elseif($exp->payment_status === 'PAID')
                                <span class="badge bg-outline-success border border-success text-success">Paid</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.expenses.show', $exp->id) }}" class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($exp->isEditable())
                                @can('expense.edit')
                                <a href="{{ route('admin.expenses.edit', $exp->id) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No expense records found matching the current filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $expenses->links() }}
    </div>
    @endif
</div>
@endsection
