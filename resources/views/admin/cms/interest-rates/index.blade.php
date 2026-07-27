@extends('layouts.admin')

@section('title', 'Interest Rates Schedule CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-percent text-primary me-2"></i>Interest Rates Schedule CMS</h4>
        <p class="text-muted small mb-0">Manage rate matrices, interest calculation methods (Flat, Reducing Balance, Daily Reducing), and processing fees.</p>
    </div>
    <a href="{{ route('admin.cms.interest-rates.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Rate Matrix Entry
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.cms.interest-rates.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search by product name or rate...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="product_type" class="form-select bg-light">
                <option value="">All Product Types</option>
                <option value="loan" {{ request('product_type') === 'loan' ? 'selected' : '' }}>Loan Products</option>
                <option value="savings" {{ request('product_type') === 'savings' ? 'selected' : '' }}>Savings Schemes</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.cms.interest-rates.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Product Name', 'Amount Range', 'Tenure', 'Interest Rate & Method', 'Processing Fee', 'Status', 'Actions']">
        @forelse($rates as $item)
            <tr>
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $item->product_name }}</div>
                    <span class="badge bg-secondary-subtle text-secondary small font-monospace">{{ strtoupper($item->product_type) }}</span>
                </td>
                <td>
                    <small class="fw-semibold text-dark">{{ $item->amount_range ?? 'N/A' }}</small>
                </td>
                <td>
                    <small class="text-muted">{{ $item->tenure_options ?? 'N/A' }}</small>
                </td>
                <td>
                    <div class="fw-bold text-primary mb-0.5">{{ $item->interest_rate }}</div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ $item->interest_method }}</span>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">{{ $item->processing_fee ?? '0.0%' }}</span>
                </td>
                <td>
                    <form action="{{ route('admin.cms.interest-rates.toggle-status', $item->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($item->status === 'active')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.interest-rates.edit', $item->id) }}" class="btn btn-light btn-sm border" title="Edit Rate Entry"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.interest-rates.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this rate entry permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Entry"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No interest rate schedule entries found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $rates->links() }}
    </div>
</x-ui.card>
@endsection
