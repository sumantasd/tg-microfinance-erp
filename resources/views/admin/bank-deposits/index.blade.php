@extends('layouts.admin')

@section('title', 'Branch Bank Deposit Management')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark font-heading">
                <i class="bi bi-bank me-2 text-primary"></i>BRANCH BANK DEPOSIT MANAGEMENT
            </h4>
            <p class="text-muted small mb-0 mt-1">Submit, verify, and approve physical branch cash deposits into company bank accounts.</p>
        </div>

        <div>
            @can('bank_deposit.create')
            <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#submitDepositModal">
                <i class="bi bi-plus-circle me-1"></i> Submit Bank Deposit
            </button>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 small py-2 px-3 mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 px-3 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.bank-deposits.index') }}" class="row g-3 align-items-center">
                @if(!$userBranchId)
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Filter Branch</label>
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string)$selectedBranchId === (string)$branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        <option value="approved" {{ $selectedStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $selectedStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-md-2 mt-4">
                    <a href="{{ route('admin.bank-deposits.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Deposits Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.875rem;">
                <thead class="bg-light border-bottom">
                    <tr>
                        <th>DATE</th>
                        <th>BRANCH</th>
                        <th class="text-end">AMOUNT (₹)</th>
                        <th>BANK / ACCOUNT</th>
                        <th>REFERENCE / SLIP</th>
                        <th>SUBMITTED BY</th>
                        <th>STATUS</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        <tr>
                            <td class="fw-bold">{{ $deposit->deposit_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $deposit->branch->name }}</span>
                            </td>
                            <td class="text-end fw-bold text-success fs-6">₹{{ number_format($deposit->amount, 2) }}</td>
                            <td>
                                <div><strong>{{ $deposit->bank_name }}</strong></div>
                                @if($deposit->account_number)
                                    <small class="text-muted">A/C: {{ strlen($deposit->account_number) > 4 ? '****' . substr($deposit->account_number, -4) : $deposit->account_number }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $deposit->reference_number }}</span></td>
                            <td>
                                <div>{{ $deposit->submittedBy->name ?? 'Staff' }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $deposit->submitted_at ? $deposit->submitted_at->format('d/m/Y H:i') : '' }}</small>
                            </td>
                            <td>
                                @if($deposit->isPending())
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5">
                                        <i class="bi bi-clock-history me-1"></i> PENDING
                                    </span>
                                @elseif($deposit->isApproved())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5">
                                        <i class="bi bi-check-circle-fill me-1"></i> APPROVED
                                    </span>
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">by {{ $deposit->approvedBy->name ?? 'Admin' }}</small>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5">
                                        <i class="bi bi-x-circle-fill me-1"></i> REJECTED
                                    </span>
                                    @if($deposit->rejection_reason)
                                        <small class="d-block text-danger" style="font-size: 0.7rem;" title="{{ $deposit->rejection_reason }}">
                                            Reason: {{ Str::limit($deposit->rejection_reason, 20) }}
                                        </small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                @if($deposit->isPending())
                                    @can('bank_deposit.approve')
                                        @if(auth()->id() !== $deposit->submitted_by)
                                            <button type="button" class="btn btn-xs btn-success rounded-pill px-2 py-1 fw-bold me-1" data-bs-toggle="modal" data-bs-target="#approveModal{{ $deposit->id }}">
                                                Approve
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $deposit->id }}">
                                                Reject
                                            </button>
                                        @else
                                            <span class="badge bg-light text-muted border" title="Cannot approve self-submitted deposit">Self Submitted</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Pending Approval</span>
                                    @endcan
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Approve Modal -->
                        @can('bank_deposit.approve')
                        @if($deposit->isPending() && auth()->id() !== $deposit->submitted_by)
                        <div class="modal fade" id="approveModal{{ $deposit->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.bank-deposits.approve', $deposit->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-heading mb-0 text-white"><i class="bi bi-check-circle-fill me-2"></i>Approve Bank Deposit</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="mb-3">Are you sure you want to approve this bank deposit? Physical cash balance for <strong>{{ $deposit->branch->name }}</strong> on <strong>{{ $deposit->deposit_date->format('d/m/Y') }}</strong> will be reduced by <strong>₹{{ number_format($deposit->amount, 2) }}</strong>.</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Approval Remarks (Optional)</label>
                                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Verified with bank statement slip">
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">Confirm Approval</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal{{ $deposit->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.bank-deposits.reject', $deposit->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-heading mb-0 text-white"><i class="bi bi-x-circle-fill me-2"></i>Reject Bank Deposit</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="mb-3">Rejecting bank deposit of <strong>₹{{ number_format($deposit->amount, 2) }}</strong>. Physical cash balance will NOT be reduced.</p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-danger">Rejection Reason *</label>
                                                <textarea name="rejection_reason" class="form-control form-control-sm" rows="3" required placeholder="Specify exact reason for rejection..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold">Confirm Rejection</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endcan

                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No bank deposits recorded matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deposits->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $deposits->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Submit Bank Deposit Modal -->
@can('bank_deposit.create')
<div class="modal fade" id="submitDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.bank-deposits.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-heading mb-0 text-white"><i class="bi bi-bank me-2"></i>Submit Branch Bank Deposit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Branch *</label>
                        <select name="branch_id" class="form-select form-select-sm" required {{ $userBranchId ? 'disabled' : '' }}>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string)$selectedBranchId === (string)$branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }} ({{ $branch->code }})
                                </option>
                            @endforeach
                        </select>
                        @if($userBranchId)
                            <input type="hidden" name="branch_id" value="{{ $userBranchId }}">
                        @endif
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deposit Date *</label>
                            <input type="date" name="deposit_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deposit Amount (₹) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bank Name *</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm" required placeholder="e.g. State Bank of India">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Account Number</label>
                            <input type="text" name="account_number" class="form-control form-control-sm" placeholder="e.g. 38472910492">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deposit Slip / Reference No *</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm" required placeholder="e.g. SLIP-849201 or UTR-92840">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description / Remarks</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional notes for bank deposit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">Submit Deposit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
