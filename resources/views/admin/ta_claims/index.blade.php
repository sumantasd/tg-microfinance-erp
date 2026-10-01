@extends('layouts.admin')

@section('title', 'Travel Allowance Claims - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-car-front-fill me-2 text-primary"></i>Travel Allowance (TA) Claims</h4>
        <p class="text-muted small mb-0">Manage staff travel reimbursements, field visit claims, approvals, and disbursements.</p>
    </div>
    @can('ta_claims.create')
    <div>
        <a href="{{ route('admin.ta-claims.create') }}" class="btn btn-primary btn-sm rounded-pill fw-bold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Submit New TA Claim
        </a>
    </div>
    @endcan
</div>

<!-- Filter Strip -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="GET" action="{{ route('admin.ta-claims.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="paid" {{ ($filters['status'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
            <a href="{{ route('admin.ta-claims.index') }}" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Claim #</th>
                    <th>Travel Date</th>
                    <th>Staff Name</th>
                    <th>Route / Mode</th>
                    <th>Distance & Rate</th>
                    <th class="text-end">Claim Amount (₹)</th>
                    <th>Status</th>
                    <th class="pe-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($claims as $c)
                    <tr>
                        <td class="ps-3 font-monospace fw-bold text-primary">
                            {{ $c->claim_number }}
                        </td>
                        <td class="font-monospace text-muted small">
                            {{ $c->travel_date->format('Y-m-d') }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $c->user->name }}</div>
                            <div class="text-muted small">{{ $c->branch->name }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $c->from_location }} <i class="bi bi-arrow-right text-muted mx-1"></i> {{ $c->to_location }}</div>
                            <div class="text-muted small">Mode: {{ strtoupper($c->transport_mode) }}</div>
                        </td>
                        <td>
                            <div class="font-monospace small">{{ $c->distance_km }} km @ ₹{{ number_format($c->rate_per_km, 2) }}/km</div>
                        </td>
                        <td class="text-end font-monospace fw-bold fs-6">
                            ₹{{ number_format($c->amount, 2) }}
                        </td>
                        <td>
                            @if($c->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1">PENDING</span>
                            @elseif($c->status === 'approved')
                                <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-2.5 py-1">APPROVED</span>
                            @elseif($c->status === 'paid')
                                <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-2.5 py-1">PAID</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill px-2.5 py-1">REJECTED</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            @if($c->status === 'pending')
                                @can('ta_claims.approve')
                                <form method="POST" action="{{ route('admin.ta-claims.approve', $c->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success rounded-pill px-2 py-0.5">Approve</button>
                                </form>
                                <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0.5" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $c->id }}">Reject</button>

                                <div class="modal fade text-start" id="rejectModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="{{ route('admin.ta-claims.reject', $c->id) }}">
                                                @csrf
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title font-monospace small"><i class="bi bi-x-circle me-2"></i>Reject TA Claim</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <label class="form-label small fw-semibold text-secondary">Rejection Reason <span class="text-danger">*</span></label>
                                                    <textarea name="rejection_reason" rows="3" class="form-control form-control-sm" required></textarea>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-danger rounded-pill fw-bold">Reject Claim</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            @elseif($c->status === 'approved')
                                @can('ta_claims.pay')
                                <button type="button" class="btn btn-xs btn-primary rounded-pill px-2.5 py-0.5" data-bs-toggle="modal" data-bs-target="#payModal{{ $c->id }}">Mark Paid</button>

                                <div class="modal fade text-start" id="payModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="{{ route('admin.ta-claims.pay', $c->id) }}">
                                                @csrf
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title font-monospace small"><i class="bi bi-cash-stack me-2"></i>Pay TA Claim #{{ $c->claim_number }}</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold text-secondary">Payment Reference / Txn # <span class="text-danger">*</span></label>
                                                        <input type="text" name="payment_reference" class="form-control form-control-sm" required placeholder="e.g. CASH-001 or UPI-Ref">
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill fw-bold">Confirm Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-car-front fs-2 text-muted d-block mb-2"></i>
                            <div class="fw-bold">No travel allowance claims submitted yet.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($claims->hasPages())
        <div class="p-3 border-top">
            {{ $claims->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
