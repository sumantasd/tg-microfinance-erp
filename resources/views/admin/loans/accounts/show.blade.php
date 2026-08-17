@extends('layouts.admin')

@section('title', 'Loan Account - ' . $account->loan_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $account->loan_number }}</h4>
            @php
                $badgeClass = match($account->status) {
                    'sanctioned' => 'bg-info text-white',
                    'ready_for_disbursement' => 'bg-warning text-dark',
                    'active' => 'bg-success text-white',
                    'closed' => 'bg-secondary text-white',
                    'defaulted', 'cancelled' => 'bg-danger text-white',
                    default => 'bg-light text-dark'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ str_replace('_', ' ', $account->status) }}</span>
        </div>
        <p class="text-muted small mb-0">
            Branch: <strong>{{ $account->branch->name ?? 'N/A' }}</strong> 
            <span class="mx-2">|</span>
            Scheme: <strong>{{ $account->loanScheme->name ?? 'N/A' }}</strong>
            <span class="mx-2">|</span>
            Sanction Date: <strong>{{ $account->sanction_date ? $account->sanction_date->format('d M Y') : 'N/A' }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-account.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Accounts
        </a>
        <a href="{{ route('admin.loan-account.statement', $account->id) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
            <i class="bi bi-printer me-1"></i> EMI Statement
        </a>
        @if($account->status === 'closed')
            @can('loan_closure.certificate')
                <a href="{{ route('admin.loan-account.noc', $account->id) }}" target="_blank" class="btn btn-success rounded-pill px-3 fw-bold shadow-sm">
                    <i class="bi bi-patch-check-fill me-1"></i> Loan NOC / Certificate
                </a>
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

<!-- Financial Breakdown Cards -->
<div class="row g-3 mb-4">
    @if($account->loan_type === 'product')
        <div class="col-md-3">
            <x-ui.card class="p-3 shadow-sm border-0 bg-light">
                <div class="small text-muted fw-bold uppercase">Total Product Price</div>
                <div class="fs-3 fw-bold text-dark mt-1 font-monospace">₹{{ number_format($account->product_price_amount, 2) }}</div>
                <div class="small text-muted">Gross Catalog Valuation</div>
            </x-ui.card>
        </div>

        <div class="col-md-3">
            <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
                <div class="small text-muted fw-bold uppercase">Customer Down Payment</div>
                <div class="fs-3 fw-bold text-success mt-1 font-monospace">₹{{ number_format($account->down_payment_amount, 2) }}</div>
                <div class="small text-muted">Paid Upfront</div>
            </x-ui.card>
        </div>
    @endif

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle">
            <div class="small text-muted fw-bold uppercase">Financed Principal Amount</div>
            <div class="fs-3 fw-bold text-primary mt-1 font-monospace">₹{{ number_format($account->sanctioned_amount, 2) }}</div>
            <div class="small text-muted">EMI calculated on this principal</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-danger-subtle">
            <div class="small text-muted fw-bold uppercase">Total Outstanding Balance</div>
            <div class="fs-3 fw-bold text-danger mt-1 font-monospace">₹{{ number_format($account->total_outstanding, 2) }}</div>
            <div class="small text-muted">Principal + Interest + Fees</div>
        </x-ui.card>
    </div>
</div>

<!-- Workflow Action Bar -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-bold text-dark"><i class="bi bi-gear-fill me-1 text-primary"></i>Loan Account Actions:</div>
        <div class="d-flex flex-wrap gap-2">
            @if($account->loan_type === 'product' && in_array($account->status, ['sanctioned', 'ready_for_disbursement']))
                @can('loan.issue_product')
                    <form action="{{ route('admin.loan-account.issue-product', $account->id) }}" method="POST" onsubmit="return confirm('Fulfill and issue physical product item? This will atomically deduct physical branch inventory stock.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-up-right me-1"></i> Fulfill Product & Issue Inventory Stock</button>
                    </form>
                @endcan
            @endif

            @if($account->loan_type === 'cash' && in_array($account->status, ['sanctioned', 'ready_for_disbursement']))
                @can('loan.disburse')
                    <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#disburseCashModal">
                        <i class="bi bi-cash-stack me-1"></i> Disburse Cash Loan
                    </button>
                @endcan
            @endif

            @if(in_array($account->status, ['active', 'defaulted']))
                @can('loan.record_repayment')
                    <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#repaymentModal">
                        <i class="bi bi-cash-coin me-1"></i> Record Repayment / EMI Collection
                    </button>
                @endcan
                @if($account->penalty_outstanding > 0)
                    @can('loans.waive_penalty')
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#accountWaivePenaltyModal">
                            <i class="bi bi-shield-slash me-1"></i> Waive Penalty (₹{{ number_format($account->penalty_outstanding, 2) }})
                        </button>
                    @endcan
                @endif
                @can('loan_foreclosure.process')
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#foreclosureModal" onclick="loadForeclosureQuote()">
                        <i class="bi bi-door-closed-fill me-1"></i> Foreclose Loan
                    </button>
                @endcan
                @can('loan_settlement.request')
                    <button type="button" class="btn btn-sm btn-outline-warning fw-bold" data-bs-toggle="modal" data-bs-target="#settlementOtsModal">
                        <i class="bi bi-handshake-fill me-1"></i> Propose Settlement (OTS)
                    </button>
                @endcan
                @can('loan_write_off.request')
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#writeOffModal">
                        <i class="bi bi-slash-circle-fill me-1"></i> Write-Off Loan
                    </button>
                @endcan
            @else
                @if(!in_array($account->status, ['closed', 'cancelled']))
                    @can('loan.record_down_payment')
                        <button type="button" class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#downPaymentModal">
                            <i class="bi bi-plus-circle me-1"></i> Record Pre-Disbursement Down Payment
                        </button>
                    @endcan
                @endif
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Loan Details Breakdown -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <x-ui.card class="shadow-sm border-0 p-4 h-100">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-info-circle text-primary me-2"></i>Loan & Borrower Profile</h5>
            @if($account->borrower_type === 'individual')
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Borrower Name</label>
                    <div class="fw-bold text-dark fs-6">{{ $account->customer->full_name ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Customer Code</label>
                    <div class="font-monospace text-primary fw-bold">{{ $account->customer->customer_code ?? 'N/A' }}</div>
                </div>
            @else
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Group Name</label>
                    <div class="fw-bold text-dark fs-6">{{ $account->customerGroup->name ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Group Code</label>
                    <div class="font-monospace text-info fw-bold">{{ $account->customerGroup->group_code ?? 'N/A' }}</div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div class="col-md-6">
        <x-ui.card class="shadow-sm border-0 p-4 h-100">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-calculator text-success me-2"></i>Financial Terms & Fees</h5>
            <div class="row g-2 small">
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Annual Interest Rate</label>
                    <div class="fw-bold text-success">{{ $account->interest_rate_per_annum }}% p.a.</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Interest Type</label>
                    <div class="fw-bold text-capitalize">{{ str_replace('_', ' ', $account->interest_type) }}</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Tenure & Frequency</label>
                    <div class="fw-bold">{{ $account->tenure_months }} Mos ({{ ucfirst($account->repayment_frequency) }})</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Total Interest Payable</label>
                    <div class="fw-bold text-primary font-monospace">₹{{ number_format($account->total_interest_amount, 2) }}</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Processing Fee</label>
                    <div>₹{{ number_format($account->processing_fee_amount, 2) }} ({{ $account->processing_fee_percentage }}%)</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Insurance Fee</label>
                    <div>₹{{ number_format($account->insurance_fee_amount, 2) }} ({{ $account->insurance_fee_percentage }}%)</div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- EMI Repayment Schedule Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-check text-primary me-2"></i>EMI Repayment Installment Schedule</h5>
        <a href="{{ route('admin.loan-account.statement', $account->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary fw-bold">
            <i class="bi bi-printer me-1"></i> Print EMI Statement
        </a>
    </div>
    <x-ui.data-table emptyMessage="No EMI installments generated for this loan account.">
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">EMI #</th>
            <th scope="col" class="py-3 px-3">Due Date</th>
            <th scope="col" class="py-3 px-3">Opening Principal</th>
            <th scope="col" class="py-3 px-3">EMI Amount</th>
            <th scope="col" class="py-3 px-3">Principal</th>
            <th scope="col" class="py-3 px-3">Interest</th>
            <th scope="col" class="py-3 px-3">Fees & Penalties</th>
            <th scope="col" class="py-3 px-3">Paid Amount</th>
            <th scope="col" class="py-3 px-3">Closing Principal</th>
            <th scope="col" class="py-3 px-3">Status</th>
        </x-slot:headers>

        @forelse($account->installments as $inst)
            <tr>
                <td class="px-3 py-3 font-monospace fw-bold text-dark">#{{ $inst->installment_number }}</td>
                <td class="px-3 py-3 small text-muted">{{ $inst->due_date ? $inst->due_date->format('d M Y') : 'N/A' }}</td>
                <td class="px-3 py-3 font-monospace small">₹{{ number_format($inst->opening_principal, 2) }}</td>
                <td class="px-3 py-3 font-monospace small fw-bold text-primary">₹{{ number_format($inst->installment_amount, 2) }}</td>
                <td class="px-3 py-3 font-monospace small fw-bold text-dark">₹{{ number_format($inst->principal_amount, 2) }}</td>
                <td class="px-3 py-3 font-monospace small text-success">₹{{ number_format($inst->interest_amount, 2) }}</td>
                <td class="px-3 py-3 font-monospace small text-muted">₹{{ number_format($inst->fee_amount + $inst->penalty_amount, 2) }}</td>
                <td class="px-3 py-3 font-monospace small fw-bold text-success">₹{{ number_format($inst->total_paid, 2) }}</td>
                <td class="px-3 py-3 font-monospace small">₹{{ number_format($inst->closing_principal, 2) }}</td>
                <td class="px-3 py-3">
                    @php
                        $stBadge = match($inst->status) {
                            'paid' => 'bg-success-subtle text-success border-success-subtle',
                            'partial' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'overdue' => 'bg-danger-subtle text-danger border-danger-subtle',
                            default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                        };
                    @endphp
                    <span class="badge {{ $stBadge }} border px-2 py-0.5 text-capitalize fw-bold">{{ $inst->status }}</span>
                </td>
            </tr>
        @empty
        @endforelse
    </x-ui.data-table>
</x-ui.card>

<!-- Payment History & Receipts -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-receipt-cutoff text-success me-2"></i>Repayment Transaction History & Receipts</h5>
    <x-ui.data-table emptyMessage="No repayment transactions recorded yet.">
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Receipt / Txn #</th>
            <th scope="col" class="py-3 px-3">Payment Date</th>
            <th scope="col" class="py-3 px-3">Total Amount</th>
            <th scope="col" class="py-3 px-3">Method</th>
            <th scope="col" class="py-3 px-3">Penalty Paid</th>
            <th scope="col" class="py-3 px-3">Fee Paid</th>
            <th scope="col" class="py-3 px-3">Interest Paid</th>
            <th scope="col" class="py-3 px-3">Principal Paid</th>
            <th scope="col" class="py-3 px-3">Adjustment</th>
            <th scope="col" class="py-3 px-3">Received By</th>
            <th scope="col" class="py-3 px-3">Ref Number</th>
            <th scope="col" class="py-3 px-3 text-end">Receipts</th>
        </x-slot:headers>

        @forelse($account->repayments as $rcpt)
            <tr>
                <td class="px-3 py-3 font-monospace fw-bold text-primary">{{ $rcpt->receipt_number }}</td>
                <td class="px-3 py-3 small text-muted">{{ $rcpt->payment_date ? $rcpt->payment_date->format('d M Y') : 'N/A' }}</td>
                <td class="px-3 py-3 font-monospace fw-bold text-success">₹{{ number_format($rcpt->amount, 0) }}</td>
                <td class="px-3 py-3 small"><span class="text-uppercase fw-bold badge bg-light text-dark border">{{ $rcpt->payment_method }}</span></td>
                <td class="px-3 py-3 font-monospace small text-danger">₹{{ number_format($rcpt->penalty_paid, 0) }}</td>
                <td class="px-3 py-3 font-monospace small text-muted">₹{{ number_format($rcpt->fee_paid, 0) }}</td>
                <td class="px-3 py-3 font-monospace small text-primary">₹{{ number_format($rcpt->interest_paid, 0) }}</td>
                <td class="px-3 py-3 font-monospace small fw-bold text-dark">₹{{ number_format($rcpt->principal_paid, 0) }}</td>
                <td class="px-3 py-3 small text-capitalize"><span class="badge bg-info-subtle text-info border text-dark">{{ str_replace('_', ' ', $rcpt->adjustment_mode) }}</span></td>
                <td class="px-3 py-3 small text-muted">{{ $rcpt->receiver->name ?? 'System' }}</td>
                <td class="px-3 py-3 small font-monospace text-muted">{{ $rcpt->reference_number ?? 'N/A' }}</td>
                <td class="px-3 py-3 text-end">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.emi-collection.receipt', $rcpt->id) }}" target="_blank" class="btn btn-sm btn-outline-success fw-bold" title="A4 Print Receipt">
                            <i class="bi bi-printer me-1"></i> A4
                        </a>
                        <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $rcpt->id, 'width' => '80']) }}" target="_blank" class="btn btn-sm btn-outline-dark fw-bold" title="Thermal 80mm Print">
                            <i class="bi bi-receipt me-1"></i> 80mm
                        </a>
                    </div>
                </td>
            </tr>
        @empty
        @endforelse
    </x-ui.data-table>
</x-ui.card>

@can('loan.disburse')
<!-- Cash Disbursement Modal -->
<div class="modal fade" id="disburseCashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-account.disburse-cash', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success">Disburse Cash Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Disbursement Amount (₹)</label>
                        <input type="text" class="form-control" value="₹{{ number_format($account->sanctioned_amount, 2) }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer / NEFT</option>
                            <option value="cash">Cash Payout</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Bank Ref / Transaction #</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR10928301">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Confirm Disbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('loan.record_repayment')
<!-- Record Repayment Modal -->
<div class="modal fade" id="repaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-account.record-repayment', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-cash-coin me-1"></i> Record Loan Repayment / EMI Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Waterfall Allocation: <strong>1. Penalties &rarr; 2. Fees &rarr; 3. Interest &rarr; 4. Principal</strong>.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Repayment Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control font-monospace fw-bold" placeholder="e.g. 5000.00" max="{{ $account->total_outstanding }}" required>
                        <div class="form-text small">Total Outstanding Balance: <strong>₹{{ number_format($account->total_outstanding, 2) }}</strong></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer / NEFT</option>
                            <option value="upi">UPI / Digital</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Debit/Credit Card</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Receipt / Txn Ref #</label>
                        <input type="text" name="reference_number" class="form-control font-monospace" placeholder="e.g. UTR / UTR990823">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Schedule Prepayment Adjustment Option <span class="text-danger">*</span></label>
                        <select name="adjustment_mode" class="form-select" required>
                            <option value="reduce_tenure" selected>Reduce Loan Tenure (Keep EMI amount, reduce future installments)</option>
                            <option value="reduce_emi">Reduce EMI Amount (Keep tenure end date, reduce future EMI amount)</option>
                            <option value="none">Standard Allocation Only</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle me-1"></i> Save Repayment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('loan.record_down_payment')
<!-- Down Payment Modal -->
<div class="modal fade" id="downPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-account.record-down-payment', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success">Record Pre-Disbursement Down Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Down Payment Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 3500.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="upi">UPI / Digital</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Debit/Credit Card</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Receipt / Txn Ref #</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="Optional reference">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Save Down Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('loans.waive_penalty')
<!-- Loan Penalty Waiver Modal -->
<div class="modal fade" id="accountWaivePenaltyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.penalties.waive', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-shield-slash me-1"></i> Waive Loan Penalty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Important:</strong> Penalty waiver is permanent and non-reversible. It directly reduces borrower outstanding balance.
                    </div>
                    <div class="bg-light p-3 rounded border mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Loan Number:</span>
                            <strong class="text-dark">{{ $account->loan_number }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Borrower:</span>
                            <strong class="text-dark">{{ $account->customer->full_name ?? 'N/A' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Max Uncollected Penalty:</span>
                            <strong class="font-monospace text-danger fs-6">₹{{ number_format($account->penalty_outstanding, 2) }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Waiver Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control font-monospace fw-bold fs-5" value="{{ $account->penalty_outstanding }}" max="{{ $account->penalty_outstanding }}" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Waiver Justification / Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Mandatory managerial justification (e.g. medical hardship, approved committee resolution)..." required></textarea>
                    </div>
                </div>
@endcan

@can('loan_foreclosure.process')
<!-- Early Foreclosure Modal -->
<div class="modal fade" id="foreclosureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.loan-settlement.foreclose', $account->id) }}" method="POST" onsubmit="return confirm('Confirm early loan foreclosure? This action will collect the full payoff amount, rebate future unearned interest, and permanently close the loan.');">
                @csrf
                <div class="modal-header bg-warning-subtle text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-door-closed-fill me-2 text-warning"></i>Early Loan Foreclosure & Full Payoff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>Pro-Rata Interest Rebate:</strong> Future unearned interest is automatically 100% discounted. All future installments will be marked as waived.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Payoff As-Of Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" id="fc_as_of_date" class="form-control" value="{{ date('Y-m-d') }}" onchange="loadForeclosureQuote()" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash in Vault</option>
                                <option value="bank_transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="upi">UPI / QR Payment</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <!-- Live Quote Sheet -->
                    <div class="card bg-light border-0 p-3 mb-3" id="fc_quote_container">
                        <h6 class="fw-bold text-dark border-bottom pb-2 mb-2">Itemized Foreclosure Calculation Sheet</h6>
                        <div class="d-flex justify-content-between py-1 small border-bottom">
                            <span class="text-muted">Principal Outstanding:</span>
                            <span class="font-monospace fw-bold" id="fc_principal">₹{{ number_format($account->principal_outstanding, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom">
                            <span class="text-muted">Accrued Earned Interest (Elapsed):</span>
                            <span class="font-monospace fw-bold text-dark" id="fc_accrued_interest">Loading...</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom text-success">
                            <span>Unearned Future Interest (100% Rebate):</span>
                            <span class="font-monospace fw-bold" id="fc_rebate">- ₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom">
                            <span class="text-muted">Outstanding Fee Charges:</span>
                            <span class="font-monospace fw-bold" id="fc_fee">₹{{ number_format($account->fee_outstanding, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom text-danger">
                            <span>Outstanding Late Penalties:</span>
                            <span class="font-monospace fw-bold" id="fc_penalty">₹{{ number_format($account->penalty_outstanding, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 small border-bottom">
                            <span class="text-muted">Foreclosure Charge / Fee:</span>
                            <span class="font-monospace fw-bold" id="fc_foreclosure_fee">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 fs-5 fw-bold text-primary">
                            <span>Net Final Payoff Amount:</span>
                            <span class="font-monospace" id="fc_final_amount">₹{{ number_format($account->total_outstanding, 2) }}</span>
                        </div>
                        <div id="fc_lock_in_alert" class="mt-2 d-none alert alert-danger py-1.5 small mb-0"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Foreclosure Remarks / Note</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional borrower pre-closure note">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="fc_submit_btn" class="btn btn-warning text-dark fw-bold px-4"><i class="bi bi-check-circle-fill me-1"></i> Confirm & Collect Foreclosure</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('loan_settlement.request')
<!-- One-Time Settlement (OTS) Proposal Modal -->
<div class="modal fade" id="settlementOtsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.loan-settlement.request-ots', $account->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-handshake-fill me-2"></i>Propose One-Time Compromise Settlement (OTS)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary py-2 small mb-3">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        <strong>Multi-Level Governance:</strong> Concessions &le; ₹5,000 require Branch Manager approval; &gt; ₹5,000 require Company Admin; &gt; ₹25,000 require Super Admin approval.
                    </div>

                    <div class="bg-light p-3 rounded mb-3 small border">
                        <div class="row g-2">
                            <div class="col-6"><span class="text-muted">Total Contractual Demand:</span> <strong class="font-monospace text-danger">₹{{ number_format($account->total_outstanding, 2) }}</strong></div>
                            <div class="col-6"><span class="text-muted">Principal Due:</span> <strong class="font-monospace">₹{{ number_format($account->principal_outstanding, 2) }}</strong></div>
                            <div class="col-6"><span class="text-muted">Interest Due:</span> <strong class="font-monospace">₹{{ number_format($account->interest_outstanding, 2) }}</strong></div>
                            <div class="col-6"><span class="text-muted">Penalties Due:</span> <strong class="font-monospace text-danger">₹{{ number_format($account->penalty_outstanding, 2) }}</strong></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Proposed Settlement Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="proposed_settlement_amount" id="ots_proposed_amount" class="form-control font-monospace fw-bold fs-5" placeholder="e.g. 15000.00" oninput="calculateOtsConcession()" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Proposal As-Of Date <span class="text-danger">*</span></label>
                            <input type="date" name="as_of_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <!-- Real-Time Concession Feedback -->
                    <div class="alert alert-warning py-2 mb-3 small" id="ots_feedback_box">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Calculated Concession / Haircut:</span>
                            <strong class="font-monospace fs-6" id="ots_concession_val">₹0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Approval Authority Required:</span>
                            <strong class="badge bg-dark" id="ots_authority_val">Branch Manager</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Valid Until Date</label>
                        <input type="date" name="valid_until_date" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Justification & Hardship Reason <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Explain borrower default circumstances, hardship proof, and management rationale for settlement..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-send-fill me-1"></i> Submit OTS Proposal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@can('loan_write_off.request')
<!-- Bad Debt Write-Off Modal -->
<div class="modal fade" id="writeOffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-settlement.request-write-off', $account->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to submit this unrecoverable loan for Bad Debt Write-Off?');">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-slash-circle-fill me-2"></i>Bad Debt Loan Write-Off</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i>
                        <strong>Loss Recognition:</strong> Writing off this loan recognizes ₹{{ number_format($account->principal_outstanding, 2) }} as bad debt expense in GL 5120 and closes the account.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Write-Off Date <span class="text-danger">*</span></label>
                        <input type="date" name="as_of_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Audit Justification & Recovery Efforts Summary <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Detail all legal notices, field recovery attempts, guarantor visits, and why this account is deemed 100% uncollectible..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-check-circle me-1"></i> Submit for Write-Off Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<script>
function loadForeclosureQuote() {
    const asOfDate = document.getElementById('fc_as_of_date').value;
    const url = `{{ route('admin.loan-settlement.quote', $account->id) }}?as_of_date=${asOfDate}&type=foreclosure`;

    fetch(url)
        .then(response => response.json())
        .then(res => {
            if (res.success && res.data) {
                const d = res.data;
                document.getElementById('fc_principal').innerText = '₹' + Number(d.principal_outstanding).toFixed(2);
                document.getElementById('fc_accrued_interest').innerText = '₹' + Number(d.accrued_interest).toFixed(2);
                document.getElementById('fc_rebate').innerText = '- ₹' + Number(d.unearned_interest_rebate).toFixed(2);
                document.getElementById('fc_fee').innerText = '₹' + Number(d.fee_outstanding).toFixed(2);
                document.getElementById('fc_penalty').innerText = '₹' + Number(d.penalty_outstanding).toFixed(2);
                document.getElementById('fc_foreclosure_fee').innerText = '₹' + Number(d.foreclosure_fee).toFixed(2);
                document.getElementById('fc_final_amount').innerText = '₹' + Number(d.final_settlement_amount).toFixed(2);

                const alertBox = document.getElementById('fc_lock_in_alert');
                const submitBtn = document.getElementById('fc_submit_btn');
                if (d.lock_in && !d.lock_in.is_allowed) {
                    alertBox.classList.remove('d-none');
                    alertBox.innerText = d.lock_in.message;
                    submitBtn.disabled = true;
                } else {
                    alertBox.classList.add('d-none');
                    submitBtn.disabled = false;
                }
            }
        })
        .catch(err => console.error('Quote load error:', err));
}

function calculateOtsConcession() {
    const totalDemand = {{ (float) $account->total_outstanding }};
    const proposed = parseFloat(document.getElementById('ots_proposed_amount').value) || 0;
    const concession = Math.max(0, totalDemand - proposed);

    document.getElementById('ots_concession_val').innerText = '₹' + concession.toFixed(2);

    let authority = 'Branch Manager';
    if (concession > 25000) {
        authority = 'Super Admin';
    } else if (concession > 5000) {
        authority = 'Company Admin';
    }
    document.getElementById('ots_authority_val').innerText = authority;
}
</script>
@endsection

