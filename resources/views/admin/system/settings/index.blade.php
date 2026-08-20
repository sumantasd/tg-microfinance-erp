@extends('layouts.admin')

@section('title', 'System Settings - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-gear-wide-connected text-primary me-2"></i>System & Operational Settings</h4>
        <p class="text-muted small mb-0">Configure global microfinance loan charges, fee policies, operational parameters, and system rules.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-3 shadow-sm mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Validation Errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <!-- 1. LOAN CHARGES & UPFRONT FEES SETTINGS CARD -->
    <div class="col-12 col-lg-8">
        <x-ui.card class="p-4 shadow-sm border-0 bg-white">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-percent fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0 font-heading">Loan Charges / Fees Settings</h6>
                        <small class="text-muted">Configure default Processing Fee and Insurance Fee rates</small>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">Upfront Policy</span>
            </div>

            <form action="{{ route('admin.system.settings.update-loan-charges') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="alert alert-info border border-info-subtle rounded-3 small mb-4">
                    <i class="bi bi-info-circle-fill me-1 text-info"></i>
                    <strong>Upfront Collection Business Rule:</strong> These charges are collected <strong>UPFRONT</strong> during loan sanction and are <strong>NOT included in the EMI principal amount</strong>.
                </div>

                <div class="row g-4 mb-4">
                    <!-- Processing Fee Config -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-bold text-dark mb-0"><i class="bi bi-receipt-cutoff text-primary me-1"></i> Processing Fee (%)</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="loan_processing_fee_enabled" value="1" id="proFeeEnabled" {{ old('loan_processing_fee_enabled', $settings->loan_processing_fee_enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-secondary" for="proFeeEnabled">Enable Fee</label>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="loan_processing_fee_percentage" value="{{ old('loan_processing_fee_percentage', number_format($settings->loan_processing_fee_percentage ?? 1.00, 2)) }}" class="form-control bg-white fw-bold font-monospace" placeholder="1.00">
                                <span class="input-group-text bg-white fw-bold">%</span>
                            </div>
                            <small class="text-muted d-block mt-2">Percentage charged on loan principal upfront.</small>
                        </div>
                    </div>

                    <!-- Insurance Fee Config -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-bold text-dark mb-0"><i class="bi bi-shield-check text-success me-1"></i> Insurance Fee (%)</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="loan_insurance_enabled" value="1" id="insFeeEnabled" {{ old('loan_insurance_enabled', $settings->loan_insurance_enabled ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-bold text-secondary" for="insFeeEnabled">Enable Insurance</label>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="loan_insurance_percentage" value="{{ old('loan_insurance_percentage', number_format($settings->loan_insurance_percentage ?? 1.00, 2)) }}" class="form-control bg-white fw-bold font-monospace" placeholder="1.00">
                                <span class="input-group-text bg-white fw-bold">%</span>
                            </div>
                            <small class="text-muted d-block mt-2">Percentage charged for borrower insurance coverage upfront.</small>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Save Loan Fee Settings
                    </button>
                </div>
            </form>
        </x-ui.card>
    </div>

    <!-- 2. QUICK NAVIGATION & POLICY SUMMARY -->
    <div class="col-12 col-lg-4">
        <x-ui.card class="p-4 shadow-sm border-0 bg-white">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-lock text-warning me-1"></i> Fee Policy Rules</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Historical Application Protection:</strong> Changing settings does not alter previously created loan applications.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Disbursement Gatekeeper:</strong> Loan disbursement remains locked until required upfront charges are paid.</span>
                </li>
                <li class="mb-2.5 d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>EMI Exclusion:</strong> Charges are collected upfront and do not inflate the borrower's EMI principal balance.</span>
                </li>
                <li class="d-flex gap-2">
                    <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-0.5"></i>
                    <span><strong>Accounting Integrity:</strong> Collections are automatically credited to Processing Fee Income (4210) & Insurance Income (4220).</span>
                </li>
            </ul>
        </x-ui.card>
    </div>
</div>
@endsection
