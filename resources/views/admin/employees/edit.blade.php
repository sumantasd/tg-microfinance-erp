@extends('layouts.admin')

@section('title', 'Edit Enterprise Employee - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Enterprise Employee</h4>
        <p class="text-muted small mb-0">Update profile for <strong>{{ $employee->full_name }}</strong> ({{ $employee->employee_code }}).</p>
    </div>
    <a href="{{ route('admin.employee.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Employees
    </a>
</div>

<form action="{{ route('admin.employee.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        <!-- LEFT COLUMN -->
        <div class="col-lg-8">
            <!-- SECTION 1: PROFILE & CONTACT -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-primary me-2"></i>1. Profile & Basic Contact</h6>
                
                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        <label class="form-label fw-semibold text-dark d-block">Profile Photo</label>
                        <div class="bg-light rounded border p-2 text-center">
                            <img src="{{ $employee->profile_photo_url }}" class="rounded-circle img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;">
                            <input type="file" name="profile_photo" class="form-control form-control-sm @error('profile_photo') is-invalid @enderror" accept="image/*">
                            @error('profile_photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-md-9">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control @error('first_name') is-invalid @enderror" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Middle Name</label>
                                <input type="text" name="middle_name" value="{{ old('middle_name', $employee->middle_name) }}" class="form-control @error('middle_name') is-invalid @enderror">
                                @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control @error('last_name') is-invalid @enderror" required>
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Employee ID (Code) <span class="text-danger">*</span></label>
                                <input type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}" class="form-control font-monospace @error('employee_code') is-invalid @enderror" required>
                                @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                    <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $employee->gender) === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Date of Birth</label>
                                <input type="date" name="dob" value="{{ old('dob', $employee->dob ? $employee->dob->format('Y-m-d') : '') }}" class="form-control @error('dob') is-invalid @enderror">
                                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Blood Group</label>
                                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                    <option value="">Select...</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg)
                                        <option value="{{ $bg }}" {{ old('blood_group', $employee->blood_group) === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark">Mobile Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control @error('email') is-invalid @enderror">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-dark">Emergency Contact</label>
                                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-dark">Emergency Phone</label>
                                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 2: PERSONAL INFORMATION -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-card-heading text-info me-2"></i>2. Personal Information & Government Identification</h6>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Father's Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name', $employee->father_name) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Mother's Name</label>
                        <input type="text" name="mother_name" value="{{ old('mother_name', $employee->mother_name) }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="single" {{ old('marital_status', $employee->marital_status) === 'single' ? 'selected' : '' }}>Single</option>
                            <option value="married" {{ old('marital_status', $employee->marital_status) === 'married' ? 'selected' : '' }}>Married</option>
                            <option value="divorced" {{ old('marital_status', $employee->marital_status) === 'divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="widowed" {{ old('marital_status', $employee->marital_status) === 'widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Aadhaar Card Number</label>
                        <input type="text" name="aadhaar_number" value="{{ old('aadhaar_number', $employee->aadhaar_number) }}" class="form-control font-monospace">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">PAN Card Number</label>
                        <input type="text" name="pan_number" value="{{ old('pan_number', $employee->pan_number) }}" class="form-control font-monospace text-uppercase">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Voter ID</label>
                        <input type="text" name="voter_id" value="{{ old('voter_id', $employee->voter_id) }}" class="form-control font-monospace">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Driving License</label>
                        <input type="text" name="driving_license" value="{{ old('driving_license', $employee->driving_license) }}" class="form-control font-monospace">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Passport Number</label>
                        <input type="text" name="passport_number" value="{{ old('passport_number', $employee->passport_number) }}" class="form-control font-monospace">
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 3: EMPLOYMENT & HIERARCHY -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-briefcase text-warning me-2"></i>3. Employment & Hierarchy</h6>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Company <span class="text-danger">*</span></label>
                        @if(auth()->user()->isSuperAdmin())
                            <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="">Select Company...</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ old('company_id', $employee->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control bg-light" value="{{ $employee->company->name ?? 'N/A' }}" readonly>
                            <input type="hidden" name="company_id" value="{{ $employee->company_id }}">
                        @endif
                        @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Branch Office <span class="text-danger">*</span></label>
                        @if(auth()->user()->hasRole('Branch Manager'))
                            <input type="text" class="form-control bg-light" value="{{ $employee->branch->name ?? 'N/A' }}" readonly>
                            <input type="hidden" name="branch_id" value="{{ $employee->branch_id }}">
                        @else
                            <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                <option value="">Select Branch...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id', $employee->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
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
                                <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                            <option value="">Select Designation...</option>
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}" {{ old('designation_id', $employee->designation_id) == $desig->id ? 'selected' : '' }}>{{ $desig->title }}</option>
                            @endforeach
                        </select>
                        @error('designation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Reporting Manager</label>
                        <select name="reporting_manager_id" class="form-select">
                            <option value="">Select Manager (Optional)...</option>
                            @foreach($managers as $manager)
                                <option value="{{ $manager->id }}" {{ old('reporting_manager_id', $employee->reporting_manager_id) == $manager->id ? 'selected' : '' }}>{{ $manager->full_name }} ({{ $manager->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Employment Type <span class="text-danger">*</span></label>
                        <select name="employment_type" class="form-select" required>
                            <option value="full_time" {{ old('employment_type', $employee->employment_type) === 'full_time' ? 'selected' : '' }}>Full Time</option>
                            <option value="part_time" {{ old('employment_type', $employee->employment_type) === 'part_time' ? 'selected' : '' }}>Part Time</option>
                            <option value="contract" {{ old('employment_type', $employee->employment_type) === 'contract' ? 'selected' : '' }}>Contractual</option>
                            <option value="intern" {{ old('employment_type', $employee->employment_type) === 'intern' ? 'selected' : '' }}>Internship</option>
                            <option value="probationary" {{ old('employment_type', $employee->employment_type) === 'probationary' ? 'selected' : '' }}>Probationary</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Joining Date <span class="text-danger">*</span></label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', $employee->joining_date ? $employee->joining_date->format('Y-m-d') : '') }}" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Probation End Date</label>
                        <input type="date" name="probation_end_date" value="{{ old('probation_end_date', $employee->probation_end_date ? $employee->probation_end_date->format('Y-m-d') : '') }}" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Confirmation Date</label>
                        <input type="date" name="confirmation_date" value="{{ old('confirmation_date', $employee->confirmation_date ? $employee->confirmation_date->format('Y-m-d') : '') }}" class="form-control">
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">
            <!-- SECTION 4: SALARY & BANKING -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-cash-stack text-success me-2"></i>4. Salary & Bank Details</h6>
                
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Basic Salary (₹)</label>
                        <input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" class="form-control font-monospace fw-bold text-success">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Salary Type</label>
                        <select name="salary_type" class="form-select">
                            <option value="monthly" {{ old('salary_type', $employee->salary_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="daily" {{ old('salary_type', $employee->salary_type) === 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="hourly" {{ old('salary_type', $employee->salary_type) === 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="commission" {{ old('salary_type', $employee->salary_type) === 'commission' ? 'selected' : '' }}>Commission Only</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Account Number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="form-control font-monospace">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">IFSC Code</label>
                        <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}" class="form-control font-monospace text-uppercase">
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 5: USER ACCOUNT & STATUS -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-lock text-danger me-2"></i>5. User Account & Status</h6>
                
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Employment Status</label>
                        <select name="status" class="form-select fw-bold">
                            <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="on_leave" {{ old('status', $employee->status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            <option value="resigned" {{ old('status', $employee->status) === 'resigned' ? 'selected' : '' }}>Resigned</option>
                            <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label fw-semibold text-dark">Link User Account</label>
                        <select name="user_id" class="form-select">
                            <option value="">None (No System Login)</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id', $employee->user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label fw-semibold text-dark">Assign Role</label>
                        <select name="role" class="form-select">
                            <option value="">Select Role...</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}" {{ ($employee->user && $employee->user->hasRole($r->name)) ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="login_enabled" id="login_enabled" value="1" {{ old('login_enabled', $employee->login_enabled) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="login_enabled">System Login Enabled</label>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- SECTION 6: DOCUMENTS -->
            <x-ui.card class="p-4 shadow-sm mb-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i>6. Upload Additional Documents</h6>
                
                @if($employee->documents->count() > 0)
                    <div class="mb-3">
                        <small class="fw-bold text-secondary d-block mb-2">Existing Documents:</small>
                        <ul class="list-group list-group-flush border rounded-3">
                            @foreach($employee->documents as $doc)
                                <li class="list-group-item d-flex justify-content-between align-items-center p-2 small">
                                    <span><i class="bi bi-file-earmark-pdf me-1 text-danger"></i>{{ $doc->document_title }}</span>
                                    <form action="{{ route('admin.employee.document.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Remove this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger p-0 border-0" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Upload New Document</label>
                        <input type="file" name="documents[0][file]" class="form-control form-control-sm">
                        <input type="hidden" name="documents[0][type]" value="other">
                        <input type="text" name="documents[0][title]" placeholder="Document Title (e.g. Experience Cert)" class="form-control form-control-sm mt-1">
                    </div>
                </div>
            </x-ui.card>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary rounded-pill py-2.5 fw-bold shadow">
                    <i class="bi bi-save me-1.5"></i> Update Employee Profile
                </button>
                <a href="{{ route('admin.employee.index') }}" class="btn btn-light border rounded-pill py-2 text-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
