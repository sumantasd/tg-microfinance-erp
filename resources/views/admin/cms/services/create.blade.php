@extends('layouts.admin')

@section('title', 'Add Service Page - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add Service Page</h4>
        <p class="text-muted small mb-0">Create a new corporate service page with SEO metadata.</p>
    </div>
    <a href="{{ route('admin.cms.services.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Services
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
    <form action="{{ route('admin.cms.services.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Service Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Digital Banking Services" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">URL Slug</label>
                <input type="text" name="slug" value="{{ old('slug') }}" class="form-control bg-light font-monospace" placeholder="e.g. digital-banking">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Bootstrap Icon</label>
                <input type="text" name="icon" value="{{ old('icon', 'bi-phone') }}" class="form-control bg-light" placeholder="e.g. bi-phone">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Short Excerpt / Summary</label>
                <textarea name="short_description" rows="2" class="form-control bg-light" placeholder="Brief summary of service features...">{{ old('short_description') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Full Service Content (HTML Supported)</label>
                <textarea name="content" rows="5" class="form-control bg-light" placeholder="Detailed service description and operational benefits...">{{ old('content') }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Header Image</label>
                <input type="file" name="banner_image" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" min="0">
            </div>

            <h5 class="fw-bold text-dark pt-3 mb-0 border-top">SEO Metadata (Search Engine Optimization)</h5>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">SEO Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title') }}" class="form-control bg-light" placeholder="e.g. Digital Banking & Mobile Wallet Services | TG Microfinance">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">SEO Meta Description</label>
                <textarea name="meta_description" rows="2" class="form-control bg-light" placeholder="Meta description snippet for search engines...">{{ old('meta_description') }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Service Page
                </button>
                <a href="{{ route('admin.cms.services.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
