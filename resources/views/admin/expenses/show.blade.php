@extends('layouts.admin')

@section('title', "Expense #{$expense->expense_number} - Grihalaxmi Finance ERP")

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-receipt text-danger me-2"></i>Expense #{{ $expense->expense_number }}
            </h4>
            @if($expense->status === 'DRAFT')
                <span class="badge bg-secondary">Draft</span>
            @elseif($expense->status === 'PENDING_APPROVAL')
                <span class="badge bg-warning text-dark">Pending Approval</span>
            @elseif($expense->status === 'APPROVED')
                <span class="badge bg-info text-dark">Approved</span>
            @elseif($expense->status === 'PARTIALLY_PAID')
                <span class="badge bg-primary">Partially Paid</span>
            @elseif($expense->status === 'PAID')
                <span class="badge bg-success">Paid</span>
            @elseif($expense->status === 'REJECTED')
                <span class="badge bg-danger">Rejected</span>
            @elseif($expense->status === 'CANCELLED')
                <span class="badge bg-dark">Cancelled</span>
            @endif
        </div>
        <p class="text-muted small mb-0">Created on {{ $expense->created_at->format('d M Y, h:i A') }} by {{ $expense->creator?->name ?? 'System' }}</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>

        @if($expense->isSubmittable())
        @can('expense.submit')
        <form method="POST" action="{{ route('admin.expenses.submit', $expense->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" onclick="return confirm('Submit this expense for management approval?')">
                <i class="bi bi-send me-1"></i> Submit for Approval
            </button>
        </form>
        @endcan
        @endif

        @if($expense->isApprovable())
        @can('expense.approve')
        <button type="button" class="btn btn-success text-white fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
            <i class="bi bi-check-lg me-1"></i> Approve Expense
        </button>
        @endcan
        @can('expense.reject')
        <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-lg me-1"></i> Reject
        </button>
        @endcan
        @endif

        @if($expense->isPayable())
        @can('expense.pay')
        <button type="button" class="btn btn-primary text-white fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#payModal">
            <i class="bi bi-cash-stack me-1"></i> Record Payment
        </button>
        @endcan
        @endif

        @if($expense->isEditable())
        @can('expense.edit')
        <a href="{{ route('admin.expenses.edit', $expense->id) }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endcan
        @endif

        @if($expense->isCancellable() && !in_array($expense->status, ['DRAFT', 'CANCELLED']))
        @can('expense.cancel')
        <button type="button" class="btn btn-outline-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelModal">
            <i class="bi bi-slash-circle me-1"></i> Cancel Expense
        </button>
        @endcan
        @endif

        @if($expense->isDeletable())
        @can('expense.delete')
        <form method="POST" action="{{ route('admin.expenses.destroy', $expense->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this draft expense?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                <i class="bi bi-trash"></i>
            </button>
        </form>
        @endcan
        @endif
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

<!-- Lifecycle Progress Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-center">
            <div class="px-3 py-2 rounded-3 {{ in_array($expense->status, ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'PARTIALLY_PAID', 'PAID']) ? 'bg-primary text-white' : 'bg-light text-muted' }}">
                <i class="bi bi-file-earmark-plus d-block fs-5"></i>
                <small class="fw-bold">1. Draft Created</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
            <div class="px-3 py-2 rounded-3 {{ in_array($expense->status, ['PENDING_APPROVAL', 'APPROVED', 'PARTIALLY_PAID', 'PAID']) ? 'bg-primary text-white' : 'bg-light text-muted' }}">
                <i class="bi bi-hourglass-split d-block fs-5"></i>
                <small class="fw-bold">2. Pending Approval</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
            <div class="px-3 py-2 rounded-3 {{ in_array($expense->status, ['APPROVED', 'PARTIALLY_PAID', 'PAID']) ? 'bg-primary text-white' : 'bg-light text-muted' }}">
                <i class="bi bi-shield-check d-block fs-5"></i>
                <small class="fw-bold">3. Approved</small>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
            <div class="px-3 py-2 rounded-3 {{ in_array($expense->status, ['PARTIALLY_PAID', 'PAID']) ? 'bg-success text-white' : 'bg-light text-muted' }}">
                <i class="bi bi-cash-stack d-block fs-5"></i>
                <small class="fw-bold">4. Payment Processed</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Main Expense Particulars -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-text text-primary me-2"></i>Expense Particulars</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">EXPENSE NUMBER</small>
                        <span class="fs-5 fw-bold font-monospace text-primary">{{ $expense->expense_number }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">EXPENSE DATE</small>
                        <span class="fs-6 fw-semibold text-dark">{{ $expense->expense_date->format('d F Y') }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">BRANCH ALLOCATION</small>
                        <span class="badge bg-light text-dark border fs-6 mt-1">{{ $expense->branch->name }} ({{ $expense->branch->code }})</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">EXPENSE CATEGORY</small>
                        <span class="fs-6 fw-bold text-dark">{{ $expense->category->category_name }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">PAYEE / VENDOR</small>
                        <span class="fs-6 fw-semibold text-dark">{{ $expense->payee_display_name }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block fw-bold">BILL / REFERENCE NO.</small>
                        <span class="fs-6 text-dark">{{ $expense->reference_number ?: 'N/A' }}</span>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted d-block fw-bold">DESCRIPTION / REASON</small>
                        <div class="p-3 bg-light rounded-3 mt-1 text-dark">{{ $expense->description }}</div>
                    </div>
                    @if($expense->notes)
                    <div class="col-md-12">
                        <small class="text-muted d-block fw-bold">INTERNAL NOTES</small>
                        <div class="p-2 text-muted italic">{{ $expense->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Transactions History -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-wallet2 text-success me-2"></i>Recorded Payment Transactions</h6>
                <span class="badge bg-success-subtle text-success">{{ $expense->payments->count() }} Payments Recorded</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Voucher #</th>
                                <th class="text-end">Paid Amount</th>
                                <th>Paid By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expense->payments as $pmt)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $pmt->payment_number }}</td>
                                <td>{{ $pmt->payment_date->format('d M Y') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $pmt->payment_method)) }}</span></td>
                                <td>
                                    @if($pmt->voucher)
                                        <span class="badge bg-info-subtle text-info font-monospace">{{ $pmt->voucher->voucher_number }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">₹{{ number_format($pmt->paid_amount, 2) }}</td>
                                <td class="small">{{ $pmt->paidBy->name ?? 'Staff' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attachments & Documents -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-paperclip text-secondary me-2"></i>Attachments & Supporting Documents</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    @forelse($expense->attachments as $att)
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between bg-light">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <i class="bi bi-file-earmark-pdf fs-3 text-danger"></i>
                                <div class="text-truncate">
                                    <span class="d-block fw-semibold text-dark text-truncate">{{ $att->file_name }}</span>
                                    <small class="text-muted">{{ number_format($att->file_size / 1024, 1) }} KB • {{ $att->created_at->format('d M Y') }}</small>
                                </div>
                            </div>
                            <a href="{{ route('admin.expenses.attachments.download', $att->id) }}" class="btn btn-sm btn-outline-primary ms-2" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center text-muted py-3">No receipt or bill attachments uploaded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Financial Summary Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calculator text-primary me-2"></i>Financial Summary</h6>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Base Amount:</span>
                    <span class="fw-semibold">₹{{ number_format($expense->amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">GST / Tax Amount:</span>
                    <span class="fw-semibold">₹{{ number_format($expense->tax_amount, 2) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">Total Amount:</span>
                    <span class="fs-4 fw-bold text-primary">₹{{ number_format($expense->total_amount, 2) }}</span>
                </div>

                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-muted">Paid Amount:</span>
                        <span class="fw-bold text-success">₹{{ number_format($expense->paid_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-semibold text-muted">Outstanding:</span>
                        <span class="fw-bold text-danger">₹{{ number_format($expense->outstanding_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow & Approval Audit Info -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-info me-2"></i>Approval & Audit Information</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <small class="text-muted d-block fw-bold">REQUESTED BY</small>
                    <span class="fw-semibold text-dark">{{ $expense->requestedBy->name ?? 'N/A' }}</span>
                </div>
                @if($expense->approvedBy)
                <div class="mb-3">
                    <small class="text-muted d-block fw-bold">APPROVED BY</small>
                    <span class="fw-semibold text-success">{{ $expense->approvedBy->name }}</span>
                    <small class="d-block text-muted">{{ $expense->approved_at ? $expense->approved_at->format('d M Y, h:i A') : '' }}</small>
                </div>
                @endif
                @if($expense->rejection_reason)
                <div class="p-3 bg-danger-subtle text-danger rounded-3 mb-3">
                    <strong class="d-block mb-1"><i class="bi bi-x-circle me-1"></i> Rejection Reason:</strong>
                    {{ $expense->rejection_reason }}
                </div>
                @endif
                @if($expense->cancellation_reason)
                <div class="p-3 bg-dark text-white rounded-3 mb-3">
                    <strong class="d-block mb-1"><i class="bi bi-slash-circle me-1"></i> Cancellation Reason:</strong>
                    {{ $expense->cancellation_reason }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Approve Expense -->
@can('expense.approve')
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.approve', $expense->id) }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-check-circle me-2"></i>Approve Expense Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3">Are you sure you want to approve Expense <strong>#{{ $expense->expense_number }}</strong> for <strong>₹{{ number_format($expense->total_amount, 2) }}</strong>?</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Approval Remarks (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Approve Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Modal: Reject Expense -->
@can('expense.reject')
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.reject', $expense->id) }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Reject Expense Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-danger">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this expense is rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">Reject Expense</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Modal: Record Payment -->
@can('expense.pay')
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.pay', $expense->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2"></i>Record Expense Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-muted">Outstanding Balance:</span>
                            <span class="fs-5 fw-bold text-danger">₹{{ number_format($expense->outstanding_amount, 2) }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Paid Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control form-control-lg fw-bold text-primary" max="{{ $expense->outstanding_amount }}" value="{{ $expense->outstanding_amount }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash (Vault / Field)</option>
                            <option value="bank_transfer">Bank Transfer / NEFT / RTGS</option>
                            <option value="upi">UPI Payment</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other Account</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bank Account (If Bank/UPI)</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_number }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transaction Reference / UTR / Cheque #</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. UTR-100293848">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Proof / Receipt Upload</label>
                        <input type="file" name="attachment" class="form-control">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Post Payment & Generate Voucher</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Modal: Cancel Expense -->
@can('expense.cancel')
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.cancel', $expense->id) }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-slash-circle me-2"></i>Cancel Expense</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-danger small mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Warning: Cancelling a paid/partially paid expense will automatically reverse all posted financial vouchers.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Provide cancellation reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4">Confirm Cancellation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@endsection
