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

        <div class="d-flex gap-2">
            @can('bank_deposit.create')
            <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#submitDepositModal">
                <i class="bi bi-plus-circle me-1"></i> Submit Bank Deposit
            </button>
            @endcan

            @if($canManageAccounts)
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                <i class="bi bi-plus-lg me-1"></i> Add Bank Account
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small py-2 px-3 mb-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3 border">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-bold {{ $activeTab === 'deposits' ? 'active shadow-sm' : 'text-dark' }}" 
               href="{{ route('admin.bank-deposits.index', ['tab' => 'deposits', 'branch_id' => $selectedBranchId, 'status' => $selectedStatus]) }}">
                <i class="bi bi-journal-text me-1"></i> Bank Deposits
                <span class="badge bg-white text-primary ms-1 rounded-pill">{{ $deposits->total() }}</span>
            </a>
        </li>
        @if($canManageAccounts)
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 fw-bold {{ $activeTab === 'accounts' ? 'active shadow-sm' : 'text-dark' }}" 
               href="{{ route('admin.bank-deposits.index', ['tab' => 'accounts']) }}">
                <i class="bi bi-credit-card-2-front me-1"></i> Company Bank Accounts (Admin)
                <span class="badge bg-white text-primary ms-1 rounded-pill">{{ $adminBankAccounts->count() }}</span>
            </a>
        </li>
        @endif
    </ul>

    @if($activeTab === 'deposits')
    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.bank-deposits.index') }}" class="row g-3 align-items-center">
                <input type="hidden" name="tab" value="deposits">
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
                        <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending Verification</option>
                        <option value="approved" {{ $selectedStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ $selectedStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="col-md-2 mt-4">
                    <a href="{{ route('admin.bank-deposits.index', ['tab' => 'deposits']) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="bi bi-x-circle me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Deposits Table -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead class="bg-light border-bottom text-uppercase small text-muted">
                    <tr>
                        <th class="py-3 px-3">DATE</th>
                        <th class="py-3 px-3">BRANCH</th>
                        <th class="py-3 px-3 text-end">AMOUNT (₹)</th>
                        <th class="py-3 px-3">CREDITED BANK / ACCOUNT</th>
                        <th class="py-3 px-3 text-center">RECEIPT SLIP</th>
                        <th class="py-3 px-3">REFERENCE</th>
                        <th class="py-3 px-3">SUBMITTED BY</th>
                        <th class="py-3 px-3 text-center">STATUS</th>
                        <th class="py-3 px-3 text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        <tr>
                            <td class="fw-bold px-3 py-3 font-monospace">{{ $deposit->deposit_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-3">
                                <span class="badge bg-light text-dark border">{{ $deposit->branch->name }}</span>
                            </td>
                            <td class="text-end fw-bold text-success fs-6 font-monospace px-3 py-3">₹{{ number_format($deposit->amount, 2) }}</td>
                            <td class="px-3 py-3">
                                <div class="fw-bold text-dark">{{ $deposit->bank_name }}</div>
                                @if($deposit->bankAccount && $deposit->bankAccount->account_nickname)
                                    <div class="small text-primary fw-semibold">{{ $deposit->bankAccount->account_nickname }}</div>
                                @endif
                                <div class="small text-muted font-monospace">
                                    A/C: {{ $deposit->masked_account_number ?: 'N/A' }}
                                </div>
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($deposit->hasReceipt())
                                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#receiptModal{{ $deposit->id }}" title="Inspect Deposit Receipt">
                                        <i class="bi bi-image me-1"></i> View Slip
                                    </button>
                                @else
                                    <span class="badge bg-light text-muted border">No Slip (Historical)</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $deposit->reference_number }}</span>
                                @if($deposit->description)
                                    <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $deposit->description }}">
                                        {{ $deposit->description }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="fw-semibold">{{ $deposit->submittedBy->name ?? 'Staff' }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $deposit->submitted_at ? $deposit->submitted_at->format('d/m/Y H:i') : '' }}</small>
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($deposit->isPending())
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-clock-history me-1"></i> PENDING
                                    </span>
                                @elseif($deposit->isApproved())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> APPROVED
                                    </span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">by {{ $deposit->approvedBy->name ?? 'Admin' }}</small>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-x-circle-fill me-1"></i> REJECTED
                                    </span>
                                    @if($deposit->rejection_reason)
                                        <small class="d-block text-danger mt-1" style="font-size: 0.7rem;" title="{{ $deposit->rejection_reason }}">
                                            Reason: {{ Str::limit($deposit->rejection_reason, 20) }}
                                        </small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center px-3 py-3">
                                @if($deposit->isPending())
                                    @can('bank_deposit.approve')
                                        @if(auth()->id() !== $deposit->submitted_by)
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-success rounded-pill px-2 py-1 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal{{ $deposit->id }}">
                                                    <i class="bi bi-check-circle me-1"></i>Verify & Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $deposit->id }}">
                                                    <i class="bi bi-x-circle me-1"></i>Reject
                                                </button>
                                            </div>
                                        @else
                                            <span class="badge bg-light text-muted border" title="Cannot approve self-submitted deposit">Self Submitted</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Pending Verification</span>
                                    @endcan
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Inspect Receipt Modal -->
                        @if($deposit->hasReceipt())
                        <div class="modal fade" id="receiptModal{{ $deposit->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title fs-6 fw-bold text-white">
                                            <i class="bi bi-receipt me-2"></i>Deposit Receipt Slip — Ref #{{ $deposit->reference_number }}
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4 text-center bg-light">
                                        <div class="mb-3 text-start small border bg-white p-3 rounded-3 shadow-sm">
                                            <div class="row g-2">
                                                <div class="col-sm-6">
                                                    <strong>Branch:</strong> {{ $deposit->branch->name }}<br>
                                                    <strong>Deposit Date:</strong> {{ $deposit->deposit_date->format('d/m/Y') }}<br>
                                                    <strong>Amount:</strong> <span class="text-success fw-bold">₹{{ number_format($deposit->amount, 2) }}</span>
                                                </div>
                                                <div class="col-sm-6">
                                                    <strong>Credited Bank:</strong> {{ $deposit->bank_name }}<br>
                                                    <strong>Account Number:</strong> {{ $deposit->masked_account_number ?: 'N/A' }}<br>
                                                    <strong>Submitted By:</strong> {{ $deposit->submittedBy->name ?? 'Staff' }} ({{ $deposit->submitted_at ? $deposit->submitted_at->format('d/m/Y H:i') : '' }})
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-center border rounded-3 p-2 bg-white shadow-sm overflow-hidden">
                                            <img src="{{ route('admin.bank-deposits.receipt', $deposit->id) }}" 
                                                 alt="Bank Deposit Receipt Slip" 
                                                 class="img-fluid rounded-2" 
                                                 style="max-height: 520px; object-fit: contain;">
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light d-flex justify-content-between">
                                        <a href="{{ route('admin.bank-deposits.receipt', $deposit->id) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Original in New Tab
                                        </a>
                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Verify & Approve Modal -->
                        @can('bank_deposit.approve')
                        @if($deposit->isPending() && auth()->id() !== $deposit->submitted_by)
                        <div class="modal fade" id="approveModal{{ $deposit->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.bank-deposits.approve', $deposit->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title fs-6 fw-bold text-white"><i class="bi bi-shield-check me-2"></i>Verify & Approve Bank Deposit</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <!-- Manual Verification Warning -->
                                            <div class="alert alert-warning border-warning small mb-3">
                                                <div class="fw-bold text-dark mb-1">
                                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                                    Manual Bank Credit Verification Required
                                                </div>
                                                Please check your bank statement/portal to verify that the credited amount of 
                                                <strong class="text-success fs-6">₹{{ number_format($deposit->amount, 2) }}</strong> 
                                                has actually arrived in <strong>{{ $deposit->bank_name }} ({{ $deposit->masked_account_number }})</strong> before approving.
                                            </div>

                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <div class="p-3 bg-light rounded-3 border h-100 small">
                                                        <div class="fw-bold mb-2 text-primary border-bottom pb-1">Deposit Details</div>
                                                        <div class="mb-1"><strong>Branch:</strong> {{ $deposit->branch->name }}</div>
                                                        <div class="mb-1"><strong>Deposit Date:</strong> {{ $deposit->deposit_date->format('d/m/Y') }}</div>
                                                        <div class="mb-1"><strong>Amount:</strong> <span class="text-success fw-bold fs-6">₹{{ number_format($deposit->amount, 2) }}</span></div>
                                                        <div class="mb-1"><strong>Bank:</strong> {{ $deposit->bank_name }}</div>
                                                        <div class="mb-1"><strong>Account:</strong> {{ $deposit->masked_account_number ?: 'N/A' }}</div>
                                                        <div class="mb-1"><strong>Slip Ref:</strong> <span class="font-monospace">{{ $deposit->reference_number }}</span></div>
                                                        <div><strong>Submitted By:</strong> {{ $deposit->submittedBy->name ?? 'Staff' }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="p-3 bg-light rounded-3 border h-100 text-center">
                                                        <div class="fw-bold mb-2 text-primary text-start border-bottom pb-1 small">Attached Receipt Preview</div>
                                                        @if($deposit->hasReceipt())
                                                            <a href="{{ route('admin.bank-deposits.receipt', $deposit->id) }}" target="_blank">
                                                                <img src="{{ route('admin.bank-deposits.receipt', $deposit->id) }}" alt="Receipt Slip" class="img-thumbnail" style="max-height: 140px; object-fit: contain;">
                                                            </a>
                                                            <div class="mt-1"><small class="text-muted">Click image to expand</small></div>
                                                        @else
                                                            <div class="text-muted py-4 small">No receipt image attached</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Verification Note / Remarks (Optional)</label>
                                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="e.g. Verified with bank statement slip on portal">
                                            </div>

                                            <div class="alert alert-secondary small mb-0 py-2">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Confirming will atomically update the deposit status and reduce physical cash in Cash Book for <strong>{{ $deposit->branch->name }}</strong> on <strong>{{ $deposit->deposit_date->format('d/m/Y') }}</strong>.
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold">
                                                <i class="bi bi-check-lg me-1"></i> Confirm & Approve
                                            </button>
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
                                            <h5 class="modal-title fs-6 fw-bold text-white"><i class="bi bi-x-circle-fill me-2"></i>Reject Bank Deposit</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="mb-2">You are rejecting bank deposit of <strong class="text-danger">₹{{ number_format($deposit->amount, 2) }}</strong> (Ref: <span class="font-monospace">{{ $deposit->reference_number }}</span>).</p>
                                            <p class="small text-muted mb-3">Physical cash balance will NOT be altered.</p>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-danger">Rejection Reason *</label>
                                                <textarea name="rejection_reason" class="form-control form-control-sm" rows="3" required placeholder="Specify why this deposit is rejected (e.g. Amount mismatch on slip, slip unreadable, credit not reflected in account)..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold">Confirm Rejection</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endcan

                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No bank deposits recorded matching criteria.</td>
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

    @else
    <!-- Tab 2: Admin Bank Account Management -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-credit-card-2-front text-primary me-2"></i>Company Bank Accounts</h5>
                <small class="text-muted">Manage active bank accounts where branch managers can deposit physical branch cash.</small>
            </div>
            <button type="button" class="btn btn-primary rounded-pill btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Account
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="py-3 px-3">BANK NAME</th>
                        <th class="py-3 px-3">ACCOUNT HOLDER NAME</th>
                        <th class="py-3 px-3">NICKNAME</th>
                        <th class="py-3 px-3 font-monospace">ACCOUNT NUMBER</th>
                        <th class="py-3 px-3 font-monospace">IFSC CODE</th>
                        <th class="py-3 px-3">BANK BRANCH</th>
                        <th class="py-3 px-3 text-center">STATUS</th>
                        <th class="py-3 px-3 text-center">LINKED DEPOSITS</th>
                        <th class="py-3 px-3 text-center">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adminBankAccounts as $acc)
                        <tr>
                            <td class="px-3 py-3 fw-bold text-dark">{{ $acc->bank_name }}</td>
                            <td class="px-3 py-3">{{ $acc->account_name }}</td>
                            <td class="px-3 py-3 text-muted">{{ $acc->account_nickname ?: '—' }}</td>
                            <td class="px-3 py-3 font-monospace fw-bold text-primary">
                                {{ $acc->masked_account_number }}
                            </td>
                            <td class="px-3 py-3 font-monospace">{{ $acc->ifsc_code ?: '—' }}</td>
                            <td class="px-3 py-3 text-muted">{{ $acc->branch_name ?: '—' }}</td>
                            <td class="px-3 py-3 text-center">
                                @if($acc->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-check-circle me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1">
                                        <i class="bi bi-dash-circle me-1"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="badge bg-light text-dark border">{{ $acc->deposits()->count() }} deposits</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" data-bs-toggle="modal" data-bs-target="#editAccountModal{{ $acc->id }}" title="Edit Account">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form action="{{ route('admin.bank-deposits.accounts.toggle-status', $acc->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $acc->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} py-1 px-2 rounded-pill" title="{{ $acc->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                            <i class="bi {{ $acc->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                        </button>
                                    </form>

                                    @if($acc->deposits()->doesntExist())
                                        <form action="{{ route('admin.bank-deposits.accounts.destroy', $acc->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bank account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill" title="Delete Account">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-light border py-1 px-2 rounded-pill text-muted" title="Cannot delete: historical deposits exist. Deactivate instead." disabled>
                                            <i class="bi bi-lock-fill"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Account Modal -->
                        <div class="modal fade" id="editAccountModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="{{ route('admin.bank-deposits.accounts.update', $acc->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title fs-6 fw-bold text-white"><i class="bi bi-pencil-square me-2"></i>Edit Bank Account</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Bank Name *</label>
                                                <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ $acc->bank_name }}" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Account Holder Name *</label>
                                                <input type="text" name="account_name" class="form-control form-control-sm" value="{{ $acc->account_name }}" required>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Account Number *</label>
                                                    <input type="text" name="account_number" class="form-control form-control-sm font-monospace" value="{{ $acc->account_number }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">IFSC Code *</label>
                                                    <input type="text" name="ifsc_code" class="form-control form-control-sm font-monospace text-uppercase" value="{{ $acc->ifsc_code }}" required>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Bank Branch Name *</label>
                                                    <input type="text" name="branch_name" class="form-control form-control-sm" value="{{ $acc->branch_name }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Account Nickname (Optional)</label>
                                                    <input type="text" name="account_nickname" class="form-control form-control-sm" value="{{ $acc->account_nickname }}">
                                                </div>
                                            </div>

                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active{{ $acc->id }}" {{ $acc->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-bold" for="edit_is_active{{ $acc->id }}">Active for Branch Deposits</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No bank accounts added yet. Click "Add New Account" above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Submit Bank Deposit Modal -->
@can('bank_deposit.create')
<div class="modal fade" id="submitDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.bank-deposits.store') }}" method="POST" enctype="multipart/form-data" id="formSubmitDeposit">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6 fw-bold text-white"><i class="bi bi-bank me-2"></i>Submit Branch Bank Deposit</h5>
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

                    <!-- Bank Account Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Destination Company Bank Account *</label>
                        @if($activeBankAccounts->isNotEmpty())
                            <select name="bank_account_id" id="deposit_bank_account_id" class="form-select form-select-sm" required onchange="handleBankAccountChange(this)">
                                <option value="">-- Choose Company Bank Account --</option>
                                @foreach($activeBankAccounts as $account)
                                    <option value="{{ $account->id }}" 
                                            data-bank="{{ $account->bank_name }}" 
                                            data-number="{{ $account->account_number }}"
                                            data-ifsc="{{ $account->ifsc_code }}"
                                            data-holder="{{ $account->account_name }}">
                                        {{ $account->display_name }} (IFSC: {{ $account->ifsc_code }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="bank_name" id="hidden_bank_name">
                            <input type="hidden" name="account_number" id="hidden_account_number">
                        @else
                            <div class="alert alert-warning small mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i> No active company bank accounts found. Please contact an Admin to configure accounts.
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="text" name="bank_name" class="form-control form-control-sm" placeholder="Bank Name *" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="account_number" class="form-control form-control-sm" placeholder="Account Number">
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deposit Date *</label>
                            <input type="date" name="deposit_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Deposit Amount (₹) *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm font-monospace fs-6" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bank Slip / Reference / UTR No *</label>
                        <input type="text" name="reference_number" class="form-control form-control-sm font-monospace" required placeholder="e.g. SLIP-849201 or UTR-SBI92840">
                    </div>

                    <!-- Receipt Upload & Camera Preview -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <label class="form-label small fw-bold text-dark d-block">
                            <i class="bi bi-camera me-1 text-primary"></i>Deposit Receipt Slip / Proof *
                        </label>
                        <small class="text-muted d-block mb-2">
                            Take a photo using your device camera or select a scanned receipt image from your gallery (JPEG, PNG, WebP — Max 5MB).
                        </small>
                        
                        <input type="file" 
                               name="receipt" 
                               id="deposit_receipt_input" 
                               class="form-control form-control-sm" 
                               accept="image/jpeg,image/png,image/webp" 
                               capture="environment"
                               required 
                               onchange="handleReceiptPreview(event)">

                        <div id="receipt_preview_container" class="mt-3 d-none">
                            <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded-2 border">
                                <div class="d-flex align-items-center gap-3">
                                    <img id="receipt_preview_img" src="#" alt="Receipt Preview" class="img-thumbnail" style="max-height: 80px; max-width: 120px; object-fit: contain;">
                                    <div>
                                        <div id="receipt_filename" class="fw-bold small text-dark"></div>
                                        <div id="receipt_filesize" class="text-muted small"></div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill py-1 px-3" onclick="clearReceiptInput()">
                                    <i class="bi bi-trash me-1"></i> Replace
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Remarks (Optional)</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Optional notes for deposit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" id="btnSubmitDeposit">
                        <i class="bi bi-upload me-1"></i> Submit Deposit for Verification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Add Bank Account Modal (Admin) -->
@if($canManageAccounts)
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('admin.bank-deposits.accounts.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6 fw-bold text-white"><i class="bi bi-plus-circle me-2"></i>Add Company Bank Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bank Name *</label>
                        <input type="text" name="bank_name" class="form-control form-control-sm" required placeholder="e.g. State Bank of India">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Account Holder Name *</label>
                        <input type="text" name="account_name" class="form-control form-control-sm" required placeholder="e.g. Grihalaxmi Finance Pvt Ltd">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Account Number *</label>
                            <input type="text" name="account_number" class="form-control form-control-sm font-monospace" required placeholder="e.g. 38472910492">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">IFSC Code *</label>
                            <input type="text" name="ifsc_code" class="form-control form-control-sm font-monospace text-uppercase" required placeholder="e.g. SBIN0001234">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bank Branch Name *</label>
                            <input type="text" name="branch_name" class="form-control form-control-sm" required placeholder="e.g. Main Branch, Patna">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nickname / Display (Optional)</label>
                            <input type="text" name="account_nickname" class="form-control form-control-sm" placeholder="e.g. SBI Main Collection A/C">
                        </div>
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_is_active" checked>
                        <label class="form-check-label small fw-bold" for="add_is_active">Active (Selectable for branch deposits)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Save Bank Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function handleBankAccountChange(select) {
    const selectedOpt = select.options[select.selectedIndex];
    const bankInput = document.getElementById('hidden_bank_name');
    const accInput = document.getElementById('hidden_account_number');

    if (selectedOpt && selectedOpt.value) {
        if (bankInput) bankInput.value = selectedOpt.getAttribute('data-bank') || '';
        if (accInput) accInput.value = selectedOpt.getAttribute('data-number') || '';
    }
}

function handleReceiptPreview(event) {
    const file = event.target.files[0];
    const container = document.getElementById('receipt_preview_container');
    const previewImg = document.getElementById('receipt_preview_img');
    const filenameEl = document.getElementById('receipt_filename');
    const filesizeEl = document.getElementById('receipt_filesize');

    if (!file) {
        if (container) container.classList.add('d-none');
        return;
    }

    // Validate size (max 5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('The selected receipt image is larger than 5 MB. Please choose a smaller file.');
        event.target.value = '';
        if (container) container.classList.add('d-none');
        return;
    }

    // Validate type
    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        alert('Invalid file format. Please upload a JPEG, PNG, or WebP image.');
        event.target.value = '';
        if (container) container.classList.add('d-none');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        if (previewImg) previewImg.src = e.target.result;
        if (filenameEl) filenameEl.textContent = file.name;
        if (filesizeEl) filesizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
        if (container) container.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

function clearReceiptInput() {
    const input = document.getElementById('deposit_receipt_input');
    const container = document.getElementById('receipt_preview_container');
    if (input) input.value = '';
    if (container) container.classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formSubmitDeposit');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('btnSubmitDeposit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting...';
            }
        });
    }
});
</script>
@endpush
@endsection
