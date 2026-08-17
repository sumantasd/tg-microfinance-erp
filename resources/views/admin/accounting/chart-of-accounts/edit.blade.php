@extends('layouts.admin')

@section('title', 'Edit Ledger Account - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Ledger Account: {{ $chartOfAccount->account_code }}
        </h4>
        <p class="text-muted small mb-0">Update account properties and classifications.</p>
    </div>
    <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Accounts
    </a>
</div>

<x-ui.card class="shadow-sm border-0 p-4" style="max-width: 800px;">
    <form action="{{ route('admin.accounting.chart-of-accounts.update', $chartOfAccount->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Account Code</label>
                <input type="text" class="form-control bg-light font-monospace fw-bold" value="{{ $chartOfAccount->account_code }}" disabled>
                <div class="form-text small">Account codes are permanent for GL integrity.</div>
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold small">Account Name <span class="text-danger">*</span></label>
                <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name', $chartOfAccount->account_name) }}" required>
                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Classification</label>
                <input type="text" class="form-control bg-light text-uppercase fw-bold" value="{{ $chartOfAccount->account_type }}" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Account Group <span class="text-danger">*</span></label>
                <input type="text" name="account_group" class="form-control @error('account_group') is-invalid @enderror" value="{{ old('account_group', $chartOfAccount->account_group) }}" required>
                @error('account_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">Parent Header Account</label>
                <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                    <option value="">None (Top-Level Account)</option>
                    @foreach($parentAccounts as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $chartOfAccount->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->account_code }} — {{ $parent->account_name }} ({{ ucfirst($parent->account_type) }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-bold small">Description / Accounting Remarks</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $chartOfAccount->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $chartOfAccount->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold small text-dark" for="isActiveSwitch">Active for Posting</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Update Account</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
