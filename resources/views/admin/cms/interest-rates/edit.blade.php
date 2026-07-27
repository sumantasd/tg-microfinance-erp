@extends('layouts.admin')

@section('title', 'Edit Interest Rate Schedule Entry - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Rate Schedule Entry</h4>
        <p class="text-muted small mb-0">Update rate, calculation method, processing fee, or status.</p>
    </div>
    <a href="{{ route('admin.cms.interest-rates.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Rate Schedule
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
    <form action="{{ route('admin.cms.interest-rates.update', $rate->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Product Name *</label>
                <input type="text" name="product_name" value="{{ old('product_name', $rate->product_name) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Product Category *</label>
                <select name="product_type" class="form-select bg-light" required>
                    <option value="loan" {{ old('product_type', $rate->product_type) === 'loan' ? 'selected' : '' }}>Loan Product</option>
                    <option value="savings" {{ old('product_type', $rate->product_type) === 'savings' ? 'selected' : '' }}>Savings Scheme</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Interest Method *</label>
                <select name="interest_method" class="form-select bg-light" required>
                    <option value="Flat" {{ old('interest_method', $rate->interest_method) === 'Flat' ? 'selected' : '' }}>Flat</option>
                    <option value="Reducing Balance" {{ old('interest_method', $rate->interest_method) === 'Reducing Balance' ? 'selected' : '' }}>Reducing Balance</option>
                    <option value="Daily Reducing" {{ old('interest_method', $rate->interest_method) === 'Daily Reducing' ? 'selected' : '' }}>Daily Reducing</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Interest Rate (P.A.) *</label>
                <input type="text" name="interest_rate" value="{{ old('interest_rate', $rate->interest_rate) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Min-Max Amount Range</label>
                <input type="text" name="amount_range" value="{{ old('amount_range', $rate->amount_range) }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Tenure Options</label>
                <input type="text" name="tenure_options" value="{{ old('tenure_options', $rate->tenure_options) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Processing Fee (%)</label>
                <input type="text" name="processing_fee" value="{{ old('processing_fee', $rate->processing_fee) }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $rate->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $rate->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $rate->sort_order) }}" class="form-control bg-light" min="0">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Additional Description / Calculation Notes</label>
                <textarea name="description" rows="3" class="form-control bg-light">{{ old('description', $rate->description) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Rate Entry
                </button>
                <a href="{{ route('admin.cms.interest-rates.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
