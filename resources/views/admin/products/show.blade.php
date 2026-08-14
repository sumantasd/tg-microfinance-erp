@extends('layouts.admin')

@section('title', 'Product Profile - ' . $product->name . ' - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $product->name }}</h4>
            <span class="badge bg-light text-secondary border font-monospace fs-6">{{ $product->sku }}</span>
            @if($product->is_active)
                <span class="badge bg-success text-white px-2.5 py-1">Active Product</span>
            @else
                <span class="badge bg-secondary text-white px-2.5 py-1">Inactive</span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            <i class="bi bi-tag text-info me-1"></i>{{ $product->brand ?? 'No Brand' }} {{ $product->model_number ? '('.$product->model_number.')' : '' }} | {{ $product->category ?? 'General' }}
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.product.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Catalog
        </a>
        @can('product.edit')
            <a href="{{ route('admin.product.edit', $product->id) }}" class="btn btn-primary rounded-pill px-3 fw-bold">
                <i class="bi bi-pencil me-1"></i> Edit Product
            </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle">
            <div class="small text-muted fw-bold uppercase">Selling Price / MRP</div>
            <div class="fs-3 fw-bold text-info mt-1 font-monospace">₹{{ number_format($product->unit_price, 2) }}</div>
            <div class="small text-muted">+ {{ $product->tax_percentage }}% GST</div>
        </x-ui.card>
    </div>

    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Cost Price</div>
            <div class="fs-3 fw-bold text-dark mt-1 font-monospace">₹{{ number_format($product->cost_price ?? 0, 2) }}</div>
            <div class="small text-muted">Purchase Cost</div>
        </x-ui.card>
    </div>

    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-muted fw-bold uppercase">Total Branch Stock</div>
            <div class="fs-3 fw-bold text-success mt-1"><i class="bi bi-boxes me-1"></i>{{ $product->stocks->sum('current_stock') }} Units</div>
            <div class="small text-muted">Across {{ $product->stocks->count() }} Branches</div>
        </x-ui.card>
    </div>
</div>

<!-- Stock Locations Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-building text-warning me-2"></i>Branch Stock Inventory</h5>
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Branch</th>
            <th scope="col" class="py-3 px-3">Current Stock</th>
            <th scope="col" class="py-3 px-3">Reserved</th>
            <th scope="col" class="py-3 px-3">Available</th>
            <th scope="col" class="py-3 px-3">Reorder Level</th>
            <th scope="col" class="py-3 px-3">Last Restocked</th>
        </x-slot:headers>

        @forelse($product->stocks as $stk)
            <tr>
                <td class="px-3 py-3 fw-bold text-dark">
                    <i class="bi bi-geo-alt text-danger me-1"></i>{{ $stk->branch->name ?? 'N/A' }} ({{ $stk->branch->code ?? '' }})
                </td>
                <td class="px-3 py-3 fw-bold text-dark fs-6">{{ $stk->current_stock }}</td>
                <td class="px-3 py-3 text-warning font-monospace">{{ $stk->reserved_stock }}</td>
                <td class="px-3 py-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-6">
                        {{ $stk->available_stock }} Units
                    </span>
                </td>
                <td class="px-3 py-3 text-muted">{{ $stk->reorder_level }}</td>
                <td class="px-3 py-3 small text-muted">
                    {{ $stk->last_restocked_at ? $stk->last_restocked_at->format('d M Y, h:i A') : 'N/A' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    No branch stock recorded for this product yet. Restock from Branch Inventory management.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
</x-ui.card>
@endsection
