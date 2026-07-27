@extends('layouts.admin')

@section('title', 'Add Job Opening - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add Job Opening</h4>
        <p class="text-muted small mb-0">Post a new position for loan officers, branch managers, or field collectors.</p>
    </div>
    <a href="{{ route('admin.cms.careers.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Careers
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
    <form action="{{ route('admin.cms.careers.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Job Position Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" class="form-control bg-light" placeholder="e.g. Field Credit Officer" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Location / Branch</label>
                <input type="text" name="location" value="{{ old('location', 'Multiple Branch Locations') }}" class="form-control bg-light" placeholder="e.g. Head Office / Eastern Region">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Employment Type *</label>
                <select name="job_type" class="form-select bg-light" required>
                    <option value="Full-Time" {{ old('job_type', 'Full-Time') === 'Full-Time' ? 'selected' : '' }}>Full-Time</option>
                    <option value="Part-Time" {{ old('job_type') === 'Part-Time' ? 'selected' : '' }}>Part-Time</option>
                    <option value="Contract" {{ old('job_type') === 'Contract' ? 'selected' : '' }}>Contract</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Application Contact Email</label>
                <input type="email" name="application_email" value="{{ old('application_email', 'hr@tgmicrofinance.org') }}" class="form-control bg-light" placeholder="e.g. hr@tgmicrofinance.org">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Application Deadline</label>
                <input type="date" name="deadline" value="{{ old('deadline') }}" class="form-control bg-light">
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Short Job Description</label>
                <textarea name="short_description" rows="2" class="form-control bg-light" placeholder="Brief summary of duties and responsibilities...">{{ old('short_description') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Key Qualifications & Requirements (HTML/Bullets Supported)</label>
                <textarea name="requirements" rows="4" class="form-control bg-light" placeholder="Degree in Accounting/Finance, 2+ years field collection experience...">{{ old('requirements') }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Button CTA Text</label>
                <input type="text" name="apply_button_text" value="{{ old('apply_button_text', 'Apply for Position') }}" class="form-control bg-light">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" min="0">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Post Job Opening
                </button>
                <a href="{{ route('admin.cms.careers.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
