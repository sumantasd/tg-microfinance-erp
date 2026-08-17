@extends('layouts.admin')

@section('title', 'Loan Settlements & Foreclosures - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-handshake text-primary me-2"></i>Loan Settlements & Foreclosures</h4>
        <p class="text-muted small mb-0">Manage early foreclosures, compromise settlements (OTS), and bad debt write-off workflows.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-account.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Loan Accounts
        </a>
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

<!-- Filters Card -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <form method="GET" action="{{ route('admin.loan-settlement.index') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Loan # / Customer</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Enter loan # or customer name..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Request Type</label>
            <select name="request_type" class="form-select form-select-sm">
                <option value="">All Request Types</option>
                <option value="foreclosure" {{ request('request_type') === 'foreclosure' ? 'selected' : '' }}>Early Foreclosure</option>
                <option value="settlement_ots" {{ request('request_type') === 'settlement_ots' ? 'selected' : '' }}>Compromise Settlement (OTS)</option>
                <option value="write_off" {{ request('request_type') === 'write_off' ? 'selected' : '' }}>Bad Debt Write-Off</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Approval Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved (Awaiting Payment)</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed / Closed</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold"><i class="bi bi-funnel me-1"></i> Filter</button>
            <a href="{{ route('admin.loan-settlement.index') }}" class="btn btn-sm btn-light border w-100">Reset</a>
        </div>
    </form>
</x-ui.card>

<!-- Table Card -->
<x-ui.card class="shadow-sm border-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th class="ps-3">Req #</th>
                    <th>Loan Number</th>
                    <th>Borrower</th>
                    <th>Type</th>
                    <th class="text-end">Principal Due</th>
                    <th class="text-end">Concession</th>
                    <th class="text-end">Final Amount</th>
                    <th>Status</th>
                    <th>Requested By</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($requests as $req)
                    @php
                        $badgeClass = match($req->status) {
                            'pending_approval' => 'bg-warning text-dark',
                            'approved' => 'bg-info text-white',
                            'completed' => 'bg-success text-white',
                            'rejected' => 'bg-danger text-white',
                            default => 'bg-secondary text-white'
                        };
                        $typeBadge = match($req->request_type) {
                            'foreclosure' => 'bg-primary-subtle text-primary border border-primary',
                            'settlement_ots' => 'bg-warning-subtle text-dark border border-warning',
                            'write_off' => 'bg-danger-subtle text-danger border border-danger',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <tr>
                        <td class="ps-3 font-monospace fw-bold">#{{ $req->id }}</td>
                        <td>
                            <a href="{{ route('admin.loan-account.show', $req->loan_account_id) }}" class="fw-bold text-decoration-none">
                                {{ $req->loanAccount->loan_number ?? 'N/A' }}
                            </a>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $req->branch->name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $req->loanAccount->customer->full_name ?? 'N/A' }}</div>
                            <div class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $req->loanAccount->customer->customer_code ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $typeBadge }} px-2 py-1 text-uppercase">{{ str_replace('_', ' ', $req->request_type) }}</span>
                        </td>
                        <td class="text-end font-monospace">₹{{ number_format($req->principal_outstanding, 2) }}</td>
                        <td class="text-end font-monospace text-danger">₹{{ number_format($req->discount_concession_amount, 2) }}</td>
                        <td class="text-end font-monospace fw-bold text-success">₹{{ number_format($req->final_settlement_amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $badgeClass }} px-2 py-1 text-capitalize">{{ str_replace('_', ' ', $req->status) }}</span>
                        </td>
                        <td>
                            <div>{{ $req->requester->name ?? 'System' }}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $req->requested_at ? $req->requested_at->format('d M Y') : '' }}</div>
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('admin.loan-settlement.show', $req->id) }}" class="btn btn-xs btn-outline-primary fw-bold px-2 py-1">
                                <i class="bi bi-eye me-1"></i> Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                            No loan settlement or foreclosure requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
        <div class="p-3 border-top bg-light">
            {{ $requests->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
