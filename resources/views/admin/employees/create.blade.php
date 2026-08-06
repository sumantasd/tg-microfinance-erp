@extends('layouts.admin')

@section('title', 'Add Enterprise Employee - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-plus text-success me-2"></i>Add Enterprise Employee</h4>
        <p class="text-muted small mb-0">Register complete staff profile, employment terms, bank details, user login, and KYC documents.</p>
    </div>
    <a href="{{ route('admin.employee.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Employees
    </a>
</div>

<form action="{{ route('admin.employee.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="row g-4">
        <!-- LEFT COLUMN: Profile & Employment -->
        <div class="col-lg-8">
            <!-- SECTION 1: PROFILE & CONTACT -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-primary me-2"></i>1. Profile & Basic Contact</h6>
                
                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        <label class="form-label fw-semibold text-dark d-block">Profile Photo</label>
                        <div class="bg-light rounded border p-2 text-center">
                            <i class="bi bi-person-circle display-4 text-secondary"></i>
                            <input type="file" name="profile_photo" class="form-control form-control-sm mt-2 @error('profile_photo') is-invalid @enderror" accept="image/*">
                            @error('profile_photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control @error('first_name') is-invalid @enderror" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Middle Name</label>
                                <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control @error('middle_name') is-invalid @enderror">
                                @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control @error('last_name') is-invalid @enderror" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Employee ID (Code)</label>
                                <input type="text" name="employee_code" value="{{ old('employee_code') }}" class="form-control font-monospace @error('employee_code') is-invalid @enderror" placeholder="Auto-generated if blank (e.g. EMP-2026-0001)">
                                @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Date of Birth</label>
                                <input type="date" name="dob" value="{{ old('dob') }}" class="form-control @error('dob') is-invalid @enderror">
                                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Blood Group</label>
                                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                    <option value="">Select...</option>
                                    <option value="A+" {{ old('blood_group') === 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group') === 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group') === 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group') === 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="O+" {{ old('blood_group') === 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group') === 'O-' ? 'selected' : '' }}>O-</option>
                                    <option value="AB+" {{ old('blood_group') === 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group') === 'AB-' ? 'selected' : '' }}>AB-</option>
                                </select>
                                @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Mobile Number</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="+91 9876543210">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="staff@grihalaxmifinance.com">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-dark">Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="form-control @error('emergency_contact_name') is-invalid @enderror">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-dark">Emergency Contact Phone</label>
                                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 2: PERSONAL INFORMATION & IDENTIFICATION -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-card-heading text-info me-2"></i>2. Personal Information & Government Identification</h6>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Father's Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control @error('father_name') is-invalid @enderror">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Mother's Name</label>
                        <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="form-control @error('mother_name') is-invalid @enderror">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Marital Status</label>
                        <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                            <option value="single" {{ old('marital_status') === 'single' ? 'selected' : '' }}>Single</option>
                            <option value="married" {{ old('marital_status') === 'married' ? 'selected' : '' }}>Married</option>
                            <option value="divorced" {{ old('marital_status') === 'divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="widowed" {{ old('marital_status') === 'widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Aadhaar Card Number</label>
                        <input type="text" name="aadhaar_number" value="{{ old('aadhaar_number') }}" class="form-control font-monospace @error('aadhaar_number') is-invalid @enderror" placeholder="12 Digit Aadhaar">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">PAN Card Number</label>
                        <input type="text" name="pan_number" value="{{ old('pan_number') }}" class="form-control font-monospace text-uppercase @error('pan_number') is-invalid @enderror" placeholder="10 Digit PAN">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Voter ID</label>
                        <input type="text" name="voter_id" value="{{ old('voter_id') }}" class="form-control font-monospace @error('voter_id') is-invalid @enderror">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Driving License</label>
                        <input type="text" name="driving_license" value="{{ old('driving_license') }}" class="form-control font-monospace @error('driving_license') is-invalid @enderror">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Passport Number (Optional)</label>
                        <input type="text" name="passport_number" value="{{ old('passport_number') }}" class="form-control font-monospace @error('passport_number') is-invalid @enderror">
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 3: EMPLOYMENT & ORGANIZATIONAL MAPPING -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-briefcase text-warning me-2"></i>3. Employment & Hierarchy</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Company <span class="text-danger">*</span></label>
                        @if(auth()->user()->isSuperAdmin())
                            <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="">Select Company...</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->company->name ?? 'N/A' }}" readonly>
                            <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
                        @endif
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Branch Office <span class="text-danger">*</span></label>
                        @if(auth()->user()->hasRole('Branch Manager'))
                            <input type="text" class="form-control bg-light" value="{{ auth()->user()->branch->name ?? 'N/A' }}" readonly>
                            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        @else
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Branch...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                                @endforeach
                            </select>
                        @endif
                        @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                            <option value="">Select Department...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                            <option value="">Select Designation...</option>
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->title }}</option>
                            @endforeach
                        </select>
                        @error('designation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Reporting Manager</label>
                        <select name="reporting_manager_id" class="form-select @error('reporting_manager_id') is-invalid @enderror">
                            <option value="">Select Manager (Optional)...</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" {{ old('reporting_manager_id') == $manager->id ? 'selected' : '' }}>{{ $manager->full_name }} ({{ $manager->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Employment Type <span class="text-danger">*</span></label>
                        <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                            <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                            <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                            <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contractual</option>
                            <option value="intern" {{ old('employment_type') === 'intern' ? 'selected' : '' }}>Internship</option>
                            <option value="probationary" {{ old('employment_type') === 'probationary' ? 'selected' : '' }}>Probationary</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Joining Date <span class="text-danger">*</span></label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" class="form-control @error('joining_date') is-invalid @enderror" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Probation End Date</label>
                        <input type="date" name="probation_end_date" value="{{ old('probation_end_date') }}" class="form-control @error('probation_end_date') is-invalid @enderror">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Confirmation Date</label>
                        <input type="date" name="confirmation_date" value="{{ old('confirmation_date') }}" class="form-control @error('confirmation_date') is-invalid @enderror">
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- RIGHT COLUMN: Compensation, Account & Documents -->
        <div class="col-lg-4">
            <!-- SECTION 4: SALARY & BANKING -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-cash-stack text-success me-2"></i>4. Salary & Bank Details</h6>
                
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Basic Salary (₹)</label>
                        <input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', '30000.00') }}" class="form-control font-monospace fw-bold text-success @error('basic_salary') is-invalid @enderror">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Salary Type</label>
                        <select name="salary_type" class="form-select @error('salary_type') is-invalid @enderror">
                            <option value="monthly" {{ old('salary_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="daily" {{ old('salary_type') === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="hourly" {{ old('salary_type') === 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="commission" {{ old('salary_type') === 'commission' ? 'selected' : '' }}>Commission Only</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control @error('bank_name') is-invalid @enderror" placeholder="e.g. State Bank of India">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Account Number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="form-control font-monospace @error('bank_account_number') is-invalid @enderror">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">IFSC Code</label>
                        <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc') }}" class="form-control font-monospace text-uppercase @error('bank_ifsc') is-invalid @enderror" placeholder="e.g. SBIN0001234">
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 5: USER ACCOUNT & PERMISSIONS -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-lock text-danger me-2"></i>5. System User & RBAC Role</h6>
                
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Link User Account</label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                            <option value="">None (No System Login)</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Assign Role</label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror">
                            <option value="">Select Role...</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}" {{ old('role') === $r->name ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="login_enabled" id="login_enabled" value="1" {{ old('login_enabled', 1) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="login_enabled">System Login Enabled</label>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 6: UPLOAD KYC DOCUMENTS -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>6. Upload Documents</h6>
                
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Aadhaar Card File</label>
                        <input type="file" name="documents[0][file]" class="form-control form-control-sm">
                        <input type="hidden" name="documents[0][type]" value="aadhaar">
                        <input type="hidden" name="documents[0][title]" value="Aadhaar Card Proof">
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label fw-semibold text-dark">PAN Card File</label>
                        <input type="file" name="documents[1][file]" class="form-control form-control-sm">
                        <input type="hidden" name="documents[1][type]" value="pan">
                        <input type="hidden" name="documents[1][title]" value="PAN Card Proof">
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label fw-semibold text-dark">Resume / CV</label>
                        <input type="file" name="documents[2][file]" class="form-control form-control-sm">
                        <input type="hidden" name="documents[2][type]" value="resume">
                        <input type="hidden" name="documents[2][title]" value="Curriculum Vitae">
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label fw-semibold text-dark">Appointment Letter</label>
                        <input type="file" name="documents[3][file]" class="form-control form-control-sm">
                        <input type="hidden" name="documents[3][type]" value="appointment_letter">
                        <input type="hidden" name="documents[3][title]" value="Offer / Appointment Letter">
                    </div>
                </div>
            </x-ui.card>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-bold shadow">
                    <i class="bi bi-save me-1.5"></i> Save Complete Staff Profile
                </button>
                <a href="{{ route('admin.employee.index') }}" class="btn btn-light border rounded-pill py-2 text-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
