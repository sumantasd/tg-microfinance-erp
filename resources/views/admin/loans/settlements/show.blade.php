@extends('layouts.admin')

@section('title', 'Settlement Request #' . $settlementRequest->id . ' - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">Settlement Request #{{ $settlementRequest->id }}</h4>
            @php
                $badgeClass = match($settlementRequest->status) {
                    'pending_approval' => 'bg-warning text-dark',
                    'approved' => 'bg-info text-white',
                    'completed' => 'bg-success text-white',
                    'rejected' => 'bg-danger text-white',
                    default => 'bg-secondary text-white'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ str_replace('_', ' ', $settlementRequest->status) }}</span>
            <span class="badge bg-light text-dark border px-2.5 py-1 text-uppercase">{{ str_replace('_', ' ', $settlementRequest->request_type) }}</span>
        </div>
        <p class="text-muted small mb-0">
            Loan Account: <strong><a href="{{ route('admin.loan-account.show', $settlementRequest->loan_account_id) }}" class="text-decoration-none">{{ $settlementRequest->loanAccount->loan_number ?? 'N/A' }}</a></strong>
            <span class="mx-2">|</span>
            Borrower: <strong>{{ $settlementRequest->loanAccount->customer->full_name ?? 'N/A' }}</strong>
            <span class="mx-2">|</span>
            Branch: <strong>{{ $settlementRequest->branch->name ?? 'N/A' }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-settlement.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Settlements Queue
        </a>
        <a href="{{ route('admin.loan-account.show', $settlementRequest->loan_account_id) }}" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-credit-card me-1"></i> View Loan Account
        </a>
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

<div class="row g-4">
    <!-- Financial Breakdown Card -->
    <div class="col-md-7">
        <x-ui.card class="shadow-sm border-0 p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-calculator-fill text-primary me-2"></i>Financial Terms & Allocation</h5>
            
            <div class="table-responsive">
                <table class="table table-sm table-borderless align-middle mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <td class="text-muted py-2">As-Of Date:</td>
                            <td class="text-end fw-bold py-2">{{ $settlementRequest->as_of_date ? $settlementRequest->as_of_date->format('d M Y') : 'N/A' }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-muted py-2">Valid Until Date:</td>
                            <td class="text-end fw-bold py-2">{{ $settlementRequest->valid_until_date ? $settlementRequest->valid_until_date->format('d M Y') : 'N/A' }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-muted py-2">Outstanding Principal Balance:</td>
                            <td class="text-end font-monospace fw-bold py-2">₹{{ number_format($settlementRequest->principal_outstanding, 2) }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-muted py-2">Accrued / Outstanding Interest:</td>
                            <td class="text-end font-monospace fw-bold py-2">₹{{ number_format($settlementRequest->accrued_interest, 2) }}</td>
                        </tr>
                        @if($settlementRequest->unearned_interest_rebate > 0)
                            <tr class="border-bottom text-success">
                                <td class="py-2">Unearned Interest Rebate (100% Discount):</td>
                                <td class="text-end font-monospace fw-bold py-2">- ₹{{ number_format($settlementRequest->unearned_interest_rebate, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-bottom">
                            <td class="text-muted py-2">Outstanding Fee Charges:</td>
                            <td class="text-end font-monospace fw-bold py-2">₹{{ number_format($settlementRequest->fee_outstanding, 2) }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="text-muted py-2">Outstanding Late Penalties:</td>
                            <td class="text-end font-monospace fw-bold text-danger py-2">₹{{ number_format($settlementRequest->penalty_outstanding, 2) }}</td>
                        </tr>
                        @if($settlementRequest->foreclosure_fee > 0)
                            <tr class="border-bottom">
                                <td class="text-muted py-2">Foreclosure Charge:</td>
                                <td class="text-end font-monospace fw-bold py-2">₹{{ number_format($settlementRequest->foreclosure_fee, 2) }}</td>
                            </tr>
                        @endif
                        @if($settlementRequest->discount_concession_amount > 0)
                            <tr class="border-bottom text-danger">
                                <td class="py-2 fw-bold">Approved Concession / Haircut Loss:</td>
                                <td class="text-end font-monospace fw-bold py-2">- ₹{{ number_format($settlementRequest->discount_concession_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="bg-light fs-5">
                            <td class="py-3 ps-2 fw-bold text-primary">Final Settlement / Payoff Amount:</td>
                            <td class="text-end pe-2 font-monospace fw-bold text-success py-3">₹{{ number_format($settlementRequest->final_settlement_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        @if($settlementRequest->approval_remarks)
            <x-ui.card class="shadow-sm border-0 p-4 mb-4">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-chat-left-text-fill text-info me-2"></i>Manager Remarks & Justification</h6>
                <p class="text-muted mb-0 small">{{ $settlementRequest->approval_remarks }}</p>
            </x-ui.card>
        @endif

        @if($settlementRequest->rejection_reason)
            <x-ui.card class="shadow-sm border-0 p-4 mb-4 bg-danger-subtle">
                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-x-circle-fill me-2"></i>Rejection Reason</h6>
                <p class="text-danger mb-0 small">{{ $settlementRequest->rejection_reason }}</p>
            </x-ui.card>
        @endif
    </div>

    <!-- Workflow Status & Actions Sidebar -->
    <div class="col-md-5">
        <!-- Audit Trail Card -->
        <x-ui.card class="shadow-sm border-0 p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-shield-check text-success me-2"></i>Governance & Audit Trail</h5>

            <div class="mb-3 small">
                <span class="text-muted d-block">Requested By:</span>
                <strong>{{ $settlementRequest->requester->name ?? 'System' }}</strong>
                <div class="text-muted" style="font-size: 0.75rem;">{{ $settlementRequest->requested_at ? $settlementRequest->requested_at->format('d M Y, h:i A') : 'N/A' }}</div>
            </div>

            @if($settlementRequest->approved_by)
                <div class="mb-3 small">
                    <span class="text-muted d-block">Approved By:</span>
                    <strong class="text-success">{{ $settlementRequest->approver->name ?? 'Authorized Signatory' }}</strong>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ $settlementRequest->approved_at ? $settlementRequest->approved_at->format('d M Y, h:i A') : 'N/A' }}</div>
                </div>
            @endif

            @if($settlementRequest->repayment)
                <div class="mb-3 small">
                    <span class="text-muted d-block">Repayment Receipt:</span>
                    <span class="font-monospace fw-bold text-primary">#{{ $settlementRequest->repayment->receipt_number }}</span>
                    <div class="text-muted" style="font-size: 0.75rem;">Paid ₹{{ number_format($settlementRequest->repayment->amount, 2) }} via {{ ucfirst($settlementRequest->repayment->payment_method) }}</div>
                </div>
            @endif

            @if($settlementRequest->voucher)
                <div class="small">
                    <span class="text-muted d-block">Accounting GL Voucher:</span>
                    <span class="font-monospace fw-bold text-dark">#{{ $settlementRequest->voucher->voucher_number }}</span>
                </div>
            @endif
        </x-ui.card>

        <!-- Action Card -->
        <x-ui.card class="shadow-sm border-0 p-4">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-gear-fill text-primary me-2"></i>Workflow Actions</h5>

            @if($settlementRequest->status === 'pending_approval')
                @if($canApprove)
                    <form action="{{ route('admin.loan-settlement.approve', $settlementRequest->id) }}" method="POST" class="mb-3" onsubmit="return confirm('Approve this settlement request?');">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-muted">Approval Remarks</label>
                            <input type="text" name="approval_remarks" class="form-control form-control-sm" placeholder="Optional approval note">
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold"><i class="bi bi-check2-circle me-1"></i> Approve Request</button>
                    </form>

                    <!-- Reject Modal Trigger -->
                    <button type="button" class="btn btn-outline-danger w-100 fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject Request
                    </button>
                @else
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-lock-fill me-1"></i>
                        You do not have the required role authority to approve this concession of ₹{{ number_format($settlementRequest->discount_concession_amount, 2) }}.
                    </div>
                @endif
            @elseif($settlementRequest->status === 'approved')
                @can('loan_foreclosure.process')
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Request is approved. Ready to collect settlement payment and finalize loan closure.
                    </div>
                    <button type="button" class="btn btn-primary w-100 fw-bold py-2" data-bs-toggle="modal" data-bs-target="#collectPaymentModal">
                        <i class="bi bi-cash-stack me-1"></i> Collect Settlement Payment & Close Loan
                    </button>
                @endcan
            @elseif($settlementRequest->status === 'completed')
                <div class="alert alert-success py-2 small mb-3">
                    <i class="bi bi-check-all me-1"></i>
                    This settlement has been fully executed and the loan is closed.
                </div>
                @can('loan_closure.certificate')
                    <a href="{{ route('admin.loan-account.noc', $settlementRequest->loan_account_id) }}" target="_blank" class="btn btn-outline-success w-100 fw-bold">
                        <i class="bi bi-patch-check-fill me-1"></i> Download / Print NOC
                    </a>
                @endcan
            @endif
        </x-ui.card>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-settlement.reject', $settlementRequest->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i>Reject Settlement Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain why this proposal is rejected..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Collect Payment & Finalize Modal -->
@if($settlementRequest->status === 'approved')
<div class="modal fade" id="collectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-settlement.execute', $settlementRequest->id) }}" method="POST" onsubmit="return confirm('Execute settlement collection? This will permanently close Loan #{{ $settlementRequest->loanAccount->loan_number }}.');">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Collect Settlement & Close Loan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        Agreed Settlement Amount: <strong class="fs-6 font-monospace">₹{{ number_format($settlementRequest->final_settlement_amount, 2) }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash in Vault</option>
                            <option value="bank_transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                            <option value="upi">UPI / Digital</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Collection Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional collection note">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-check-circle-fill me-1"></i> Finalize Settlement Collection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
