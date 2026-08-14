@extends('layouts.admin')

@section('title', 'Loan Accounts - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-wallet2 text-success me-2"></i>Loan Accounts & Portfolios
        </h4>
        <p class="text-muted small mb-0">Manage sanctioned active loan accounts, down payments, disbursements, and EMI repayment schedules.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.loan-application.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Loan Applications
        </a>
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
    <form method="GET" action="{{ route('admin.loan-account.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Loan Account</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Loan #, Customer, Group..." value="{{ $filters['search'] ?? '' }}">
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

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="sanctioned" {{ ($filters['status'] ?? '') === 'sanctioned' ? 'selected' : '' }}>Sanctioned</option>
                <option value="ready_for_disbursement" {{ ($filters['status'] ?? '') === 'ready_for_disbursement' ? 'selected' : '' }}>Ready for Disbursement</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
                <option value="defaulted" {{ ($filters['status'] ?? '') === 'defaulted' ? 'selected' : '' }}>Defaulted</option>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <a href="{{ route('admin.loan-account.index') }}" class="btn btn-light border text-secondary fw-bold w-50">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold w-50"><i class="bi bi-filter"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Loan Accounts Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Loan # & Date</th>
            <th scope="col" class="py-3 px-3">Borrower / Applicant</th>
            <th scope="col" class="py-3 px-3">Financed Principal</th>
            <th scope="col" class="py-3 px-3">Down Payment</th>
            <th scope="col" class="py-3 px-3">Total Outstanding</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($accounts as $acc)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.loan-account.show', $acc->id) }}" class="fw-bold font-monospace text-primary text-decoration-none hover-primary">{{ $acc->loan_number }}</a>
                    <div class="small text-muted">{{ $acc->sanction_date ? $acc->sanction_date->format('d M Y') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    @if($acc->borrower_type === 'individual')
                        <div class="fw-bold text-dark"><i class="bi bi-person text-primary me-1"></i>{{ $acc->customer->full_name ?? 'N/A' }}</div>
                        <div class="text-muted font-monospace">{{ $acc->customer->customer_code ?? '' }}</div>
                    @else
                        <div class="fw-bold text-dark"><i class="bi bi-people text-info me-1"></i>{{ $acc->customerGroup->name ?? 'N/A' }}</div>
                        <div class="text-muted font-monospace">{{ $acc->customerGroup->group_code ?? '' }}</div>
                    @endif
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <div class="fw-bold text-dark">₹{{ number_format($acc->sanctioned_amount, 2) }}</div>
                    <span class="badge bg-light text-secondary border text-uppercase">{{ $acc->loan_type }} Loan</span>
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <div class="fw-bold text-success">₹{{ number_format($acc->down_payment_amount, 2) }}</div>
                    <div class="text-muted small">Val: ₹{{ number_format($acc->product_price_amount, 2) }}</div>
                </td>
                <td class="px-3 py-3 small font-monospace">
                    <div class="fw-bold text-danger">₹{{ number_format($acc->total_outstanding, 2) }}</div>
                    <div class="text-muted">Prin: ₹{{ number_format($acc->principal_outstanding, 2) }}</div>
                </td>
                <td class="px-3 py-3">
                    @php
                        $badgeClass = match($acc->status) {
                            'sanctioned' => 'bg-info-subtle text-info border-info-subtle',
                            'ready_for_disbursement' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'active' => 'bg-success-subtle text-success border-success-subtle',
                            'closed' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            'defaulted' => 'bg-danger-subtle text-danger border-danger-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border px-2.5 py-1 text-capitalize fw-bold">
                        {{ str_replace('_', ' ', $acc->status) }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end">
                    <a href="{{ route('admin.loan-account.show', $acc->id) }}" class="btn btn-sm btn-outline-info" title="View Loan Account Profile">
                        <i class="bi bi-eye"></i> Details
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-wallet2 fs-1 d-block text-secondary mb-2"></i>
                    No loan accounts found matching specified criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($accounts->hasPages())
        <div class="p-3 border-top">
            {{ $accounts->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
