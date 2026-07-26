@extends('layouts.admin')

@section('title', 'Add New User - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add New Staff User</h4>
        <p class="text-muted small mb-0">Create staff account credentials, assign roles, and set initial status.</p>
    </div>
    <a href="{{ route('admin.system.users.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to User List
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix validation errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.system.users.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Full Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control bg-light" placeholder="John Doe" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Staff Email Address *</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control bg-light" placeholder="john.doe@tgmicrofinance.com" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Employee ID</label>
                <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-control bg-light" placeholder="EMP-2025-001">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Mobile Phone Number</label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control bg-light" placeholder="+1 (555) 000-0000">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Assigned Role *</label>
                <select name="role" class="form-select bg-light" required>
                    <option value="">Select Role Assignment...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Account Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Can Login)</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="locked" {{ old('status') === 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Password *</label>
                <input type="password" name="password" class="form-control bg-light" placeholder="••••••••" required>
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Minimum 8 chars, uppercase, lowercase, number, special char.</small>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save User Account
                </button>
                <a href="{{ route('admin.system.users.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
