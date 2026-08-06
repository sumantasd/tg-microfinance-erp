@extends('layouts.admin')

@section('title', 'Edit Company Profile - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Company Profile</h4>
        <p class="text-muted small mb-0">Update corporate details for <strong>{{ $company->name }}</strong>.</p>
    </div>
    <a href="{{ route('admin.company.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Companies
    </a>
</div>

<x-ui.card class="p-4 shadow-sm" style="max-width: 900px;">
    <form action="{{ route('admin.company.update', $company->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold text-dark">Company Legal Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $company->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Company Code <span class="text-danger">*</span></label>
                <input type="text" name="code" value="{{ old('code', $company->code) }}" class="form-control font-monospace @error('code') is-invalid @enderror" required>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Registration / Incorporation No.</label>
                <input type="text" name="registration_number" value="{{ old('registration_number', $company->registration_number) }}" class="form-control @error('registration_number') is-invalid @enderror">
                @error('registration_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Tax / GST Identification Number</label>
                <input type="text" name="tax_id" value="{{ old('tax_id', $company->tax_id) }}" class="form-control @error('tax_id') is-invalid @enderror">
                @error('tax_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Official Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $company->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Contact Phone Number <span class="text-danger">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $company->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold text-dark">Headquarters Registered Address <span class="text-danger">*</span></label>
                <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $company->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Base Currency Code <span class="text-danger">*</span></label>
                <input type="text" name="currency_code" value="{{ old('currency_code', $company->currency_code) }}" class="form-control font-monospace @error('currency_code') is-invalid @enderror" required>
                @error('currency_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Currency Symbol <span class="text-danger">*</span></label>
                <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol) }}" class="form-control font-monospace @error('currency_symbol') is-invalid @enderror" required>
                @error('currency_symbol') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-dark" for="is_active">Set Company Active</label>
                </div>
            </div>

            <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-save me-1.5"></i> Update Company Profile
                </button>
                <a href="{{ route('admin.company.index') }}" class="btn btn-light border rounded-pill px-4 py-2 text-secondary">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
