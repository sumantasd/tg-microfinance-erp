@extends('layouts.admin')

@section('title', 'Edit Service Page - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Service Page</h4>
        <p class="text-muted small mb-0">Update service details, banner image, or SEO metadata.</p>
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
    <form action="{{ route('admin.cms.services.update', $service->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Service Title *</label>
                <input type="text" name="title" value="{{ old('title', $service->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">URL Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $service->slug) }}" class="form-control bg-light font-monospace">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Bootstrap Icon</label>
                <input type="text" name="icon" value="{{ old('icon', $service->icon) }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Short Excerpt / Summary</label>
                <textarea name="short_description" rows="2" class="form-control bg-light">{{ old('short_description', $service->short_description) }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Full Service Content (HTML Supported)</label>
                <textarea name="content" rows="5" class="form-control bg-light">{{ old('content', $service->content) }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Header Image</label>
                <input type="file" name="banner_image" class="form-control bg-light">
                @if($service->banner_image_url)
                    <div class="mt-2 p-2 bg-light border rounded d-inline-flex align-items-center gap-3">
                        <img src="{{ $service->banner_image_url }}" alt="{{ $service->title }}" class="rounded border" style="max-height: 60px;">
                        <span class="small text-muted">Current Banner Thumbnail</span>
                    </div>
                @endif
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $service->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $service->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $service->sort_order) }}" class="form-control bg-light" min="0">
            </div>

            <h5 class="fw-bold text-dark pt-3 mb-0 border-top">SEO Metadata (Search Engine Optimization)</h5>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">SEO Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $service->meta_title) }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">SEO Meta Description</label>
                <textarea name="meta_description" rows="2" class="form-control bg-light">{{ old('meta_description', $service->meta_description) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Service Page
                </button>
                <a href="{{ route('admin.cms.services.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
