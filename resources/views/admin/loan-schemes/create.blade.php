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
                    <option value="weekly" {{ old('repayment_frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="bi_weekly" {{ old('repayment_frequency', 'bi_weekly') === 'bi_weekly' ? 'selected' : '' }}>15 Days</option>
                    <option value="monthly" {{ old('repayment_frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
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
                <label class="form-label fw-bold small">Penalty Calculation Mode <span class="text-danger">*</span></label>
                <select name="penalty_type" id="penaltyTypeSelect" class="form-select @error('penalty_type') is-invalid @enderror" onchange="togglePenaltyFields()">
                    <option value="none" {{ old('penalty_type') === 'none' ? 'selected' : '' }}>None (No Penalty)</option>
                    <option value="percentage_one_time" {{ old('penalty_type', 'percentage_one_time') === 'percentage_one_time' ? 'selected' : '' }}>Percentage (One-Time)</option>
                    <option value="percentage_per_day" {{ old('penalty_type') === 'percentage_per_day' ? 'selected' : '' }}>Percentage (Per Day)</option>
                    <option value="flat_one_time" {{ old('penalty_type') === 'flat_one_time' ? 'selected' : '' }}>Flat Fee (One-Time)</option>
                    <option value="flat_per_day" {{ old('penalty_type') === 'flat_per_day' ? 'selected' : '' }}>Flat Fee (Per Day)</option>
                </select>
                @error('penalty_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3" id="gracePeriodContainer">
                <label class="form-label fw-bold small">Grace Period (Days)</label>
                <input type="number" name="grace_period_days" class="form-control @error('grace_period_days') is-invalid @enderror" value="{{ old('grace_period_days', '5') }}" min="0">
                <div class="form-text small">Days before penalty begins</div>
                @error('grace_period_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3" id="lateFeePercentageContainer">
                <label class="form-label fw-bold small" id="lateFeeLabel">Late Penalty (%)</label>
                <input type="number" step="0.01" name="late_fee_percentage" class="form-control @error('late_fee_percentage') is-invalid @enderror" value="{{ old('late_fee_percentage', '1.50') }}" min="0" max="100">
                <div class="form-text small" id="lateFeeHelp">Calculated on unpaid installment balance</div>
                @error('late_fee_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3" id="flatPenaltyContainer">
                <label class="form-label fw-bold small" id="flatPenaltyLabel">Flat Penalty Amount (₹)</label>
                <input type="number" step="0.01" name="flat_penalty_amount" class="form-control @error('flat_penalty_amount') is-invalid @enderror" value="{{ old('flat_penalty_amount', '0.00') }}" min="0">
                <div class="form-text small" id="flatPenaltyHelp">Fixed late fee charge</div>
                @error('flat_penalty_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3" id="maxPenaltyAmountContainer">
                <label class="form-label fw-bold small">Max Penalty Cap (₹)</label>
                <input type="number" step="0.01" name="max_penalty_amount" class="form-control @error('max_penalty_amount') is-invalid @enderror" value="{{ old('max_penalty_amount') }}" placeholder="No Cap" min="0">
                <div class="form-text small">Absolute maximum late fee cap</div>
                @error('max_penalty_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3" id="maxPenaltyPctContainer">
                <label class="form-label fw-bold small">Max Penalty Cap (%)</label>
                <input type="number" step="0.01" name="max_penalty_percentage" class="form-control @error('max_penalty_percentage') is-invalid @enderror" value="{{ old('max_penalty_percentage') }}" placeholder="No Cap" min="0" max="100">
                <div class="form-text small">Max % of installment balance</div>
                @error('max_penalty_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">4. Foreclosure & Pre-Closure Policy</h5>

            <div class="col-md-3">
                <div class="form-check form-switch mt-4">
                    <input class="form-check-input" type="checkbox" name="allow_foreclosure" id="allowForeclosureSwitch" value="1" {{ old('allow_foreclosure', '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small" for="allowForeclosureSwitch">Allow Early Foreclosure</label>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Foreclosure Fee Mode</label>
                <select name="foreclosure_fee_type" class="form-select @error('foreclosure_fee_type') is-invalid @enderror">
                    <option value="none" {{ old('foreclosure_fee_type', 'none') === 'none' ? 'selected' : '' }}>None (0% Free Foreclosure)</option>
                    <option value="percentage" {{ old('foreclosure_fee_type') === 'percentage' ? 'selected' : '' }}>Percentage of Outstanding Principal</option>
                    <option value="flat" {{ old('foreclosure_fee_type') === 'flat' ? 'selected' : '' }}>Flat Fee Amount</option>
                </select>
                @error('foreclosure_fee_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold small">Foreclosure Fee (%)</label>
                <input type="number" step="0.01" name="foreclosure_fee_percentage" class="form-control @error('foreclosure_fee_percentage') is-invalid @enderror" value="{{ old('foreclosure_fee_percentage', '0.00') }}" min="0" max="100">
                @error('foreclosure_fee_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold small">Flat Foreclosure Fee (₹)</label>
                <input type="number" step="0.01" name="foreclosure_flat_fee" class="form-control @error('foreclosure_flat_fee') is-invalid @enderror" value="{{ old('foreclosure_flat_fee', '0.00') }}" min="0">
                @error('foreclosure_flat_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold small">Lock-In Period (Months)</label>
                <input type="number" name="min_months_before_foreclosure" class="form-control @error('min_months_before_foreclosure') is-invalid @enderror" value="{{ old('min_months_before_foreclosure', '0') }}" min="0">
                <div class="form-text small">0 = Immediate payoff</div>
                @error('min_months_before_foreclosure') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

@push('scripts')
<script>
function togglePenaltyFields() {
    const pType = document.getElementById('penaltyTypeSelect').value;
    const graceContainer = document.getElementById('gracePeriodContainer');
    const lateFeeContainer = document.getElementById('lateFeePercentageContainer');
    const flatPenaltyContainer = document.getElementById('flatPenaltyContainer');
    const maxPenaltyAmountContainer = document.getElementById('maxPenaltyAmountContainer');
    const maxPenaltyPctContainer = document.getElementById('maxPenaltyPctContainer');
    const lateFeeLabel = document.getElementById('lateFeeLabel');
    const flatPenaltyLabel = document.getElementById('flatPenaltyLabel');

    if (pType === 'none') {
        graceContainer.style.display = 'none';
        lateFeeContainer.style.display = 'none';
        flatPenaltyContainer.style.display = 'none';
        maxPenaltyAmountContainer.style.display = 'none';
        maxPenaltyPctContainer.style.display = 'none';
    } else if (pType === 'percentage_one_time') {
        graceContainer.style.display = 'block';
        lateFeeContainer.style.display = 'block';
        flatPenaltyContainer.style.display = 'none';
        maxPenaltyAmountContainer.style.display = 'block';
        maxPenaltyPctContainer.style.display = 'block';
        lateFeeLabel.innerText = 'Late Penalty (% One-Time)';
    } else if (pType === 'percentage_per_day') {
        graceContainer.style.display = 'block';
        lateFeeContainer.style.display = 'block';
        flatPenaltyContainer.style.display = 'none';
        maxPenaltyAmountContainer.style.display = 'block';
        maxPenaltyPctContainer.style.display = 'block';
        lateFeeLabel.innerText = 'Late Penalty (% Per Day)';
    } else if (pType === 'flat_one_time') {
        graceContainer.style.display = 'block';
        lateFeeContainer.style.display = 'none';
        flatPenaltyContainer.style.display = 'block';
        maxPenaltyAmountContainer.style.display = 'block';
        maxPenaltyPctContainer.style.display = 'block';
        flatPenaltyLabel.innerText = 'Flat Penalty Fee (₹ One-Time)';
    } else if (pType === 'flat_per_day') {
        graceContainer.style.display = 'block';
        lateFeeContainer.style.display = 'none';
        flatPenaltyContainer.style.display = 'block';
        maxPenaltyAmountContainer.style.display = 'block';
        maxPenaltyPctContainer.style.display = 'block';
        flatPenaltyLabel.innerText = 'Flat Penalty Fee (₹ Per Day)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    togglePenaltyFields();
});
</script>
@endpush
