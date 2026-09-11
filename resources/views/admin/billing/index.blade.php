@extends('layouts.admin')

@section('title', 'Billing & Invoices - ' . config('app.name'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Billing & Invoice Management</h4>
        <p class="text-muted small mb-0">Centralized billing system for Direct Product Sales and Product Loan Financing invoices.</p>
    </div>
    @can('billing.create')
    <div class="mt-3 mt-md-0">
        <a href="{{ route('admin.billing.sales.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-cart-plus me-1"></i> New Direct Sale
        </a>
    </div>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filters Card -->
<x-ui.card class="p-3 shadow-sm border-0 bg-white mb-4">
    <form action="{{ route('admin.billing.invoices.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Invoice #, Customer, Phone...">
            </div>
        </div>

        <div class="col-md-2">
            <select name="invoice_type" class="form-select bg-light">
                <option value="">All Invoice Types</option>
                <option value="direct_sale" {{ request('invoice_type') === 'direct_sale' ? 'selected' : '' }}>Direct Sale</option>
                <option value="product_loan" {{ request('invoice_type') === 'product_loan' ? 'selected' : '' }}>Product Loan</option>
            </select>
        </div>

        @if(auth()->user()->isSuperAdmin() || auth()->user()->isCompanyAdmin())
        <div class="col-md-2">
            <select name="branch_id" class="form-select bg-light">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="col-md-2">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Issued / Active</option>
                <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control bg-light" title="From Date">
        </div>

        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 fw-bold" title="Apply Filters"><i class="bi bi-funnel"></i></button>
            <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-light border w-100" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Invoices Table -->
<x-ui.card class="shadow-sm border-0 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase extra-small fw-bold text-muted">
                <tr>
                    <th class="ps-4">Invoice #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Branch</th>
                    <th>Reference</th>
                    <th class="text-end">Grand Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance Due</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody class="small">
                @forelse($invoices as $inv)
                <tr>
                    <td class="ps-4 fw-bold">
                        <a href="{{ route('admin.billing.invoices.show', $inv->id) }}" class="text-primary font-monospace text-decoration-none">
                            {{ $inv->invoice_number }}
                        </a>
                    </td>
                    <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : 'N/A' }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $inv->customer->full_name ?? ($inv->invoiceable->customer_name ?? 'Walk-in Customer') }}</div>
                        <small class="text-muted extra-small">{{ $inv->customer->customer_code ?? 'Walk-in' }}</small>
                    </td>
                    <td>
                        @if($inv->invoice_type === 'direct_sale')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">Direct Sale</span>
                        @else
                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-1">Product Loan</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-semibold text-dark">{{ $inv->branch->name ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="font-monospace extra-small text-secondary">{{ $inv->reference_number ?: 'N/A' }}</span>
                    </td>
                    <td class="text-end font-monospace fw-bold text-dark">
                        ₹{{ number_format($inv->grand_total, 2) }}
                    </td>
                    <td class="text-end font-monospace text-success">
                        ₹{{ number_format($inv->paid_amount, 2) }}
                    </td>
                    <td class="text-end font-monospace text-danger">
                        ₹{{ number_format($inv->balance_due, 2) }}
                    </td>
                    <td>
                        @if($inv->status === 'paid')
                            <span class="badge bg-success text-white px-2 py-1 rounded-pill">Paid</span>
                        @elseif($inv->status === 'issued')
                            <span class="badge bg-primary text-white px-2 py-1 rounded-pill">Issued</span>
                        @elseif($inv->status === 'partially_paid')
                            <span class="badge bg-warning text-dark px-2 py-1 rounded-pill">Partially Paid</span>
                        @elseif($inv->status === 'cancelled')
                            <span class="badge bg-danger text-white px-2 py-1 rounded-pill">Cancelled</span>
                        @else
                            <span class="badge bg-secondary text-white px-2 py-1 rounded-pill">{{ ucfirst($inv->status) }}</span>
                        @endif
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.billing.invoices.show', $inv->id) }}" class="btn btn-outline-secondary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('billing.print')
                            <a href="{{ route('admin.billing.invoices.print', $inv->id) }}" target="_blank" class="btn btn-outline-primary" title="Print A4 Invoice">
                                <i class="bi bi-printer"></i>
                            </a>
                            @endcan
                            @can('billing.pdf')
                            <a href="{{ route('admin.billing.invoices.pdf', $inv->id) }}" target="_blank" class="btn btn-outline-info" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                        No invoices found matching your criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices</small>
        {{ $invoices->links() }}
    </div>
    @endif
</x-ui.card>
@endsection
