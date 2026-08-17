@extends('layouts.admin')

@section('title', 'Overdue Loans List - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header & Filters -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-wallet2 text-danger me-2"></i>Delinquent & Overdue Loans</h4>
            <p class="text-muted small mb-0">List of all active loan accounts with past-due installments and dynamic DPD calculation</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="{{ route('admin.overdue.installments') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-list-check me-1"></i> Overdue Installments</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.overdue.loans') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Search Loan / Customer</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Loan number, Name, Mobile, Code" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Loan Scheme</label>
                    <select name="loan_scheme_id" class="form-select form-select-sm">
                        <option value="">All Schemes</option>
                        @foreach($loanSchemes as $s)
                            <option value="{{ $s->id }}" {{ ($filters['loan_scheme_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Aging / DPD Bucket</label>
                    <select name="dpd_bucket" class="form-select form-select-sm">
                        <option value="">All Buckets</option>
                        <option value="1_30" {{ ($filters['dpd_bucket'] ?? '') === '1_30' ? 'selected' : '' }}>1–30 Days</option>
                        <option value="31_60" {{ ($filters['dpd_bucket'] ?? '') === '31_60' ? 'selected' : '' }}>31–60 Days</option>
                        <option value="61_90" {{ ($filters['dpd_bucket'] ?? '') === '61_90' ? 'selected' : '' }}>61–90 Days</option>
                        <option value="90_plus" {{ ($filters['dpd_bucket'] ?? '') === '90_plus' ? 'selected' : '' }}>90+ Days</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Overdue Loans Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table text-danger me-2"></i>Overdue Loans ({{ $overdueLoans->count() }} Accounts)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Loan Number</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th>Scheme / Type</th>
                            <th class="text-end">Principal Out.</th>
                            <th class="text-end">Overdue Amount</th>
                            <th class="text-center">Overdue Inst.</th>
                            <th class="text-center">DPD</th>
                            <th>Aging Bucket</th>
                            <th>Oldest Due Date</th>
                            <th>Next Due Date</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueLoans as $item)
                            @php
                                $loan = $item['loan'];
                                $det = $item['details'];
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="text-decoration-none text-primary">
                                        {{ $loan->loan_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($loan->customer)
                                        <a href="{{ route('admin.overdue.customer-profile', $loan->customer->id) }}" class="text-dark fw-semibold text-decoration-none">
                                            {{ $loan->customer->full_name }}
                                        </a>
                                        <div class="small text-muted">{{ $loan->customer->customer_code }}</div>
                                    @else
                                        <span class="text-muted">Group / Direct</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $loan->branch->name ?? 'N/A' }}</span></td>
                                <td>
                                    <div class="small fw-semibold">{{ $loan->loanScheme->name ?? 'N/A' }}</div>
                                    <span class="badge {{ $loan->loan_type === 'product' ? 'bg-info bg-opacity-10 text-info' : 'bg-primary bg-opacity-10 text-primary' }}">
                                        {{ ucfirst($loan->loan_type) }} Loan
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($det['principal_outstanding'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($det['overdue_amount'], 2) }}</td>
                                <td class="text-center"><span class="badge bg-danger bg-opacity-10 text-danger">{{ $det['overdue_installments_count'] }}</span></td>
                                <td class="text-center">
                                    <span class="badge {{ $det['dpd'] > 60 ? 'bg-danger' : ($det['dpd'] > 30 ? 'bg-warning text-dark' : 'bg-warning bg-opacity-25 text-dark') }} px-2 py-1">
                                        {{ $det['dpd'] }} Days
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ $det['aging_bucket'] }}</span></td>
                                <td class="small">{{ $det['oldest_overdue_date'] ? \Carbon\Carbon::parse($det['oldest_overdue_date'])->format('d M, Y') : 'N/A' }}</td>
                                <td class="small text-muted">{{ $det['next_due_date'] ? \Carbon\Carbon::parse($det['next_due_date'])->format('d M, Y') : 'Matured' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="btn btn-sm btn-outline-primary" title="View Loan Account">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                    No overdue loans found matching the filter criteria.
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
