@extends('layouts.admin')

@section('title', 'Product Catalog - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-box-seam-fill text-info me-2"></i>Product Catalog Master
        </h4>
        <p class="text-muted small mb-0">Manage products, SKUs, MRP pricing, tax rates, brand masters, and category classifications for Product Loans.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.product-brand.index') }}" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-tag-fill me-1"></i> Brand Master
        </a>
        <a href="{{ route('admin.product-category.index') }}" class="btn btn-outline-success rounded-pill px-3">
            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Category Master
        </a>
        @can('product.create')
            <a href="{{ route('admin.product.create') }}" class="btn btn-info text-white fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Add Product
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

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.product.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Product</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Product Name, SKU, Model..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Category</label>
            <select name="category_id" class="form-select">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Brand</label>
            <select name="brand_id" class="form-select">
                <option value="">All Brands</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ ($filters['brand_id'] ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="is_active" class="form-select">
                <option value="">All</option>
                <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.product.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Products Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">SKU & Product Name</th>
            <th scope="col" class="py-3 px-3">Brand & Category</th>
            <th scope="col" class="py-3 px-3">Unit Price (MRP)</th>
            <th scope="col" class="py-3 px-3">GST Tax %</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($products as $product)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.product.show', $product->id) }}" class="fw-bold text-dark text-decoration-none hover-primary fs-6">{{ $product->name }}</a>
                    <div class="small font-monospace text-info fw-bold">{{ $product->sku }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">
                        <i class="bi bi-tag text-primary me-1"></i>{{ $product->brandRel->name ?? $product->brand ?? 'N/A' }} 
                        {{ $product->model_number ? '('.$product->model_number.')' : '' }}
                    </div>
                    <div class="text-muted">
                        <i class="bi bi-grid-3x3-gap text-success me-1"></i>{{ $product->categoryRel->name ?? $product->category ?? 'General Product' }}
                    </div>
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark">
                    ₹{{ number_format($product->unit_price, 2) }}
                </td>
                <td class="px-3 py-3 small">
                    <span class="badge bg-light text-secondary border font-monospace">{{ $product->tax_percentage }}% GST</span>
                </td>
                <td class="px-3 py-3">
                    @if($product->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Inactive</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.product.show', $product->id) }}" class="btn btn-sm btn-outline-info" title="View Product">
                            <i class="bi bi-eye"></i> View
                        </a>
                        @can('product.edit')
                            <a href="{{ route('admin.product.edit', $product->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Product">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                        <form action="{{ route('admin.product.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete product \'{{ $product->name }}\'?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-box-seam fs-1 d-block text-secondary mb-2"></i>
                    No products found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($products->hasPages())
        <div class="p-3 border-top">
            {{ $products->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
