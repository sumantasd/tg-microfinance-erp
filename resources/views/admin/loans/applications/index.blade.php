@extends('layouts.admin')

@section('title', 'Loan Applications - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Loan Applications & Approvals
        </h4>
        <p class="text-muted small mb-0">Manage individual and group loan applications for cash and product schemes across branches.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-scheme.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-journal-bookmark me-1"></i> Loan Schemes
        </a>
        @can('loan_application.create')
            <a href="{{ route('admin.loan-application.create') }}" class="btn btn-success fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Apply For Loan
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

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.loan-application.index') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Search Application</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="LN-APP #, Customer, Group..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Loan Type</label>
            <select name="loan_type" class="form-select">
                <option value="">All Types</option>
                <option value="cash" {{ ($filters['loan_type'] ?? '') === 'cash' ? 'selected' : '' }}>Cash Loan</option>
                <option value="product" {{ ($filters['loan_type'] ?? '') === 'product' ? 'selected' : '' }}>Product Loan</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Borrower Type</label>
            <select name="borrower_type" class="form-select">
                <option value="">All Borrowers</option>
                <option value="individual" {{ ($filters['borrower_type'] ?? '') === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="group" {{ ($filters['borrower_type'] ?? '') === 'group' ? 'selected' : '' }}>Group (JLG/SHG)</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Loan Scheme</label>
            <select name="loan_scheme_id" class="form-select">
                <option value="">All Loan Schemes</option>
                @foreach($schemes as $s)
                    <option value="{{ $s->id }}" {{ ($filters['loan_scheme_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="submitted" {{ ($filters['status'] ?? '') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="under_review" {{ ($filters['status'] ?? '') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.loan-application.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter Applications</button>
        </div>
    </form>
</x-ui.card>

<!-- Applications Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Application # & Date</th>
            <th scope="col" class="py-3 px-3">Borrower / Applicant</th>
            <th scope="col" class="py-3 px-3">Scheme & Type</th>
            <th scope="col" class="py-3 px-3">Requested / Approved</th>
            <th scope="col" class="py-3 px-3">Tenure & Interest</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($applications as $app)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.loan-application.show', $app->id) }}" class="fw-bold font-monospace text-primary text-decoration-none hover-primary">{{ $app->application_number }}</a>
                    <div class="small text-muted">{{ $app->application_date ? $app->application_date->format('d M Y') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    @if($app->borrower_type === 'individual')
                        <div class="fw-bold text-dark"><i class="bi bi-person text-primary me-1"></i>{{ $app->customer->full_name ?? 'N/A' }}</div>
                        <div class="text-muted font-monospace">{{ $app->customer->customer_code ?? '' }}</div>
                    @else
                        <div class="fw-bold text-dark"><i class="bi bi-people text-info me-1"></i>{{ $app->customerGroup->name ?? 'N/A' }}</div>
                        <div class="text-muted font-monospace">{{ $app->customerGroup->code ?? '' }}</div>
                    @endif
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">{{ $app->loanScheme->name ?? 'N/A' }}</div>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 text-uppercase fw-bold">{{ $app->loan_type }} Loan</span>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 text-capitalize ms-1">{{ $app->borrower_type }}</span>
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <div class="fw-bold text-dark">Req: ₹{{ number_format($app->requested_amount, 2) }}</div>
                    @if($app->approved_amount)
                        <div class="text-success fw-bold">App: ₹{{ number_format($app->approved_amount, 2) }}</div>
                    @else
                        <div class="text-muted">Pending Appr.</div>
                    @endif
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-success">{{ $app->interest_rate_per_annum }}% p.a.</div>
                    <div class="text-muted">{{ $app->tenure_months }} Mos ({{ ucfirst($app->repayment_frequency) }})</div>
                </td>
                <td class="px-3 py-3">
                    @php
                        $badgeClass = match($app->status) {
                            'draft' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            'submitted' => 'bg-info-subtle text-info border-info-subtle',
                            'under_review' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'approved' => 'bg-success-subtle text-success border-success-subtle',
                            'rejected', 'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border px-2.5 py-1 text-capitalize fw-bold">
                        {{ str_replace('_', ' ', $app->status) }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end">
                    <a href="{{ route('admin.loan-application.show', $app->id) }}" class="btn btn-sm btn-outline-info" title="View Application Profile">
                        <i class="bi bi-eye"></i> Details
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x fs-1 d-block text-secondary mb-2"></i>
                    No loan applications found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($applications->hasPages())
        <div class="p-3 border-top">
            {{ $applications->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
