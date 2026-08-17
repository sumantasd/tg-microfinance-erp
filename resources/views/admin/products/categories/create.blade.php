@extends('layouts.admin')

@section('title', 'Add New Category - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-grid-3x3-gap text-success me-2"></i>Add Product Category
        </h4>
        <p class="text-muted small mb-0">Register a new product category classification in the catalog.</p>
    </div>
    <a href="{{ route('admin.product-category.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Categories
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4" style="max-width: 800px;">
    <form action="{{ route('admin.product-category.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-12">
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

            <div class="col-md-8">
                <label class="form-label fw-bold small">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Solar Energy, Sewing & Tailoring, Home Appliances" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Category Code</label>
                <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. SOLAR, SEWING, APPLIANCES">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">Description / Notes</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Optional notes regarding product classification, warranty policies...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small text-dark" for="isActiveSwitch">Active Category</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.product-category.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-success text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Category</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
