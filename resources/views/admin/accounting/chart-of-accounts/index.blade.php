@extends('layouts.admin')

@section('title', 'Chart of Accounts - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-diagram-3-fill text-primary me-2"></i>Chart of Accounts (COA)
        </h4>
        <p class="text-muted small mb-0">Master classification of all general ledger accounts across Assets, Liabilities, Equity, Revenue, and Expenses.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.accounting.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Accounting Hub
        </a>
        <a href="{{ route('admin.accounting.chart-of-accounts.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Add Ledger Account
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

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.accounting.chart-of-accounts.index') }}" class="row g-3">
        <div class="col-md-5">
            <label class="form-label small fw-bold text-muted">Search Account</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Account Code, Name, Group..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Account Classification</label>
            <select name="account_type" class="form-select">
                <option value="">All Account Types</option>
                <option value="asset" {{ request('account_type') === 'asset' ? 'selected' : '' }}>1000 - Assets</option>
                <option value="liability" {{ request('account_type') === 'liability' ? 'selected' : '' }}>2000 - Liabilities</option>
                <option value="equity" {{ request('account_type') === 'equity' ? 'selected' : '' }}>3000 - Equity</option>
                <option value="revenue" {{ request('account_type') === 'revenue' ? 'selected' : '' }}>4000 - Revenue</option>
                <option value="expense" {{ request('account_type') === 'expense' ? 'selected' : '' }}>5000 - Expenses</option>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <a href="{{ route('admin.accounting.chart-of-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4 flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Chart of Accounts Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Account Code</th>
            <th scope="col" class="py-3 px-3">Account Name & Group</th>
            <th scope="col" class="py-3 px-3">Classification</th>
            <th scope="col" class="py-3 px-3">Current Balance (₹)</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($accounts as $acc)
            <tr>
                <td class="px-3 py-3 font-monospace fw-bold text-dark fs-6">
                    {{ $acc->account_code }}
                    @if($acc->is_system)
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1" style="font-size: 0.65rem;">System</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <span class="fw-bold text-dark fs-6">{{ $acc->account_name }}</span>
                    <div class="small text-muted font-monospace text-uppercase" style="font-size: 0.75rem;">
                        <i class="bi bi-folder2 me-1"></i>{{ str_replace('_', ' ', $acc->account_group) }}
                    </div>
                </td>
                <td class="px-3 py-3">
                    @php
                        $typeBadge = match($acc->account_type) {
                            'asset' => 'bg-info-subtle text-info border-info-subtle',
                            'liability' => 'bg-warning-subtle text-warning border-warning-subtle',
                            'equity' => 'bg-purple-subtle text-primary border-primary-subtle',
                            'revenue' => 'bg-success-subtle text-success border-success-subtle',
                            default => 'bg-danger-subtle text-danger border-danger-subtle'
                        };
                    @endphp
                    <span class="badge {{ $typeBadge }} border px-2.5 py-1 text-uppercase fw-bold">
                        {{ $acc->account_type }}
                    </span>
                </td>
                <td class="px-3 py-3 font-monospace fw-bold text-dark">
                    ₹{{ number_format($acc->getBalance(), 2) }}
                </td>
                <td class="px-3 py-3">
                    @if($acc->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5">Inactive</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.accounting.chart-of-accounts.edit', $acc->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Account">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @if(!$acc->is_system)
                            <form action="{{ route('admin.accounting.chart-of-accounts.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete account \'{{ $acc->account_name }}\'?');" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Account">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 fs-1 d-block mb-2 text-secondary"></i>
                    No accounts found matching search criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($accounts->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $accounts->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
