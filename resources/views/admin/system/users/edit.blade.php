@extends('layouts.admin')

@section('title', 'Edit User - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit User: {{ $user->name }}</h4>
        <p class="text-muted small mb-0">Update account details, role permissions, and status.</p>
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
    <form action="{{ route('admin.system.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Staff Email Address *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Employee ID</label>
                <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Mobile Phone Number</label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Assigned Role *</label>
                <select name="role" class="form-select bg-light" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Account Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active (Can Login)</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="locked" {{ old('status', $user->status) === 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">New Password (Leave blank to keep unchanged)</label>
                <input type="password" name="password" class="form-control bg-light" placeholder="••••••••">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update User Account
                </button>
                <a href="{{ route('admin.system.users.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
