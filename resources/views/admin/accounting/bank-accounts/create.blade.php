@extends('layouts.admin')

@section('title', 'Register Bank Account - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-plus-circle text-primary me-2"></i>Register Bank Account
        </h4>
        <p class="text-muted small mb-0">Link an organizational bank account to the General Ledger.</p>
    </div>
    <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Bank Accounts
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4" style="max-width: 800px;">
    <form action="{{ route('admin.accounting.bank-accounts.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-12">
                    <label class="form-label fw-bold small">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', $companyId) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @endif

            <div class="col-md-6">
                <label class="form-label fw-bold small">Bank Name <span class="text-danger">*</span></label>
                <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="e.g. State Bank of India, HDFC Bank" required>
                @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Holder / Title <span class="text-danger">*</span></label>
                <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" placeholder="e.g. Grihalaxmi Current Operating A/c" required>
                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Number <span class="text-danger">*</span></label>
                <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="e.g. 384729104820" required>
                @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">IFSC Code</label>
                <input type="text" name="ifsc_code" class="form-control @error('ifsc_code') is-invalid @enderror" value="{{ old('ifsc_code') }}" placeholder="e.g. SBIN0001234">
                @error('ifsc_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Operating Branch</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Head Office / All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Bank Branch Location</label>
                <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name') }}" placeholder="e.g. Fraser Road, Patna">
                @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Linked General Ledger Account</label>
                <select name="chart_of_account_id" class="form-select @error('chart_of_account_id') is-invalid @enderror">
                    <option value="">Select Asset Account (e.g. 1130)</option>
                    @foreach($bankAccountsCoa as $coa)
                        <option value="{{ $coa->id }}" {{ old('chart_of_account_id') == $coa->id ? 'selected' : '' }}>
                            {{ $coa->account_code }} — {{ $coa->account_name }}
                        </option>
                    @endforeach
                </select>
                @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Opening Balance (₹)</label>
                <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', '0.00') }}">
                @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small text-dark" for="isActiveSwitch">Active Account</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Register Account</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
