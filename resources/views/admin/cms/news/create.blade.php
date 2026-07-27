@extends('layouts.admin')

@section('title', 'Add News Article - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add New Press Release / News Article</h4>
        <p class="text-muted small mb-0">Create a corporate news post for the public portal media center.</p>
    </div>
    <a href="{{ route('admin.cms.news.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to News Articles
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
    <form action="{{ route('admin.cms.news.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-secondary">Article Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. TG Microfinance Expands Branch Network" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">URL Slug (Auto-generated if left blank)</label>
                <input type="text" name="slug" value="{{ old('slug') }}" class="form-control bg-light font-monospace" placeholder="e.g. tg-microfinance-expands-branch-network">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Published Date</label>
                <input type="date" name="published_date" value="{{ old('published_date', date('Y-m-d')) }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" required>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Featured Image Graphic</label>
                <input type="file" name="featured_image" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Short Summary / Excerpt</label>
                <textarea name="short_description" rows="2" class="form-control bg-light" placeholder="Brief 1-2 sentence excerpt shown on listing cards...">{{ old('short_description') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Full Article Content (HTML allowed)</label>
                <textarea name="content" rows="8" class="form-control bg-light" placeholder="Full story, press release details, and financial disclosures...">{{ old('content') }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save News Article
                </button>
                <a href="{{ route('admin.cms.news.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
