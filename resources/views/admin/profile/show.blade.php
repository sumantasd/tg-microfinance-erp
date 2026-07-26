@extends('layouts.admin')

@section('title', 'My Profile - TG Microfinance ERP')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge text-primary me-2"></i>My Staff Profile</h4>
    <p class="text-muted small mb-0">Manage your personal credentials, contact details, and account security.</p>
</div>

<div class="row g-4">
    <!-- Left Column: User Summary Card & Digital ID Slots -->
    <div class="col-lg-4">
        <x-ui.card class="p-4 shadow-sm text-center mb-4">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-1 mx-auto mb-3 shadow" style="width: 90px; height: 90px;">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <h5 class="fw-bold text-dark mb-1">{{ $user->name }}</h5>
            <p class="text-muted small mb-2">{{ $user->email }}</p>
            @foreach($user->roles as $role)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace mb-3">{{ $role->name }}</span>
            @endforeach
            <hr class="my-3">
            <div class="text-start small">
                <div class="mb-2"><strong class="text-secondary">Employee ID:</strong> <span class="font-monospace text-dark">{{ $user->employee_id ?? 'EMP-ADMIN-001' }}</span></div>
                <div class="mb-2"><strong class="text-secondary">Mobile Phone:</strong> <span class="text-dark">{{ $user->mobile_number ?? 'Not Set' }}</span></div>
                <div class="mb-2"><strong class="text-secondary">Assigned Branch:</strong> <span class="text-dark">Head Office Branch</span></div>
                <div><strong class="text-secondary">Account Status:</strong> <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span></div>
            </div>
        </x-ui.card>

        <!-- Future Digital ID & Signature Cards -->
        <x-ui.card class="p-4 shadow-sm bg-light text-center">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-person text-info me-2"></i>Digital Identification</h6>
            <p class="text-muted small mb-3">Staff Digital ID card and biometric signature upload slots.</p>
            <div class="p-3 bg-white rounded border border-dashed mb-2">
                <small class="text-muted font-monospace">[ Future Digital Signature Upload Slot ]</small>
            </div>
            <div class="p-3 bg-white rounded border border-dashed">
                <small class="text-muted font-monospace">[ Future Digital ID Verification Slot ]</small>
            </div>
        </x-ui.card>
    </div>

    <!-- Right Column: Profile Update & Password Change Forms -->
    <div class="col-lg-8">
        <!-- Update Profile Card -->
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-gear text-primary me-2"></i>Update Personal Details</h5>
            <form action="{{ route('admin.profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control bg-light" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Email Address *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control bg-light" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Mobile Phone Number</label>
                        <input type="text" name="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}" class="form-control bg-light">
                    </div>

                    <div class="col-12 pt-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i> Update Profile Information
                        </button>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Change Password Card -->
        <x-ui.card class="p-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock text-warning me-2"></i>Change Staff Password</h5>
            <form action="{{ route('admin.profile.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Current Password *</label>
                        <input type="password" name="current_password" class="form-control bg-light" placeholder="••••••••" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">New Password *</label>
                        <input type="password" name="password" class="form-control bg-light" placeholder="••••••••" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Confirm New Password *</label>
                        <input type="password" name="password_confirmation" class="form-control bg-light" placeholder="••••••••" required>
                    </div>

                    <div class="col-12 pt-2">
                        <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 py-2 fw-bold shadow-sm">
                            <i class="bi bi-key me-1"></i> Update Account Password
                        </button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
