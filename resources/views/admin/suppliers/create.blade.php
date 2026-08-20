@extends('layouts.admin')

@section('title', 'Add New Supplier - Grihalaxmi Finance ERP Pro')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-person-plus-fill text-primary me-2"></i>Add New Supplier / Vendor
        </h4>
        <p class="text-muted small mb-0">Register a new product vendor, distributor, or manufacturer into the system.</p>
    </div>
    <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the following errors:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.suppliers.store') }}" method="POST">
    @csrf

    <!-- 1. Basic Information -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>1. Basic Information</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @if(auth()->user() && auth()->user()->isSuperAdmin())
                    <div class="col-md-12 mb-2">
                        <label class="form-label fw-bold small required text-dark">Enterprise Company</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}" {{ old('company_id', auth()->user()->company_id) == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} ({{ $comp->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @else
                    <input type="hidden" name="company_id" value="{{ auth()->user() ? auth()->user()->company_id : '' }}">
                @endif

                <div class="col-md-6">
                    <label class="form-label fw-bold small required text-dark">Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name') }}" placeholder="e.g. Acme Electronics Pvt Ltd" required>
                    @error('supplier_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Company / Organization Name</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="e.g. Acme Group Inc.">
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Supplier Type</label>
                    <select name="supplier_type" class="form-select @error('supplier_type') is-invalid @enderror" required>
                        <option value="company" {{ old('supplier_type') == 'company' ? 'selected' : '' }}>Company / Enterprise</option>
                        <option value="individual" {{ old('supplier_type') == 'individual' ? 'selected' : '' }}>Individual / Sole Proprietor</option>
                        <option value="distributor" {{ old('supplier_type') == 'distributor' ? 'selected' : '' }}>Authorized Distributor</option>
                        <option value="manufacturer" {{ old('supplier_type') == 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                        <option value="other" {{ old('supplier_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('supplier_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" placeholder="e.g. Rahul Sharma">
                    @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="10-digit mobile number" required>
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Alternate Mobile Number</label>
                    <input type="text" name="alternate_mobile" class="form-control @error('alternate_mobile') is-invalid @enderror" value="{{ old('alternate_mobile') }}" placeholder="Secondary contact number">
                    @error('alternate_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="vendor@example.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Tax Information -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text text-success me-2"></i>2. Tax & Statutory Information</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">GSTIN Number</label>
                    <input type="text" name="gstin" class="form-control text-uppercase font-monospace @error('gstin') is-invalid @enderror" value="{{ old('gstin') }}" placeholder="15-digit GSTIN (e.g. 27AAAAA0000A1Z5)">
                    @error('gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">PAN Number</label>
                    <input type="text" name="pan" class="form-control text-uppercase font-monospace @error('pan') is-invalid @enderror" value="{{ old('pan') }}" placeholder="10-digit PAN (e.g. ABCDE1234F)">
                    @error('pan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Address -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-geo-alt text-danger me-2"></i>3. Address Details</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold small text-dark">Street Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="Full office/warehouse address">{{ old('address') }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">City</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" placeholder="City">
                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">State</label>
                    <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state') }}" placeholder="State">
                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">PIN Code</label>
                    <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" placeholder="PIN Code">
                    @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Country</label>
                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', 'India') }}">
                    @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Financial Information -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-wallet2 text-warning me-2"></i>4. Financial & Credit Information</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Opening Balance (₹)</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', '0.00') }}">
                    @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Opening Balance Type</label>
                    <select name="opening_balance_type" class="form-select @error('opening_balance_type') is-invalid @enderror" required>
                        <option value="payable" {{ old('opening_balance_type', 'payable') == 'payable' ? 'selected' : '' }}>Payable (We owe supplier)</option>
                        <option value="receivable" {{ old('opening_balance_type') == 'receivable' ? 'selected' : '' }}>Receivable (Supplier owes us)</option>
                    </select>
                    @error('opening_balance_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Credit Limit (₹)</label>
                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', '0.00') }}">
                    @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Payment Terms</label>
                    <input type="text" name="payment_terms" class="form-control @error('payment_terms') is-invalid @enderror" value="{{ old('payment_terms') }}" placeholder="e.g. Net 30, COD, 50% Advance">
                    @error('payment_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Bank Information -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bank text-info me-2"></i>5. Supplier Bank Account Details</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="e.g. HDFC Bank">
                    @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Account Number</label>
                    <input type="text" name="account_number" class="form-control font-monospace @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="Bank Account Number">
                    @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">IFSC Code</label>
                    <input type="text" name="ifsc_code" class="form-control text-uppercase font-monospace @error('ifsc_code') is-invalid @enderror" value="{{ old('ifsc_code') }}" placeholder="IFSC Code (e.g. HDFC0001234)">
                    @error('ifsc_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Branch Name</label>
                    <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name') }}" placeholder="Bank Branch Name">
                    @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- 6. Additional Notes & Status -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-light py-3 border-0">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle text-secondary me-2"></i>6. Status & Internal Notes</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold small text-dark">Notes / Internal Remarks</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Special pricing agreements, terms, or notes">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Initial Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
        <button type="submit" class="btn btn-primary fw-bold shadow-sm rounded-pill px-5">
            <i class="bi bi-save me-1"></i> Save Supplier Record
        </button>
    </div>
</form>
@endsection
