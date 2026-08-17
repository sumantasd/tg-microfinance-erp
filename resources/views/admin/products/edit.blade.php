@extends('layouts.admin')

@section('title', 'Edit Product - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Product - {{ $product->name }}
        </h4>
        <p class="text-muted small mb-0">SKU: <span class="font-monospace text-dark fw-bold">{{ $product->sku }}</span></p>
    </div>
    <a href="{{ route('admin.product.show', $product->id) }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Profile
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.product.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Product Identification</h5>

            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', $product->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $product->company_id }}">
            @endif

            <div class="col-md-3">
                <label class="form-label fw-bold small">SKU <span class="text-danger">*</span></label>
                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}" required>
                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Status <span class="text-danger">*</span></label>
                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                    <option value="1" {{ old('is_active', $product->is_active) ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $product->is_active) ? '' : 'selected' }}>Inactive</option>
                </select>
                @error('is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold small mb-0">Category</label>
                    <a href="{{ route('admin.product-category.create') }}" target="_blank" class="small text-decoration-none text-success fw-bold"><i class="bi bi-plus-circle me-0.5"></i>New</a>
                </div>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (old('category_id', $product->category_id) == $category->id || old('category', $product->category) == $category->name) ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold small mb-0">Brand</label>
                    <a href="{{ route('admin.product-brand.create') }}" target="_blank" class="small text-decoration-none text-primary fw-bold"><i class="bi bi-plus-circle me-0.5"></i>New</a>
                </div>
                <select name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ (old('brand_id', $product->brand_id) == $brand->id || old('brand', $product->brand) == $brand->name) ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Model Number</label>
                <input type="text" name="model_number" class="form-control @error('model_number') is-invalid @enderror" value="{{ old('model_number', $product->model_number) }}">
                @error('model_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Pricing & Taxation</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Selling Price / MRP (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price', $product->unit_price) }}" required>
                @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Cost Price (₹)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', $product->cost_price) }}">
                @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">GST Tax Rate (%)</label>
                <input type="number" step="0.01" name="tax_percentage" class="form-control @error('tax_percentage') is-invalid @enderror" value="{{ old('tax_percentage', $product->tax_percentage) }}">
                @error('tax_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Product Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $product->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.product.show', $product->id) }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Update Product</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
