@extends('layouts.admin')

@section('title', 'Product Brand Master - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-tag-fill text-primary me-2"></i>Product Brand Master
        </h4>
        <p class="text-muted small mb-0">Manage manufacturer brands and brands catalog for product loans and retail items.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="{{ route('admin.product.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Product Catalog
        </a>
        <a href="{{ route('admin.product-brand.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Add Brand
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

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.product-brand.index') }}" class="row g-3">
        <div class="col-md-6">
            <label class="form-label small fw-bold text-muted">Search Brand</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Brand name, code, description..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="is_active" class="form-select">
                <option value="">All Statuses</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <a href="{{ route('admin.product-brand.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4 flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Brands Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Brand Name</th>
            <th scope="col" class="py-3 px-3">Brand Code</th>
            <th scope="col" class="py-3 px-3">Associated Products</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($brands as $brand)
            <tr>
                <td class="px-3 py-3">
                    <span class="fw-bold text-dark fs-6">{{ $brand->name }}</span>
                    @if($brand->description)
                        <div class="small text-muted text-truncate" style="max-width: 350px;">{{ $brand->description }}</div>
                    @endif
                </td>
                <td class="px-3 py-3 small font-monospace text-secondary">
                    {{ $brand->code ?? '—' }}
                </td>
                <td class="px-3 py-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">
                        <i class="bi bi-box-seam me-1"></i>{{ $brand->products_count }} Products
                    </span>
                </td>
                <td class="px-3 py-3">
                    @if($brand->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Inactive</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.product-brand.edit', $brand->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Brand">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('admin.product-brand.destroy', $brand->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete brand \'{{ $brand->name }}\'?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Brand">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-tag fs-1 d-block mb-2 text-secondary"></i>
                    No product brands found in catalog.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($brands->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $brands->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
