@extends('layouts.admin')

@section('title', 'Loan Schemes Directory - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>Loan Schemes / Products Master
        </h4>
        <p class="text-muted small mb-0">Configure cash and product loan schemes, interest rates, tenure limits, and fee percentages.</p>
    </div>
    @can('loan_scheme.create')
        <div class="mt-3 mt-md-0">
            <a href="{{ route('admin.loan-scheme.create') }}" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Create Loan Scheme
            </a>
        </div>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.loan-scheme.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Scheme</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Scheme Name, Code..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Loan Category</label>
            <select name="loan_type" class="form-select">
                <option value="">All Categories</option>
                <option value="cash" {{ ($filters['loan_type'] ?? '') === 'cash' ? 'selected' : '' }}>Cash Loan</option>
                <option value="product" {{ ($filters['loan_type'] ?? '') === 'product' ? 'selected' : '' }}>Product Loan</option>
                <option value="both" {{ ($filters['loan_type'] ?? '') === 'both' ? 'selected' : '' }}>Cash & Product Both</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Applicant Eligibility</label>
            <select name="applicant_type" class="form-select">
                <option value="">All Applicants</option>
                <option value="individual" {{ ($filters['applicant_type'] ?? '') === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="group" {{ ($filters['applicant_type'] ?? '') === 'group' ? 'selected' : '' }}>Group (JLG/SHG)</option>
                <option value="both" {{ ($filters['applicant_type'] ?? '') === 'both' ? 'selected' : '' }}>Both</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="is_active" class="form-select">
                <option value="">All</option>
                <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.loan-scheme.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Loan Schemes Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Scheme Code & Name</th>
            <th scope="col" class="py-3 px-3">Type & Eligibility</th>
            <th scope="col" class="py-3 px-3">Amount Range</th>
            <th scope="col" class="py-3 px-3">Interest & Tenure</th>
            <th scope="col" class="py-3 px-3">Processing Fee</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($schemes as $scheme)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.loan-scheme.show', $scheme->id) }}" class="fw-bold text-dark text-decoration-none hover-primary fs-6">{{ $scheme->name }}</a>
                    <div class="small font-monospace text-primary fw-bold">{{ $scheme->code }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 text-uppercase fw-bold">{{ $scheme->loan_type }}</span>
                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 text-capitalize ms-1">{{ $scheme->applicant_type }}</span>
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <div>₹{{ number_format($scheme->min_amount, 2) }} - ₹{{ number_format($scheme->max_amount, 2) }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-success">{{ $scheme->interest_rate_per_annum }}% p.a. ({{ ucfirst(str_replace('_', ' ', $scheme->interest_type)) }})</div>
                    <div class="text-muted">{{ $scheme->min_tenure_months }} - {{ $scheme->max_tenure_months }} Mos ({{ $scheme->repayment_frequency === 'bi_weekly' ? '15 Days' : ($scheme->repayment_frequency === 'weekly' ? 'Weekly' : 'Monthly') }})</div>
                </td>
                <td class="px-3 py-3 small">
                    <div>Processing: {{ $scheme->processing_fee_percentage }}%</div>
                    <div class="text-muted">Insurance: {{ $scheme->insurance_fee_percentage }}%</div>
                </td>
                <td class="px-3 py-3">
                    @if($scheme->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Inactive</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.loan-scheme.show', $scheme->id) }}" class="btn btn-sm btn-outline-info" title="View Scheme">
                            <i class="bi bi-eye"></i> View
                        </a>
                        @can('loan_scheme.edit')
                            <a href="{{ route('admin.loan-scheme.edit', $scheme->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Scheme">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block text-secondary mb-2"></i>
                    No loan schemes found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($schemes->hasPages())
        <div class="p-3 border-top">
            {{ $schemes->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
