@extends('layouts.admin')

@section('title', 'Add Ledger Account - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-plus-circle text-primary me-2"></i>Add General Ledger Account
        </h4>
        <p class="text-muted small mb-0">Create a new ledger account under the Chart of Accounts.</p>
    </div>
    <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Accounts
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4" style="max-width: 800px;">
    <form action="{{ route('admin.accounting.chart-of-accounts.store') }}" method="POST">
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

            <div class="col-md-4">
                <label class="form-label fw-bold small">Account Code <span class="text-danger">*</span></label>
                <input type="text" name="account_code" class="form-control @error('account_code') is-invalid @enderror" value="{{ old('account_code') }}" placeholder="e.g. 1140, 5360" required>
                @error('account_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold small">Account Name <span class="text-danger">*</span></label>
                <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" placeholder="e.g. Field Collection Petty Cash - Patna" required>
                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Classification / Primary Type <span class="text-danger">*</span></label>
                <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                    <option value="asset" {{ old('account_type') === 'asset' ? 'selected' : '' }}>1000 — Asset</option>
                    <option value="liability" {{ old('account_type') === 'liability' ? 'selected' : '' }}>2000 — Liability</option>
                    <option value="equity" {{ old('account_type') === 'equity' ? 'selected' : '' }}>3000 — Equity</option>
                    <option value="revenue" {{ old('account_type') === 'revenue' ? 'selected' : '' }}>4000 — Revenue</option>
                    <option value="expense" {{ old('account_type') === 'expense' ? 'selected' : '' }}>5000 — Expense</option>
                </select>
                @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Group <span class="text-danger">*</span></label>
                <input type="text" name="account_group" class="form-control @error('account_group') is-invalid @enderror" value="{{ old('account_group', 'current_asset') }}" placeholder="e.g. current_asset, administrative_expense" required>
                @error('account_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">Parent Header Account</label>
                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                    <option value="">None (Top-Level Account)</option>
                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->account_code }} — {{ $parent->account_name }} ({{ ucfirst($parent->account_type) }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">Description / Accounting Remarks</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Specify account purpose and transaction rules...">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small text-dark" for="isActiveSwitch">Active for Posting</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save Account</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
