@extends('layouts.admin')

@section('title', 'Edit Banner - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Banner Slide</h4>
        <p class="text-muted small mb-0">Update banner details, upload graphic image, and toggle visibility.</p>
    </div>
    <a href="{{ route('admin.cms.banners.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Banners
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
    <form action="{{ route('admin.cms.banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Headline / Title *</label>
                <input type="text" name="title" value="{{ old('title', $banner->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Subtitle / Tagline</label>
                <input type="text" name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button CTA Label</label>
                <input type="text" name="button_text" value="{{ old('button_text', $banner->button_text) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button Destination Link</label>
                <input type="text" name="button_url" value="{{ old('button_url', $banner->button_url) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Graphic Image</label>
                <input type="file" name="image" class="form-control bg-light">
                @if($banner->image)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ asset('storage/' . $banner->image) }}" alt="Banner Graphic" class="rounded border" style="max-height: 60px;">
                        <span class="small text-muted">Current Banner Graphic</span>
                    </div>
                @endif
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $banner->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $banner->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Banner Slide
                </button>
                <a href="{{ route('admin.cms.banners.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
