@extends('layouts.admin')

@section('title', 'Bank Accounts - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-bank2 text-primary me-2"></i>Bank Accounts Master
        </h4>
        <p class="text-muted small mb-0">Manage corporate bank accounts, branch operating accounts, and ledger account linkages.</p>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.accounting.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Accounting Hub
        </a>
        <a href="{{ route('admin.accounting.bank-accounts.create') }}" class="btn btn-primary text-white fw-bold shadow-sm rounded-pill px-4">
            <i class="bi bi-plus-circle me-1"></i> Register Bank Account
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
    <form method="GET" action="{{ route('admin.accounting.bank-accounts.index') }}" class="row g-3">
        <div class="col-md-9">
            <label class="form-label small fw-bold text-muted">Search Bank or Account</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Bank Name, Account Title, Account Number, IFSC..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <a href="{{ route('admin.accounting.bank-accounts.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4 flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Bank Accounts Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Bank & Account Name</th>
            <th scope="col" class="py-3 px-3">Account Number & IFSC</th>
            <th scope="col" class="py-3 px-3">Branch Location</th>
            <th scope="col" class="py-3 px-3">Linked GL Account</th>
            <th scope="col" class="py-3 px-3">Opening Balance (₹)</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($bankAccounts as $bank)
            <tr>
                <td class="px-3 py-3">
                    <span class="fw-bold text-dark fs-6">{{ $bank->bank_name }}</span>
                    <div class="small text-muted">{{ $bank->account_name }}</div>
                </td>
                <td class="px-3 py-3 font-monospace">
                    <div class="fw-bold text-dark">{{ $bank->account_number }}</div>
                    <div class="small text-muted">IFSC: {{ $bank->ifsc_code ?: 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">{{ $bank->branch ? $bank->branch->name : 'All / Head Office' }}</div>
                    <div class="text-muted">{{ $bank->branch_name ?: 'Main Branch' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    @if($bank->chartOfAccount)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                            {{ $bank->chartOfAccount->account_code }} - {{ $bank->chartOfAccount->account_name }}
                        </span>
                    @else
                        <span class="text-muted italic">Not Linked</span>
                    @endif
                </td>
                <td class="px-3 py-3 font-monospace fw-bold text-dark">
                    ₹{{ number_format($bank->opening_balance, 2) }}
                </td>
                <td class="px-3 py-3">
                    @if($bank->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0.5">Inactive</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.accounting.bank-accounts.edit', $bank->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Bank Account">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('admin.accounting.bank-accounts.destroy', $bank->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove bank account \'{{ $bank->account_name }}\'?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Bank Account">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-bank fs-1 d-block mb-2 text-secondary"></i>
                    No bank accounts registered yet.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($bankAccounts->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $bankAccounts->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
