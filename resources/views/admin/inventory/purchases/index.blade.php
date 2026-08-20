@extends('layouts.admin')

@section('title', 'Product Purchase History - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-cart-check-fill text-primary me-2"></i>Product Procurement & Purchase History
        </h4>
        <p class="text-muted small mb-0">Record supplier purchases, track invoice payments, and receive stock into branch inventory.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-boxes me-1"></i> Branch Stock
        </a>
        @can('purchase.create')
            <a href="{{ route('admin.product-purchase.create') }}" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Create New Purchase
            </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.product-purchase.index') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Search Purchase</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="PUR #, Invoice, Supplier..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Destination Branch</label>
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Purchase Status</label>
            <select name="purchase_status" class="form-select">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($filters['purchase_status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="confirmed" {{ ($filters['purchase_status'] ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="received" {{ ($filters['purchase_status'] ?? '') === 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ ($filters['purchase_status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Payment Status</label>
            <select name="payment_status" class="form-select">
                <option value="">All Payments</option>
                <option value="unpaid" {{ ($filters['payment_status'] ?? '') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="partially_paid" {{ ($filters['payment_status'] ?? '') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                <option value="paid" {{ ($filters['payment_status'] ?? '') === 'paid' ? 'selected' : '' }}>Fully Paid</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Product</label>
            <select name="product_id" class="form-select">
                <option value="">All Products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter Purchases</button>
        </div>
    </form>
</x-ui.card>

<!-- Product Purchases Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0 table-responsive-cards">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Purchase # & Date</th>
            <th scope="col" class="py-3 px-3">Supplier & Invoice</th>
            <th scope="col" class="py-3 px-3">Branch Location</th>
            <th scope="col" class="py-3 px-3">Grand Total</th>
            <th scope="col" class="py-3 px-3">Paid / Due</th>
            <th scope="col" class="py-3 px-3">Purchase Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($purchases as $pur)
            <tr>
                <td class="px-3 py-3" data-label="Purchase Order">
                    <a href="{{ route('admin.product-purchase.show', $pur->id) }}" class="fw-bold font-monospace text-primary text-decoration-none hover-primary">{{ $pur->purchase_number }}</a>
                    <div class="small text-muted">{{ $pur->purchase_date ? $pur->purchase_date->format('d M Y') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small" data-label="Supplier & Inv">
                    @if($pur->supplier_id)
                        <a href="{{ route('admin.suppliers.show', $pur->supplier_id) }}" class="fw-bold text-dark text-decoration-none hover-primary"><i class="bi bi-truck me-1 text-primary"></i>{{ $pur->supplier_name }}</a>
                    @else
                        <div class="fw-bold text-dark"><i class="bi bi-truck me-1 text-muted"></i>{{ $pur->supplier_name }}</div>
                    @endif
                    <div class="text-muted font-monospace">Inv: {{ $pur->supplier_invoice_number ?? 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small fw-bold text-dark" data-label="Branch">
                    <i class="bi bi-geo-alt text-danger me-1"></i>{{ $pur->branch->name ?? 'N/A' }}
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark" data-label="Grand Total">
                    ₹{{ number_format($pur->grand_total, 2) }}
                </td>
                <td class="px-3 py-3 small font-monospace" data-label="Paid / Due">
                    <div class="text-success fw-bold">Paid: ₹{{ number_format($pur->paid_amount, 2) }}</div>
                    @if($pur->due_amount > 0)
                        <div class="text-danger fw-bold">Due: ₹{{ number_format($pur->due_amount, 2) }}</div>
                    @else
                        <div class="text-muted">Cleared</div>
                    @endif
                </td>
                <td class="px-3 py-3" data-label="Status">
                    @php
                        $badgeClass = match($pur->purchase_status) {
                            'draft' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            'confirmed' => 'bg-info-subtle text-info border-info-subtle',
                            'received' => 'bg-success-subtle text-success border-success-subtle',
                            'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border px-2.5 py-1 text-capitalize fw-bold">
                        {{ $pur->purchase_status }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end" data-label="Actions">
                    <a href="{{ route('admin.product-purchase.show', $pur->id) }}" class="btn btn-sm btn-outline-info w-100 w-md-auto fw-bold" title="View Purchase Details">
                        <i class="bi bi-eye me-1"></i> Details
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x fs-1 d-block text-secondary mb-2"></i>
                    No product purchases found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($purchases->hasPages())
        <div class="p-3 border-top">
            {{ $purchases->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
