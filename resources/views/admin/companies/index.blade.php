@extends('layouts.admin')

@section('title', 'Company Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-buildings text-primary me-2"></i>Company Management</h4>
        <p class="text-muted small mb-0">Manage multi-company corporate profiles, legal registration details, and currency standards.</p>
    </div>
    @can('company.create')
        <a href="{{ route('admin.company.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-plus-circle fs-6"></i> Add New Company
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Bar -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.company.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by name, code, email, phone, tax ID...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Company Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                <option value="trashed" {{ ($filters['status'] ?? '') === 'trashed' ? 'selected' : '' }}>Soft Deleted (Trash)</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.company.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Companies Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Company Name & Code', 'Tax & Registration', 'Contact Details', 'Currency', 'Branches & Staff', 'Status', 'Actions']">
        @forelse($companies as $company)
            <tr class="{{ $company->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2.5">
                        @if($company->logo_path)
                            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="rounded border p-1" style="width: 40px; height: 40px; object-fit: contain;">
                        @else
                            <div class="bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; flex-shrink: 0;">
                                <i class="bi bi-building fs-5"></i>
                            </div>
                        @endif
                        <div>
                            <a href="{{ route('admin.company.show', $company->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $company->name }}</a>
                            <div class="font-monospace small text-primary fw-semibold">{{ $company->code }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small fw-semibold text-secondary">Reg: {{ $company->registration_number ?? 'N/A' }}</div>
                    <small class="text-muted">Tax ID: {{ $company->tax_id ?? 'N/A' }}</small>
                </td>
                <td>
                    <div class="small"><i class="bi bi-envelope me-1 text-muted"></i>{{ $company->email }}</div>
                    <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $company->phone }}</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">{{ $company->currency_symbol }} {{ $company->currency_code }}</span>
                </td>
                <td>
                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill me-1">
                        <i class="bi bi-diagram-3 me-1"></i>{{ $company->branches_count }} Branches
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill">
                        <i class="bi bi-people me-1"></i>{{ $company->users_count }} Staff
                    </span>
                </td>
                <td>
                    @if($company->trashed())
                        <span class="badge bg-danger text-white rounded-pill"><i class="bi bi-trash me-1"></i>Deleted</span>
                    @elseif($company->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.company.show', $company->id) }}" class="btn btn-light btn-sm border" title="View Profile"><i class="bi bi-eye text-secondary"></i></a>
                        
                        @if($company->trashed())
                            <form action="{{ route('admin.company.restore', $company->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm border text-success" title="Restore Company"><i class="bi bi-arrow-counterclockwise"></i></button>
                            </form>
                        @else
                            @can('company.edit')
                                <a href="{{ route('admin.company.edit', $company->id) }}" class="btn btn-light btn-sm border" title="Edit Company"><i class="bi bi-pencil text-primary"></i></a>
                                <form action="{{ route('admin.company.toggle-status', $company->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $company->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-light btn-sm border {{ $company->is_active ? 'text-warning' : 'text-success' }}" title="{{ $company->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $company->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                            @endcan

                            @can('company.delete')
                                <form action="{{ route('admin.company.destroy', $company->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to soft delete this company profile?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm border text-danger" title="Soft Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                    No company profiles found.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $companies->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $companies->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $companies->total() }}</span> company profiles
        </div>
        <div class="d-flex align-items-center">
            {{ $companies->links() }}
        </div>
    </div>
</x-ui.card>
@endsection
