@extends('layouts.admin')

@section('title', 'Branch Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-building text-warning me-2"></i>Branch Management</h4>
        <p class="text-muted small mb-0">Manage regional branch network, vault cash reserves, and branch managers.</p>
    </div>
    @can('branch.create')
        <a href="{{ route('admin.branch.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-plus-circle fs-6"></i> Add New Branch
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.branch.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="{{ auth()->user()->isSuperAdmin() ? 'col-md-4' : 'col-md-7' }}">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by branch name, code, city, state...">
            </div>
        </div>
        
        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-3">
                <select name="company_id" class="form-select bg-light">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                <option value="trashed" {{ ($filters['status'] ?? '') === 'trashed' ? 'selected' : '' }}>Soft Deleted (Trash)</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.branch.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Branches Data Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Branch Name & Code', 'Company', 'Location & Contact', 'Branch Manager', 'Vault Limit & Balance', 'Status', 'Actions']">
        @forelse($branches as $branch)
            <tr class="{{ $branch->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                            <i class="bi bi-building fs-6"></i>
                        </div>
                        <div>
                            <a href="{{ route('admin.branch.show', $branch->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $branch->name }}</a>
                            <div class="font-monospace small text-primary fw-semibold">{{ $branch->code }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="fw-semibold text-secondary small">{{ $branch->company->name ?? 'N/A' }}</span>
                </td>
                <td>
                    <div class="small fw-semibold text-dark"><i class="bi bi-geo-alt me-1 text-danger"></i>{{ $branch->city }}, {{ $branch->state }}</div>
                    <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $branch->phone }}</small>
                </td>
                <td>
                    @if($branch->manager)
                        <div class="small fw-semibold text-dark"><i class="bi bi-person me-1 text-primary"></i>{{ $branch->manager->name }}</div>
                        <small class="text-muted">{{ $branch->manager->email }}</small>
                    @else
                        <span class="badge bg-light text-muted border">Unassigned</span>
                    @endif
                </td>
                <td>
                    <div class="font-monospace small fw-bold text-dark">Limit: ₹{{ number_format($branch->vault_cash_limit, 2) }}</div>
                    <small class="font-monospace text-success">Bal: ₹{{ number_format($branch->current_vault_balance, 2) }}</small>
                </td>
                <td>
                    @if($branch->trashed())
                        <span class="badge bg-danger text-white rounded-pill"><i class="bi bi-trash me-1"></i>Deleted</span>
                    @elseif($branch->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        @can('branch.view')
                            <a href="{{ route('admin.branch.show', $branch->id) }}" class="btn btn-light btn-sm border" title="View Branch"><i class="bi bi-eye text-secondary"></i></a>
                        @endcan
                        
                        @if($branch->trashed())
                            @can('branch.restore')
                                <form action="{{ route('admin.branch.restore', $branch->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm border text-success" title="Restore Branch"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @endcan
                        @else
                            @can('branch.edit')
                                <a href="{{ route('admin.branch.edit', $branch->id) }}" class="btn btn-light btn-sm border" title="Edit Branch"><i class="bi bi-pencil text-primary"></i></a>
                            @endcan

                            @can('branch.toggle_status')
                                <form action="{{ route('admin.branch.toggle-status', $branch->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $branch->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-light btn-sm border {{ $branch->is_active ? 'text-warning' : 'text-success' }}" title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $branch->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                            @endcan

                            @can('branch.delete')
                                <form action="{{ route('admin.branch.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to soft delete this branch office?');">
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
                    No branch offices found.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $branches->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $branches->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $branches->total() }}</span> branch offices
        </div>
        <div class="d-flex align-items-center">
            {{ $branches->links() }}
        </div>
    </div>
</x-ui.card>
@endsection
