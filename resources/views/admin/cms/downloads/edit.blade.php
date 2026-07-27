@extends('layouts.admin')

@section('title', 'Edit Download Document - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Download Document</h4>
        <p class="text-muted small mb-0">Update document title, description, uploaded file, or status.</p>
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
    <form action="{{ route('admin.cms.downloads.update', $download->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-secondary">Document Title *</label>
                <input type="text" name="title" value="{{ old('title', $download->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $download->sort_order) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $download->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $download->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Replace Document File</label>
                <input type="file" name="file" class="form-control bg-light">
                @if($download->file_url)
                    <div class="mt-2 p-2 bg-light border rounded d-flex align-items-center justify-content-between">
                        <span class="small text-muted"><i class="bi bi-file-earmark-text me-1"></i>Current File: <strong>{{ basename($download->file) }}</strong></span>
                        <a href="{{ route('public.resources.downloads.file', $download->id) }}" class="btn btn-outline-success btn-sm rounded-pill"><i class="bi bi-download me-1"></i>Download</a>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Description / Usage Instructions</label>
                <textarea name="description" rows="3" class="form-control bg-light">{{ old('description', $download->description) }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Document
                </button>
                <a href="{{ route('admin.cms.downloads.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
