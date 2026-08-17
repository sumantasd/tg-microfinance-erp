@extends('layouts.admin')

@section('title', 'Customer Overdue Management - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header & Filters -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-people text-danger me-2"></i>Customer-Wise Overdue Portfolio</h4>
            <p class="text-muted small mb-0">Consolidated delinquency aggregation per borrower across all active loan accounts</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-wallet2 me-1"></i> Overdue Loans</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.overdue.customers') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted mb-1">Search Customer Name / Mobile / Code</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Customer name, mobile number, member code..." value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Search</button>
                    <a href="{{ route('admin.overdue.customers') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Customer Overdue Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-exclamation text-danger me-2"></i>Borrower Overdue Summary ({{ $customerSummaries->count() }} Borrowers)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Customer</th>
                            <th>Branch</th>
                            <th>Contact</th>
                            <th class="text-center">Active Loans</th>
                            <th class="text-center">Overdue Loans</th>
                            <th class="text-end">Principal Out.</th>
                            <th class="text-end">Total Overdue</th>
                            <th class="text-center">Max DPD</th>
                            <th>Aging Bucket</th>
                            <th>Oldest Overdue Date</th>
                            <th class="text-end pe-3">Profile</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerSummaries as $cust)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('admin.overdue.customer-profile', $cust['customer_id']) }}" class="text-dark text-decoration-none">
                                        {{ $cust['customer_name'] }}
                                    </a>
                                    <div class="small text-muted">{{ $cust['customer_code'] }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $cust['branch_name'] }}</span></td>
                                <td><span class="small font-monospace">{{ $cust['mobile_number'] }}</span></td>
                                <td class="text-center fw-semibold">{{ $cust['active_loans_count'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $cust['delinquent_loans_count'] > 0 ? 'bg-danger' : 'bg-success' }}">
                                        {{ $cust['delinquent_loans_count'] }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($cust['total_principal_outstanding'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($cust['total_overdue_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $cust['max_dpd'] > 60 ? 'bg-danger' : ($cust['max_dpd'] > 30 ? 'bg-warning text-dark' : 'bg-warning bg-opacity-25 text-dark') }} px-2 py-1">
                                        {{ $cust['max_dpd'] }} Days
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ $cust['aging_bucket'] }}</span></td>
                                <td class="small">{{ $cust['oldest_overdue_date'] ? \Carbon\Carbon::parse($cust['oldest_overdue_date'])->format('d M, Y') : 'N/A' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.overdue.customer-profile', $cust['customer_id']) }}" class="btn btn-sm btn-outline-primary" title="View Customer Overdue Profile">
                                        <i class="bi bi-person-lines-fill me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                    No delinquent customers found matching the search criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
