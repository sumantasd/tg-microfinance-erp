@extends('layouts.admin')

@section('title', 'Edit SEO Page Setting - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit SEO Page Setting</h4>
        <p class="text-muted small mb-0">Update title, description, keywords, or OpenGraph preview image.</p>
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
    <form action="{{ route('admin.cms.seo.update', $seo->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Page Key / Name *</label>
                <input type="text" name="page_name" value="{{ old('page_name', $seo->page_name) }}" class="form-control bg-light font-monospace" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $seo->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $seo->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $seo->meta_title) }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Meta Description</label>
                <textarea name="meta_description" rows="3" class="form-control bg-light">{{ old('meta_description', $seo->meta_description) }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Search Keywords (Comma Separated)</label>
                <input type="text" name="keywords" value="{{ old('keywords', $seo->keywords) }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">OpenGraph Social Share Image (OG Image)</label>
                <input type="file" name="og_image" class="form-control bg-light">
                @if($seo->og_image_url)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ $seo->og_image_url }}" alt="OG Image" class="rounded border" style="max-height: 70px;">
                        <span class="small text-muted">Current Social Share Graphic</span>
                    </div>
                @endif
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update SEO Setting
                </button>
                <a href="{{ route('admin.cms.seo.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
