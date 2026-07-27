@extends('layouts.admin')

@section('title', 'Edit Savings Product - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Savings Product</h4>
        <p class="text-muted small mb-0">Update deposit parameters, interest rates, graphic images, and status.</p>
    </div>
    <a href="{{ route('admin.cms.savings-products.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Savings Products
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix validation errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.cms.savings-products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Product Name *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">URL Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control bg-light font-monospace">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Interest Rate Label</label>
                <input type="text" name="interest_rate" value="{{ old('interest_rate', $product->interest_rate) }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Minimum Opening Balance</label>
                <input type="text" name="min_balance" value="{{ old('min_balance', $product->min_balance) }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Tenure Terms</label>
                <input type="text" name="tenure" value="{{ old('tenure', $product->tenure) }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Bootstrap Icon Class</label>
                <input type="text" name="icon" value="{{ old('icon', $product->icon) }}" class="form-control bg-light font-monospace">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Theme Badge Color *</label>
                <select name="badge_color" class="form-select bg-light" required>
                    <option value="success" {{ old('badge_color', $product->badge_color) === 'success' ? 'selected' : '' }}>Success (Green)</option>
                    <option value="primary" {{ old('badge_color', $product->badge_color) === 'primary' ? 'selected' : '' }}>Primary (Blue)</option>
                    <option value="info" {{ old('badge_color', $product->badge_color) === 'info' ? 'selected' : '' }}>Info (Cyan)</option>
                    <option value="warning" {{ old('badge_color', $product->badge_color) === 'warning' ? 'selected' : '' }}>Warning (Yellow)</option>
                    <option value="danger" {{ old('badge_color', $product->badge_color) === 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Product Feature Image</label>
                <input type="file" name="image" class="form-control bg-light">
                @if($product->image_url)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ $product->image_url }}" alt="Feature Image" class="rounded border" style="max-height: 60px;">
                        <span class="small text-muted">Current Product Image</span>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Product Description</label>
                <textarea name="description" rows="3" class="form-control bg-light">{{ old('description', $product->description) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Savings Product
                </button>
                <a href="{{ route('admin.cms.savings-products.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
