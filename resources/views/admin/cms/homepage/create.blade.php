@extends('layouts.admin')

@section('title', 'Add Homepage Section - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add New Homepage Section</h4>
        <p class="text-muted small mb-0">Create a new content block for the landing page.</p>
    </div>
    <a href="{{ route('admin.cms.homepage.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Sections List
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
    <form action="{{ route('admin.cms.homepage.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Section Unique Key *</label>
                <input type="text" name="section_key" value="{{ old('section_key') }}" class="form-control bg-light font-monospace" placeholder="e.g. hero_banner, about_us, core_features" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Section Title</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Empowering Micro Businesses">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Subtitle</label>
                <input type="text" name="subtitle" value="{{ old('subtitle') }}" class="form-control bg-light" placeholder="e.g. Fast & Transparent Financial Solutions">
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

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Description / Content Body</label>
                <textarea name="description" rows="4" class="form-control bg-light" placeholder="Detailed section text or promotional copy...">{{ old('description') }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button CTA Text</label>
                <input type="text" name="button_text" value="{{ old('button_text') }}" class="form-control bg-light" placeholder="e.g. Apply Now, Learn More">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Button Link URL</label>
                <input type="text" name="button_url" value="{{ old('button_url') }}" class="form-control bg-light" placeholder="e.g. /apply-loan or https://example.com">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Section Image Asset</label>
                <input type="file" name="image" class="form-control bg-light">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save Homepage Section
                </button>
                <a href="{{ route('admin.cms.homepage.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
