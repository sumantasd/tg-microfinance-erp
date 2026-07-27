@extends('layouts.admin')

@section('title', 'Add Download Document - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add Download Document</h4>
        <p class="text-muted small mb-0">Upload official PDF forms, application kits, or disclosures.</p>
    </div>
    <a href="{{ route('admin.cms.downloads.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Downloads
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
    <form action="{{ route('admin.cms.downloads.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-secondary">Document Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Individual Loan Application Form" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Document File (PDF, DOC, ZIP) *</label>
                <input type="file" name="file" class="form-control bg-light" required>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Description / Usage Instructions</label>
                <textarea name="description" rows="3" class="form-control bg-light" placeholder="Brief note e.g. PDF Document (Version 2.4 - 1.2 MB)...">{{ old('description') }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Upload Document
                </button>
                <a href="{{ route('admin.cms.downloads.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
