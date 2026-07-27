@extends('layouts.admin')

@section('title', 'Savings Products CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-piggy-bank text-primary me-2"></i>Savings Products CMS</h4>
        <p class="text-muted small mb-0">Create and manage high-yield deposit schemes, compound interest rates, and savings passbook accounts.</p>
    </div>
    <a href="{{ route('admin.cms.savings-products.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Savings Product
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.cms.savings-products.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search savings products by name or description...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.cms.savings-products.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Product Name & Icon', 'Interest Rate & Deposit', 'Tenure Option', 'Image Graphic', 'Sort Order', 'Status', 'Actions']">
        @forelse($products as $product)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="badge bg-{{ $product->badge_color }}-subtle text-{{ $product->badge_color }} p-2 rounded-circle">
                            <i class="bi {{ $product->icon ?? 'bi-wallet2' }} fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0.5">{{ $product->name }}</div>
                            <small class="text-muted font-monospace opacity-75">/{{ $product->slug }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small fw-bold text-success">{{ $product->interest_rate ?? 'Standard Interest' }}</div>
                    <small class="text-muted d-block">{{ $product->min_balance ? 'Min Balance: $'.$product->min_balance : 'No Min Deposit' }}</small>
                </td>
                <td>
                    <span class="small text-secondary fw-medium">{{ $product->tenure ?? 'Flexible Tenure' }}</span>
                </td>
                <td>
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="rounded border shadow-sm" style="width: 60px; height: 38px; object-fit: cover;">
                    @else
                        <span class="badge bg-light text-muted border">No Image</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace fw-bold">{{ $product->sort_order }}</span>
                </td>
                <td>
                    <form action="{{ route('admin.cms.savings-products.toggle-status', $product->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($product->status === 'active')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Deactivate">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Activate">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.savings-products.edit', $product->id) }}" class="btn btn-light btn-sm border" title="Edit Savings Product"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.savings-products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Delete this savings product permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Product"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No savings products found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $products->links() }}
    </div>
</x-ui.card>
@endsection
