@extends('layouts.admin')

@section('title', 'Edit Bank Account - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Bank Account: {{ $bankAccount->bank_name }}
        </h4>
        <p class="text-muted small mb-0">Update banking information and general ledger links.</p>
    </div>
    <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Bank Accounts
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4" style="max-width: 800px;">
    <form action="{{ route('admin.accounting.bank-accounts.update', $bankAccount->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small">Bank Name <span class="text-danger">*</span></label>
                <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $bankAccount->bank_name) }}" required>
                @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Holder / Title <span class="text-danger">*</span></label>
                <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name', $bankAccount->account_name) }}" required>
                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Number <span class="text-danger">*</span></label>
                <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number', $bankAccount->account_number) }}" required>
                @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">IFSC Code</label>
                <input type="text" name="ifsc_code" class="form-control @error('ifsc_code') is-invalid @enderror" value="{{ old('ifsc_code', $bankAccount->ifsc_code) }}">
                @error('ifsc_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Operating Branch</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Head Office / All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $bankAccount->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Bank Branch Location</label>
                <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name', $bankAccount->branch_name) }}">
                @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Linked General Ledger Account</label>
                <select name="chart_of_account_id" class="form-select @error('chart_of_account_id') is-invalid @enderror">
                    <option value="">Select Asset Account (e.g. 1130)</option>
                    @foreach($bankAccountsCoa as $coa)
                        <option value="{{ $coa->id }}" {{ old('chart_of_account_id', $bankAccount->chart_of_account_id) == $coa->id ? 'selected' : '' }}>
                            {{ $coa->account_code }} — {{ $coa->account_name }}
                        </option>
                    @endforeach
                </select>
                @error('chart_of_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Opening Balance (₹)</label>
                <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', $bankAccount->opening_balance) }}">
                @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $bankAccount->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small text-dark" for="isActiveSwitch">Active Account</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Update Account</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
