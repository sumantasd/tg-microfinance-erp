@extends('layouts.admin')

@section('title', 'Overdue Installments List - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header & Filters -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-list-check text-danger me-2"></i>Overdue Installments Tracker</h4>
            <p class="text-muted small mb-0">Detailed line-item tracking of each unpaid or partially paid past-due installment</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
            <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-wallet2 me-1"></i> Overdue Loans</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.overdue.installments') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Search Customer / Loan Number</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Loan number, Customer name, Code, Mobile" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">As of Date</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('admin.overdue.installments') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Overdue Installments Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-calendar-x text-danger me-2"></i>Past-Due Installment Schedule ({{ $overdueInstallments->count() }} Overdue Installments)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Loan Account</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th class="text-center">Inst. #</th>
                            <th>Due Date</th>
                            <th class="text-end">Due Amount</th>
                            <th class="text-end">Paid Amount</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-center">DPD</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueInstallments as $item)
                            @php
                                $inst = $item['installment'];
                                $dpd = $item['dpd_info'];
                                $loan = $inst->loanAccount;
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
                                <td class="text-center fw-bold">{{ $inst->installment_number }}</td>
                                <td class="fw-semibold text-danger">{{ \Carbon\Carbon::parse($inst->due_date)->format('d M, Y') }}</td>
                                <td class="text-end">₹{{ number_format($dpd['due_amount'], 2) }}</td>
                                <td class="text-end text-success">₹{{ number_format($dpd['paid_amount'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($dpd['outstanding_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $dpd['dpd'] > 60 ? 'bg-danger' : ($dpd['dpd'] > 30 ? 'bg-warning text-dark' : 'bg-warning bg-opacity-25 text-dark') }} px-2 py-1">
                                        {{ $dpd['dpd'] }} Days
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $inst->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger' }}">
                                        {{ $dpd['display_status'] }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="btn btn-sm btn-outline-primary" title="View Account">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                    No overdue installments found for the selected filter.
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
