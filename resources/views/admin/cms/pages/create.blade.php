@extends('layouts.admin')

@section('title', 'Create CMS Page - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Create New CMS Page</h4>
        <p class="text-muted small mb-0">Build static information pages with title, slug, content body, and image.</p>
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
    <form action="{{ route('admin.cms.pages.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Page Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Terms & Conditions, Privacy Policy" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">URL Slug (Auto-generated if left blank)</label>
                <input type="text" name="slug" value="{{ old('slug') }}" class="form-control bg-light font-monospace" placeholder="e.g. terms-and-conditions">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Featured Header Image</label>
                <input type="file" name="image" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Publishing Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Page Content Body (HTML / Markdown / Text)</label>
                <textarea name="content" rows="10" class="form-control bg-light" placeholder="Write page content body here...">{{ old('content') }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save CMS Page
                </button>
                <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
