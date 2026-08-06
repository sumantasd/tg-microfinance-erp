@extends('layouts.admin')

@section('title', 'Edit Branch Office - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Branch Office</h4>
        <p class="text-muted small mb-0">Update branch details for <strong>{{ $branch->name }}</strong>.</p>
    </div>
    <a href="{{ route('admin.branch.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Branches
    </a>
</div>

<x-ui.card class="p-4 shadow-sm" style="max-width: 900px;">
    <form action="{{ route('admin.branch.update', $branch->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Parent Company <span class="text-danger">*</span></label>
                @if(auth()->user()->isSuperAdmin())
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        <option value="">Select Company...</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id', $branch->company_id) == $company->id ? 'selected' : '' }}>
                                {{ $company->name }} ({{ $company->code }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control bg-light" value="{{ $branch->company->name ?? 'N/A' }} ({{ $branch->company->code ?? '' }})" readonly>
                    <input type="hidden" name="company_id" value="{{ $branch->company_id }}">
                @endif
                @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Branch Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $branch->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Branch Location Code <span class="text-danger">*</span></label>
                <input type="text" name="code" value="{{ old('code', $branch->code) }}" class="form-control font-monospace @error('code') is-invalid @enderror" required>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Branch Manager</label>
                <select name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                    <option value="">Select Branch Manager (Optional)...</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}" {{ old('manager_id', $branch->manager_id) == $manager->id ? 'selected' : '' }}>
                            {{ $manager->name }} ({{ $manager->email }})
                        </option>
                    @endforeach
                </select>
                @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Branch Email Address</label>
                <input type="email" name="email" value="{{ old('email', $branch->email) }}" class="form-control @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold text-dark">Branch Phone Number <span class="text-danger">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold text-dark">Branch Office Address <span class="text-danger">*</span></label>
                <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $branch->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">City <span class="text-danger">*</span></label>
                <input type="text" name="city" value="{{ old('city', $branch->city) }}" class="form-control @error('city') is-invalid @enderror" required>
                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">State <span class="text-danger">*</span></label>
                <input type="text" name="state" value="{{ old('state', $branch->state) }}" class="form-control @error('state') is-invalid @enderror" required>
                @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Pincode <span class="text-danger">*</span></label>
                <input type="text" name="pincode" value="{{ old('pincode', $branch->pincode) }}" class="form-control @error('pincode') is-invalid @enderror" required>
                @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Vault Cash Limit (₹)</label>
                <input type="number" step="0.01" name="vault_cash_limit" value="{{ old('vault_cash_limit', $branch->vault_cash_limit) }}" class="form-control font-monospace @error('vault_cash_limit') is-invalid @enderror">
                @error('vault_cash_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold text-dark">Current Vault Balance (₹)</label>
                <input type="number" step="0.01" name="current_vault_balance" value="{{ old('current_vault_balance', $branch->current_vault_balance) }}" class="form-control font-monospace @error('current_vault_balance') is-invalid @enderror">
                @error('current_vault_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold text-dark" for="is_active">Set Branch Active</label>
                </div>
            </div>

            <div class="col-12 mt-4 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-save me-1.5"></i> Update Branch Office
                </button>
                <a href="{{ route('admin.branch.index') }}" class="btn btn-light border rounded-pill px-4 py-2 text-secondary">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
