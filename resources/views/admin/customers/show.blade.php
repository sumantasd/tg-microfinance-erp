@extends('layouts.admin')

@section('title', $customer->full_name . ' - Customer Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Customer Profile</h4>
        <p class="text-muted small mb-0">Comprehensive record for <strong>{{ $customer->full_name }}</strong> ({{ $customer->customer_code }}).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.customer.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold d-flex align-items-center gap-1">
            <i class="bi bi-arrow-left"></i> Back to Listing
        </a>
        @can('customer.edit')
            <a href="{{ route('admin.customer.edit', $customer->id) }}" class="btn btn-warning rounded-pill px-3.5 py-1.5 fw-bold shadow-sm d-flex align-items-center gap-1.5">
                <i class="bi bi-pencil-square"></i> Edit Details
            </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Profile Header Card -->
<x-ui.card class="p-4 mb-4 shadow-sm">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            @if($customer->profile_photo_path)
                <img src="{{ asset('storage/' . $customer->profile_photo_path) }}" alt="{{ $customer->full_name }}" class="rounded-circle object-fit-cover shadow border border-2 border-white" style="width: 72px; height: 72px;">
            @else
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow fs-3" style="width: 72px; height: 72px; flex-shrink: 0;">
                    {{ strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)) }}
                </div>
            @endif
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold text-dark mb-0">{{ $customer->full_name }}</h3>
                    @php
                        $statusBadgeClass = match($customer->status) {
                            'active' => 'bg-success-subtle text-success border border-success-subtle',
                            'inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            'blacklisted' => 'bg-danger-subtle text-danger border border-danger-subtle',
                            'deceased' => 'bg-dark-subtle text-dark border border-dark-subtle',
                            'closed' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $statusBadgeClass }} px-2.5 py-1 text-capitalize fw-bold">{{ $customer->status }}</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-1 text-muted small">
                    <span class="font-monospace text-primary fw-bold"><i class="bi bi-hash"></i> {{ $customer->customer_code }}</span>
                    @if($customer->member_number)
                        <span class="font-monospace"><i class="bi bi-card-heading me-1"></i> Member #: {{ $customer->member_number }}</span>
                    @endif
                    <span><i class="bi bi-building me-1"></i> {{ $customer->branch->name ?? 'N/A' }}</span>
                    <span><i class="bi bi-calendar3 me-1"></i> Registered: {{ $customer->registration_date ? $customer->registration_date->format('d M Y') : 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @php
                $verifiedKycCount = $customer->kycDocuments->where('verification_status', 'verified')->count();
                $totalKycCount = $customer->kycDocuments->count();
            @endphp
            <div class="text-md-end">
                <div class="small text-muted fw-bold text-uppercase">KYC Health</div>
                @if($verifiedKycCount > 0)
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="bi bi-shield-check me-1"></i> {{ $verifiedKycCount }} Verified Doc(s)</span>
                @else
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle fs-6"><i class="bi bi-clock-history me-1"></i> {{ $totalKycCount }} Pending Doc(s)</span>
                @endif
            </div>
        </div>
    </div>
</x-ui.card>

<!-- Nav Tabs -->
<ul class="nav nav-tabs custom-tabs mb-4 bg-white p-2 rounded-3 border shadow-sm" id="customerProfileTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold px-3 py-2" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><i class="bi bi-person-lines-fill me-1.5"></i> Overview & Address</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-3 py-2" id="kyc-docs-tab" data-bs-toggle="tab" data-bs-target="#kyc-docs" type="button" role="tab"><i class="bi bi-file-earmark-text-fill me-1.5"></i> KYC Documents ({{ $customer->kycDocuments->count() }})</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-3 py-2" id="guarantors-tab" data-bs-toggle="tab" data-bs-target="#guarantors" type="button" role="tab"><i class="bi bi-people-fill me-1.5"></i> Guarantors ({{ $customer->guarantors->count() }})</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-3 py-2" id="nominees-tab" data-bs-toggle="tab" data-bs-target="#nominees" type="button" role="tab"><i class="bi bi-heart-fill me-1.5"></i> Nominees ({{ $customer->nominees->count() }})</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold px-3 py-2" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab"><i class="bi bi-wallet2 me-1.5"></i> Financial Portfolio</button>
    </li>
</ul>

<div class="tab-content" id="customerProfileTabsContent">
    <!-- OVERVIEW TAB -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row g-4">
            <div class="col-md-6">
                <x-ui.card class="p-4 shadow-sm h-100">
                    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-vcard text-primary me-2"></i>Personal Details</h5>
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted fw-semibold" style="width: 40%;">Full Name:</td>
                            <td class="fw-bold text-dark">{{ $customer->full_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Guardian / Spouse:</td>
                            <td class="fw-semibold">{{ $customer->father_husband_guardian_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Customer Type:</td>
                            <td class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $customer->customer_type) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Gender / Marital:</td>
                            <td class="fw-semibold text-capitalize">{{ $customer->gender }} / {{ $customer->marital_status ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Date of Birth:</td>
                            <td class="fw-semibold">{{ $customer->dob ? $customer->dob->format('d M Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Occupation:</td>
                            <td class="fw-semibold">{{ $customer->occupation ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Monthly Income:</td>
                            <td class="fw-bold text-success">₹{{ number_format($customer->monthly_income ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </x-ui.card>
            </div>

            <div class="col-md-6">
                <x-ui.card class="p-4 shadow-sm h-100">
                    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-telephone-fill text-primary me-2"></i>Contact Details</h5>
                    <table class="table table-borderless table-sm mb-3">
                        <tr>
                            <td class="text-muted fw-semibold" style="width: 40%;">Mobile Number:</td>
                            <td class="fw-bold text-dark"><i class="bi bi-telephone text-primary me-1"></i> {{ $customer->mobile_number }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Alternate Contact:</td>
                            <td class="fw-semibold">{{ $customer->alternate_contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-semibold">Email Address:</td>
                            <td class="fw-semibold">{{ $customer->email ?? 'N/A' }}</td>
                        </tr>
                    </table>

                    <h6 class="fw-bold text-dark mb-2 mt-3"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Present Address</h6>
                    @if($customer->presentAddress)
                        <div class="p-2.5 bg-light rounded-3 small">
                            <div class="fw-bold">{{ $customer->presentAddress->address_line }}</div>
                            <div>{{ $customer->presentAddress->village_area ? $customer->presentAddress->village_area . ', ' : '' }}PO: {{ $customer->presentAddress->post_office ?? 'N/A' }}</div>
                            <div>Dist: {{ $customer->presentAddress->district }}, State: {{ $customer->presentAddress->state }} - {{ $customer->presentAddress->pin_code }}</div>
                        </div>
                    @else
                        <div class="text-muted small italic">No present address recorded.</div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>

    <!-- KYC DOCS TAB -->
    <div class="tab-pane fade" id="kyc-docs" role="tabpanel">
        <x-ui.card class="p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock text-success me-2"></i>Multi-Document KYC Vault</h5>
                @can('customer.verify_kyc')
                    <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#uploadKycModal">
                        <i class="bi bi-cloud-upload me-1"></i> Upload KYC Document
                    </button>
                @endcan
            </div>

            <x-ui.data-table :headers="['Document Type', 'Document Number', 'File Name', 'Verification Status', 'Verified By / Date', 'Actions']">
                @forelse($customer->kycDocuments as $doc)
                    <tr>
                        <td class="fw-bold text-dark text-capitalize">{{ str_replace('_', ' ', $doc->kyc_document_type) }}</td>
                        <td class="font-monospace fw-semibold">{{ $doc->document_number }}</td>
                        <td class="small"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> {{ $doc->file_name }} ({{ $doc->file_size_kb }} KB)</td>
                        <td>
                            @if($doc->verification_status === 'verified')
                                <span class="badge bg-success text-white px-2.5 py-1"><i class="bi bi-check-circle me-1"></i> Verified</span>
                            @elseif($doc->verification_status === 'rejected')
                                <span class="badge bg-danger text-white px-2.5 py-1" title="Reason: {{ $doc->rejection_reason }}"><i class="bi bi-x-circle me-1"></i> Rejected</span>
                            @else
                                <span class="badge bg-warning text-dark px-2.5 py-1"><i class="bi bi-clock me-1"></i> Pending Verification</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            @if($doc->verifier)
                                <div>{{ $doc->verifier->name }}</div>
                                <div>{{ $doc->verified_at ? $doc->verified_at->format('d M Y H:i') : '' }}</div>
                            @else
                                <em>Pending</em>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.customer.kyc.download', $doc->id) }}" class="btn btn-sm btn-outline-primary" title="Download Document">
                                    <i class="bi bi-download"></i>
                                </a>
                                @can('customer.verify_kyc')
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#verifyKycModal{{ $doc->id }}" title="Verify/Reject">
                                        <i class="bi bi-shield-check"></i>
                                    </button>
                                    <form action="{{ route('admin.customer.kyc.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this KYC document?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endcan
                            </div>

                            <!-- Verify/Reject Modal -->
                            <div class="modal fade" id="verifyKycModal{{ $doc->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.customer.kyc.verify', $doc->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-header-title fw-bold">Verify KYC Document - {{ strtoupper($doc->kyc_document_type) }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Verification Decision <span class="text-danger">*</span></label>
                                                    <select name="verification_status" class="form-select" required>
                                                        <option value="verified" {{ $doc->verification_status === 'verified' ? 'selected' : '' }}>Approve & Verify Document</option>
                                                        <option value="rejected" {{ $doc->verification_status === 'rejected' ? 'selected' : '' }}>Reject Document</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Rejection Reason (If rejecting)</label>
                                                    <textarea name="rejection_reason" class="form-control" rows="2" placeholder="State explicit reason for rejection...">{{ $doc->rejection_reason }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary fw-bold">Save Decision</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No KYC documents uploaded yet.</td>
                    </tr>
                @endforelse
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <!-- GUARANTORS TAB -->
    <div class="tab-pane fade" id="guarantors" role="tabpanel">
        <x-ui.card class="p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people text-warning me-2"></i>Guarantor Profiles</h5>
                @can('customer.manage_guarantor')
                    <button class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addGuarantorModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Guarantor
                    </button>
                @endcan
            </div>

            <div class="row g-3">
                @forelse($customer->guarantors as $g)
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light shadow-sm position-relative">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">{{ $g->full_name }}</h6>
                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle small fw-bold">{{ $g->relationship }}</span>
                                </div>
                                @can('customer.manage_guarantor')
                                    <form action="{{ route('admin.customer.guarantor.destroy', $g->id) }}" method="POST" onsubmit="return confirm('Remove guarantor?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove Guarantor"><i class="bi bi-trash fs-6"></i></button>
                                    </form>
                                @endcan
                            </div>
                            <hr class="my-2">
                            <div class="small">
                                <div><i class="bi bi-telephone text-muted me-1"></i> <strong>Mobile:</strong> {{ $g->mobile }}</div>
                                <div><i class="bi bi-briefcase text-muted me-1"></i> <strong>Occupation:</strong> {{ $g->occupation ?? 'N/A' }}</div>
                                <div><i class="bi bi-currency-rupee text-muted me-1"></i> <strong>Monthly Income:</strong> ₹{{ number_format($g->monthly_income ?? 0, 2) }}</div>
                                <div class="mt-1"><i class="bi bi-geo-alt text-muted me-1"></i> <strong>Address:</strong> {{ $g->address }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-4 text-muted">No guarantors added yet.</div>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <!-- NOMINEES TAB -->
    <div class="tab-pane fade" id="nominees" role="tabpanel">
        <x-ui.card class="p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-heart text-danger me-2"></i>Nominee Details</h5>
                @can('customer.manage_nominee')
                    <button class="btn btn-sm btn-danger rounded-pill px-3 fw-bold text-white" data-bs-toggle="modal" data-bs-target="#addNomineeModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Nominee
                    </button>
                @endcan
            </div>

            <div class="row g-3">
                @forelse($customer->nominees as $nom)
                    <div class="col-md-6">
                        <div class="p-3 border rounded-3 bg-light shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">{{ $nom->nominee_name }}</h6>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small fw-bold">{{ $nom->relationship }} ({{ $nom->share_percentage }}% Share)</span>
                                </div>
                                @can('customer.manage_nominee')
                                    <form action="{{ route('admin.customer.nominee.destroy', $nom->id) }}" method="POST" onsubmit="return confirm('Remove nominee?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Remove Nominee"><i class="bi bi-trash fs-6"></i></button>
                                    </form>
                                @endcan
                            </div>
                            <hr class="my-2">
                            <div class="small">
                                <div><i class="bi bi-calendar3 text-muted me-1"></i> <strong>DOB:</strong> {{ $nom->dob ? $nom->dob->format('d M Y') : 'N/A' }}</div>
                                <div><i class="bi bi-telephone text-muted me-1"></i> <strong>Mobile:</strong> {{ $nom->mobile ?? 'N/A' }}</div>
                                @if($nom->is_minor)
                                    <div class="mt-2 p-2 bg-warning-subtle rounded border border-warning-subtle">
                                        <div class="fw-bold text-dark small"><i class="bi bi-shield-exclamation me-1"></i> Minor Guardian Info:</div>
                                        <div><strong>Guardian:</strong> {{ $nom->guardian_name }} ({{ $nom->guardian_relationship }})</div>
                                        <div><strong>Contact:</strong> {{ $nom->guardian_contact }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-4 text-muted">No nominees added yet.</div>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <!-- FINANCIAL PORTFOLIO TAB -->
    <div class="tab-pane fade" id="financial" role="tabpanel">
        <div class="row g-4">
            <div class="col-md-4">
                <x-ui.card class="p-4 shadow-sm text-center">
                    <i class="bi bi-bank fs-1 text-primary mb-2"></i>
                    <h6 class="fw-bold text-dark">Loan Summary</h6>
                    <p class="text-muted small mb-3">Active loans, repayment schedule & PAR tracking.</p>
                    <div class="alert alert-light border small text-muted mb-0">Loan Module will link active borrower portfolio here once enabled.</div>
                </x-ui.card>
            </div>
            <div class="col-md-4">
                <x-ui.card class="p-4 shadow-sm text-center">
                    <i class="bi bi-piggy-bank fs-1 text-success mb-2"></i>
                    <h6 class="fw-bold text-dark">Savings Summary</h6>
                    <p class="text-muted small mb-3">Deposit accounts, balance & passbooks.</p>
                    <div class="alert alert-light border small text-muted mb-0">Savings Module will display account balances here once enabled.</div>
                </x-ui.card>
            </div>
            <div class="col-md-4">
                <x-ui.card class="p-4 shadow-sm text-center">
                    <i class="bi bi-receipt fs-1 text-warning mb-2"></i>
                    <h6 class="fw-bold text-dark">Collections History</h6>
                    <p class="text-muted small mb-3">Daily collection receipts & officer field logs.</p>
                    <div class="alert alert-light border small text-muted mb-0">Collection Module will display field postings here once enabled.</div>
                </x-ui.card>
            </div>
        </div>
    </div>
</div>

<!-- Upload KYC Modal -->
<div class="modal fade" id="uploadKycModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.customer.kyc.store', $customer->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload KYC Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Document Type <span class="text-danger">*</span></label>
                        <select name="kyc_document_type" class="form-select" required>
                            <option value="aadhaar">Aadhaar Card</option>
                            <option value="pan">PAN Card</option>
                            <option value="voter_id">Voter ID Card</option>
                            <option value="ration_card">Ration Card</option>
                            <option value="driving_license">Driving License</option>
                            <option value="passport">Passport</option>
                            <option value="other">Other Identity Proof</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Document Number <span class="text-danger">*</span></label>
                        <input type="text" name="document_number" class="form-control" placeholder="Identity document number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select File (PDF/Image) <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".pdf,image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-cloud-upload me-1"></i> Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Guarantor Modal -->
<div class="modal fade" id="addGuarantorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.customer.guarantor.store', $customer->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Guarantor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Relationship <span class="text-danger">*</span></label>
                        <input type="text" name="relationship" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mobile <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Occupation</label>
                        <input type="text" name="occupation" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Residential Address <span class="text-danger">*</span></label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold">Add Guarantor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Nominee Modal -->
<div class="modal fade" id="addNomineeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.customer.nominee.store', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Nominee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nominee Name <span class="text-danger">*</span></label>
                        <input type="text" name="nominee_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Relationship <span class="text-danger">*</span></label>
                        <input type="text" name="relationship" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Share Percentage (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="share_percentage" value="100.00" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger text-white fw-bold">Add Nominee</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
