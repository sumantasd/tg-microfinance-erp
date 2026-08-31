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
                    <h3 class="fw-bold font-monospace mb-0">₹{{ number_format($cashBook->opening_balance, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
                <div class="card-body p-3">
                    <small class="text-white-50 font-monospace text-uppercase d-block mb-1">Total Cash Received</small>
                    <h3 class="fw-bold font-monospace mb-0">₹{{ number_format($cashBook->total_cash_received, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-danger text-white h-100">
                <div class="card-body p-3">
                    <small class="text-white-50 font-monospace text-uppercase d-block mb-1">Total Cash Payment</small>
                    <h3 class="fw-bold font-monospace mb-0">₹{{ number_format($cashBook->total_cash_payment, 2) }}</h3>
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
                    <h3 class="fw-bold font-monospace mb-0">₹{{ number_format($cashBook->closing_cash, 2) }}</h3>
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
                    @if($cashBook->isOpen())
                        <button type="button" class="btn btn-xs btn-success rounded-pill px-2.5 font-monospace fw-bold" onclick="openEntryModal('received')">
                            + Add Receipt
                        </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 font-monospace" style="font-size: 0.825rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 15%;">DATE</th>
                                <th>PARTICULARS</th>
                                <th style="width: 20%;">AMOUNT RS</th>
                                <th style="width: 18%;">PRODUCT AMT</th>
                                <th style="width: 18%;">BANK AMT</th>
                                @if($cashBook->isOpen()) <th style="width: 5%;"></th> @endif
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
                                    @if($cashBook->isOpen())
                                        <td class="text-center p-0">
                                            @if(!$entry->category_code || !in_array($entry->category_code, ['cash_opening_balance', 'weekly_collection', 'processing_fee', 'card_fee']))
                                                <form action="{{ route('admin.cash-book.destroy-entry', [$cashBook->id, $entry->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this particular item?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 m-0"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">No receipts recorded</td></tr>
                            @endforelse

                            <!-- TOTAL RECEIVED ROW -->
                            <tr class="table-success fw-bold">
                                <td colspan="2" class="text-uppercase text-end">TOTAL RECEIVED AMOUNT</td>
                                <td class="text-end text-success fs-6">₹{{ number_format($cashBook->total_cash_received, 2) }}</td>
                                <td class="text-end">₹{{ number_format($cashBook->total_product_received, 2) }}</td>
                                <td class="text-end text-primary">₹{{ number_format($cashBook->total_bank_received, 2) }}</td>
                                @if($cashBook->isOpen()) <td></td> @endif
                            </tr>

                            <!-- CASH IN HAND SUMMARY ROW -->
                            <tr class="table-dark text-white fw-bold">
                                <td colspan="2" class="text-uppercase text-end">CASH IN HAND (RECEIVED - PAYMENT)</td>
                                <td class="text-end text-warning fs-6">₹{{ number_format($cashBook->closing_cash, 2) }}</td>
                                <td colspan="2" class="text-center text-white-50 font-monospace small">Formula: Opening + Rec. - Pay</td>
                                @if($cashBook->isOpen()) <td></td> @endif
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
                    @if($cashBook->isOpen())
                        <button type="button" class="btn btn-xs btn-danger rounded-pill px-2.5 font-monospace fw-bold" onclick="openEntryModal('payment')">
                            + Add Payment
                        </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0 font-monospace" style="font-size: 0.825rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="width: 15%;">DATE</th>
                                <th>PARTICULARS</th>
                                <th style="width: 20%;">AMOUNT RS</th>
                                <th style="width: 18%;">PRODUCT AMT</th>
                                <th style="width: 18%;">BANK AMT</th>
                                @if($cashBook->isOpen()) <th style="width: 5%;"></th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashBook->paymentEntries as $entry)
                                <tr>
                                    <td class="text-center">{{ $entry->entry_date->format('d/m') }}</td>
                                    <td class="fw-semibold text-uppercase text-dark">{{ $entry->particulars }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $entry->cash_amount > 0 ? number_format($entry->cash_amount, 2) : '' }}
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $entry->product_amount > 0 ? number_format($entry->product_amount, 2) : '' }}
                                    </td>
                                    <td class="text-end text-primary">
                                        {{ $entry->bank_amount > 0 ? number_format($entry->bank_amount, 2) : '' }}
                                    </td>
                                    @if($cashBook->isOpen())
                                        <td class="text-center p-0">
                                            @if(!$entry->category_code || !in_array($entry->category_code, ['loan_disbursed', 'deposit_to_bank', 'management_expense']))
                                                <form action="{{ route('admin.cash-book.destroy-entry', [$cashBook->id, $entry->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment item?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 m-0"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">No payments recorded</td></tr>
                            @endforelse

                            <!-- TOTAL PAYMENT ROW -->
                            <tr class="table-danger fw-bold">
                                <td colspan="2" class="text-uppercase text-end">TOTAL PAYMENT RS.</td>
                                <td class="text-end text-danger fs-6">₹{{ number_format($cashBook->total_cash_payment, 2) }}</td>
                                <td class="text-end">₹{{ number_format($cashBook->total_product_payment, 2) }}</td>
                                <td class="text-end text-primary">₹{{ number_format($cashBook->total_bank_payment, 2) }}</td>
                                @if($cashBook->isOpen()) <td></td> @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- LOWER SECTION: CASH DENOMINATION (LEFT) & ONLINE COLLECTION DETAILS (RIGHT) -->
    <div class="row g-4 mb-4">
        <!-- LOWER LEFT: CASH DENOMINATION DETAILS -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold font-monospace mb-0 small text-uppercase">
                        <i class="bi bi-calculator me-1"></i>CASH DENOMINATION DETAILS (PHYSICAL COUNT)
                    </h6>
                    <span class="badge bg-light text-dark font-monospace">Physical Reconciliation</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('admin.cash-book.save-denominations', $cashBook->id) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle font-monospace mb-3" style="font-size: 0.85rem;">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width: 30%;">DENOMINATION</th>
                                        <th style="width: 35%;">QUANTITY (COUNT)</th>
                                        <th style="width: 35%;">AMOUNT (RS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $denomMap = $cashBook->denominations->keyBy('denomination'); @endphp
                                    @foreach([500, 200, 100, 50, 20, 10, 5, 2, 1] as $denom)
                                        @php $count = $denomMap[$denom]->count ?? 0; @endphp
                                        <tr>
                                            <td class="fw-bold text-center">₹{{ $denom }} X</td>
                                            <td>
                                                <input type="number" min="0" name="denominations[{{ $denom }}]" value="{{ $count }}" 
                                                    class="form-control form-control-sm text-center font-monospace denom-input {{ !$cashBook->isOpen() ? 'bg-light' : '' }}" 
                                                    data-denom="{{ $denom }}" oninput="calculateDenominations()" {{ !$cashBook->isOpen() ? 'readonly' : '' }}>
                                            </td>
                                            <td class="text-end fw-bold text-dark denom-total" id="denom-total-{{ $denom }}">
                                                ₹{{ number_format($denom * $count, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Denomination Summary Box -->
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small text-secondary">Physical Cash Total:</span>
                                <span class="fw-bold font-monospace fs-6" id="physical-cash-total">₹{{ number_format($cashBook->physical_cash, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small text-secondary">System Closing Cash:</span>
                                <span class="fw-bold font-monospace text-dark">₹{{ number_format($cashBook->closing_cash, 2) }}</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Cash Difference:</span>
                                <span class="fw-bold font-monospace fs-5 {{ $cashBook->cash_difference < 0 ? 'text-danger' : ($cashBook->cash_difference > 0 ? 'text-warning' : 'text-success') }}">
                                    ₹{{ number_format($cashBook->cash_difference, 2) }}
                                </span>
                            </div>
                        </div>

                        @if($cashBook->isOpen())
                            <div class="mt-3">
                                <button type="submit" class="btn btn-dark w-100 rounded-pill font-monospace fw-bold">
                                    <i class="bi bi-check2-circle me-1"></i> Update Denominations & Reconcile
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- LOWER RIGHT: ONLINE COLLECTION DETAILS -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold font-monospace mb-0 small text-uppercase">
                        <i class="bi bi-phone me-1"></i>ONLINE COLLECTION DETAILS
                    </h6>
                    @if($cashBook->isOpen())
                        <button type="button" class="btn btn-xs btn-light text-primary rounded-pill px-2.5 font-monospace fw-bold" data-bs-toggle="modal" data-bs-target="#onlineCollectionModal">
                            + Add Entry
                        </button>
                    @endif
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
                                    @if($cashBook->isOpen()) <th></th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashBook->onlineCollections as $oc)
                                    <tr>
                                        <td class="ps-3">{{ $oc->collection_date->format('d/m') }}</td>
                                        <td class="fw-bold text-dark">{{ $oc->customer_name }}</td>
                                        <td class="text-end fw-bold text-primary">₹{{ number_format($oc->amount, 2) }}</td>
                                        <td class="text-muted">{{ $oc->group_name ?? '-' }}</td>
                                        <td class="text-muted">{{ $oc->mobile_no ?? '-' }}</td>
                                        @if($cashBook->isOpen())
                                            <td class="text-end pe-2">
                                                <form action="{{ route('admin.cash-book.destroy-online-collection', [$cashBook->id, $oc->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove online collection record?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 m-0"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
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

<!-- MODAL: ADD PARTICULAR ENTRY ITEM -->
<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-header-title fw-bold font-heading text-dark mb-0">Add Register Particular</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.cash-book.store-entry', $cashBook->id) }}" method="POST">
                @csrf
                <input type="hidden" name="entry_type" id="modal_entry_type" value="received">
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Particulars Title / Category</label>
                        <input type="text" name="particulars" class="form-control bg-light border-0" placeholder="e.g. MISCELLANEOUS EXPENSE, OTHER RECEIPTS" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Cash Amount (Rs)</label>
                            <input type="number" step="0.01" min="0" name="cash_amount" class="form-control bg-light border-0" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Bank Amount (Rs)</label>
                            <input type="number" step="0.01" min="0" name="bank_amount" class="form-control bg-light border-0" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Product Amount (Rs)</label>
                        <input type="number" step="0.01" min="0" name="product_amount" class="form-control bg-light border-0" value="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control bg-light border-0" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Particular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: ADD ONLINE COLLECTION -->
<div class="modal fade" id="onlineCollectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold font-heading text-dark mb-0">Add Online Collection Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.cash-book.store-online-collection', $cashBook->id) }}" method="POST">
                @csrf
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Collection Date</label>
                        <input type="date" name="collection_date" value="{{ $cashBook->date->format('Y-m-d') }}" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control bg-light border-0" placeholder="e.g. Sabitri Giri" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Amount (Rs)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control bg-light border-0" placeholder="1390.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Group Name</label>
                            <input type="text" name="group_name" class="form-control bg-light border-0" placeholder="e.g. Sabitri Group">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Mobile No</label>
                            <input type="text" name="mobile_no" class="form-control bg-light border-0" placeholder="9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Payment Method</label>
                            <select name="payment_method" class="form-select bg-light border-0">
                                <option value="upi">UPI / GPay / PhonePe</option>
                                <option value="bank_transfer">Bank Transfer / NEFT</option>
                                <option value="qr">QR Code</option>
                                <option value="card">Debit/Credit Card</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Add Detail</button>
                </div>
            </form>
        </div>
    </div>
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
                        <div class="d-flex justify-content-between mb-1">
                            <span>System Closing Cash:</span> <strong>₹{{ number_format($cashBook->closing_cash, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Physical Cash Total:</span> <strong>₹{{ number_format($cashBook->physical_cash, 2) }}</strong>
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

<script>
function openEntryModal(type) {
    document.getElementById('modal_entry_type').value = type;
    const title = type === 'received' ? 'Add Received Particular' : 'Add Payment Particular';
    document.querySelector('#entryModal .modal-header-title').innerText = title;
    var modal = new bootstrap.Modal(document.getElementById('entryModal'));
    modal.show();
}

function calculateDenominations() {
    let physicalTotal = 0;
    document.querySelectorAll('.denom-input').forEach(input => {
        const denom = parseInt(input.dataset.denom);
        const count = parseInt(input.value) || 0;
        const total = denom * count;
        physicalTotal += total;
        
        const totalEl = document.getElementById('denom-total-' + denom);
        if (totalEl) {
            totalEl.innerText = '₹' + total.toFixed(2);
        }
    });

    const physicalCashEl = document.getElementById('physical-cash-total');
    if (physicalCashEl) {
        physicalCashEl.innerText = '₹' + physicalTotal.toFixed(2);
    }
}
</script>
@endsection
