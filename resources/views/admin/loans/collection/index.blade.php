@extends('layouts.admin')

@section('title', 'EMI Collection & Repayments - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-cash-coin text-success me-2"></i>Daily EMI Collection & Field Repayments
        </h4>
        <p class="text-muted small mb-0">Search customer by mobile number, customer ID, or loan account to record instant EMI collections.</p>
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

<!-- Today's Collection Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-muted fw-bold uppercase">Today's Total Collection</div>
            <div class="fs-3 fw-bold text-success mt-1 font-monospace">₹{{ number_format($metrics['today_total'], 0) }}</div>
            <div class="small text-muted">{{ $metrics['today_customers_count'] }} Customers Collected</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle">
            <div class="small text-muted fw-bold uppercase">Cash / Digital Split</div>
            <div class="d-flex justify-content-between mt-1 font-monospace fw-bold">
                <span class="text-dark" title="Cash">Cash: ₹{{ number_format($metrics['today_cash'], 0) }}</span>
                <span class="text-primary" title="UPI">UPI: ₹{{ number_format($metrics['today_upi'], 0) }}</span>
            </div>
            <div class="small text-muted">Bank: ₹{{ number_format($metrics['today_bank'], 0) }}</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-muted fw-bold uppercase">Pending Today's EMIs</div>
            <div class="fs-3 fw-bold text-dark mt-1 font-monospace">{{ $metrics['pending_today_count'] }}</div>
            <div class="small text-muted">Due Date = Today</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-danger-subtle">
            <div class="small text-muted fw-bold uppercase">Total Overdue Portfolio</div>
            <div class="fs-3 fw-bold text-danger mt-1 font-monospace">₹{{ number_format($metrics['overdue_total'], 0) }}</div>
            <div class="small text-muted">Overdue Principal + Interest</div>
        </x-ui.card>
    </div>
</div>

<!-- Prominent Customer & Loan Search Card -->
<x-ui.card class="p-4 shadow-sm border-0 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.emi-collection.index') }}">
        <label class="form-label fw-bold text-dark fs-6 mb-2">
            <i class="bi bi-search text-primary me-1"></i> Quick Customer & Loan Search
        </label>
        <div class="input-group input-group-lg">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-person-bounding-box text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0 font-monospace" placeholder="Enter Mobile Number (e.g. 7029737769), Customer Code, Loan #, Group..." value="{{ $searchTerm }}" autofocus required>
            <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-search me-1"></i> Search Customer</button>
        </div>
        <div class="form-text mt-2 small text-muted">
            Search by Customer Mobile Number, Customer Code, Member ID, Customer Name, Loan Account Number, or Group Code.
        </div>
    </form>
</x-ui.card>

<!-- Search Results Section -->
@if($searchTerm !== '')
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-card-checklist text-primary me-2"></i>Search Results for "{{ $searchTerm }}"</h5>

        @forelse($searchResults as $cust)
            <x-ui.card class="shadow-sm border-0 mb-3 p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-3 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">{{ $cust->full_name }}</h5>
                        <p class="text-muted small mb-0">
                            Mobile: <strong class="text-dark font-monospace">{{ $cust->mobile_number }}</strong>
                            <span class="mx-2">|</span>
                            Code: <strong class="text-primary font-monospace">{{ $cust->customer_code }}</strong>
                            <span class="mx-2">|</span>
                            Branch: <strong>{{ $cust->branch->name ?? 'N/A' }}</strong>
                        </p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <span class="badge bg-primary-subtle text-primary border px-3 py-1.5 fs-6">{{ $cust->loanAccounts->count() }} Active Loan(s)</span>
                    </div>
                </div>

                <!-- Active Loans Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-uppercase">
                            <tr>
                                <th>Loan Number</th>
                                <th>Type</th>
                                <th>Sanctioned Principal</th>
                                <th>Total Outstanding</th>
                                <th>Current EMI</th>
                                <th>Next Due Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cust->loanAccounts as $acc)
                                @php
                                    $nextInst = $acc->installments->where('status', '!=', 'paid')->first();
                                    $currentEmi = $nextInst ? $nextInst->installment_amount : 0;
                                    $nextDueDate = $nextInst && $nextInst->due_date ? $nextInst->due_date->format('d M Y') : 'N/A';
                                @endphp
                                <tr>
                                    <td class="font-monospace fw-bold text-primary">{{ $acc->loan_number }}</td>
                                    <td><span class="badge bg-light text-dark border text-uppercase">{{ $acc->loan_type }} Loan</span></td>
                                    <td class="font-monospace fw-bold">₹{{ number_format($acc->sanctioned_amount, 0) }}</td>
                                    <td class="font-monospace fw-bold text-danger">₹{{ number_format($acc->total_outstanding, 0) }}</td>
                                    <td class="font-monospace fw-bold text-success">₹{{ number_format($currentEmi, 0) }}</td>
                                    <td class="text-muted">{{ $nextDueDate }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-success fw-bold px-3" data-bs-toggle="modal" data-bs-target="#collectModal{{ $acc->id }}">
                                            <i class="bi bi-cash-coin me-1"></i> Collect EMI
                                        </button>
                                    </td>
                                </tr>

                                <!-- Collect EMI Modal for this loan -->
                                <div class="modal fade" id="collectModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.loan-account.record-repayment', $acc->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold text-success"><i class="bi bi-cash-coin me-1"></i> Collect Loan EMI - {{ $acc->loan_number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="bg-light p-3 rounded border mb-3 small">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted">Customer:</span>
                                                            <strong class="text-dark">{{ $cust->full_name }} ({{ $cust->customer_code }})</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted">Current EMI Amount:</span>
                                                            <strong class="font-monospace text-success fs-6">₹{{ number_format($currentEmi, 0) }}</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted">Total Outstanding Balance:</span>
                                                            <strong class="font-monospace text-danger">₹{{ number_format($acc->total_outstanding, 0) }}</strong>
                                                        </div>
                                                    </div>

                                                    <!-- Quick Amount Selector Buttons -->
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small text-muted d-block">Quick Amount Presets</label>
                                                        <div class="btn-group w-100" role="group">
                                                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold" onclick="document.getElementById('amtInput{{ $acc->id }}').value = {{ round($currentEmi, 0) }};">Full EMI (₹{{ number_format($currentEmi, 0) }})</button>
                                                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="document.getElementById('amtInput{{ $acc->id }}').value = {{ round($acc->total_outstanding, 0) }};">Full Payoff (₹{{ number_format($acc->total_outstanding, 0) }})</button>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Payment Collection Amount (₹) <span class="text-danger">*</span></label>
                                                        <input type="number" step="1" name="amount" id="amtInput{{ $acc->id }}" class="form-control font-monospace fw-bold fs-5" value="{{ round($currentEmi, 0) }}" max="{{ round($acc->total_outstanding, 0) }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Collection Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                                                        <select name="payment_method" class="form-select" required>
                                                            <option value="cash" selected>Cash Collection</option>
                                                            <option value="upi">UPI / Digital Payment</option>
                                                            <option value="bank_transfer">Bank Transfer / NEFT</option>
                                                            <option value="cheque">Cheque</option>
                                                            <option value="card">Debit/Credit Card</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Receipt / Transaction Ref #</label>
                                                        <input type="text" name="reference_number" class="form-control font-monospace" placeholder="Optional transaction reference">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Schedule Prepayment Adjustment <span class="text-danger">*</span></label>
                                                        <select name="adjustment_mode" class="form-select" required>
                                                            <option value="reduce_tenure" selected>Reduce Loan Tenure (Keep EMI, reduce future installments)</option>
                                                            <option value="reduce_emi">Reduce EMI Amount (Keep tenure end date, reduce future EMI)</option>
                                                            <option value="none">Standard Waterfall Allocation</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Remarks</label>
                                                        <input type="text" name="remarks" class="form-control" placeholder="Field collector notes">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Confirm & Collect EMI</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">No active loan accounts for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @empty
            @if($searchGroupResults->isEmpty())
                <x-ui.card class="p-5 text-center text-muted border-0 shadow-sm mb-4">
                    <i class="bi bi-search fs-1 d-block text-secondary mb-2"></i>
                    <h6 class="fw-bold text-dark">No Customer or Group Found</h6>
                    <p class="small mb-0">No active customer or loan account matched search term "{{ $searchTerm }}".</p>
                </x-ui.card>
            @endif
        @endforelse

        <!-- Group Search Results -->
        @foreach($searchGroupResults as $grp)
            <x-ui.card class="shadow-sm border-0 mb-3 p-4 bg-light-subtle">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-people text-info me-2"></i>Group: {{ $grp->name }} ({{ $grp->group_code }})
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Group Member</th>
                                <th>Loan Account #</th>
                                <th>Financed Principal</th>
                                <th>Total Outstanding</th>
                                <th>Current EMI</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grp->members as $m)
                                @php $memberCust = $m->customer; @endphp
                                @if($memberCust)
                                    @foreach($memberCust->loanAccounts as $acc)
                                        @php
                                            $nextInst = $acc->installments->where('status', '!=', 'paid')->first();
                                            $currentEmi = $nextInst ? $nextInst->installment_amount : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $memberCust->full_name }}</div>
                                                <div class="text-muted font-monospace">{{ $memberCust->customer_code }}</div>
                                            </td>
                                            <td class="font-monospace fw-bold text-primary">{{ $acc->loan_number }}</td>
                                            <td class="font-monospace">₹{{ number_format($acc->sanctioned_amount, 0) }}</td>
                                            <td class="font-monospace fw-bold text-danger">₹{{ number_format($acc->total_outstanding, 0) }}</td>
                                            <td class="font-monospace fw-bold text-success">₹{{ number_format($currentEmi, 0) }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.loan-account.show', $acc->id) }}" class="btn btn-sm btn-outline-primary fw-bold">View Account</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endforeach
    </div>
@endif

<!-- Recent Collection History Section -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Collection Transaction Log & Receipts</h5>
    </div>

    <x-ui.data-table emptyMessage="No repayment collection records found.">
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Receipt #</th>
            <th scope="col" class="py-3 px-3">Collection Date</th>
            <th scope="col" class="py-3 px-3">Customer / Borrower</th>
            <th scope="col" class="py-3 px-3">Loan Account</th>
            <th scope="col" class="py-3 px-3">Amount Collected</th>
            <th scope="col" class="py-3 px-3">Method</th>
            <th scope="col" class="py-3 px-3">Collected By</th>
            <th scope="col" class="py-3 px-3 text-end">Action</th>
        </x-slot:headers>

        @forelse($history as $rcpt)
            <tr>
                <td class="px-3 py-3 font-monospace fw-bold text-primary">{{ $rcpt->receipt_number }}</td>
                <td class="px-3 py-3 small text-muted">{{ $rcpt->payment_date ? $rcpt->payment_date->format('d M Y') : 'N/A' }}</td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">{{ $rcpt->customer->full_name ?? $rcpt->loanAccount->customerGroup->name ?? 'N/A' }}</div>
                    <div class="text-muted font-monospace">{{ $rcpt->customer->mobile_number ?? '' }}</div>
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark">{{ $rcpt->loanAccount->loan_number ?? 'N/A' }}</td>
                <td class="px-3 py-3 font-monospace fw-bold text-success fs-6">₹{{ number_format($rcpt->amount, 0) }}</td>
                <td class="px-3 py-3 small"><span class="badge bg-light text-dark border text-uppercase">{{ $rcpt->payment_method }}</span></td>
                <td class="px-3 py-3 small text-muted">{{ $rcpt->receiver->name ?? 'System' }}</td>
                <td class="px-3 py-3 text-end">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.emi-collection.receipt', $rcpt->id) }}" target="_blank" class="btn btn-sm btn-outline-success fw-bold" title="A4 Print Receipt">
                            <i class="bi bi-printer me-1"></i> A4
                        </a>
                        <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $rcpt->id, 'width' => '80']) }}" target="_blank" class="btn btn-sm btn-outline-dark fw-bold" title="Thermal 80mm Print">
                            <i class="bi bi-receipt me-1"></i> 80mm
                        </a>
                        <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $rcpt->id, 'width' => '58']) }}" target="_blank" class="btn btn-sm btn-outline-dark fw-bold" title="Thermal 58mm Print">
                            58mm
                        </a>
                    </div>
                </td>
            </tr>
        @empty
        @endforelse
    </x-ui.data-table>

    @if($history->hasPages())
        <div class="mt-3">
            {{ $history->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
