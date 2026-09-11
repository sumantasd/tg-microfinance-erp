@extends('layouts.admin')

@section('title', 'Daily Cash Book Register - ' . $cashBook->date->format('d/m/Y'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Top Action Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.cash-book.index') }}" class="btn btn-sm btn-light rounded-circle"><i class="bi bi-arrow-left"></i></a>
                <h4 class="fw-bold mb-0 font-heading text-dark">
                    DAILY CASH BOOK REGISTER — <span class="text-primary">{{ strtoupper($cashBook->branch->name) }}</span>
                </h4>
                @if($cashBook->status === 'open')
                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">
                        <i class="bi bi-unlock-fill me-1"></i> REGISTER OPEN
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3">
                        <i class="bi bi-lock-fill me-1"></i> CLOSED & RECONCILED
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0 ms-4 mt-1">
                Date: <strong>{{ $cashBook->date->format('d/m/Y') }}</strong> | 
                Responsible Staff: <strong>{{ $cashBook->responsibleStaff->name ?? 'System Staff' }}</strong> | 
                Company: <strong>{{ $cashBook->company->name ?? 'Grihalaxmi Finance' }}</strong>
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($cashBook->isOpen())
                <form action="{{ route('admin.cash-book.sync-erp', $cashBook->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-success rounded-pill px-3 btn-sm fw-bold">
                        <i class="bi bi-arrow-repeat me-1"></i> Sync ERP
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.cash-book.print', $cashBook->id) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3 btn-sm font-monospace fw-bold">
                <i class="bi bi-printer me-1"></i> Print Register
            </a>

            <a href="{{ route('admin.cash-book.export', $cashBook->id) }}" class="btn btn-outline-dark rounded-pill px-3 btn-sm font-monospace fw-bold">
                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
            </a>

            @if($cashBook->isOpen())
                @can('cashbook.close')
                <button type="button" class="btn btn-danger rounded-pill px-3 btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#closeRegisterModal">
                    <i class="bi bi-lock-fill me-1"></i> Close Register
                </button>
                @endcan
            @else
                @can('cashbook.close')
                <button type="button" class="btn btn-warning rounded-pill px-3 btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#reopenRegisterModal">
                    <i class="bi bi-unlock-fill me-1"></i> Reopen Register
                </button>
                @endcan
            @endif
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

    <!-- Reconciliation Overview Banner -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100">
                <div class="card-body p-3">
                    <small class="text-white-50 font-monospace text-uppercase d-block mb-1">Opening Cash Balance</small>
                    <h3 class="fw-bold font-monospace mb-0 text-white">₹{{ number_format($cashBook->opening_balance, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
                <div class="card-body p-3">
                    <small class="text-white-50 font-monospace text-uppercase d-block mb-1">Total Cash Received</small>
                    <h3 class="fw-bold font-monospace mb-0 text-white">₹{{ number_format($cashBook->total_cash_received, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-danger text-white h-100">
                <div class="card-body p-3">
                    <small class="text-white-50 font-monospace text-uppercase d-block mb-1">Total Cash Payment</small>
                    <h3 class="fw-bold font-monospace mb-0 text-white">₹{{ number_format($cashBook->total_cash_payment, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-dark text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-white-50 font-monospace text-uppercase">System Closing Cash</small>
                        <span class="badge {{ $cashBook->reconciled_status === 'balanced' ? 'bg-success' : ($cashBook->reconciled_status === 'cash_short' ? 'bg-danger' : 'bg-warning') }} rounded-pill font-monospace" style="font-size: 0.65rem;">
                            {{ strtoupper(str_replace('_', ' ', $cashBook->reconciled_status)) }}
                        </span>
                    </div>
                    <h3 class="fw-bold font-monospace mb-0 text-white">₹{{ number_format($cashBook->closing_cash, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- PHYSICAL REGISTER LAYOUT (2-Column RECEIPT vs PAYMENT Grid) -->
    <div class="card border border-2 border-secondary-subtle shadow-sm rounded-3 overflow-hidden mb-4 bg-white">
        <!-- Physical Register Page Banner -->
        <div class="bg-light px-4 py-2 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="fw-bold font-monospace mb-0 text-dark tracking-wide">
                <i class="bi bi-book me-2"></i>PHYSICAL CASH BOOK REGISTER SHEET
            </h5>
            <span class="text-muted font-monospace small">Branch: {{ $cashBook->branch->name }} | Date: {{ $cashBook->date->format('d/m/Y') }}</span>
        </div>

        <div class="row g-0">
            <!-- LEFT COLUMN: RECEIVED SECTION -->
            <div class="col-lg-6 border-end border-2 border-secondary-subtle">
                <div class="p-3 bg-success bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-success font-monospace mb-0 tracking-wider text-uppercase">
                        <i class="bi bi-arrow-down-left-circle-fill me-2"></i>RECEIVED (RECEIPTS)
                    </h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 font-monospace">AUTOMATIC ERP</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 font-monospace" style="font-size: 0.825rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 15%;">DATE</th>
                                <th>PARTICULARS</th>
                                <th style="width: 22%;">AMOUNT RS</th>
                                <th style="width: 20%;">PRODUCT AMT</th>
                                <th style="width: 20%;">BANK AMT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashBook->receivedEntries as $entry)
                                <tr>
                                    <td class="text-center">{{ $entry->entry_date->format('d/m') }}</td>
                                    <td class="fw-semibold text-uppercase text-dark">{{ $entry->particulars }}</td>
                                    <td class="text-end fw-bold text-success">
                                        {{ $entry->cash_amount > 0 ? number_format($entry->cash_amount, 2) : '' }}
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $entry->product_amount > 0 ? number_format($entry->product_amount, 2) : '' }}
                                    </td>
                                    <td class="text-end text-primary">
                                        {{ $entry->bank_amount > 0 ? number_format($entry->bank_amount, 2) : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-3 text-muted">No receipts recorded</td></tr>
                            @endforelse

                            <!-- TOTAL RECEIVED ROW -->
                            <tr class="table-success fw-bold">
                                <td colspan="2" class="text-uppercase text-end">TOTAL RECEIVED AMOUNT</td>
                                <td class="text-end text-success fs-6">₹{{ number_format($cashBook->total_cash_received, 2) }}</td>
                                <td class="text-end">₹{{ number_format($cashBook->total_product_received, 2) }}</td>
                                <td class="text-end text-primary">₹{{ number_format($cashBook->total_bank_received, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT COLUMN: PAYMENT SECTION -->
            <div class="col-lg-6">
                <div class="p-3 bg-danger bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-danger font-monospace mb-0 tracking-wider text-uppercase">
                        <i class="bi bi-arrow-up-right-circle-fill me-2"></i>PAYMENT (EXPENSES & DISBURSEMENTS)
                    </h6>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 font-monospace">AUTOMATIC ERP</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 font-monospace" style="font-size: 0.825rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 15%;">DATE</th>
                                <th>PARTICULARS</th>
                                <th style="width: 22%;">AMOUNT RS</th>
                                <th style="width: 20%;">PRODUCT AMT</th>
                                <th style="width: 20%;">BANK AMT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashBook->paymentEntries as $entry)
                                <tr>
                                    <td class="text-center">{{ $entry->entry_date->format('d/m') }}</td>
                                    <td class="fw-semibold text-uppercase text-dark">{{ $entry->particulars }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $entry->cash_amount > 0 ? ($entry->category_code === 'member_no' ? number_format($entry->cash_amount, 0) : number_format($entry->cash_amount, 2)) : '' }}
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $entry->product_amount > 0 ? number_format($entry->product_amount, 2) : '' }}
                                    </td>
                                    <td class="text-end text-primary">
                                        {{ $entry->bank_amount > 0 ? number_format($entry->bank_amount, 2) : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-3 text-muted">No payments recorded</td></tr>
                            @endforelse

                            <!-- TOTAL PAYMENT ROW -->
                            <tr class="table-danger fw-bold">
                                <td colspan="2" class="text-uppercase text-end">TOTAL PAYMENT RS.</td>
                                <td class="text-end text-danger fs-6">₹{{ number_format($cashBook->total_cash_payment, 2) }}</td>
                                <td class="text-end">₹{{ number_format($cashBook->total_product_payment, 2) }}</td>
                                <td class="text-end text-primary">₹{{ number_format($cashBook->total_bank_payment, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- LOWER SECTION: ONLINE COLLECTION DETAILS (FULL WIDTH) -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold font-monospace mb-0 small text-uppercase">
                        <i class="bi bi-phone me-1"></i>ONLINE COLLECTION DETAILS
                    </h6>
                    <span class="badge bg-light text-primary font-monospace">AUTOMATIC ERP TRANSACTIONS</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.825rem;">
                            <thead class="bg-light text-uppercase text-muted">
                                <tr>
                                    <th class="ps-3">DATE</th>
                                    <th>CUSTOMER NAME</th>
                                    <th class="text-end">AMOUNT</th>
                                    <th>GROUP NAME</th>
                                    <th>MOBILE NO</th>
                                    <th>PAYMENT METHOD</th>
                                    <th>REFERENCE NO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashBook->onlineCollections as $oc)
                                    <tr>
                                        <td class="ps-3">{{ $oc->collection_date->format('d/m/Y') }}</td>
                                        <td class="fw-bold text-dark">{{ $oc->customer_name }}</td>
                                        <td class="text-end fw-bold text-primary">₹{{ number_format($oc->amount, 2) }}</td>
                                        <td class="text-muted">{{ $oc->group_name ?? '-' }}</td>
                                        <td class="text-muted">{{ $oc->mobile_no ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill text-uppercase">
                                                {{ str_replace('_', ' ', $oc->payment_method ?? 'online') }}
                                            </span>
                                        </td>
                                        <td class="text-muted font-monospace">{{ $oc->transaction_reference ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No online collection details recorded for this date.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light p-3 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-secondary">Total Online Collections:</span>
                        <span class="fw-bold font-monospace text-primary fs-6">
                            ₹{{ number_format($cashBook->onlineCollections->sum('amount'), 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AUDIT TRAIL LOG -->
    @if($cashBook->audits->count() > 0)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-light py-2 px-3">
                <h6 class="fw-bold font-monospace mb-0 small text-uppercase text-secondary">
                    <i class="bi bi-shield-check me-1"></i>SECURITY & CLOSING AUDIT TRAIL LOG
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size: 0.8rem;">
                        <thead class="bg-light text-muted">
                            <tr>
                                <th class="ps-3">TIMESTAMP</th>
                                <th>USER</th>
                                <th>ACTION</th>
                                <th>NOTES / REASON</th>
                                <th>IP ADDRESS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashBook->audits as $audit)
                                <tr>
                                    <td class="ps-3">{{ $audit->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="fw-bold text-dark">{{ $audit->user->name ?? 'System' }}</td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-dark border rounded-pill">
                                            {{ strtoupper($audit->action) }}
                                        </span>
                                    </td>
                                    <td class="text-muted">{{ $audit->notes ?? '-' }}</td>
                                    <td class="text-muted">{{ $audit->ip_address ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- MODAL: CLOSE REGISTER -->
<div class="modal fade" id="closeRegisterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold font-heading text-dark mb-0">Confirm Daily Cash Book Closing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.cash-book.close', $cashBook->id) }}" method="POST">
                @csrf
                <div class="modal-body pt-3">
                    <div class="alert alert-warning small rounded-3 mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Once closed, the register will be locked against unauthorized editing.
                    </div>
                    <div class="p-3 bg-light rounded-3 border mb-3 font-monospace small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Opening Cash:</span> <strong>₹{{ number_format($cashBook->opening_balance, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Received:</span> <strong class="text-success">₹{{ number_format($cashBook->total_cash_received, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Total Payment:</span> <strong class="text-danger">₹{{ number_format($cashBook->total_cash_payment, 2) }}</strong>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between">
                            <span>System Closing Cash:</span> <strong class="text-primary fs-6">₹{{ number_format($cashBook->closing_cash, 2) }}</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Closing Remarks / Reconciliation Notes</label>
                        <textarea name="remarks" class="form-control bg-light border-0" rows="3" placeholder="Enter any notes regarding cash status"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Close & Lock Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: REOPEN REGISTER -->
<div class="modal fade" id="reopenRegisterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold font-heading text-dark mb-0">Reopen Closed Cash Book Register</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.cash-book.reopen', $cashBook->id) }}" method="POST">
                @csrf
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Reason for Reopening</label>
                        <textarea name="reason" class="form-control bg-light border-0" rows="3" placeholder="Enter reason for reopening register" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Reopen Register</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
