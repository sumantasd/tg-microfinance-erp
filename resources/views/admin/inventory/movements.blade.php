@extends('layouts.admin')

@section('title', 'Generic Stock Movement Audit Ledger - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-clock-history text-info me-2"></i>Generic Stock Movement Audit Ledger
        </h4>
        <p class="text-muted small mb-0">Immutable transaction log tracking restocks, product loan issues, loan returns, sales, and stock adjustments.</p>
    </div>
    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Branch Stock
    </a>
</div>

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.inventory.movements') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Search Movement</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Code, Remarks, SKU..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Branch</label>
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Movement Type</label>
            <select name="movement_type" class="form-select">
                <option value="">All Movement Types</option>
                <option value="opening_stock" {{ ($filters['movement_type'] ?? '') === 'opening_stock' ? 'selected' : '' }}>Opening Stock</option>
                <option value="purchase_in" {{ ($filters['movement_type'] ?? '') === 'purchase_in' ? 'selected' : '' }}>Purchase / Restock In</option>
                <option value="product_loan_issue" {{ ($filters['movement_type'] ?? '') === 'product_loan_issue' ? 'selected' : '' }}>Product Loan Issue</option>
                <option value="product_loan_return" {{ ($filters['movement_type'] ?? '') === 'product_loan_return' ? 'selected' : '' }}>Product Loan Return (Reversal)</option>
                <option value="sales_issue" {{ ($filters['movement_type'] ?? '') === 'sales_issue' ? 'selected' : '' }}>Direct Sales Issue</option>
                <option value="sales_return" {{ ($filters['movement_type'] ?? '') === 'sales_return' ? 'selected' : '' }}>Sales Return</option>
                <option value="adjustment" {{ ($filters['movement_type'] ?? '') === 'adjustment' ? 'selected' : '' }}>Stock Adjustment</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Product</label>
            <select name="product_id" class="form-select">
                <option value="">All Products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.inventory.movements') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter Ledger</button>
        </div>
    </form>
</x-ui.card>

<!-- Stock Movement Audit Ledger Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Movement Code & Date</th>
            <th scope="col" class="py-3 px-3">Branch</th>
            <th scope="col" class="py-3 px-3">Product Name & SKU</th>
            <th scope="col" class="py-3 px-3">Movement Type</th>
            <th scope="col" class="py-3 px-3">Qty</th>
            <th scope="col" class="py-3 px-3">Stock Audit (Before ➔ After)</th>
            <th scope="col" class="py-3 px-3">Total Value</th>
            <th scope="col" class="py-3 px-3">Recorded By & Remarks</th>
        </x-slot:headers>

        @forelse($movements as $m)
            <tr>
                <td class="px-3 py-3">
                    <div class="fw-bold font-monospace text-primary small">{{ $m->movement_code }}</div>
                    <div class="small text-muted">{{ $m->created_at ? $m->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small fw-bold text-dark">
                    <i class="bi bi-geo-alt me-1 text-danger"></i>{{ $m->branch->name ?? 'N/A' }}
                </td>
                <td class="px-3 py-3">
                    <div class="fw-bold text-dark small">{{ $m->product->name ?? 'N/A' }}</div>
                    <div class="small font-monospace text-info">{{ $m->product->sku ?? '' }}</div>
                </td>
                <td class="px-3 py-3">
                    @php
                        $badgeClass = match($m->movement_type) {
                            'purchase_in', 'opening_stock', 'product_loan_return', 'sales_return' => 'bg-success-subtle text-success border-success-subtle',
                            'product_loan_issue', 'sales_issue' => 'bg-danger-subtle text-danger border-danger-subtle',
                            'adjustment' => 'bg-warning-subtle text-warning border-warning-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border px-2.5 py-1 text-capitalize fw-bold">
                        {{ str_replace('_', ' ', $m->movement_type) }}
                    </span>
                </td>
                <td class="px-3 py-3 fs-6 fw-bold">
                    @if($m->quantity > 0)
                        <span class="text-success">+{{ $m->quantity }}</span>
                    @else
                        <span class="text-danger">{{ $m->quantity }}</span>
                    @endif
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <span class="text-muted">{{ $m->stock_before }}</span> <i class="bi bi-arrow-right text-secondary mx-1"></i> <span class="fw-bold text-dark">{{ $m->stock_after }}</span>
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark">
                    ₹{{ number_format($m->total_value, 2) }}
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark"><i class="bi bi-person me-1 text-muted"></i>{{ $m->creator->name ?? 'System' }}</div>
                    <div class="text-muted text-truncate" style="max-width: 200px;">{{ $m->remarks ?? 'N/A' }}</div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-clock-history fs-1 d-block text-secondary mb-2"></i>
                    No inventory stock movements recorded yet.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($movements->hasPages())
        <div class="p-3 border-top">
            {{ $movements->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
