@extends('layouts.admin')

@section('title', 'Add New Banner - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add New Banner Slide</h4>
        <p class="text-muted small mb-0">Upload a banner image graphic and set call-to-action details.</p>
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
    <form action="{{ route('admin.cms.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Headline / Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Flexible Micro-Loans for Growth" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Subtitle / Tagline</label>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="form-control bg-light" placeholder="e.g. Competitive interest rates with fast approval process">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button CTA Label</label>
                <input type="text" name="button_text" value="{{ old('button_text') }}" class="form-control bg-light" placeholder="e.g. Apply For Loan">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button Destination Link</label>
                <input type="text" name="button_url" value="{{ old('button_url') }}" class="form-control bg-light" placeholder="e.g. /apply-loan">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Banner Graphic Image *</label>
                <input type="file" name="image" class="form-control bg-light">
                <small class="text-muted d-block mt-1">Recommended size: 1920x600px or high resolution hero image.</small>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Banner Slide
                </button>
                <a href="{{ route('admin.cms.banners.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
