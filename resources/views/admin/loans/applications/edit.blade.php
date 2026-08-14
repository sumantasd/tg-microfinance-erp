@extends('layouts.admin')

@section('title', 'Edit Loan Application - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Draft Application - {{ $loanApplication->application_number }}
        </h4>
        <p class="text-muted small mb-0">Status: <span class="badge bg-secondary text-white">{{ $loanApplication->status }}</span></p>
    </div>
    <a href="{{ route('admin.loan-application.show', $loanApplication->id) }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.loan-application.update', $loanApplication->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Loan Terms & Amount</h5>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Branch Location</label>
                <input type="text" class="form-control" value="{{ $loanApplication->branch->name }} ({{ $loanApplication->branch->code }})" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Loan Scheme Master <span class="text-danger">*</span></label>
                <select name="loan_scheme_id" class="form-select @error('loan_scheme_id') is-invalid @enderror" required>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ old('loan_scheme_id', $loanApplication->loan_scheme_id) == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }}) - {{ $s->interest_rate_per_annum }}% p.a.
                        </option>
                    @endforeach
                </select>
                @error('loan_scheme_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Total Requested Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="requested_amount" class="form-control @error('requested_amount') is-invalid @enderror" value="{{ old('requested_amount', $loanApplication->requested_amount) }}" required>
                @error('requested_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Requested Tenure (Months) <span class="text-danger">*</span></label>
                <input type="number" name="tenure_months" class="form-control @error('tenure_months') is-invalid @enderror" value="{{ old('tenure_months', $loanApplication->tenure_months) }}" required>
                @error('tenure_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Repayment Frequency</label>
                <select name="repayment_frequency" class="form-select @error('repayment_frequency') is-invalid @enderror">
                    <option value="monthly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="bi_weekly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly</option>
                    <option value="weekly" {{ old('repayment_frequency', $loanApplication->repayment_frequency) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                </select>
                @error('repayment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Loan Purpose</label>
                <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose', $loanApplication->purpose) }}">
                @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Remarks</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2">{{ old('remarks', $loanApplication->remarks) }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.loan-application.show', $loanApplication->id) }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i> Update Draft Application</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
