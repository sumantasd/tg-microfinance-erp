@extends('layouts.admin')

@section('title', 'Edit CMS Page - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit CMS Page</h4>
        <p class="text-muted small mb-0">Modify page content, slug URL, featured banner, and publishing status.</p>
    </div>
    <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Pages
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
    <form action="{{ route('admin.cms.pages.update', $page->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Page Title *</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">URL Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="form-control bg-light font-monospace">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Featured Header Image</label>
                <input type="file" name="image" class="form-control bg-light">
                @if($page->image)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center gap-3">
                        <img src="{{ asset('storage/' . $page->image) }}" alt="Header Image" class="rounded border" style="max-height: 60px;">
                        <span class="small text-muted">Current Featured Image</span>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Publishing Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="inactive" {{ old('status', $page->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Page Content Body</label>
                <textarea name="content" rows="10" class="form-control bg-light">{{ old('content', $page->content) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update CMS Page
                </button>
                <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
