@extends('layouts.admin')

@section('title', 'Central Warehouse Stock Management - Grihalaxmi Finance ERP')

@section('content')

{{-- TOP HEADER & BREADCRUMBS --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}" class="text-decoration-none text-muted">Products & Inventory</a></li>
                <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">Central Warehouse</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-building text-primary"></i> {{ $centralWarehouse->name }}
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 align-middle">
                <i class="bi bi-shield-lock me-1"></i>{{ $centralWarehouse->code }}
            </span>
        </h4>
        <p class="text-muted small mb-0">Central receiving depot for supplier procurement and stock distribution to retail branches.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        @can('purchase.view')
            <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                <i class="bi bi-cart-plus me-1"></i> Product Purchases
            </a>
        @endcan
        @can('inventory.transfer.create')
            <a href="{{ route('admin.inventory-transfer.create', ['source_branch_id' => $centralWarehouse->id]) }}" class="btn btn-primary rounded-pill px-3 fw-bold shadow-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Transfer Stock to Branch
            </a>
        @endcan
        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-info rounded-pill px-3 fw-bold">
            <i class="bi bi-clock-history me-1"></i> Movement Ledger
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

{{-- SUMMARY METRIC CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <x-ui.card class="shadow-sm border-0 p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-bold">Total Products</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($statsData->total_products ?? 0) }}</div>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-3">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
        </x-ui.card>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <x-ui.card class="shadow-sm border-0 p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-bold">Available Stock Units</div>
                    <div class="fs-4 fw-bold text-success mt-1">{{ number_format($statsData->total_available_units ?? 0) }}</div>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-3">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </x-ui.card>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <x-ui.card class="shadow-sm border-0 p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-bold">Low Stock Items</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ number_format($statsData->low_stock_count ?? 0) }}</div>
                </div>
                <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
        </x-ui.card>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <x-ui.card class="shadow-sm border-0 p-3 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-bold">Out of Stock Items</div>
                    <div class="fs-4 fw-bold text-danger mt-1">{{ number_format($statsData->out_of_stock_count ?? 0) }}</div>
                </div>
                <div class="p-3 bg-danger-subtle text-danger rounded-3">
                    <i class="bi bi-x-circle fs-4"></i>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

{{-- FILTER CARD --}}
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.warehouse.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Product</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Product Name, SKU, Brand..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Product Catalog Filter</label>
            <select name="product_id" class="form-select">
                <option value="">All Products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Stock Status</label>
            <select name="stock_status" class="form-select">
                <option value="">All Stock Statuses</option>
                <option value="in_stock" {{ ($filters['stock_status'] ?? '') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ ($filters['stock_status'] ?? '') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ ($filters['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.warehouse.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset Filters</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Apply Filters</button>
        </div>
    </form>
</x-ui.card>

{{-- WAREHOUSE STOCK DIRECTORY TABLE --}}
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Product Name & SKU</th>
            <th scope="col" class="py-3 px-3">Current Stock</th>
            <th scope="col" class="py-3 px-3">Reserved</th>
            <th scope="col" class="py-3 px-3">Available</th>
            <th scope="col" class="py-3 px-3">Stock Status</th>
            <th scope="col" class="py-3 px-3">Last Restocked</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($stocks as $stk)
            <tr>
                <td class="px-3 py-3">
                    @if($stk->product)
                        <a href="{{ route('admin.product.show', $stk->product->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $stk->product->name }}</a>
                        <div class="small font-monospace text-info">{{ $stk->product->sku }}</div>
                    @else
                        <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Unknown Product (ID: {{ $stk->product_id }})</span>
                    @endif
                </td>
                <td class="px-3 py-3 fs-6 fw-bold text-dark">{{ $stk->current_stock }}</td>
                <td class="px-3 py-3 text-warning font-monospace">{{ $stk->reserved_stock }}</td>
                <td class="px-3 py-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-6">
                        {{ $stk->available_stock }} Units
                    </span>
                </td>
                <td class="px-3 py-3">
                    @if($stk->current_stock <= 0)
                        <span class="badge bg-danger text-white px-2.5 py-1"><i class="bi bi-x-circle me-1"></i>Out of Stock</span>
                    @elseif($stk->current_stock <= $stk->reorder_level)
                        <span class="badge bg-warning text-dark px-2.5 py-1"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock (<= {{ $stk->reorder_level }})</span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1"><i class="bi bi-check-circle me-1"></i>In Stock</span>
                    @endif
                </td>
                <td class="px-3 py-3 small text-muted">
                    {{ $stk->last_restocked_at ? $stk->last_restocked_at->format('d M Y, h:i A') : 'N/A' }}
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        @can('inventory.adjust')
                            <button type="button" class="btn btn-sm btn-outline-warning btn-adjust-stock" 
                                data-branch="{{ $stk->branch_id }}" 
                                data-product="{{ $stk->product_id }}" 
                                data-current="{{ $stk->current_stock }}"
                                data-name="{{ $stk->product->name ?? 'Unknown Product' }}"
                                data-bs-toggle="modal" data-bs-target="#adjustModal">
                                <i class="bi bi-sliders"></i> Adjust
                            </button>
                        @endcan
                        @can('inventory.transfer.create')
                            @if($stk->available_stock > 0)
                                <a href="{{ route('admin.inventory-transfer.create', ['source_branch_id' => $centralWarehouse->id, 'product_id' => $stk->product_id]) }}" class="btn btn-sm btn-outline-primary fw-bold" title="Transfer stock to branch">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Transfer
                                </a>
                            @endif
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-boxes fs-1 d-block text-secondary mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">No central warehouse stock records found.</h6>
                    <p class="small text-muted mb-0">No product stock records match the specified search or filter criteria in the Central Warehouse.</p>
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($stocks && $stocks->hasPages())
        <div class="p-3 border-top">
            {{ $stocks->appends(request()->query())->links() }}
        </div>
    @endif
</x-ui.card>

{{-- STOCK ADJUST MODAL --}}
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.warehouse.adjust') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-sliders text-warning me-2"></i>Warehouse Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="adjustProductId">

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="small text-muted fw-bold">Central Warehouse Target Product</div>
                        <div class="fw-bold text-dark fs-6" id="adjustProductName"></div>
                        <div class="small text-muted">Current System Stock: <span class="fw-bold text-primary" id="adjustCurrentStock"></span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">New Actual Physical Stock Count <span class="text-danger">*</span></label>
                        <input type="number" name="new_stock_level" class="form-control" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Adjustment Reason / Audit Explanation <span class="text-danger">*</span></label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Warehouse physical audit correction / Damaged goods write-off" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">Save Stock Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const adjustButtons = document.querySelectorAll('.btn-adjust-stock');
        adjustButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('adjustProductId').value = this.dataset.product;
                document.getElementById('adjustProductName').textContent = this.dataset.name;
                document.getElementById('adjustCurrentStock').textContent = this.dataset.current + ' Units';
            });
        });
    });
</script>
@endpush
@endsection
