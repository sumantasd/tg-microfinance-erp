@extends('layouts.admin')

@section('title', 'Edit Designation - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Designation</h4>
        <p class="text-muted small mb-0">Update title details for <strong>{{ $designation->title }}</strong>.</p>
    </div>
    <a href="{{ route('admin.designation.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Designations
    </a>
</div>

<x-ui.card class="p-4 shadow-sm" style="max-width: 750px;">
    <form action="{{ route('admin.designation.update', $designation->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Parent Company <span class="text-danger">*</span></label>
                @if(auth()->user()->isSuperAdmin())
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        <option value="">Select Company...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', $designation->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control bg-light" value="{{ $designation->company->name ?? 'N/A' }}" readonly>
                    <input type="hidden" name="company_id" value="{{ $designation->company_id }}">
                @endif
                @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Department <span class="text-danger">*</span></label>
                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                    <option value="">Select Department...</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id', $designation->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }} ({{ $dept->code }})</option>
                    @endforeach
                </select>
                @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-7">
                <label class="form-label fw-semibold text-dark">Designation Title <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title', $designation->title) }}" class="form-control @error('title') is-invalid @enderror" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-5">
                <label class="form-label fw-semibold text-dark">Designation Code</label>
                <input type="text" name="code" value="{{ old('code', $designation->code) }}" class="form-control font-monospace @error('code') is-invalid @enderror">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold text-dark">Designation Description</label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $designation->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex align-items-center">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $designation->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-dark" for="is_active">Set Active Status</label>
                </div>
            </div>

            <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-save me-1.5"></i> Update Designation
                </button>
                <a href="{{ route('admin.designation.index') }}" class="btn btn-light border rounded-pill px-4 py-2 text-secondary">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
