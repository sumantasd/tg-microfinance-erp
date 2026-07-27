@extends('layouts.admin')

@section('title', 'Add SEO Page Setting - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add SEO Page Setting</h4>
        <p class="text-muted small mb-0">Configure title, description, and preview image for a specific page route.</p>
    </div>
    <a href="{{ route('admin.cms.seo.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to SEO Settings
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
    <form action="{{ route('admin.cms.seo.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Page Key / Name *</label>
                <input type="text" name="page_name" value="{{ old('page_name') }}" class="form-control bg-light font-monospace" placeholder="e.g. home, about, loan_products, contact" required>
                <small class="text-muted">Use lowercase identifier matching the route (e.g., home, about, contact).</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title') }}" class="form-control bg-light" placeholder="e.g. TG Microfinance - Fast Business Loans & High-Yield Savings">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Meta Description</label>
                <textarea name="meta_description" rows="3" class="form-control bg-light" placeholder="Search engine description summary (150-160 characters)...">{{ old('meta_description') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Search Keywords (Comma Separated)</label>
                <input type="text" name="keywords" value="{{ old('keywords') }}" class="form-control bg-light" placeholder="microfinance, loan products, savings interest, field banking">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">OpenGraph Social Share Image (OG Image)</label>
                <input type="file" name="og_image" class="form-control bg-light">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save SEO Setting
                </button>
                <a href="{{ route('admin.cms.seo.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
