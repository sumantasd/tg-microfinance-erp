@extends('layouts.admin')

@section('title', 'Application Profile - ' . $application->application_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $application->application_number }}</h4>
            @php
                $badgeClass = match($application->status) {
                    'draft' => 'bg-secondary text-white',
                    'submitted' => 'bg-info text-white',
                    'under_review' => 'bg-warning text-dark',
                    'approved' => 'bg-success text-white',
                    'rejected', 'cancelled' => 'bg-danger text-white',
                    default => 'bg-light text-dark'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ str_replace('_', ' ', $application->status) }}</span>
        </div>
        <p class="text-muted small mb-0">
            Branch: <strong>{{ $application->branch->name ?? 'N/A' }}</strong> 
            <span class="mx-2">|</span>
            Scheme: <strong>{{ $application->loanScheme->name ?? 'N/A' }}</strong>
            <span class="mx-2">|</span>
            Applied Date: <strong>{{ $application->application_date ? $application->application_date->format('d M Y') : 'N/A' }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-application.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
        @if($application->status === 'draft')
            @can('loan_application.edit')
                <a href="{{ route('admin.loan-application.edit', $application->id) }}" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                    <i class="bi bi-pencil me-1"></i> Edit Draft
                </a>
            @endcan
        @endif
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

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle">
            <div class="small text-muted fw-bold uppercase">Requested Amount</div>
            <div class="fs-3 fw-bold text-primary mt-1 font-monospace">₹{{ number_format($application->requested_amount, 2) }}</div>
            <div class="small text-muted">Tenure: {{ $application->tenure_months }} Months</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-muted fw-bold uppercase">Approved Amount</div>
            <div class="fs-3 fw-bold text-success mt-1 font-monospace">
                ₹{{ number_format($application->approved_amount ?? 0, 2) }}
            </div>
            <div class="small text-muted">
                @if($application->approved_amount)
                    Approved {{ $application->approved_at ? $application->approved_at->format('d M Y') : '' }}
                @else
                    Pending Approval
                @endif
            </div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-muted fw-bold uppercase">Total Upfront Charges</div>
            <div class="fs-3 fw-bold text-dark mt-1 font-monospace">₹{{ number_format($application->upfront_charges_total, 2) }}</div>
            <div class="small text-muted">Proc: ₹{{ number_format($application->processing_fee_amount, 2) }} ({{ $application->processing_fee_percentage }}%) | Ins: ₹{{ number_format($application->insurance_fee_amount, 2) }} ({{ $application->insurance_fee_percentage }}%)</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Borrower Type</div>
            <div class="fs-5 fw-bold text-dark mt-1 text-capitalize">
                @if($application->borrower_type === 'individual')
                    <i class="bi bi-person text-primary me-1"></i>Individual
                @else
                    <i class="bi bi-people text-info me-1"></i>Group ({{ $application->members->count() }} Members)
                @endif
            </div>
            <div class="small text-muted text-uppercase fw-bold">{{ $application->loan_type }} Loan</div>
        </x-ui.card>
    </div>
</div>

<!-- Workflow Action Bar -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-bold text-dark"><i class="bi bi-gear-fill me-1 text-primary"></i>Workflow Actions:</div>
        <div class="d-flex flex-wrap gap-2">
            @if($application->status === 'draft')
                @can('loan_application.submit')
                    <form action="{{ route('admin.loan-application.submit', $application->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info text-white fw-bold"><i class="bi bi-send me-1"></i> Submit Application</button>
                    </form>
                @endcan
            @endif

            @if(in_array($application->status, ['submitted', 'draft']))
                @can('loan_application.review')
                    <form action="{{ route('admin.loan-application.start-review', $application->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold"><i class="bi bi-search me-1"></i> Start Review</button>
                    </form>
                @endcan
            @endif

            @if(in_array($application->status, ['submitted', 'under_review']))
                @can('loan_application.approve')
                    <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle me-1"></i> Approve Application
                    </button>
                @endcan

                @can('loan_application.reject')
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject Application
                    </button>
                @endcan
            @endif

            @if($application->status === 'approved')
                @php
                    $sanctionedAcc = \App\Models\LoanAccount::where('loan_application_id', $application->id)->first();
                @endphp

                @if($sanctionedAcc)
                    <a href="{{ route('admin.loan-account.show', $sanctionedAcc->id) }}" class="btn btn-sm btn-info text-white fw-bold">
                        <i class="bi bi-wallet2 me-1"></i> View Loan Account ({{ $sanctionedAcc->loan_number }})
                    </a>
                @else
                    @can('loan.sanction')
                        <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#sanctionModal">
                            <i class="bi bi-patch-check-fill me-1"></i> Sanction & Create Loan Account
                        </button>
                    @endcan
                @endif
            @endif

            @if(in_array($application->status, ['draft', 'submitted', 'under_review']))
                @can('loan_application.cancel')
                    <form action="{{ route('admin.loan-application.cancel', $application->id) }}" method="POST" onsubmit="return confirm('Cancel this loan application?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-slash-circle me-1"></i> Cancel Application</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Borrower Info & Allocations -->
<div class="row g-4 mb-4">
    <!-- Borrower Info -->
    <div class="col-md-6">
        <x-ui.card class="shadow-sm border-0 p-4 h-100">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-person-badge text-primary me-2"></i>Borrower Details</h5>
            @if($application->borrower_type === 'individual')
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Customer Name</label>
                    <div class="fw-bold text-dark fs-6">{{ $application->customer->full_name ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Customer Code</label>
                    <div class="font-monospace text-primary fw-bold">{{ $application->customer->customer_code ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Mobile / KYC Status</label>
                    <div>{{ $application->customer->mobile_number ?? 'N/A' }} | <span class="badge bg-success-subtle text-success border">KYC Verified</span></div>
                </div>
            @else
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Group Name</label>
                    <div class="fw-bold text-dark fs-6">{{ $application->customerGroup->name ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Group Code</label>
                    <div class="font-monospace text-info fw-bold">{{ $application->customerGroup->code ?? 'N/A' }}</div>
                </div>
                <div class="mb-2">
                    <label class="small text-muted fw-bold d-block">Total Members</label>
                    <div class="fw-bold">{{ $application->members->count() }} Members Allocated</div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Loan Scheme & Terms -->
    <div class="col-md-6">
        <x-ui.card class="shadow-sm border-0 p-4 h-100">
            <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-journal-bookmark text-success me-2"></i>Scheme Snapshots</h5>
            <div class="row g-2 small">
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Repayment Frequency</label>
                    <div class="fw-bold text-capitalize">{{ $application->repayment_frequency }}</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Grace Period</label>
                    <div class="fw-bold">{{ $application->grace_period_days }} Days</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Late Fee Penalty</label>
                    <div class="fw-bold text-danger">{{ $application->late_fee_percentage }}%</div>
                </div>
                <div class="col-6">
                    <label class="text-muted fw-bold d-block">Processing Fee %</label>
                    <div class="fw-bold">{{ $application->processing_fee_percentage }}%</div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- Group Member Allocation Breakdown (If Group Loan) -->
@if($application->borrower_type === 'group' && $application->members->count() > 0)
    <x-ui.card class="shadow-sm border-0 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-people text-info me-2"></i>Group Member Allocation Breakdown</h5>
        <x-ui.data-table>
            <x-slot:headers>
                <th scope="col" class="py-3 px-3">Member Name & Code</th>
                <th scope="col" class="py-3 px-3">Requested Amount</th>
                <th scope="col" class="py-3 px-3">Approved Amount</th>
                <th scope="col" class="py-3 px-3">Remarks</th>
            </x-slot:headers>

            @foreach($application->members as $m)
                <tr>
                    <td class="px-3 py-3 fw-bold text-dark">
                        {{ $m->customer->full_name ?? 'N/A' }}
                        <div class="small font-monospace text-muted">{{ $m->customer->customer_code ?? '' }}</div>
                    </td>
                    <td class="px-3 py-3 font-monospace fw-bold text-dark">₹{{ number_format($m->requested_amount, 2) }}</td>
                    <td class="px-3 py-3 font-monospace fw-bold text-success">₹{{ number_format($m->approved_amount ?? $m->requested_amount, 2) }}</td>
                    <td class="px-3 py-3 small text-muted">{{ $m->remarks ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>
@endif

<!-- Product Items Breakdown (If Product Loan) -->
@if($application->loan_type === 'product' && $application->products->count() > 0)
    <x-ui.card class="shadow-sm border-0 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-box-seam text-warning me-2"></i>Product Line Items</h5>
        <x-ui.data-table>
            <x-slot:headers>
                <th scope="col" class="py-3 px-3">Product Name</th>
                <th scope="col" class="py-3 px-3">SKU</th>
                <th scope="col" class="py-3 px-3">Quantity</th>
                <th scope="col" class="py-3 px-3">Unit Valuation</th>
                <th scope="col" class="py-3 px-3 text-end">Total Valuation</th>
            </x-slot:headers>

            @foreach($application->products as $p)
                <tr>
                    <td class="px-3 py-3 fw-bold text-dark">{{ $p->product_name_snapshot }}</td>
                    <td class="px-3 py-3 font-monospace text-info small">{{ $p->product_sku_snapshot }}</td>
                    <td class="px-3 py-3 fs-6 fw-bold text-dark">{{ $p->quantity }} Units</td>
                    <td class="px-3 py-3 font-monospace small">₹{{ number_format($p->unit_price_snapshot, 2) }}</td>
                    <td class="px-3 py-3 text-end font-monospace fw-bold text-dark">₹{{ number_format($p->total_value, 2) }}</td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>
@endif

<!-- Audit Timeline -->
<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary me-2"></i>Workflow Audit Trail</h5>
    <div class="row g-3 small">
        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Created By</label>
            <div>{{ $application->creator->name ?? 'System' }}</div>
            <div class="text-muted">{{ $application->created_at ? $application->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Reviewed By</label>
            <div>{{ $application->reviewer->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $application->reviewed_at ? $application->reviewed_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Approved By</label>
            <div>{{ $application->approver->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $application->approved_at ? $application->approved_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Rejected / Cancelled By</label>
            <div>{{ $application->rejecter->name ?? $application->canceller->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $application->rejected_at ? $application->rejected_at->format('d M Y, h:i A') : ($application->cancelled_at ? $application->cancelled_at->format('d M Y, h:i A') : 'N/A') }}</div>
        </div>

        @if($application->purpose)
            <div class="col-12 mt-2">
                <label class="text-muted fw-bold d-block">Loan Purpose</label>
                <div class="p-2 bg-light rounded border">{{ $application->purpose }}</div>
            </div>
        @endif

        @if($application->rejection_reason)
            <div class="col-12 mt-2">
                <label class="text-danger fw-bold d-block">Rejection Reason</label>
                <div class="p-2 bg-danger-subtle text-danger rounded border border-danger-subtle">{{ $application->rejection_reason }}</div>
            </div>
        @endif
    </div>
</x-ui.card>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-application.approve', $application->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success">Approve Loan Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Approved Principal Amount (₹)</label>
                        <input type="number" step="0.01" name="approved_amount" class="form-control" value="{{ $application->requested_amount }}" required>
                        <div class="form-text">Defaults to requested amount (₹{{ number_format($application->requested_amount, 2) }}). Can be adjusted lower.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-application.reject', $application->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Loan Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Specify why this loan application is rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sanction Loan Account Modal -->
<div class="modal fade" id="sanctionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.loan-account.sanction') }}" method="POST">
                @csrf
                <input type="hidden" name="loan_application_id" value="{{ $application->id }}">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-wallet2 me-1"></i> Sanction & Create Loan Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Sanctioning converts this approved application into an active Loan Account and generates the EMI Repayment Schedule.
                    </div>

                    @if($application->loan_type === 'product')
                        @php
                            $prodVal = (float) $application->products->sum('total_value');
                            $approvedVal = (float) ($application->approved_amount ?? $application->requested_amount);
                            $suggestedDown = max(0, $prodVal - $approvedVal);
                        @endphp
                        <div class="bg-light p-3 rounded border mb-3 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Product Catalog Price:</span>
                                <strong class="font-monospace text-dark">₹{{ number_format($prodVal, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Financed Principal Limit:</span>
                                <strong class="font-monospace text-primary">₹{{ number_format($approvedVal, 2) }}</strong>
                            </div>
                            <div class="text-muted border-top pt-1 mt-1 font-italic">
                                Financed Principal = Product Price - Down Payment. EMI is calculated ONLY on Financed Principal!
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Customer Upfront Down Payment (₹)</label>
                            <input type="number" step="0.01" name="down_payment_amount" class="form-control font-monospace fw-bold" value="{{ $suggestedDown }}" required>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Sanctioned Cash Principal (₹)</label>
                            <input type="text" class="form-control font-monospace fw-bold" value="₹{{ number_format($application->approved_amount ?? $application->requested_amount, 2) }}" disabled>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Other Charges (₹)</label>
                        <input type="number" step="0.01" name="other_charges_amount" class="form-control font-monospace" value="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Sanction Date</label>
                        <input type="date" name="sanction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-check-circle me-1"></i> Sanction Loan Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
