@extends('layouts.admin')

@section('title', 'Penalty & Late Fee Ledger - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header & Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Automatic Penalty & Late Fee Ledger</h4>
            <p class="text-muted small mb-0">System audit log of late fee accruals, grace period compliance, penalty collections & managerial waivers</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i> Overdue Dashboard</a>
            <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-wallet2 me-1"></i> Overdue Loans</a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Penalty Charged -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Total Late Fees Charged</span>
                            <h4 class="fw-bold mb-0 text-dark mt-1">₹{{ number_format($totalCharged, 2) }}</h4>
                            <small class="text-muted">Accrued on Overdue Installments</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                            <i class="bi bi-receipt-cutoff fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Penalty Waived -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Total Penalty Waived</span>
                            <h4 class="fw-bold mb-0 text-info mt-1">₹{{ number_format($totalWaived, 2) }}</h4>
                            <small class="text-muted">Managerial Relief / Settlements</small>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Active Penalty Outstanding -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Active Penalty Outstanding</span>
                            <h4 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($totalPenaltyOutstanding, 2) }}</h4>
                            <small class="text-danger fw-semibold">Pending Repayment Collection</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.penalties.ledger') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Search Loan / Customer</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Loan number, name, code..." value="{{ $filters['search'] ?? '' }}">
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
                    <label class="form-label small fw-bold text-muted mb-1">Penalty Mode</label>
                    <select name="penalty_type" class="form-select form-select-sm">
                        <option value="">All Modes</option>
                        <option value="percentage_one_time" {{ ($filters['penalty_type'] ?? '') === 'percentage_one_time' ? 'selected' : '' }}>% One-Time</option>
                        <option value="percentage_per_day" {{ ($filters['penalty_type'] ?? '') === 'percentage_per_day' ? 'selected' : '' }}>% Per Day</option>
                        <option value="flat_one_time" {{ ($filters['penalty_type'] ?? '') === 'flat_one_time' ? 'selected' : '' }}>Flat One-Time</option>
                        <option value="flat_per_day" {{ ($filters['penalty_type'] ?? '') === 'flat_per_day' ? 'selected' : '' }}>Flat Per Day</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Charge Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i></button>
                    <a href="{{ route('admin.penalties.ledger') }}" class="btn btn-sm btn-outline-secondary" title="Reset"><i class="bi bi-x"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Penalty Charges Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-journal-text text-warning me-2"></i>Penalty Accrual Ledger ({{ $charges->total() }} Records)
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
                            <th>Charge Date</th>
                            <th class="text-center">DPD</th>
                            <th>Penalty Type</th>
                            <th class="text-end">Charge (₹)</th>
                            <th class="text-end">Inst. Penalty (₹)</th>
                            <th class="text-end">Penalty Paid (₹)</th>
                            <th class="text-end">Outstanding (₹)</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($charges as $c)
                            @php
                                $loan = $c->loanAccount;
                                $inst = $c->loanInstallment;
                                $instOutstanding = $inst ? max(0, $inst->penalty_amount - $inst->penalty_paid) : 0;
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="text-primary text-decoration-none">
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
                                <td class="text-center fw-bold">{{ $inst ? $inst->installment_number : 'N/A' }}</td>
                                <td class="small">{{ \Carbon\Carbon::parse($c->charge_date)->format('d M, Y') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $c->dpd_at_charge > 30 ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ $c->dpd_at_charge }} DPD
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ str_replace('_', ' ', ucfirst($c->calculation_type)) }}</span></td>
                                <td class="text-end fw-bold text-danger">₹{{ number_format($c->charge_amount, 2) }}</td>
                                <td class="text-end fw-semibold">₹{{ number_format($inst->penalty_amount ?? 0, 2) }}</td>
                                <td class="text-end text-success">₹{{ number_format($inst->penalty_paid ?? 0, 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($instOutstanding, 2) }}</td>
                                <td class="text-end pe-3">
                                    @can('loans.waive_penalty')
                                        @if($instOutstanding > 0)
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#waiveModal{{ $c->id }}" title="Waive Penalty">
                                                <i class="bi bi-shield-slash me-1"></i> Waive
                                            </button>

                                            <!-- Waive Modal for this installment -->
                                            <div class="modal fade" id="waiveModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('admin.penalties.waive', $loan->id) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="loan_installment_id" value="{{ $inst->id }}">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-shield-slash me-1"></i> Waive Penalty - Loan #{{ $loan->loan_number }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="alert alert-warning py-2 small mb-3">
                                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                                                    <strong>Important:</strong> Penalty waiver is permanent and non-reversible. It directly reduces borrower outstanding without altering GL cash entries.
                                                                </div>
                                                                <div class="bg-light p-3 rounded border mb-3 small">
                                                                    <div class="d-flex justify-content-between mb-1">
                                                                        <span class="text-muted">Customer:</span>
                                                                        <strong class="text-dark">{{ $loan->customer->full_name ?? 'N/A' }}</strong>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-1">
                                                                        <span class="text-muted">Installment #:</span>
                                                                        <strong class="text-dark">{{ $inst->installment_number }}</strong>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between mb-1">
                                                                        <span class="text-muted">Max Uncollected Penalty:</span>
                                                                        <strong class="font-monospace text-danger fs-6">₹{{ number_format($instOutstanding, 2) }}</strong>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Waiver Amount (₹) <span class="text-danger">*</span></label>
                                                                    <input type="number" step="0.01" name="amount" class="form-control font-monospace fw-bold fs-5" value="{{ $instOutstanding }}" max="{{ $instOutstanding }}" min="0.01" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small">Waiver Justification / Reason <span class="text-danger">*</span></label>
                                                                    <textarea name="reason" class="form-control" rows="3" placeholder="Mandatory managerial justification (e.g. medical hardship, committee approval)..." required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-sm btn-danger fw-bold"><i class="bi bi-check-circle me-1"></i> Confirm Penalty Waiver</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted border">Cleared</span>
                                        @endif
                                    @else
                                        <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                    No penalty charges recorded matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($charges->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $charges->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
