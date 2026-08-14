@extends('layouts.admin')

@section('title', 'Branch Inventory Stock - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-boxes text-success me-2"></i>Branch Inventory Stock Management
        </h4>
        <p class="text-muted small mb-0">Monitor branch-wise physical stock levels, restock inventory, track purchases, and manage branch transfers.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        @can('purchase.view')
            <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                <i class="bi bi-cart-plus me-1"></i> Product Purchases
            </a>
        @endcan
        @can('inventory.transfer.view')
            <a href="{{ route('admin.inventory-transfer.index') }}" class="btn btn-outline-warning text-dark rounded-pill px-3 fw-bold">
                <i class="bi bi-arrow-left-right me-1"></i> Stock Transfers
            </a>
        @endcan
        <a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-info rounded-pill px-3 fw-bold">
            <i class="bi bi-clock-history me-1"></i> Movement Ledger
        </a>
        @can('inventory.manage')
            <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#restockModal">
                <i class="bi bi-plus-circle me-1"></i> Quick Restock
            </button>
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
    <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Product</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Product Name, SKU, Brand..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Branch Filter</label>
            <select name="branch_id" class="form-select">
                <option value="">All Accessible Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                @endforeach
            </select>
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

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter Inventory</button>
        </div>
    </form>
</x-ui.card>

<!-- Inventory Stocks Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Branch Location</th>
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
                <td class="px-3 py-3 fw-bold text-dark">
                    <i class="bi bi-geo-alt text-danger me-1"></i>{{ $stk->branch->name ?? 'N/A' }}
                    <div class="small text-muted font-monospace">{{ $stk->branch->code ?? '' }}</div>
                </td>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.product.show', $stk->product->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $stk->product->name }}</a>
                    <div class="small font-monospace text-info">{{ $stk->product->sku }}</div>
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
                    @can('inventory.adjust')
                        <button type="button" class="btn btn-sm btn-outline-warning btn-adjust-stock" 
                            data-branch="{{ $stk->branch_id }}" 
                            data-product="{{ $stk->product_id }}" 
                            data-current="{{ $stk->current_stock }}"
                            data-name="{{ $stk->product->name }}"
                            data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="bi bi-sliders"></i> Adjust
                        </button>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-boxes fs-1 d-block text-secondary mb-2"></i>
                    No branch inventory stock records found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($stocks->hasPages())
        <div class="p-3 border-top">
            {{ $stocks->links() }}
        </div>
    @endif
</x-ui.card>

<!-- Quick Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.inventory.restock') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>Quick Restock Branch Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Target Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Product Catalog Item <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku }}) - ₹{{ number_format($p->unit_price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Restock Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" placeholder="e.g. 50" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Unit Price / Purchase Cost (₹)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" placeholder="Optional cost override">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Restock Remarks / PO Reference</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Opening stock batch or direct supplier delivery">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Confirm Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.inventory.adjust') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-sliders text-warning me-2"></i>Stock Level Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="branch_id" id="adjustBranchId">
                    <input type="hidden" name="product_id" id="adjustProductId">

                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="small text-muted fw-bold">Target Product</div>
                        <div class="fw-bold text-dark fs-6" id="adjustProductName"></div>
                        <div class="small text-muted">Current System Stock: <span class="fw-bold text-primary" id="adjustCurrentStock"></span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">New Actual Physical Stock Count <span class="text-danger">*</span></label>
                        <input type="number" name="new_stock_level" class="form-control" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Adjustment Reason / Audit Explanation <span class="text-danger">*</span></label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Damaged unit write-off / Physical audit correction" required>
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
                document.getElementById('adjustBranchId').value = this.dataset.branch;
                document.getElementById('adjustProductId').value = this.dataset.product;
                document.getElementById('adjustProductName').textContent = this.dataset.name;
                document.getElementById('adjustCurrentStock').textContent = this.dataset.current + ' Units';
            });
        });
    });
</script>
@endpush
@endsection
