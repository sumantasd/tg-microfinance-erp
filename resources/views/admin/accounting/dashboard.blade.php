@extends('layouts.admin')

@section('title', 'General Ledger & Accounting - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-calculator text-primary me-2"></i>General Ledger & Financial Accounting
        </h4>
        <p class="text-muted small mb-0">Double-entry bookkeeping, Chart of Accounts, Bank Accounts, and Journal Vouchers.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-diagram-3 me-1"></i> Chart of Accounts
        </a>
        <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-outline-primary rounded-pill px-3">
            <i class="bi bi-bank me-1"></i> Bank Accounts
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

<!-- Summary KPIs -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle border-start border-primary border-4">
            <div class="small text-muted fw-bold text-uppercase">Total Ledger Accounts</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $totalAccounts }} Accounts</div>
            <div class="small text-muted mt-1"><a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="text-primary text-decoration-none fw-semibold">View Chart of Accounts &rarr;</a></div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle border-start border-success border-4">
            <div class="small text-muted fw-bold text-uppercase">Registered Bank Accounts</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $totalBankAccounts }} Accounts</div>
            <div class="small text-muted mt-1"><a href="{{ route('admin.accounting.bank-accounts.index') }}" class="text-success text-decoration-none fw-semibold">Manage Bank Accounts &rarr;</a></div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle border-start border-info border-4">
            <div class="small text-muted fw-bold text-uppercase">Total Vouchers Posted</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $totalVouchers }} Vouchers</div>
            <div class="small text-muted mt-1"><a href="{{ route('admin.accounting.vouchers.index') }}" class="text-info text-decoration-none fw-semibold">View All Vouchers &rarr;</a></div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle border-start border-warning border-4">
            <div class="small text-muted fw-bold text-uppercase">GL Volume (Debits)</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace">₹{{ number_format($totalDebitVolume, 2) }}</div>
            <div class="small text-muted mt-1">Balanced Double-Entry Volume</div>
        </x-ui.card>
    </div>
</div>

<!-- Active Financial Year Badge -->
@if($currentFy)
    <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center mb-4 py-2 px-3">
        <div class="small">
            <i class="bi bi-calendar3 text-primary me-2"></i><strong>Active Financial Period:</strong> {{ $currentFy->title }} ({{ $currentFy->start_date->format('d M Y') }} — {{ $currentFy->end_date->format('d M Y') }})
        </div>
        <span class="badge {{ $currentFy->is_closed ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} px-2.5 py-1">
            {{ $currentFy->is_closed ? 'Closed Period' : 'Open for Posting' }}
        </span>
    </div>
@endif

<!-- Recent Vouchers Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Recent Financial Vouchers</h6>
        <a href="{{ route('admin.accounting.vouchers.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Vouchers</a>
    </div>

    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Voucher #</th>
            <th scope="col" class="py-3 px-3">Date & Branch</th>
            <th scope="col" class="py-3 px-3">Type</th>
            <th scope="col" class="py-3 px-3">Narration</th>
            <th scope="col" class="py-3 px-3 text-end">Amount (₹)</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($recentVouchers as $v)
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
                    <span class="badge {{ $typeBadge }} border px-2.5 py-1 text-uppercase">{{ $v->voucher_type }}</span>
                </td>
                <td class="px-3 py-3 small text-muted text-truncate" style="max-width: 300px;">
                    {{ $v->narration ?: '—' }}
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark text-end">
                    ₹{{ number_format($v->total_debit, 2) }}
                </td>
                <td class="px-3 py-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 text-uppercase">
                        {{ $v->status }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end">
                    <a href="{{ route('admin.accounting.vouchers.show', $v->id) }}" class="btn btn-sm btn-outline-info" title="View Voucher Details">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                    No financial vouchers posted yet.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
</x-ui.card>
@endsection
