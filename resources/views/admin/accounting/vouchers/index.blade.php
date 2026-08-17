@extends('layouts.admin')

@section('title', 'Journal Vouchers - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-journal-check text-primary me-2"></i>Financial Vouchers & Journal Register
        </h4>
        <p class="text-muted small mb-0">Double-entry audit log of all financial receipts, payments, contra, and journal vouchers.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.accounting.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Accounting Hub
        </a>
        <a href="{{ route('admin.accounting.vouchers.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> New Journal Voucher
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

<!-- Filters -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.accounting.vouchers.index') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Search Voucher / Narration</label>
            <input type="text" name="search" class="form-control" placeholder="Voucher #, narration..." value="{{ request('search') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Voucher Type</label>
            <select name="voucher_type" class="form-select">
                <option value="">All Types</option>
                <option value="journal" {{ request('voucher_type') === 'journal' ? 'selected' : '' }}>Journal (JV)</option>
                <option value="receipt" {{ request('voucher_type') === 'receipt' ? 'selected' : '' }}>Receipt (RV)</option>
                <option value="payment" {{ request('voucher_type') === 'payment' ? 'selected' : '' }}>Payment (PV)</option>
                <option value="contra" {{ request('voucher_type') === 'contra' ? 'selected' : '' }}>Contra (CV)</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Branch</label>
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">From Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">To Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary fw-bold w-100" title="Apply Filter"><i class="bi bi-filter"></i></button>
        </div>
    </form>
</x-ui.card>

<!-- Vouchers Register Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Voucher Number</th>
            <th scope="col" class="py-3 px-3">Date & Branch</th>
            <th scope="col" class="py-3 px-3">Type</th>
            <th scope="col" class="py-3 px-3">Narration & Accounts</th>
            <th scope="col" class="py-3 px-3 text-end">Total Debit (₹)</th>
            <th scope="col" class="py-3 px-3 text-end">Total Credit (₹)</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($vouchers as $v)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.accounting.vouchers.show', $v->id) }}" class="fw-bold text-dark font-monospace text-decoration-none hover-primary">
                        {{ $v->voucher_number }}
                    </a>
                    @if($v->is_reversal)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1">Reversal</span>
                    @endif
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">{{ $v->voucher_date->format('d M, Y') }}</div>
                    <div class="text-muted">{{ $v->branch->name ?? 'HO' }}</div>
                </td>
                <td class="px-3 py-3">
                    @php
                        $typeBadge = match($v->voucher_type) {
                            'receipt' => 'bg-success-subtle text-success border-success-subtle',
                            'payment' => 'bg-danger-subtle text-danger border-danger-subtle',
                            'contra' => 'bg-info-subtle text-info border-info-subtle',
                            default => 'bg-primary-subtle text-primary border-primary-subtle'
                        };
                    @endphp
                    <span class="badge {{ $typeBadge }} border px-2.5 py-1 text-uppercase fw-bold">
                        {{ $v->voucher_type }}
                    </span>
                </td>
                <td class="px-3 py-3 small" style="max-width: 320px;">
                    <div class="text-dark fw-semibold text-truncate">{{ $v->narration ?: 'No narration provided' }}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">
                        {{ $v->entries->count() }} line items &bull; Created by {{ $v->creator->name ?? 'System' }}
                    </div>
                </td>
                <td class="px-3 py-3 font-monospace fw-bold text-dark text-end">
                    ₹{{ number_format($v->total_debit, 2) }}
                </td>
                <td class="px-3 py-3 font-monospace fw-bold text-dark text-end">
                    ₹{{ number_format($v->total_credit, 2) }}
                </td>
                <td class="px-3 py-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 text-uppercase">
                        {{ $v->status }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end">
                    <a href="{{ route('admin.accounting.vouchers.show', $v->id) }}" class="btn btn-sm btn-outline-info" title="View Detailed Voucher Sheet">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                    No financial vouchers found matching filters.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($vouchers->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $vouchers->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
