@extends('layouts.admin')

@section('title', 'Edit Gallery Image - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Gallery Image</h4>
        <p class="text-muted small mb-0">Update photo caption, category tag, image file, or status.</p>
    </div>
    <a href="{{ route('admin.cms.gallery.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Gallery
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
    <form action="{{ route('admin.cms.gallery.update', $gallery->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-secondary">Image Title / Caption *</label>
                <input type="text" name="title" value="{{ old('title', $gallery->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Category Label</label>
                <input type="text" name="category" value="{{ old('category', $gallery->category) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $gallery->sort_order) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $gallery->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $gallery->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Replace Image File</label>
                <input type="file" name="image" class="form-control bg-light">
                @if($gallery->image_url)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ $gallery->image_url }}" alt="Gallery Image" class="rounded border" style="max-height: 80px;">
                        <span class="small text-muted">Current Gallery Photo</span>
                    </div>
                @endif
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Gallery Item
                </button>
                <a href="{{ route('admin.cms.gallery.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
