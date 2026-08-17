@extends('layouts.admin')

@section('title', 'Add New Product - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-box-seam text-info me-2"></i>Add Product to Catalog
        </h4>
        <p class="text-muted small mb-0">Register a new physical product or goods item available for Product Loans.</p>
    </div>
    <a href="{{ route('admin.product.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.product.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Product Identification</h5>

            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @endif

            <div class="col-md-6">
                <label class="form-label fw-bold small">Product Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Solar Home Lighting Kit 20W" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold small mb-0">Product Category</label>
                    <a href="{{ route('admin.product-category.create') }}" target="_blank" class="small text-decoration-none text-success fw-bold"><i class="bi bi-plus-circle me-0.5"></i>New Category</a>
                </div>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold small mb-0">Product Brand</label>
                    <a href="{{ route('admin.product-brand.create') }}" target="_blank" class="small text-decoration-none text-primary fw-bold"><i class="bi bi-plus-circle me-0.5"></i>New Brand</a>
                </div>
                <select name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>
                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Model Number</label>
                <input type="text" name="model_number" class="form-control @error('model_number') is-invalid @enderror" value="{{ old('model_number') }}" placeholder="e.g. SHL-20W-V2">
                @error('model_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Pricing & Taxation</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Selling Price / MRP (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{ old('unit_price') }}" placeholder="e.g. 12500.00" required>
                @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Cost Price / Purchase Price (₹)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price') }}" placeholder="e.g. 9800.00">
                @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">GST Tax Rate (%)</label>
                <input type="number" step="0.01" name="tax_percentage" class="form-control @error('tax_percentage') is-invalid @enderror" value="{{ old('tax_percentage', '18.00') }}" placeholder="e.g. 18.00">
                @error('tax_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Product Description & Technical Specifications</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Enter warranty, battery specs, accessories included...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.product.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-info text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Product</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
