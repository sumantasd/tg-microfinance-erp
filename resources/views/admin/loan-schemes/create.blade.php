@extends('layouts.admin')

@section('title', 'Create Loan Scheme - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-plus-circle text-primary me-2"></i>Create New Loan Scheme
        </h4>
        <p class="text-muted small mb-0">Define financial rules, interest calculations, tenure, and fee parameters.</p>
    </div>
    <a href="{{ route('admin.loan-scheme.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.loan-scheme.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Scheme Identification</h5>

            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @endif

            <div class="col-md-6">
                <label class="form-label fw-bold small">Branch (Optional - Leave blank for All Branches)</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">All Branches (Company-wide)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Scheme Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Micro Enterprise Cash Loan" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Loan Type <span class="text-danger">*</span></label>
                <select name="loan_type" class="form-select @error('loan_type') is-invalid @enderror" required>
                    <option value="cash" {{ old('loan_type') === 'cash' ? 'selected' : '' }}>Cash Loan</option>
                    <option value="product" {{ old('loan_type') === 'product' ? 'selected' : '' }}>Product Loan</option>
                    <option value="both" {{ old('loan_type') === 'both' ? 'selected' : '' }}>Cash & Product Both</option>
                </select>
                @error('loan_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Applicant Eligibility <span class="text-danger">*</span></label>
                <select name="applicant_type" class="form-select @error('applicant_type') is-invalid @enderror" required>
                    <option value="individual" {{ old('applicant_type') === 'individual' ? 'selected' : '' }}>Individual Borrower</option>
                    <option value="group" {{ old('applicant_type') === 'group' ? 'selected' : '' }}>Group (JLG / SHG)</option>
                    <option value="both" {{ old('applicant_type') === 'both' ? 'selected' : '' }}>Both</option>
                </select>
                @error('applicant_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Financial & Amortization Limits</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Min Loan Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="min_amount" class="form-control @error('min_amount') is-invalid @enderror" value="{{ old('min_amount', '5000.00') }}" required>
                @error('min_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Max Loan Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="max_amount" class="form-control @error('max_amount') is-invalid @enderror" value="{{ old('max_amount', '100000.00') }}" required>
                @error('max_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Interest Type <span class="text-danger">*</span></label>
                <select name="interest_type" class="form-select @error('interest_type') is-invalid @enderror" required>
                    <option value="flat" {{ old('interest_type') === 'flat' ? 'selected' : '' }}>Flat Interest</option>
                    <option value="reducing_balance" {{ old('interest_type') === 'reducing_balance' ? 'selected' : '' }}>Reducing Balance</option>
                </select>
                @error('interest_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Interest Rate (% per annum) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="interest_rate_per_annum" class="form-control @error('interest_rate_per_annum') is-invalid @enderror" value="{{ old('interest_rate_per_annum', '18.00') }}" required>
                @error('interest_rate_per_annum') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Min Tenure (Months) <span class="text-danger">*</span></label>
                <input type="number" name="min_tenure_months" class="form-control @error('min_tenure_months') is-invalid @enderror" value="{{ old('min_tenure_months', '6') }}" required>
                @error('min_tenure_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Max Tenure (Months) <span class="text-danger">*</span></label>
                <input type="number" name="max_tenure_months" class="form-control @error('max_tenure_months') is-invalid @enderror" value="{{ old('max_tenure_months', '36') }}" required>
                @error('max_tenure_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Repayment Frequency <span class="text-danger">*</span></label>
                <select name="repayment_frequency" class="form-select @error('repayment_frequency') is-invalid @enderror" required>
                    <option value="monthly" {{ old('repayment_frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="bi_weekly" {{ old('repayment_frequency') === 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly (Fortnightly)</option>
                    <option value="weekly" {{ old('repayment_frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                </select>
                @error('repayment_frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">3. Fees & Penalties</h5>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Processing Fee (%)</label>
                <input type="number" step="0.01" name="processing_fee_percentage" class="form-control @error('processing_fee_percentage') is-invalid @enderror" value="{{ old('processing_fee_percentage', '2.00') }}">
                @error('processing_fee_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Insurance Fee (%)</label>
                <input type="number" step="0.01" name="insurance_fee_percentage" class="form-control @error('insurance_fee_percentage') is-invalid @enderror" value="{{ old('insurance_fee_percentage', '1.00') }}">
                @error('insurance_fee_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Late Penalty (%)</label>
                <input type="number" step="0.01" name="late_fee_percentage" class="form-control @error('late_fee_percentage') is-invalid @enderror" value="{{ old('late_fee_percentage', '1.50') }}">
                @error('late_fee_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Grace Period (Days)</label>
                <input type="number" name="grace_period_days" class="form-control @error('grace_period_days') is-invalid @enderror" value="{{ old('grace_period_days', '5') }}">
                @error('grace_period_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold small">Remarks & Operational Guidelines</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" placeholder="Internal notes or eligibility rules...">{{ old('remarks') }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.loan-scheme.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Loan Scheme</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
