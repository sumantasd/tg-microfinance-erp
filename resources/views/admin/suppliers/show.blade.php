@extends('layouts.admin')

@section('title', 'Supplier Profile - ' . $supplier->supplier_name)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-truck text-primary me-2"></i>{{ $supplier->supplier_name }}
            </h4>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1">
                {{ $supplier->supplier_code }}
            </span>
            @if($supplier->status === 'active')
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Inactive</span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            <i class="bi bi-building me-1"></i>{{ $supplier->company->name ?? 'Company' }} |
            <i class="bi bi-telephone me-1"></i>{{ $supplier->mobile }} |
            <i class="bi bi-tag me-1"></i>{{ ucfirst($supplier->supplier_type) }}
        </p>
    </div>

    <div class="d-flex gap-2 mt-3 mt-md-0">
        @can('supplier.payments')
            <button type="button" class="btn btn-success fw-bold shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                <i class="bi bi-currency-rupee me-1"></i> Record Payment
            </button>
        @endcan
        @can('purchase.create')
            <a href="{{ route('admin.product-purchase.create') }}?supplier_id={{ $supplier->id }}" class="btn btn-primary fw-bold shadow-sm rounded-pill px-3">
                <i class="bi bi-cart-plus me-1"></i> Create Purchase
            </a>
        @endcan
        @can('supplier.edit')
            <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-outline-warning rounded-pill px-3">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        @endcan
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Directory
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

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-primary border-4">
            <div class="text-muted small fw-bold text-uppercase">Total Invoiced Purchases</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹{{ number_format($supplier->total_purchase, 2) }}</div>
            <div class="small text-muted mt-1">{{ $supplier->purchase_count }} Purchase Orders</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-success border-4">
            <div class="text-muted small fw-bold text-uppercase">Total Payments Paid</div>
            <div class="fs-4 fw-bold text-success mt-1">₹{{ number_format($supplier->total_paid, 2) }}</div>
            <div class="small text-success mt-1"><i class="bi bi-check-circle me-1"></i>Disbursed</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-danger border-4">
            <div class="text-muted small fw-bold text-uppercase">Outstanding Payable</div>
            <div class="fs-4 fw-bold {{ $supplier->outstanding_payable > 0 ? 'text-danger' : 'text-success' }} mt-1">
                ₹{{ number_format($supplier->outstanding_payable, 2) }}
            </div>
            <div class="small text-muted mt-1">Current Net Due Balance</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-warning border-4">
            <div class="text-muted small fw-bold text-uppercase">Credit Limit & Terms</div>
            <div class="fs-5 fw-bold text-dark mt-1">₹{{ number_format($supplier->credit_limit, 2) }}</div>
            <div class="small text-muted mt-1">{{ $supplier->payment_terms ?: 'Standard Terms' }}</div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-0 px-3">
        <ul class="nav nav-tabs card-header-tabs nav-tabs-mobile border-bottom-0" id="supplierTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                    <i class="bi bi-person-badge me-1"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold {{ $activeTab === 'purchases' ? 'active' : '' }}" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab">
                    <i class="bi bi-cart-check me-1"></i> Purchases ({{ $supplier->purchases->count() }})
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold {{ $activeTab === 'payments' ? 'active' : '' }}" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                    <i class="bi bi-currency-rupee me-1"></i> Payments ({{ $supplier->payments->count() }})
                </button>
            </li>
            @can('supplier.ledger')
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold {{ $activeTab === 'ledger' ? 'active' : '' }}" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab">
                        <i class="bi bi-journal-text me-1"></i> Financial Ledger
                    </button>
                </li>
            @endcan
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold {{ $activeTab === 'inventory' ? 'active' : '' }}" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                    <i class="bi bi-boxes me-1"></i> Supplied Products
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <div class="tab-content" id="supplierTabsContent">

            <!-- TAB 1: OVERVIEW -->
            <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="overview" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border border-light-subtle rounded-3 p-3 h-100">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-building text-primary me-2"></i>Company & Contact Details</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th class="text-muted w-35">Supplier Code:</th><td class="font-monospace fw-bold text-primary">{{ $supplier->supplier_code }}</td></tr>
                                <tr><th class="text-muted">Supplier Name:</th><td class="fw-bold text-dark">{{ $supplier->supplier_name }}</td></tr>
                                <tr><th class="text-muted">Company Name:</th><td>{{ $supplier->company_name ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Supplier Type:</th><td><span class="badge bg-light text-dark border">{{ ucfirst($supplier->supplier_type) }}</span></td></tr>
                                <tr><th class="text-muted">Contact Person:</th><td class="fw-bold">{{ $supplier->contact_person ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Mobile:</th><td><i class="bi bi-telephone text-success me-1"></i>{{ $supplier->mobile }}</td></tr>
                                <tr><th class="text-muted">Alternate Mobile:</th><td>{{ $supplier->alternate_mobile ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Email Address:</th><td>{{ $supplier->email ?: 'N/A' }}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border border-light-subtle rounded-3 p-3 h-100">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-text text-success me-2"></i>Tax & Statutory Info</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th class="text-muted w-35">GSTIN Number:</th><td class="font-monospace fw-bold text-dark">{{ $supplier->gstin ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">PAN Number:</th><td class="font-monospace fw-bold text-dark">{{ $supplier->pan ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Payment Terms:</th><td>{{ $supplier->payment_terms ?: 'Standard' }}</td></tr>
                                <tr><th class="text-muted">Credit Limit:</th><td class="fw-bold text-dark">₹{{ number_format($supplier->credit_limit, 2) }}</td></tr>
                                <tr><th class="text-muted">Opening Balance:</th><td>₹{{ number_format($supplier->opening_balance, 2) }} ({{ ucfirst($supplier->opening_balance_type) }})</td></tr>
                            </table>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3"><i class="bi bi-bank text-info me-2"></i>Bank Account Information</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th class="text-muted w-35">Bank Name:</th><td class="fw-bold text-dark">{{ $supplier->bank_name ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Account Number:</th><td class="font-monospace fw-bold text-primary">{{ $supplier->account_number ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">IFSC Code:</th><td class="font-monospace">{{ $supplier->ifsc_code ?: 'N/A' }}</td></tr>
                                <tr><th class="text-muted">Branch Name:</th><td>{{ $supplier->branch_name ?: 'N/A' }}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card border border-light-subtle rounded-3 p-3">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-geo-alt text-danger me-2"></i>Address & Notes</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted small fw-bold">Street Address:</p>
                                    <p class="fw-bold text-dark mb-0">{{ $supplier->address ?: 'No address specified.' }}</p>
                                    <p class="small text-muted">{{ implode(', ', array_filter([$supplier->city, $supplier->state, $supplier->pincode, $supplier->country])) }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 text-muted small fw-bold">Internal Notes:</p>
                                    <p class="small text-secondary fst-italic mb-0">{{ $supplier->notes ?: 'No internal notes registered.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PURCHASES -->
            <div class="tab-pane fade {{ $activeTab === 'purchases' ? 'show active' : '' }}" id="purchases" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>Purchase #</th>
                                <th>Branch</th>
                                <th>Date</th>
                                <th>Grand Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Payment Status</th>
                                <th>Order Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplier->purchases as $p)
                                <tr>
                                    <td class="font-monospace fw-bold text-primary">
                                        <a href="{{ route('admin.product-purchase.show', $p->id) }}" class="text-decoration-none">
                                            {{ $p->purchase_number }}
                                        </a>
                                    </td>
                                    <td>{{ $p->branch->name ?? 'N/A' }}</td>
                                    <td>{{ $p->purchase_date ? $p->purchase_date->format('d M Y') : 'N/A' }}</td>
                                    <td class="fw-bold text-dark">₹{{ number_format($p->grand_total, 2) }}</td>
                                    <td class="text-success fw-bold">₹{{ number_format($p->paid_amount, 2) }}</td>
                                    <td class="text-danger fw-bold">₹{{ number_format($p->due_amount, 2) }}</td>
                                    <td>
                                        @if($p->payment_status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                        @elseif($p->payment_status === 'partially_paid')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partial</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unpaid</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->purchase_status === 'received')
                                            <span class="badge bg-success text-white">Received</span>
                                        @elseif($p->purchase_status === 'confirmed')
                                            <span class="badge bg-info text-white">Confirmed</span>
                                        @elseif($p->purchase_status === 'draft')
                                            <span class="badge bg-secondary text-white">Draft</span>
                                        @else
                                            <span class="badge bg-danger text-white">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.product-purchase.show', $p->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No purchase orders found for this supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: PAYMENTS -->
            <div class="tab-pane fade {{ $activeTab === 'payments' ? 'show active' : '' }}" id="payments" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>Payment #</th>
                                <th>Payment Date</th>
                                <th>Total Paid</th>
                                <th>Allocated</th>
                                <th>Unallocated</th>
                                <th>Method</th>
                                <th>Reference #</th>
                                <th>Allocation Details / Notes</th>
                                <th>Logged By</th>
                                <th class="text-center">Status & Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplier->payments as $pmt)
                                <tr>
                                    <td class="font-monospace fw-bold text-success">{{ $pmt->payment_number }}</td>
                                    <td>{{ $pmt->payment_date ? $pmt->payment_date->format('d M Y') : 'N/A' }}</td>
                                    <td class="fs-6 fw-bold text-success">₹{{ number_format($pmt->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fs-6">
                                            ₹{{ number_format($pmt->total_allocated, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($pmt->unallocated_amount > 0.001)
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace fs-6">
                                                ₹{{ number_format($pmt->unallocated_amount, 2) }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border font-monospace">₹0.00</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ strtoupper(str_replace('_', ' ', $pmt->payment_method)) }}</span></td>
                                    <td class="font-monospace small">{{ $pmt->reference_number ?: 'N/A' }}</td>
                                    <td class="small">
                                        @if($pmt->allocations->count() > 0)
                                            @foreach($pmt->allocations as $alloc)
                                                <div class="mb-0.5">
                                                    <span class="fw-bold text-primary font-monospace">{{ $alloc->purchase->purchase_number ?? 'PO' }}</span>:
                                                    <span class="text-dark fw-bold">₹{{ number_format($alloc->allocated_amount, 2) }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted fst-italic">{{ $pmt->notes ?: 'General Payment' }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $pmt->creator->name ?? 'System' }}</td>
                                    <td class="text-center">
                                        @if($pmt->unallocated_amount <= 0.001)
                                            <span class="badge bg-success text-white px-2.5 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Fully Allocated</span>
                                        @elseif($pmt->total_allocated > 0)
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-pie-chart-fill me-1"></i>Partially Allocated</span>
                                                <button type="button" class="btn btn-xs btn-outline-warning fw-bold btn-allocate-existing" 
                                                    data-payment-id="{{ $pmt->id }}" 
                                                    data-payment-number="{{ $pmt->payment_number }}"
                                                    data-total-amount="{{ $pmt->amount }}"
                                                    data-allocated-amount="{{ $pmt->total_allocated }}"
                                                    data-unallocated-amount="{{ $pmt->unallocated_amount }}"
                                                    data-bs-toggle="modal" data-bs-target="#allocateExistingPaymentModal">
                                                    <i class="bi bi-box-arrow-in-right me-1"></i>Allocate Remaining
                                                </button>
                                            </div>
                                        @else
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Unallocated</span>
                                                <button type="button" class="btn btn-xs btn-primary fw-bold btn-allocate-existing" 
                                                    data-payment-id="{{ $pmt->id }}" 
                                                    data-payment-number="{{ $pmt->payment_number }}"
                                                    data-total-amount="{{ $pmt->amount }}"
                                                    data-allocated-amount="{{ $pmt->total_allocated }}"
                                                    data-unallocated-amount="{{ $pmt->unallocated_amount }}"
                                                    data-bs-toggle="modal" data-bs-target="#allocateExistingPaymentModal">
                                                    <i class="bi bi-box-arrow-in-right me-1"></i>Allocate Payment
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">No payment records logged for this supplier.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: LEDGER -->
            @can('supplier.ledger')
                <div class="tab-pane fade {{ $activeTab === 'ledger' ? 'show active' : '' }}" id="ledger" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Supplier Running Ledger</h6>
                        <span class="badge bg-light text-dark border px-3 py-2 fs-6">
                            Closing Balance: <strong class="{{ ($ledgerData['closing_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">₹{{ number_format($ledgerData['closing_balance'] ?? 0, 2) }}</strong>
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th>Date</th>
                                    <th>Reference #</th>
                                    <th>Description / Transaction Type</th>
                                    <th class="text-end text-success">Debit (-)</th>
                                    <th class="text-end text-danger">Credit (+)</th>
                                    <th class="text-end fw-bold">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgerData['entries'] ?? [] as $entry)
                                    <tr>
                                        <td>{{ $entry['date'] }}</td>
                                        <td class="font-monospace fw-bold text-primary">{{ $entry['reference'] }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border me-2">{{ $entry['type'] }}</span>
                                            {{ $entry['description'] }}
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            {{ $entry['debit'] > 0 ? '₹' . number_format($entry['debit'], 2) : '-' }}
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            {{ $entry['credit'] > 0 ? '₹' . number_format($entry['credit'], 2) : '-' }}
                                        </td>
                                        <td class="text-end fw-bold {{ $entry['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                            ₹{{ number_format($entry['balance'], 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No ledger entries found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endcan

            <!-- TAB 5: INVENTORY -->
            <div class="tab-pane fade {{ $activeTab === 'inventory' ? 'show active' : '' }}" id="inventory" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Brand & Category</th>
                                <th class="text-center">Total Received Qty</th>
                                <th class="text-end">Avg Purchase Cost</th>
                                <th class="text-end">Total Spent</th>
                                <th>Last Purchased</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliedProducts as $item)
                                <tr>
                                    <td class="font-monospace fw-bold text-info">{{ $item->sku }}</td>
                                    <td class="fw-bold text-dark">{{ $item->name }}</td>
                                    <td class="small text-muted">{{ $item->brand }} | {{ $item->category }}</td>
                                    <td class="text-center fs-6 fw-bold text-primary">{{ $item->total_qty_purchased }} Units</td>
                                    <td class="text-end fw-bold">₹{{ number_format($item->avg_unit_cost, 2) }}</td>
                                    <td class="text-end text-dark fw-bold">₹{{ number_format($item->total_spent, 2) }}</td>
                                    <td class="small text-muted">{{ $item->last_purchased_at ? date('d M Y', strtotime($item->last_purchased_at)) : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No products recorded from this supplier yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Record Supplier Payment & Allocation -->
@can('supplier.payments')
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">
                    <i class="bi bi-currency-rupee me-1"></i> Record Supplier Payment & Invoice Allocation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.supplier-payments.store') }}" method="POST" id="supplierPaymentForm">
                @csrf
                <input type="hidden" name="supplier_id" value="{{ $supplier->id }}">

                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted text-uppercase fw-bold">Supplier Name</div>
                                <div class="fs-5 fw-bold text-dark">{{ $supplier->supplier_name }}</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted text-uppercase fw-bold">Total Outstanding Payable</div>
                                <div class="fs-4 fw-bold text-danger">₹{{ number_format($supplier->outstanding_payable, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="99999999" name="amount" id="totalPaymentAmount" class="form-control font-monospace fs-5 fw-bold" placeholder="Enter amount paid" value="{{ old('amount', $supplier->outstanding_payable > 0 ? $supplier->outstanding_payable : '') }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="bank">Bank Transfer / NEFT / RTGS</option>
                                <option value="upi">UPI / Digital Payment</option>
                                <option value="cheque">Cheque</option>
                                <option value="cash">Cash</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Paying Bank Account</label>
                            <select name="bank_account_id" class="form-select">
                                <option value="">-- Select Bank Account (Optional) --</option>
                                @foreach($bankAccounts as $ba)
                                    <option value="{{ $ba->id }}">{{ $ba->account_name }} - {{ $ba->bank_name }} ({{ $ba->account_number }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Branch Location</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- Select Branch (Optional) --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Transaction Reference / UTR #</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR98213712 / Cheque No">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Payment Mode & Allocation Strategy</label>
                        <div class="d-flex gap-4 p-2.5 bg-white border rounded">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="allocation_mode" id="allocAuto" value="auto" checked>
                                <label class="form-check-label fw-bold text-dark small" for="allocAuto">
                                    <i class="bi bi-lightning-charge text-warning me-1"></i> Auto-Allocate FIFO (Oldest Outstanding Purchases First)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="allocation_mode" id="allocManual" value="manual">
                                <label class="form-check-label fw-bold text-dark small" for="allocManual">
                                    <i class="bi bi-sliders me-1 text-primary"></i> Manually Allocate Amounts to Invoices
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Invoices Table -->
                    <div class="mb-3 border rounded p-3 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-receipt text-primary me-2"></i>Outstanding Purchases / Invoices</h6>
                            <div class="small fw-bold text-muted">
                                Total Allocated: <span id="summaryTotalAllocated" class="text-success font-monospace fw-bold">₹0.00</span> | 
                                Unallocated: <span id="summaryUnallocated" class="text-warning font-monospace fw-bold">₹0.00</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 bg-white border">
                                <thead class="table-light small text-uppercase text-muted">
                                    <tr>
                                        <th>Purchase #</th>
                                        <th>Date</th>
                                        <th class="text-end">Grand Total</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end text-danger">Due Amount</th>
                                        <th style="width: 180px;" class="text-end">Apply Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($outstandingPurchases as $idx => $op)
                                        <tr>
                                            <td class="font-monospace fw-bold text-primary">{{ $op->purchase_number }}</td>
                                            <td class="small">{{ $op->purchase_date ? $op->purchase_date->format('d M Y') : 'N/A' }}</td>
                                            <td class="text-end font-monospace">₹{{ number_format($op->grand_total, 2) }}</td>
                                            <td class="text-end font-monospace text-success">₹{{ number_format($op->paid_amount, 2) }}</td>
                                            <td class="text-end font-monospace text-danger fw-bold">₹{{ number_format($op->due_amount, 2) }}</td>
                                            <td>
                                                <input type="hidden" name="allocations[{{ $idx }}][purchase_id]" value="{{ $op->id }}">
                                                <input type="number" step="0.01" min="0" max="{{ $op->due_amount }}" name="allocations[{{ $idx }}][amount]" class="form-control form-control-sm text-end font-monospace fw-bold manual-alloc-input" data-due="{{ $op->due_amount }}" value="0.00" disabled>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-3 text-muted small">No outstanding purchases requiring payment allocation.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small">Payment Notes / Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment notes or instructions"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Modal: Allocate Existing Unallocated Payment -->
@can('supplier.payments')
<div class="modal fade" id="allocateExistingPaymentModal" tabindex="-1" aria-labelledby="allocateExistingPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="allocateExistingPaymentModalLabel">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Allocate Supplier Payment: <span id="modalPaymentNumberHead" class="font-monospace text-warning"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="allocateExistingPaymentForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                        <div class="row text-center g-2">
                            <div class="col-4">
                                <div class="small text-muted text-uppercase fw-bold">Total Payment</div>
                                <div class="fs-5 fw-bold text-dark font-monospace" id="modalTotalPmt">₹0.00</div>
                            </div>
                            <div class="col-4 border-start border-end">
                                <div class="small text-muted text-uppercase fw-bold">Already Allocated</div>
                                <div class="fs-5 fw-bold text-success font-monospace" id="modalAlreadyAlloc">₹0.00</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted text-uppercase fw-bold">Available to Allocate</div>
                                <div class="fs-4 fw-bold text-warning font-monospace" id="modalAvailableAlloc">₹0.00</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Allocation Strategy</label>
                        <div class="d-flex gap-4 p-2.5 bg-white border rounded">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="allocation_mode" id="existAllocAuto" value="auto" checked>
                                <label class="form-check-label fw-bold text-dark small" for="existAllocAuto">
                                    <i class="bi bi-lightning-charge text-warning me-1"></i> Auto-Allocate FIFO (Oldest Outstanding Purchases First)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="allocation_mode" id="existAllocManual" value="manual">
                                <label class="form-check-label fw-bold text-dark small" for="existAllocManual">
                                    <i class="bi bi-sliders me-1 text-primary"></i> Manually Allocate Amounts to Invoices
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Invoices Table -->
                    <div class="mb-3 border rounded p-3 bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-receipt text-primary me-2"></i>Select Outstanding Purchases to Apply Payment</h6>
                            <div class="small fw-bold text-muted">
                                Unallocated Balance Remaining: <span id="modalRemainingUnalloc" class="text-danger font-monospace fw-bold">₹0.00</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 bg-white border">
                                <thead class="table-light small text-uppercase text-muted">
                                    <tr>
                                        <th>Purchase #</th>
                                        <th>Date</th>
                                        <th class="text-end">Grand Total</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end text-danger">Due Amount</th>
                                        <th style="width: 180px;" class="text-end">Allocate Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($outstandingPurchases as $idx => $op)
                                        <tr>
                                            <td class="font-monospace fw-bold text-primary">{{ $op->purchase_number }}</td>
                                            <td class="small">{{ $op->purchase_date ? $op->purchase_date->format('d M Y') : 'N/A' }}</td>
                                            <td class="text-end font-monospace">₹{{ number_format($op->grand_total, 2) }}</td>
                                            <td class="text-end font-monospace text-success">₹{{ number_format($op->paid_amount, 2) }}</td>
                                            <td class="text-end font-monospace text-danger fw-bold">₹{{ number_format($op->due_amount, 2) }}</td>
                                            <td>
                                                <input type="hidden" name="allocations[{{ $idx }}][purchase_id]" value="{{ $op->id }}">
                                                <input type="number" step="0.01" min="0" max="{{ $op->due_amount }}" name="allocations[{{ $idx }}][amount]" class="form-control form-control-sm text-end font-monospace fw-bold existing-manual-alloc-input" data-due="{{ $op->due_amount }}" value="0.00" disabled>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-3 text-muted small">No outstanding purchases requiring payment allocation.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                        <i class="bi bi-check-circle me-1"></i> Save & Apply Allocation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalAmountInput = document.getElementById('totalPaymentAmount');
        const allocAutoRadio = document.getElementById('allocAuto');
        const allocManualRadio = document.getElementById('allocManual');
        const manualInputs = document.querySelectorAll('.manual-alloc-input');
        const summaryAllocated = document.getElementById('summaryTotalAllocated');
        const summaryUnallocated = document.getElementById('summaryUnallocated');

        function updateAllocationUI() {
            const totalPmt = parseFloat(totalAmountInput.value) || 0;
            const isManual = allocManualRadio.checked;

            let totalAllocated = 0;

            manualInputs.forEach(input => {
                input.disabled = !isManual;
                if (!isManual) {
                    input.value = '0.00';
                } else {
                    const val = parseFloat(input.value) || 0;
                    const maxDue = parseFloat(input.getAttribute('data-due')) || 0;
                    if (val > maxDue) {
                        input.value = maxDue.toFixed(2);
                    }
                    totalAllocated += parseFloat(input.value) || 0;
                }
            });

            if (!isManual) {
                summaryAllocated.textContent = '₹' + Math.min(totalPmt, {{ $supplier->outstanding_payable }}).toFixed(2);
                const unalloc = Math.max(0, totalPmt - {{ $supplier->outstanding_payable }});
                summaryUnallocated.textContent = '₹' + unalloc.toFixed(2);
            } else {
                summaryAllocated.textContent = '₹' + totalAllocated.toFixed(2);
                const unalloc = Math.max(0, totalPmt - totalAllocated);
                summaryUnallocated.textContent = '₹' + unalloc.toFixed(2);
            }
        }

        if (allocAutoRadio && allocManualRadio) {
            allocAutoRadio.addEventListener('change', updateAllocationUI);
            allocManualRadio.addEventListener('change', updateAllocationUI);
        }

        if (totalAmountInput) {
            totalAmountInput.addEventListener('input', updateAllocationUI);
        }

        manualInputs.forEach(input => {
            input.addEventListener('input', function () {
                const totalPmt = parseFloat(totalAmountInput.value) || 0;
                const maxDue = parseFloat(this.getAttribute('data-due')) || 0;
                let currentVal = parseFloat(this.value) || 0;

                if (currentVal > maxDue) {
                    this.value = maxDue.toFixed(2);
                    currentVal = maxDue;
                }

                updateAllocationUI();
            });
        });

        updateAllocationUI();

        // Existing Unallocated Payment Modal Logic
        const allocateBtns = document.querySelectorAll('.btn-allocate-existing');
        const modalForm = document.getElementById('allocateExistingPaymentForm');
        const modalPaymentHead = document.getElementById('modalPaymentNumberHead');
        const modalTotalPmt = document.getElementById('modalTotalPmt');
        const modalAlreadyAlloc = document.getElementById('modalAlreadyAlloc');
        const modalAvailableAlloc = document.getElementById('modalAvailableAlloc');
        const modalRemainingUnalloc = document.getElementById('modalRemainingUnalloc');
        const existAutoRadio = document.getElementById('existAllocAuto');
        const existManualRadio = document.getElementById('existAllocManual');
        const existManualInputs = document.querySelectorAll('.existing-manual-alloc-input');

        let currentAvailableUnalloc = 0;

        allocateBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const pmtId = this.getAttribute('data-payment-id');
                const pmtNum = this.getAttribute('data-payment-number');
                const totalPmt = parseFloat(this.getAttribute('data-total-amount')) || 0;
                const alreadyAlloc = parseFloat(this.getAttribute('data-allocated-amount')) || 0;
                currentAvailableUnalloc = parseFloat(this.getAttribute('data-unallocated-amount')) || 0;

                modalForm.action = `/admin/supplier-payments/${pmtId}/allocate`;
                modalPaymentHead.textContent = pmtNum;
                modalTotalPmt.textContent = '₹' + totalPmt.toFixed(2);
                modalAlreadyAlloc.textContent = '₹' + alreadyAlloc.toFixed(2);
                modalAvailableAlloc.textContent = '₹' + currentAvailableUnalloc.toFixed(2);

                existAutoRadio.checked = true;
                updateExistAllocUI();
            });
        });

        function updateExistAllocUI() {
            const isManual = existManualRadio.checked;
            let sumManual = 0;

            existManualInputs.forEach(input => {
                input.disabled = !isManual;
                if (!isManual) {
                    input.value = '0.00';
                } else {
                    const val = parseFloat(input.value) || 0;
                    const maxDue = parseFloat(input.getAttribute('data-due')) || 0;
                    if (val > maxDue) {
                        input.value = maxDue.toFixed(2);
                    }
                    sumManual += parseFloat(input.value) || 0;
                }
            });

            if (!isManual) {
                modalRemainingUnalloc.textContent = '₹' + currentAvailableUnalloc.toFixed(2);
            } else {
                const rem = Math.max(0, currentAvailableUnalloc - sumManual);
                modalRemainingUnalloc.textContent = '₹' + rem.toFixed(2);
            }
        }

        if (existAutoRadio && existManualRadio) {
            existAutoRadio.addEventListener('change', updateExistAllocUI);
            existManualRadio.addEventListener('change', updateExistAllocUI);
        }

        existManualInputs.forEach(input => {
            input.addEventListener('input', function () {
                const maxDue = parseFloat(this.getAttribute('data-due')) || 0;
                let val = parseFloat(this.value) || 0;
                if (val > maxDue) {
                    this.value = maxDue.toFixed(2);
                }
                updateExistAllocUI();
            });
        });
    });
</script>
@endpush

@endsection
