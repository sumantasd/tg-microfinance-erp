@extends('layouts.admin')

@section('title', 'Edit User' . ' - ' . config('app.name'))

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
                <select name="role" id="userRoleSelect" class="form-select bg-light" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">
                    Assigned Branch <span id="branchRequiredBadge" class="text-danger" style="display: none;">*</span>
                </label>
                <select name="branch_id" id="userBranchSelect" class="form-select bg-light">
                    <option value="">-- Select Active Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }} ({{ $branch->code }})
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted d-block mt-1" id="branchHelpText">
                    Mandatory when role is <strong>Branch Manager</strong>.
                </small>
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

            <div class="col-12 mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-person-badge text-primary me-1"></i> Employee Profile Mapping
                    </h6>
                    @if($linkedEmployee)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 font-monospace">
                            <i class="bi bi-check-circle-fill me-1"></i>Linked: {{ $linkedEmployee->employee_code }}
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1.5 font-monospace">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>No Linked Employee Profile
                        </span>
                    @endif
                </div>
                <p class="text-muted small mb-3">Ensure this user account is linked to an active Employee profile to enable the Employee Duty & Live Attendance tracker.</p>

                @if($linkedEmployee)
                    <div class="card bg-light border p-3 rounded-3 mb-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">EMPLOYEE NAME & CODE</small>
                                <strong class="text-dark">{{ $linkedEmployee->full_name }}</strong>
                                <span class="badge bg-secondary font-monospace ms-1">{{ $linkedEmployee->employee_code }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">DESIGNATION</small>
                                <span>{{ $linkedEmployee->designation?->title ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">ASSIGNED BRANCH</small>
                                <span>{{ $linkedEmployee->branch?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="unlink_employee" value="1" id="unlinkEmployeeCheckbox">
                                    <label class="form-check-label text-danger small fw-bold" for="unlinkEmployeeCheckbox">
                                        Unlink Profile
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">
                    {{ $linkedEmployee ? 'Reassign to Different Employee' : 'Link to Existing Employee' }}
                </label>
                <select name="employee_record_id" id="employeeRecordSelect" class="form-select bg-light">
                    <option value="">-- {{ $linkedEmployee ? 'Keep Current Profile' : 'Select Employee Profile...' }} --</option>
                    @foreach($availableEmployees as $emp)
                        <option value="{{ $emp->id }}" data-branch="{{ $emp->branch_id }}" {{ old('employee_record_id') == $emp->id || ($linkedEmployee && $linkedEmployee->id == $emp->id && !old('employee_record_id')) ? 'selected' : '' }}>
                            {{ $emp->full_name }} ({{ $emp->employee_code }}) - {{ $emp->branch?->name ?? 'No Branch' }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Only active unlinked employees are available for reassignment.</small>
            </div>

            @if(!$linkedEmployee)
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="auto_create_employee" value="1" id="autoCreateEmployeeCheckbox" {{ old('auto_create_employee', true) ? 'checked' : '' }}>
                        <label class="form-check-label small fw-bold text-dark" for="autoCreateEmployeeCheckbox">
                            Auto-create and link Employee profile
                        </label>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            Provisions an Employee record with this user's details and assigns default designation.
                        </small>
                    </div>
                </div>
            @endif

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update User Account
                </button>
                <a href="{{ route('admin.system.users.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('userRoleSelect');
    const branchSelect = document.getElementById('userBranchSelect');
    const branchRequiredBadge = document.getElementById('branchRequiredBadge');

    function toggleBranchRequirement() {
        if (roleSelect && roleSelect.value === 'Branch Manager') {
            if (branchRequiredBadge) branchRequiredBadge.style.display = 'inline';
            if (branchSelect) branchSelect.setAttribute('required', 'required');
        } else {
            if (branchRequiredBadge) branchRequiredBadge.style.display = 'none';
            if (branchSelect) branchSelect.removeAttribute('required');
        }
    }

    if (roleSelect) {
        roleSelect.addEventListener('change', toggleBranchRequirement);
        toggleBranchRequirement();
    }
});
</script>
@endsection
