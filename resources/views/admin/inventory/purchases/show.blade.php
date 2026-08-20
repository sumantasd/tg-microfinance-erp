@extends('layouts.admin')

@section('title', 'Purchase Details - ' . $purchase->purchase_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $purchase->purchase_number }}</h4>
            @php
                $badgeClass = match($purchase->purchase_status) {
                    'draft' => 'bg-secondary text-white',
                    'confirmed' => 'bg-info text-white',
                    'received' => 'bg-success text-white',
                    'cancelled' => 'bg-danger text-white',
                    default => 'bg-light text-dark'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ $purchase->purchase_status }}</span>
        </div>
        <p class="text-muted small mb-0">
            Supplier: <strong>{{ $purchase->supplier_name ?: ($purchase->supplier->supplier_name ?? 'N/A') }}</strong>
            <span class="mx-2">|</span>
            Invoice: <strong>{{ $purchase->supplier_invoice_number ?: 'N/A' }}</strong>
            <span class="mx-2">|</span>
            Branch: <strong>{{ $purchase->branch->name ?? ($purchase->company->name ?? 'N/A') }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchases
        </a>
        @if($purchase->purchase_status === 'draft')
            @can('purchase.edit')
                <a href="{{ route('admin.product-purchase.edit', $purchase->id) }}" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                    <i class="bi bi-pencil me-1"></i> Edit Draft
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

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle h-100">
            <div class="small text-muted fw-bold text-uppercase">Grand Total Amount</div>
            <div class="fs-3 fw-bold text-primary mt-1 font-monospace">₹{{ number_format($purchase->grand_total, 2) }}</div>
            <div class="small text-muted">Subtotal: ₹{{ number_format($purchase->subtotal, 2) }} + Tax: ₹{{ number_format($purchase->tax_amount, 2) }}</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle h-100">
            <div class="small text-muted fw-bold text-uppercase">Paid Amount</div>
            <div class="fs-3 fw-bold text-success mt-1 font-monospace">₹{{ number_format($purchase->paid_amount, 2) }}</div>
            <div class="small text-success text-capitalize fw-bold"><i class="bi bi-info-circle me-1"></i>{{ str_replace('_', ' ', $purchase->payment_status) }}</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-danger-subtle h-100">
            <div class="small text-muted fw-bold text-uppercase">Due Amount</div>
            <div class="fs-3 fw-bold text-danger mt-1 font-monospace">₹{{ number_format($purchase->due_amount, 2) }}</div>
            <div class="small text-muted">Outstanding Payable</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light h-100">
            <div class="small text-muted fw-bold text-uppercase">Inventory Integration Status</div>
            <div class="fs-5 fw-bold text-dark mt-1">
                @if($purchase->is_inventory_processed || $purchase->purchase_status === 'received')
                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Stock Updated</span>
                @else
                    <span class="text-warning"><i class="bi bi-clock me-1"></i>Stock Pending</span>
                @endif
            </div>
            <div class="small text-muted">
                @if($purchase->received_at)
                    Stock Updated {{ $purchase->received_at->format('d M Y, h:i A') }}
                @else
                    Not added to physical stock yet
                @endif
            </div>
        </x-ui.card>
    </div>
</div>

<!-- Workflow Action Bar -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-bold text-dark"><i class="bi bi-gear-fill me-1 text-primary"></i>Available Actions:</div>
        <div class="d-flex flex-wrap gap-2">
            @if($purchase->purchase_status === 'draft')
                <form action="{{ route('admin.product-purchase.confirm', $purchase->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold"><i class="bi bi-check-lg me-1"></i> Approve & Confirm Purchase Order</button>
                </form>
            @endif

            @if(in_array($purchase->purchase_status, ['draft', 'confirmed']))
                @can('purchase.receive')
                    <form action="{{ route('admin.product-purchase.receive', $purchase->id) }}" method="POST" onsubmit="return confirm('Receiving purchase will add product quantities into branch inventory stock. Confirm receipt?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Receive Goods into Branch Inventory</button>
                    </form>
                @endcan

                @can('purchase.cancel')
                    <form action="{{ route('admin.product-purchase.cancel', $purchase->id) }}" method="POST" onsubmit="return confirm('Cancel this purchase order?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Cancel Purchase</button>
                    </form>
                @endcan
            @endif

            @if(in_array($purchase->purchase_status, ['confirmed', 'received']) && $purchase->due_amount > 0 && $purchase->supplier_id)
                @can('supplier.payments')
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                        <i class="bi bi-currency-rupee me-1"></i> Record Supplier Payment
                    </button>
                @endcan
            @endif

            @if($purchase->supplier_id)
                <a href="{{ route('admin.suppliers.show', $purchase->supplier_id) }}?tab=ledger" class="btn btn-sm btn-outline-secondary fw-bold">
                    <i class="bi bi-journal-text me-1"></i> View Supplier Ledger
                </a>
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Purchase Line Items Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-list-check text-primary me-2"></i>Purchased Line Items</h5>
    <div class="table-responsive rounded-3 border bg-white">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-muted" style="font-size: 0.75rem;">
                <tr>
                    <th scope="col" class="py-3 px-3">Product Name</th>
                    <th scope="col" class="py-3 px-3">SKU</th>
                    <th scope="col" class="py-3 px-3">Quantity</th>
                    <th scope="col" class="py-3 px-3">Unit Purchase Cost</th>
                    <th scope="col" class="py-3 px-3">GST Tax %</th>
                    <th scope="col" class="py-3 px-3 text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->items as $item)
                    <tr>
                        <td class="px-3 py-3 fw-bold text-dark">{{ $item->product_name_snapshot }}</td>
                        <td class="px-3 py-3 font-monospace text-info small">{{ $item->product_sku_snapshot }}</td>
                        <td class="px-3 py-3 fs-6 fw-bold text-dark">{{ $item->quantity }} Units</td>
                        <td class="px-3 py-3 font-monospace small">₹{{ number_format($item->unit_purchase_cost, 2) }}</td>
                        <td class="px-3 py-3 small"><span class="badge bg-light text-secondary border">{{ $item->tax_rate }}% GST</span></td>
                        <td class="px-3 py-3 text-end font-monospace fw-bold text-dark">₹{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1 opacity-50"></i>
                            No line items found for this purchase order.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<!-- Audit Timeline & Details -->
<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary me-2"></i>Purchase Details & Audit Log</h5>
    <div class="row g-3 small">
        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Created By</label>
            <div>{{ $purchase->creator->name ?? 'System' }}</div>
            <div class="text-muted">{{ $purchase->created_at ? $purchase->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Received By</label>
            <div>{{ $purchase->receiver->name ?? 'Pending Receipt' }}</div>
            <div class="text-muted">{{ $purchase->received_at ? $purchase->received_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Payment Method</label>
            <div class="text-uppercase fw-bold text-dark">{{ str_replace('_', ' ', $purchase->payment_method ?? 'N/A') }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Supplier Reference</label>
            <div>{{ $purchase->supplier_reference ?? 'N/A' }}</div>
        </div>

        @if($purchase->remarks)
            <div class="col-12 mt-2">
                <label class="text-muted fw-bold d-block">Remarks</label>
                <div class="p-2 bg-light rounded border">{{ $purchase->remarks }}</div>
            </div>
        @endif
    </div>
</x-ui.card>

<!-- Payment History & Allocations Card -->
<x-ui.card class="p-4 shadow-sm border-0 mb-4 bg-white">
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-success"></i>Payment History & Allocations</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0 border">
            <thead class="bg-light small text-uppercase text-muted">
                <tr>
                    <th>Date</th>
                    <th>Payment #</th>
                    <th>Method</th>
                    <th>Reference / UTR #</th>
                    <th class="text-end">Allocated Amount (₹)</th>
                    <th>Logged By</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $allocations = $purchase->paymentAllocations()->with('payment.creator')->get();
                    $directPayments = \App\Models\SupplierPayment::where('purchase_id', $purchase->id)->whereDoesntHave('allocations')->with('creator')->get();
                @endphp
                @forelse($allocations as $alloc)
                    <tr>
                        <td class="small">{{ $alloc->payment->payment_date ? $alloc->payment->payment_date->format('d M Y') : 'N/A' }}</td>
                        <td class="font-monospace fw-bold text-primary">{{ $alloc->payment->payment_number ?? 'PAY' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ strtoupper(str_replace('_', ' ', $alloc->payment->payment_method ?? 'bank')) }}</span></td>
                        <td class="font-monospace small">{{ $alloc->payment->reference_number ?: 'N/A' }}</td>
                        <td class="text-end font-monospace fw-bold text-success">₹{{ number_format($alloc->allocated_amount, 2) }}</td>
                        <td class="small text-muted">{{ $alloc->payment->creator->name ?? 'System' }}</td>
                    </tr>
                @empty
                    @forelse($directPayments as $dp)
                        <tr>
                            <td class="small">{{ $dp->payment_date ? $dp->payment_date->format('d M Y') : 'N/A' }}</td>
                            <td class="font-monospace fw-bold text-primary">{{ $dp->payment_number }}</td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper(str_replace('_', ' ', $dp->payment_method)) }}</span></td>
                            <td class="font-monospace small">{{ $dp->reference_number ?: 'N/A' }}</td>
                            <td class="text-end font-monospace fw-bold text-success">₹{{ number_format($dp->amount, 2) }}</td>
                            <td class="small text-muted">{{ $dp->creator->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-3 text-muted small">No payment allocation history found for this purchase order.</td>
                        </tr>
                    @endforelse
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

@if($purchase->supplier_id)
    <!-- Record Payment Modal -->
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">
                        <i class="bi bi-currency-rupee me-1"></i> Record Supplier Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.supplier-payments.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="supplier_id" value="{{ $purchase->supplier_id }}">
                    <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
                    <input type="hidden" name="branch_id" value="{{ $purchase->branch_id }}">

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Supplier</label>
                            <input type="text" class="form-control" value="{{ $purchase->supplier_name ?: ($purchase->supplier->supplier_name ?? 'N/A') }}" readonly>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Purchase Number</label>
                                <input type="text" class="form-control font-monospace" value="{{ $purchase->purchase_number }}" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Outstanding Due</label>
                                <input type="text" class="form-control font-monospace text-danger fw-bold" value="₹{{ number_format($purchase->due_amount, 2) }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control font-monospace fw-bold" value="{{ $purchase->due_amount }}" max="{{ $purchase->due_amount }}" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="bank">Bank Transfer / NEFT</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="cash">Cash</option>
                                    <option value="upi">UPI / Digital</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Reference / UTR / Cheque #</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR98213712">
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-bold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4"><i class="bi bi-save me-1"></i> Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
