@extends('layouts.admin')

@section('title', 'Register Customer - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-plus text-primary me-2"></i>New Customer Registration</h4>
        <p class="text-muted small mb-0">Register a new borrower or savings member with complete KYC, guarantor, and nominee details.</p>
    </div>
    <a href="{{ route('admin.customer.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Listing
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

<form action="{{ route('admin.customer.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- Nav Tabs for Registration Form -->
    <ul class="nav nav-pills custom-tabs mb-4 bg-white p-2 rounded-3 border shadow-sm" id="customerFormTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-item-btn nav-link active fw-bold px-3 py-2 me-1" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab"><i class="bi bi-person-lines-fill me-1.5"></i> 1. Basic Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-item-btn nav-link fw-bold px-3 py-2 me-1" id="address-tab" data-bs-toggle="tab" data-bs-target="#address" type="button" role="tab"><i class="bi bi-geo-alt-fill me-1.5"></i> 2. Address</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-item-btn nav-link fw-bold px-3 py-2 me-1" id="kyc-tab" data-bs-toggle="tab" data-bs-target="#kyc" type="button" role="tab"><i class="bi bi-shield-check me-1.5"></i> 3. KYC Document</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-item-btn nav-link fw-bold px-3 py-2 me-1" id="guarantor-tab" data-bs-toggle="tab" data-bs-target="#guarantor" type="button" role="tab"><i class="bi bi-people-fill me-1.5"></i> 4. Guarantor</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-item-btn nav-link fw-bold px-3 py-2" id="nominee-tab" data-bs-toggle="tab" data-bs-target="#nominee" type="button" role="tab"><i class="bi bi-heart-fill me-1.5"></i> 5. Nominee</button>
        </li>
    </ul>

    <div class="tab-content" id="customerFormTabsContent">
        <!-- 1. BASIC INFORMATION TAB -->
        <div class="tab-pane fade show active" id="basic" role="tabpanel">
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-fill text-primary me-2"></i>Personal & Account Information</h5>
                <div class="row g-3">
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRole('Company Admin'))
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Branch <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Customer Type <span class="text-danger">*</span></label>
                        <select name="customer_type" class="form-select" required>
                            <option value="individual" {{ old('customer_type') === 'individual' ? 'selected' : '' }}>Individual Borrower / Member</option>
                            <option value="group_member" {{ old('customer_type') === 'group_member' ? 'selected' : '' }}>Group Member (JLG / SHG)</option>
                            <option value="micro_enterprise" {{ old('customer_type') === 'micro_enterprise' ? 'selected' : '' }}>Micro Enterprise</option>
                            <option value="corporate" {{ old('customer_type') === 'corporate' ? 'selected' : '' }}>Corporate</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control" placeholder="e.g. Ramesh" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control" placeholder="e.g. Kumar">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control" placeholder="e.g. Sharma" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Father / Husband / Guardian Name</label>
                        <input type="text" name="father_husband_guardian_name" value="{{ old('father_husband_guardian_name') }}" class="form-control" placeholder="Full guardian/spouse name">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="single" {{ old('marital_status') === 'single' ? 'selected' : '' }}>Single</option>
                            <option value="married" {{ old('marital_status') === 'married' ? 'selected' : '' }}>Married</option>
                            <option value="divorced" {{ old('marital_status') === 'divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="widowed" {{ old('marital_status') === 'widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" placeholder="10-digit mobile number" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Alternate Contact</label>
                        <input type="text" name="alternate_contact" value="{{ old('alternate_contact') }}" class="form-control" placeholder="Landline / Alt mobile">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="e.g. customer@example.com">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Date of Birth</label>
                        <input type="date" name="dob" value="{{ old('dob') }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Registration Date <span class="text-danger">*</span></label>
                        <input type="date" name="registration_date" value="{{ old('registration_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Occupation</label>
                        <input type="text" name="occupation" value="{{ old('occupation') }}" class="form-control" placeholder="e.g. Retail Shopkeeper, Farmer, Driver">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Monthly Income (₹)</label>
                        <input type="number" step="0.01" name="monthly_income" value="{{ old('monthly_income') }}" class="form-control" placeholder="e.g. 25000">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Remarks / Internal Notes</label>
                        <textarea name="remarks" rows="2" class="form-control" placeholder="Any special notes or observations...">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- 2. ADDRESS TAB -->
        <div class="tab-pane fade" id="address" role="tabpanel">
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Present Residence Address</h5>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Address Line <span class="text-danger">*</span></label>
                        <input type="text" name="addresses[present][address_line]" value="{{ old('addresses.present.address_line') }}" class="form-control" placeholder="House/Flat No., Street, Landmark" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Village / Locality</label>
                        <input type="text" name="addresses[present][village_area]" value="{{ old('addresses.present.village_area') }}" class="form-control" placeholder="e.g. Rampur">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Post Office</label>
                        <input type="text" name="addresses[present][post_office]" value="{{ old('addresses.present.post_office') }}" class="form-control" placeholder="e.g. Rampur HO">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Police Station</label>
                        <input type="text" name="addresses[present][police_station]" value="{{ old('addresses.present.police_station') }}" class="form-control" placeholder="e.g. Sadar PS">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">District <span class="text-danger">*</span></label>
                        <input type="text" name="addresses[present][district]" value="{{ old('addresses.present.district') }}" class="form-control" placeholder="e.g. Patna" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">State <span class="text-danger">*</span></label>
                        <input type="text" name="addresses[present][state]" value="{{ old('addresses.present.state', 'Bihar') }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">PIN Code <span class="text-danger">*</span></label>
                        <input type="text" name="addresses[present][pin_code]" value="{{ old('addresses.present.pin_code') }}" class="form-control" placeholder="6-digit PIN" required>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2"><i class="bi bi-house-door-fill text-secondary me-2"></i>Permanent Address (Optional)</h5>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Address Line</label>
                        <input type="text" name="addresses[permanent][address_line]" value="{{ old('addresses.permanent.address_line') }}" class="form-control" placeholder="Same as present or permanent address">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Village / Locality</label>
                        <input type="text" name="addresses[permanent][village_area]" value="{{ old('addresses.permanent.village_area') }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">District</label>
                        <input type="text" name="addresses[permanent][district]" value="{{ old('addresses.permanent.district') }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">State</label>
                        <input type="text" name="addresses[permanent][state]" value="{{ old('addresses.permanent.state') }}" class="form-control">
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- 3. KYC TAB -->
        <div class="tab-pane fade" id="kyc" role="tabpanel">
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-shield-check text-success me-2"></i>Initial Primary KYC Document</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">KYC Document Type</label>
                        <select name="kyc[0][type]" class="form-select">
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="pan">PAN Card</option>
                            <option value="voter_id">Voter ID Card</option>
                            <option value="ration_card">Ration Card</option>
                            <option value="driving_license">Driving License</option>
                            <option value="passport">Passport</option>
                            <option value="other">Other Identity Document</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Document Number</label>
                        <input type="text" name="kyc[0][number]" class="form-control" placeholder="e.g. 12-digit Aadhaar / 10-char PAN">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">KYC Document Upload (PDF/Image)</label>
                        <input type="file" name="kyc[0][file]" class="form-control" accept=".pdf,image/*">
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- 4. GUARANTOR TAB -->
        <div class="tab-pane fade" id="guarantor" role="tabpanel">
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-people-fill text-warning me-2"></i>Primary Guarantor Details</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Guarantor Full Name</label>
                        <input type="text" name="guarantors[0][full_name]" class="form-control" placeholder="Guarantor name">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Relationship with Customer</label>
                        <input type="text" name="guarantors[0][relationship]" class="form-control" placeholder="e.g. Brother, Neighbor, Friend">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Mobile Number</label>
                        <input type="text" name="guarantors[0][mobile]" class="form-control" placeholder="10-digit mobile number">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small">Guarantor Residential Address</label>
                        <input type="text" name="guarantors[0][address]" class="form-control" placeholder="Full residential address of guarantor">
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- 5. NOMINEE TAB -->
        <div class="tab-pane fade" id="nominee" role="tabpanel">
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-heart-fill text-danger me-2"></i>Primary Nominee Details</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Nominee Full Name</label>
                        <input type="text" name="nominees[0][nominee_name]" class="form-control" placeholder="Nominee full name">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Relationship</label>
                        <input type="text" name="nominees[0][relationship]" class="form-control" placeholder="e.g. Spouse, Son, Daughter">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Share Percentage (%)</label>
                        <input type="number" step="0.01" name="nominees[0][share_percentage]" value="100.00" class="form-control">
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Submit Action Bar -->
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('admin.customer.index') }}" class="btn btn-light border px-4 py-2 fw-semibold">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm"><i class="bi bi-check-lg me-1"></i> Register Customer</button>
    </div>
</form>
@endsection
