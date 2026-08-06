@extends('layouts.admin')

@section('title', $employee->full_name . ' Profile - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge text-success me-2"></i>{{ $employee->full_name }}</h4>
        <p class="text-muted small mb-0">Employee Code: <span class="font-monospace fw-bold text-primary">{{ $employee->employee_code }}</span> | UUID: <span class="font-monospace text-muted small">{{ $employee->uuid }}</span></p>
    </div>
    <div class="d-flex gap-2">
        @can('employee.edit')
            <a href="{{ route('admin.employee.edit', $employee->id) }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Profile
            </a>
        @endcan
        <a href="{{ route('admin.employee.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT SIDEBAR CARD -->
    <div class="col-lg-4">
        <x-ui.card class="p-4 text-center shadow-sm mb-4">
            <img src="{{ $employee->profile_photo_url }}" class="rounded-circle img-thumbnail mb-3 shadow-sm" style="width: 110px; height: 110px; object-fit: cover;">
            <h5 class="fw-bold text-dark mb-1">{{ $employee->full_name }}</h5>
            <div class="font-monospace text-primary fw-bold mb-1">{{ $employee->employee_code }}</div>
            <div class="text-muted small mb-3">{{ $employee->designation->title ?? 'N/A' }} ({{ $employee->department->name ?? 'N/A' }})</div>
            
            <div class="mb-4 d-flex justify-content-center gap-1.5">
                @if($employee->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5"><i class="bi bi-check-circle me-1"></i>Active</span>
                @elseif($employee->status === 'on_leave')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1.5"><i class="bi bi-clock me-1"></i>On Leave</span>
                @elseif($employee->status === 'resigned')
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5"><i class="bi bi-person-dash me-1"></i>Resigned</span>
                @else
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5"><i class="bi bi-x-circle me-1"></i>Terminated</span>
                @endif

                <span class="badge bg-info-subtle text-info border rounded-pill px-3 py-1.5">{{ strtoupper(str_replace('_', ' ', $employee->employment_type)) }}</span>
            </div>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Company:</span>
                    <span class="fw-semibold text-dark">{{ $employee->company->name ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Branch Office:</span>
                    <span class="fw-semibold text-dark">{{ $employee->branch->name ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Reporting Manager:</span>
                    <span class="fw-semibold text-dark">{{ $employee->reportingManager->full_name ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Joining Date:</span>
                    <span class="fw-semibold text-dark">{{ $employee->joining_date ? $employee->joining_date->format('M d, Y') : 'N/A' }}</span>
                </div>
            </div>
        </x-ui.card>

        <!-- SYSTEM ACCOUNT CARD -->
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-lock text-danger me-2"></i>System User Account</h6>
            @if($employee->user)
                <div class="small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Account Name:</span>
                        <span class="fw-bold text-dark">{{ $employee->user->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Login Email:</span>
                        <span class="fw-semibold text-dark">{{ $employee->user->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Assigned Roles:</span>
                        <span class="badge bg-primary-subtle text-primary">{{ implode(', ', $employee->user->getRoleNames()->toArray()) ?: 'No Role' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Login Access:</span>
                        <span class="{{ $employee->login_enabled ? 'text-success fw-bold' : 'text-danger fw-bold' }}">{{ $employee->login_enabled ? 'ENABLED' : 'DISABLED' }}</span>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-0">No system user account linked to this staff profile.</p>
            @endif
        </x-ui.card>
    </div>

    <!-- MAIN RIGHT DETAILS -->
    <div class="col-lg-8">
        <!-- TABBED NAVIGATION OR SECTIONS -->
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-card-heading text-info me-2"></i>Personal & Contact Details</h6>
            <div class="row g-3 text-dark small">
                <div class="col-md-4">
                    <small class="text-muted d-block">Full Name</small>
                    <span class="fw-semibold text-dark fs-6">{{ $employee->full_name }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Gender & Blood Group</small>
                    <span class="fw-semibold text-dark">{{ ucfirst($employee->gender) }} {{ $employee->blood_group ? "({$employee->blood_group})" : '' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Date of Birth</small>
                    <span class="fw-semibold text-dark">{{ $employee->dob ? $employee->dob->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Mobile Phone</small>
                    <span class="fw-semibold text-dark">{{ $employee->phone ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Email Address</small>
                    <span class="fw-semibold text-dark">{{ $employee->email ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Emergency Contact</small>
                    <span class="fw-semibold text-dark">{{ $employee->emergency_contact_name }} {{ $employee->emergency_contact_phone ? "({$employee->emergency_contact_phone})" : '' }}</span>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-file-person text-primary me-2"></i>Family & Identification Numbers</h6>
            <div class="row g-3 text-dark small">
                <div class="col-md-4">
                    <small class="text-muted d-block">Father's Name</small>
                    <span class="fw-semibold text-dark">{{ $employee->father_name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Mother's Name</small>
                    <span class="fw-semibold text-dark">{{ $employee->mother_name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Marital Status</small>
                    <span class="fw-semibold text-dark">{{ ucfirst($employee->marital_status) }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Aadhaar Number</small>
                    <span class="font-monospace fw-bold text-dark">{{ $employee->aadhaar_number ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">PAN Card Number</small>
                    <span class="font-monospace fw-bold text-dark">{{ $employee->pan_number ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Voter ID / Driving License</small>
                    <span class="font-monospace fw-bold text-dark">{{ $employee->voter_id ?? $employee->driving_license ?? 'N/A' }}</span>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-cash-stack text-success me-2"></i>Salary & Bank Account Information</h6>
            <div class="row g-3 text-dark small">
                <div class="col-md-4">
                    <small class="text-muted d-block">Basic Salary</small>
                    <span class="font-monospace fw-bold text-success fs-5">₹{{ number_format($employee->basic_salary, 2) }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Salary Type</small>
                    <span class="fw-semibold text-dark">{{ ucfirst($employee->salary_type) }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Bank Name</small>
                    <span class="fw-semibold text-dark">{{ $employee->bank_name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Bank Account Number</small>
                    <span class="font-monospace fw-bold text-dark">{{ $employee->bank_account_number ?? 'N/A' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Bank IFSC Code</small>
                    <span class="font-monospace fw-bold text-dark">{{ $employee->bank_ifsc ?? 'N/A' }}</span>
                </div>
            </div>
        </x-ui.card>

        <!-- DOCUMENTS ATTACHMENT CARD -->
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-paperclip text-primary me-2"></i>Uploaded Proofs & Documents ({{ $employee->documents->count() }})</h6>
            <div class="table-responsive">
                <table class="table align-middle table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Document Title</th>
                            <th>Type</th>
                            <th>File Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->documents as $doc)
                            <tr>
                                <td><span class="fw-semibold text-dark">{{ $doc->document_title }}</span></td>
                                <td><span class="badge bg-light text-dark border">{{ strtoupper($doc->document_type) }}</span></td>
                                <td><span class="small text-muted">{{ $doc->file_size_kb }} KB</span></td>
                                <td>
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5">
                                        <i class="bi bi-download me-1"></i> View File
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No documents uploaded for this employee.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
