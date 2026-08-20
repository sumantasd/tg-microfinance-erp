@extends('layouts.admin')

@section('title', 'Edit Supplier - ' . $supplier->supplier_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-warning me-2"></i>Edit Supplier: {{ $supplier->supplier_name }}
        </h4>
        <p class="text-muted small mb-0">Supplier Code: <span class="font-monospace text-primary fw-bold">{{ $supplier->supplier_code }}</span></p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-eye me-1"></i> View Profile
        </a>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Directory
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the errors below:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST">
    @csrf
    @method('PUT')

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
                                <option value="{{ $comp->id }}" {{ old('company_id', $supplier->company_id) == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} ({{ $comp->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @else
                    <input type="hidden" name="company_id" value="{{ $supplier->company_id }}">
                @endif

                <div class="col-md-6">
                    <label class="form-label fw-bold small required text-dark">Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name', $supplier->supplier_name) }}" required>
                    @error('supplier_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Company / Organization Name</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $supplier->company_name) }}">
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Supplier Type</label>
                    <select name="supplier_type" class="form-select @error('supplier_type') is-invalid @enderror" required>
                        <option value="company" {{ old('supplier_type', $supplier->supplier_type) == 'company' ? 'selected' : '' }}>Company / Enterprise</option>
                        <option value="individual" {{ old('supplier_type', $supplier->supplier_type) == 'individual' ? 'selected' : '' }}>Individual / Sole Proprietor</option>
                        <option value="distributor" {{ old('supplier_type', $supplier->supplier_type) == 'distributor' ? 'selected' : '' }}>Authorized Distributor</option>
                        <option value="manufacturer" {{ old('supplier_type', $supplier->supplier_type) == 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                        <option value="other" {{ old('supplier_type', $supplier->supplier_type) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('supplier_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person', $supplier->contact_person) }}">
                    @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Mobile Number</label>
                    <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $supplier->mobile) }}" required>
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Alternate Mobile Number</label>
                    <input type="text" name="alternate_mobile" class="form-control @error('alternate_mobile') is-invalid @enderror" value="{{ old('alternate_mobile', $supplier->alternate_mobile) }}">
                    @error('alternate_mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $supplier->email) }}">
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
                    <input type="text" name="gstin" class="form-control text-uppercase font-monospace @error('gstin') is-invalid @enderror" value="{{ old('gstin', $supplier->gstin) }}">
                    @error('gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">PAN Number</label>
                    <input type="text" name="pan" class="form-control text-uppercase font-monospace @error('pan') is-invalid @enderror" value="{{ old('pan', $supplier->pan) }}">
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
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $supplier->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">City</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $supplier->city) }}">
                    @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">State</label>
                    <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $supplier->state) }}">
                    @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">PIN Code</label>
                    <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', $supplier->pincode) }}">
                    @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold small text-dark">Country</label>
                    <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $supplier->country) }}">
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
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', $supplier->opening_balance) }}">
                    @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Opening Balance Type</label>
                    <select name="opening_balance_type" class="form-select @error('opening_balance_type') is-invalid @enderror" required>
                        <option value="payable" {{ old('opening_balance_type', $supplier->opening_balance_type) == 'payable' ? 'selected' : '' }}>Payable (We owe supplier)</option>
                        <option value="receivable" {{ old('opening_balance_type', $supplier->opening_balance_type) == 'receivable' ? 'selected' : '' }}>Receivable (Supplier owes us)</option>
                    </select>
                    @error('opening_balance_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small text-dark">Credit Limit (₹)</label>
                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', $supplier->credit_limit) }}">
                    @error('credit_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Payment Terms</label>
                    <input type="text" name="payment_terms" class="form-control @error('payment_terms') is-invalid @enderror" value="{{ old('payment_terms', $supplier->payment_terms) }}">
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
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $supplier->bank_name) }}">
                    @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Account Number</label>
                    <input type="text" name="account_number" class="form-control font-monospace @error('account_number') is-invalid @enderror" value="{{ old('account_number', $supplier->account_number) }}">
                    @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">IFSC Code</label>
                    <input type="text" name="ifsc_code" class="form-control text-uppercase font-monospace @error('ifsc_code') is-invalid @enderror" value="{{ old('ifsc_code', $supplier->ifsc_code) }}">
                    @error('ifsc_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold small text-dark">Branch Name</label>
                    <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name', $supplier->branch_name) }}">
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
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $supplier->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold small required text-dark">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', $supplier->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $supplier->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-outline-secondary rounded-pill px-4">Cancel</a>
        <button type="submit" class="btn btn-warning text-dark fw-bold shadow-sm rounded-pill px-5">
            <i class="bi bi-check-circle me-1"></i> Update Supplier Record
        </button>
    </div>
</form>
@endsection
