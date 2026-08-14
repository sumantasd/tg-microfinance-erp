@extends('layouts.admin')

@section('title', 'Edit Customer - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Customer Profile</h4>
        <p class="text-muted small mb-0">Update personal, contact, and address details for <strong>{{ $customer->full_name }}</strong> ({{ $customer->customer_code }}).</p>
    </div>
    <a href="{{ route('admin.customer.show', $customer->id) }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> View Profile
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the validation errors:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('admin.customer.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <x-ui.card class="p-4 shadow-sm mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-fill text-primary me-2"></i>Basic Customer Details</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Customer Code <span class="text-danger">*</span></label>
                <input type="text" name="customer_code" value="{{ old('customer_code', $customer->customer_code) }}" class="form-control font-monospace fw-bold bg-light" required readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Member Number</label>
                <input type="text" name="member_number" value="{{ old('member_number', $customer->member_number) }}" class="form-control font-monospace" placeholder="Optional Member ID">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Customer Type <span class="text-danger">*</span></label>
                <select name="customer_type" class="form-select" required>
                    <option value="individual" {{ old('customer_type', $customer->customer_type) === 'individual' ? 'selected' : '' }}>Individual Borrower / Member</option>
                    <option value="group_member" {{ old('customer_type', $customer->customer_type) === 'group_member' ? 'selected' : '' }}>Group Member (JLG / SHG)</option>
                    <option value="micro_enterprise" {{ old('customer_type', $customer->customer_type) === 'micro_enterprise' ? 'selected' : '' }}>Micro Enterprise</option>
                    <option value="corporate" {{ old('customer_type', $customer->customer_type) === 'corporate' ? 'selected' : '' }}>Corporate</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Account Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="blacklisted" {{ old('status', $customer->status) === 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                    <option value="deceased" {{ old('status', $customer->status) === 'deceased' ? 'selected' : '' }}>Deceased</option>
                    <option value="closed" {{ old('status', $customer->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name', $customer->middle_name) }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Father / Husband / Guardian Name</label>
                <input type="text" name="father_husband_guardian_name" value="{{ old('father_husband_guardian_name', $customer->father_husband_guardian_name) }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select" required>
                    <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold small">Marital Status</label>
                <select name="marital_status" class="form-select">
                    <option value="">Select Status</option>
                    <option value="single" {{ old('marital_status', $customer->marital_status) === 'single' ? 'selected' : '' }}>Single</option>
                    <option value="married" {{ old('marital_status', $customer->marital_status) === 'married' ? 'selected' : '' }}>Married</option>
                    <option value="divorced" {{ old('marital_status', $customer->marital_status) === 'divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="widowed" {{ old('marital_status', $customer->marital_status) === 'widowed' ? 'selected' : '' }}>Widowed</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number', $customer->mobile_number) }}" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Alternate Contact</label>
                <input type="text" name="alternate_contact" value="{{ old('alternate_contact', $customer->alternate_contact) }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Date of Birth</label>
                <input type="date" name="dob" value="{{ old('dob', $customer->dob ? $customer->dob->format('Y-m-d') : '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Registration Date <span class="text-danger">*</span></label>
                <input type="date" name="registration_date" value="{{ old('registration_date', $customer->registration_date ? $customer->registration_date->format('Y-m-d') : date('Y-m-d')) }}" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Update Photo</label>
                <input type="file" name="profile_photo" class="form-control" accept="image/*">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Occupation</label>
                <input type="text" name="occupation" value="{{ old('occupation', $customer->occupation) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold small">Monthly Income (₹)</label>
                <input type="number" step="0.01" name="monthly_income" value="{{ old('monthly_income', $customer->monthly_income) }}" class="form-control">
            </div>
        </div>
    </x-ui.card>

    <!-- Present Address -->
    @php
        $present = $customer->presentAddress;
        $permanent = $customer->permanentAddress;
    @endphp
    <x-ui.card class="p-4 shadow-sm mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Present Residence Address</h5>
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-bold small">Address Line <span class="text-danger">*</span></label>
                <input type="text" name="addresses[present][address_line]" value="{{ old('addresses.present.address_line', $present->address_line ?? '') }}" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Village / Locality</label>
                <input type="text" name="addresses[present][village_area]" value="{{ old('addresses.present.village_area', $present->village_area ?? '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Post Office</label>
                <input type="text" name="addresses[present][post_office]" value="{{ old('addresses.present.post_office', $present->post_office ?? '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">Police Station</label>
                <input type="text" name="addresses[present][police_station]" value="{{ old('addresses.present.police_station', $present->police_station ?? '') }}" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">District <span class="text-danger">*</span></label>
                <input type="text" name="addresses[present][district]" value="{{ old('addresses.present.district', $present->district ?? '') }}" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">State <span class="text-danger">*</span></label>
                <input type="text" name="addresses[present][state]" value="{{ old('addresses.present.state', $present->state ?? 'Bihar') }}" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold small">PIN Code <span class="text-danger">*</span></label>
                <input type="text" name="addresses[present][pin_code]" value="{{ old('addresses.present.pin_code', $present->pin_code ?? '') }}" class="form-control" required>
            </div>
        </div>
    </x-ui.card>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('admin.customer.show', $customer->id) }}" class="btn btn-light border px-4 py-2 fw-semibold">Cancel</a>
        <button type="submit" class="btn btn-warning px-4 py-2 fw-bold shadow-sm"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
    </div>
</form>
@endsection
