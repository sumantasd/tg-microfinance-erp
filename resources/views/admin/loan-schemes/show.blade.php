@extends('layouts.admin')

@section('title', 'Scheme Profile - ' . $scheme->name . ' - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $scheme->name }}</h4>
            <span class="badge bg-light text-secondary border font-monospace fs-6">{{ $scheme->code }}</span>
            @if($scheme->is_active)
                <span class="badge bg-success text-white px-2.5 py-1">Active Scheme</span>
            @else
                <span class="badge bg-secondary text-white px-2.5 py-1">Inactive</span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            <i class="bi bi-building text-warning me-1"></i>{{ $scheme->branch->name ?? 'All Branches (Company-wide)' }} | {{ $scheme->company->name ?? 'N/A' }}
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-scheme.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
        @can('loan_scheme.edit')
            <a href="{{ route('admin.loan-scheme.edit', $scheme->id) }}" class="btn btn-primary rounded-pill px-3 fw-bold">
                <i class="bi bi-pencil me-1"></i> Edit Scheme
            </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle">
            <div class="small text-muted fw-bold uppercase">Interest Rate</div>
            <div class="fs-3 fw-bold text-primary mt-1">{{ $scheme->interest_rate_per_annum }}% p.a.</div>
            <div class="small text-muted text-capitalize">{{ ucfirst(str_replace('_', ' ', $scheme->interest_type)) }} Method</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-muted fw-bold uppercase">Loan Amount Range</div>
            <div class="fw-bold text-success fs-5 mt-1 font-monospace">₹{{ number_format($scheme->min_amount) }} - ₹{{ number_format($scheme->max_amount) }}</div>
            <div class="small text-muted">Min to Max Principal Limit</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle">
            <div class="small text-muted fw-bold uppercase">Tenure Range</div>
            <div class="fw-bold text-info fs-5 mt-1">{{ $scheme->min_tenure_months }} to {{ $scheme->max_tenure_months }} Months</div>
            <div class="small text-muted text-capitalize">{{ $scheme->repayment_frequency === 'bi_weekly' ? '15 Days' : ($scheme->repayment_frequency === 'weekly' ? 'Weekly' : 'Monthly') }} Repayments</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-muted fw-bold uppercase">Processing Fee</div>
            <div class="fw-bold text-dark fs-5 mt-1">{{ $scheme->processing_fee_percentage }}%</div>
            <div class="small text-muted">Insurance Fee: {{ $scheme->insurance_fee_percentage }}%</div>
        </x-ui.card>
    </div>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-info-circle text-primary me-2"></i>Scheme Rules & Specifications</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Loan Category</label>
            <div class="fw-bold text-capitalize fs-6">{{ $scheme->loan_type }} Loan</div>
        </div>

        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Applicant Eligibility</label>
            <div class="fw-bold text-capitalize fs-6">{{ $scheme->applicant_type }} Borrower</div>
        </div>

        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Grace Period</label>
            <div class="fw-bold fs-6">{{ $scheme->grace_period_days }} Days</div>
        </div>

        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Late Fee Penalty</label>
            <div class="fw-bold fs-6 text-danger">{{ $scheme->late_fee_percentage }}%</div>
        </div>

        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Created By</label>
            <div class="fw-bold fs-6">{{ $scheme->creator->name ?? 'System' }}</div>
        </div>

        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block">Created Date</label>
            <div class="fw-bold fs-6">{{ $scheme->created_at ? $scheme->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-12 mt-3">
            <label class="small text-muted fw-bold d-block">Operational Notes & Remarks</label>
            <div class="p-3 bg-light rounded-3 small border">{{ $scheme->remarks ?? 'No special operational guidelines specified.' }}</div>
        </div>
    </div>
</x-ui.card>
@endsection
