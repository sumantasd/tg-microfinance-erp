@extends('layouts.admin')

@section('title', 'Customer Overdue Profile - ' . $customer->full_name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.overdue.dashboard') }}">Overdue & DPD</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.overdue.customers') }}">Customers</a></li>
                    <li class="breadcrumb-item active">{{ $customer->full_name }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Borrower Overdue Profile</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.overdue.customers') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
            <a href="{{ url('/admin/customer/' . $customer->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-badge me-1"></i> Full Member Record</a>
        </div>
    </div>

    <!-- Customer Overview Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center gy-3">
                <div class="col-md-6 d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="bi bi-person fs-2"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $customer->full_name }}</h5>
                        <div class="text-muted small">
                            <span class="fw-semibold">Code:</span> {{ $customer->customer_code }} | 
                            <span class="fw-semibold">Mobile:</span> {{ $customer->mobile_number }} | 
                            <span class="fw-semibold">Branch:</span> {{ $customer->branch->name ?? 'N/A' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="text-muted small d-block">Total Overdue</span>
                                <h5 class="fw-bold text-danger mb-0">₹{{ number_format($summary['total_overdue_amount'], 2) }}</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="text-muted small d-block">Max DPD</span>
                                <h5 class="fw-bold {{ $summary['max_dpd'] > 30 ? 'text-danger' : 'text-warning' }} mb-0">{{ $summary['max_dpd'] }} Days</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="text-muted small d-block">Active Loans</span>
                                <h5 class="fw-bold text-dark mb-0">{{ $summary['active_loans_count'] }} <span class="small text-danger fw-normal">({{ $summary['delinquent_loans_count'] }} Due)</span></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Loans Breakdown -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-wallet2 text-primary me-2"></i>Active Loan Accounts Breakdown</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Loan Number</th>
                            <th>Scheme</th>
                            <th>Loan Type</th>
                            <th class="text-end">Principal Out.</th>
                            <th class="text-end">Total Outstanding</th>
                            <th class="text-end">Overdue Amount</th>
                            <th class="text-center">Overdue Inst.</th>
                            <th class="text-center">DPD</th>
                            <th>Aging Bucket</th>
                            <th>Oldest Due Date</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary['loans'] as $loanDet)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('admin.loan-account.show', $loanDet['loan_id']) }}" class="text-primary text-decoration-none">
                                        {{ $loanDet['loan_number'] }}
                                    </a>
                                </td>
                                <td>{{ $loanDet['scheme_name'] }}</td>
                                <td>
                                    <span class="badge {{ $loanDet['loan_type'] === 'product' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary' }}">
                                        {{ ucfirst($loanDet['loan_type']) }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($loanDet['principal_outstanding'], 2) }}</td>
                                <td class="text-end text-dark">₹{{ number_format($loanDet['total_outstanding'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($loanDet['overdue_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $loanDet['overdue_installments_count'] > 0 ? 'bg-danger' : 'bg-success' }}">
                                        {{ $loanDet['overdue_installments_count'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $loanDet['dpd'] > 60 ? 'bg-danger' : ($loanDet['dpd'] > 30 ? 'bg-warning text-dark' : 'bg-warning bg-opacity-25 text-dark') }} px-2 py-1">
                                        {{ $loanDet['dpd'] }} Days
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ $loanDet['aging_bucket'] }}</span></td>
                                <td class="small">{{ $loanDet['oldest_overdue_date'] ? \Carbon\Carbon::parse($loanDet['oldest_overdue_date'])->format('d M, Y') : 'N/A' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.loan-account.show', $loanDet['loan_id']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> Loan</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">No active loans found for this customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
