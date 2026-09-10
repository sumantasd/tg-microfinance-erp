@extends('layouts.admin')

@section('title', isset($selectedBranch) && $selectedBranch ? $selectedBranch->name . ' Inventory - Grihalaxmi Finance ERP' : 'Branch Inventory Stock Management - Grihalaxmi Finance ERP')

@section('content')

{{-- TOP HEADER & BREADCRUMBS --}}
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        @if(isset($selectedBranch) && $selectedBranch)
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}" class="text-decoration-none text-muted">Products & Inventory</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.index') }}" class="text-decoration-none text-muted">Branch Inventory</a></li>
                    <li class="breadcrumb-item active fw-bold text-primary" aria-current="page">{{ $selectedBranch->name }}</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary rounded-circle me-1" title="Back to All Branches">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <i class="bi bi-geo-alt-fill text-danger"></i> {{ $selectedBranch->name }}
                <span class="badge bg-secondary font-monospace fs-6 align-middle">{{ $selectedBranch->code }}</span>
            </h4>
            <p class="text-muted small mb-0">Viewing physical stock levels and inventory records for {{ $selectedBranch->name }}.</p>
        @else
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-boxes text-success me-2"></i>Branch Inventory Stock Management
            </h4>
            <p class="text-muted small mb-0">Select an authorized branch location to view its product stock, restock inventory, and manage adjustments.</p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
        @if(isset($selectedBranch) && $selectedBranch)
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold">
                <i class="bi bi-arrow-left me-1"></i> All Branches
            </a>
        @endif
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

@if(!isset($selectedBranch) || !$selectedBranch)
    {{-- STEP 1: BRANCH LIST VIEW --}}
    <div class="row g-4 mb-4">
        @forelse($branches as $b)
            @php
                $stats = isset($branchStats) ? $branchStats->get($b->id) : null;
                $productsCount = $stats ? (int) $stats->products_count : 0;
                $availableUnits = $stats ? (int) $stats->total_available_units : 0;
                $outOfStockCount = $stats ? (int) $stats->out_of_stock_count : 0;
                $lowStockCount = $stats ? (int) $stats->low_stock_count : 0;
            @endphp
            <div class="col-12 col-md-6 col-lg-4">
                <x-ui.card class="h-100 shadow-sm border-0 hover-shadow transition-all">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 mb-2 font-monospace">
                                <i class="bi bi-geo-alt me-1"></i>{{ $b->code }}
                            </span>
                            <h5 class="fw-bold text-dark mb-0">{{ $b->name }}</h5>
                            @if($b->city || $b->state)
                                <small class="text-muted"><i class="bi bi-building me-1"></i>{{ implode(', ', array_filter([$b->city, $b->state])) }}</small>
                            @endif
                        </div>
                        <div class="p-2 bg-light rounded-3 text-primary">
                            <i class="bi bi-shop fs-4"></i>
                        </div>
                    </div>

                    <div class="row g-2 text-center my-3 p-3 bg-light rounded-3 border">
                        <div class="col-6 border-end">
                            <div class="small text-muted fw-bold">Products in Stock</div>
                            <div class="fs-4 fw-bold text-dark">{{ $productsCount }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted fw-bold">Available Units</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($availableUnits) }}</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @if($outOfStockCount > 0)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-x-circle me-1"></i>{{ $outOfStockCount }} Out of Stock
                            </span>
                        @endif
                        @if($lowStockCount > 0)
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>{{ $lowStockCount }} Low Stock
                            </span>
                        @endif
                        @if($outOfStockCount == 0 && $lowStockCount == 0)
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                <i class="bi bi-check-circle me-1"></i>Healthy Stock
                            </span>
                        @endif
                    </div>

                    <a href="{{ route('admin.inventory.index', ['branch_id' => $b->id]) }}" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm py-2">
                        View Inventory <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12">
                <x-ui.card class="shadow-sm border-0 text-center py-5">
                    <i class="bi bi-building-slash fs-1 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-bold text-dark mb-1">No Accessible Branches Found</h5>
                    <p class="text-muted mb-0">You do not have access to any active branch locations or no active branches exist.</p>
                </x-ui.card>
            </div>
        @endforelse
    </div>
@else
    {{-- STEP 2: SELECTED BRANCH INVENTORY VIEW --}}

    <!-- Filter Card -->
    <x-ui.card class="mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Branch Context</label>
                <select name="branch_id" class="form-select fw-bold border-primary" onchange="this.form.submit()">
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $selectedBranch->id == $b->id ? 'selected' : '' }}>
                            {{ $b->name }} ({{ $b->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search Product</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Product Name, SKU, Brand..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Product Catalog Filter</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ ($filters['product_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Stock Status</label>
                <select name="stock_status" class="form-select">
                    <option value="">All Stock Statuses</option>
                    <option value="in_stock" {{ ($filters['stock_status'] ?? '') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low_stock" {{ ($filters['stock_status'] ?? '') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ ($filters['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>

            <div class="col-12 d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Back to Branch List
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.inventory.index', ['branch_id' => $selectedBranch->id]) }}" class="btn btn-light border text-secondary fw-bold px-3">Reset Filters</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Apply Filters</button>
                </div>
            </div>
        </form>
    </x-ui.card>

    <!-- Branch Inventory Table -->
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
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-boxes fs-1 d-block text-secondary mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">No inventory found for {{ $selectedBranch->name }}.</h6>
                        <p class="small text-muted mb-0">No product stock records match the specified search or filter criteria in this branch.</p>
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
@endif

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
