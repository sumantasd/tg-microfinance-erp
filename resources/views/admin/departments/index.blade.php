@extends('layouts.admin')

@section('title', 'Department Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-diagram-2 text-primary me-2"></i>Department Management</h4>
        <p class="text-muted small mb-0">Organize company functional units, operational departments, and staffing divisions.</p>
    </div>
    @can('department.create')
        <a href="{{ route('admin.department.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-plus-circle fs-6"></i> Add Department
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
    <form action="{{ route('admin.department.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="{{ auth()->user()->isSuperAdmin() ? 'col-md-5' : 'col-md-7' }}">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by department name or code...">
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

        <div class="col-md-2">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active Only</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                <option value="trashed" {{ ($filters['status'] ?? '') === 'trashed' ? 'selected' : '' }}>Trashed</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.department.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Department Name & Code', 'Company', 'Designations', 'Employees', 'Status', 'Actions']">
        @forelse($departments as $department)
            <tr class="{{ $department->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <a href="{{ route('admin.department.show', $department->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $department->name }}</a>
                    <div class="font-monospace small text-primary fw-semibold">{{ $department->code }}</div>
                </td>
                <td><span class="small fw-semibold text-secondary">{{ $department->company->name ?? 'N/A' }}</span></td>
                <td>
                    <span class="badge bg-primary-subtle text-primary border rounded-pill">
                        <i class="bi bi-person-workspace me-1"></i>{{ $department->designations_count }} Designations
                    </span>
                </td>
                <td>
                    <span class="badge bg-info-subtle text-info border rounded-pill">
                        <i class="bi bi-people me-1"></i>{{ $department->employees_count }} Employees
                    </span>
                </td>
                <td>
                    @if($department->trashed())
                        <span class="badge bg-danger text-white rounded-pill"><i class="bi bi-trash me-1"></i>Deleted</span>
                    @elseif($department->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        @can('department.view')
                            <a href="{{ route('admin.department.show', $department->id) }}" class="btn btn-light btn-sm border" title="View"><i class="bi bi-eye text-secondary"></i></a>
                        @endcan

                        @if($department->trashed())
                            @can('department.restore')
                                <form action="{{ route('admin.department.restore', $department->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm border text-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @endcan
                        @else
                            @can('department.edit')
                                <a href="{{ route('admin.department.edit', $department->id) }}" class="btn btn-light btn-sm border" title="Edit"><i class="bi bi-pencil text-primary"></i></a>
                            @endcan

                            @can('department.toggle_status')
                                <form action="{{ route('admin.department.toggle-status', $department->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $department->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-light btn-sm border {{ $department->is_active ? 'text-warning' : 'text-success' }}" title="{{ $department->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $department->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                            @endcan

                            @can('department.delete')
                                <form action="{{ route('admin.department.destroy', $department->id) }}" method="POST" onsubmit="return confirm('Soft delete this department?');">
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
                <td colspan="6" class="text-center py-4 text-muted">No departments found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $departments->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $departments->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $departments->total() }}</span> departments
        </div>
        <div class="d-flex align-items-center">
            {{ $departments->links() }}
        </div>
    </div>
</x-ui.card>
@endsection
