@extends('layouts.admin')

@section('title', 'Designation Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-workspace text-primary me-2"></i>Designation Management</h4>
        <p class="text-muted small mb-0">Manage job titles, organizational designations, and employee roles across departments.</p>
    </div>
    @can('designation.create')
        <a href="{{ route('admin.designation.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-plus-circle fs-6"></i> Add Designation
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
    <form action="{{ route('admin.designation.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by title or code...">
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
            <select name="department_id" class="form-select bg-light">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.designation.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Designation Title & Code', 'Department', 'Company', 'Staff Count', 'Status', 'Actions']">
        @forelse($designations as $designation)
            <tr class="{{ $designation->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <a href="{{ route('admin.designation.show', $designation->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $designation->title }}</a>
                    <div class="font-monospace small text-primary fw-semibold">{{ $designation->code ?? 'N/A' }}</div>
                </td>
                <td><span class="fw-semibold text-dark">{{ $designation->department->name ?? 'N/A' }}</span></td>
                <td><span class="small fw-semibold text-secondary">{{ $designation->company->name ?? 'N/A' }}</span></td>
                <td>
                    <span class="badge bg-info-subtle text-info border rounded-pill">
                        <i class="bi bi-people me-1"></i>{{ $designation->employees_count }} Staff
                    </span>
                </td>
                <td>
                    @if($designation->trashed())
                        <span class="badge bg-danger text-white rounded-pill"><i class="bi bi-trash me-1"></i>Deleted</span>
                    @elseif($designation->is_active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-pause-circle me-1"></i>Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        @can('designation.view')
                            <a href="{{ route('admin.designation.show', $designation->id) }}" class="btn btn-light btn-sm border" title="View"><i class="bi bi-eye text-secondary"></i></a>
                        @endcan

                        @if($designation->trashed())
                            @can('designation.restore')
                                <form action="{{ route('admin.designation.restore', $designation->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm border text-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @endcan
                        @else
                            @can('designation.edit')
                                <a href="{{ route('admin.designation.edit', $designation->id) }}" class="btn btn-light btn-sm border" title="Edit"><i class="bi bi-pencil text-primary"></i></a>
                            @endcan

                            @can('designation.toggle_status')
                                <form action="{{ route('admin.designation.toggle-status', $designation->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $designation->is_active ? 0 : 1 }}">
                                    <button type="submit" class="btn btn-light btn-sm border {{ $designation->is_active ? 'text-warning' : 'text-success' }}" title="{{ $designation->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $designation->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                            @endcan

                            @can('designation.delete')
                                <form action="{{ route('admin.designation.destroy', $designation->id) }}" method="POST" onsubmit="return confirm('Soft delete this designation?');">
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
                <td colspan="6" class="text-center py-4 text-muted">No designations found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $designations->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $designations->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $designations->total() }}</span> designations
        </div>
        <div class="d-flex align-items-center">
            {{ $designations->links() }}
        </div>
    </div>
</x-ui.card>
@endsection
