@extends('layouts.admin')

@section('title', 'Employee Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-people text-success me-2"></i>Employee Management</h4>
        <p class="text-muted small mb-0">Manage enterprise staff directory, department assignments, and branch staffing levels.</p>
    </div>
    @can('employee.create')
        <a href="{{ route('admin.employee.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-person-plus fs-6"></i> Add Employee
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
    <form action="{{ route('admin.employee.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="{{ auth()->user()->isSuperAdmin() ? 'col-md-3' : 'col-md-5' }}">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by name, code, email, phone...">
            </div>
        </div>

        @if(auth()->user()->isSuperAdmin())
            <div class="col-md-2">
                <select name="company_id" class="form-select bg-light">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if(auth()->user()->isSuperAdmin() || auth()->user()->hasRole('Company Admin'))
            <div class="col-md-2">
                <select name="branch_id" class="form-select bg-light">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-2">
            <select name="department_id" class="form-select bg-light">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="on_leave" {{ ($filters['status'] ?? '') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                <option value="resigned" {{ ($filters['status'] ?? '') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                <option value="terminated" {{ ($filters['status'] ?? '') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                <option value="trashed" {{ ($filters['status'] ?? '') === 'trashed' ? 'selected' : '' }}>Trashed</option>
            </select>
        </div>

        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3" title="Filter"><i class="bi bi-filter"></i></button>
            <a href="{{ route('admin.employee.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Employee Code & Name', 'Department & Designation', 'Branch & Company', 'Contact & Basic Salary', 'Status', 'Actions']">
        @forelse($employees as $employee)
            <tr class="{{ $employee->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <a href="{{ route('admin.employee.show', $employee->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $employee->full_name }}</a>
                            <div class="font-monospace small text-primary fw-semibold">{{ $employee->employee_code }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small fw-semibold text-dark">{{ $employee->department->name ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $employee->designation->title ?? 'N/A' }}</small>
                </td>
                <td>
                    <div class="small fw-semibold text-dark"><i class="bi bi-building me-1 text-warning"></i>{{ $employee->branch->name ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $employee->company->name ?? 'N/A' }}</small>
                </td>
                <td>
                    <div class="small"><i class="bi bi-telephone me-1 text-muted"></i>{{ $employee->phone ?? 'N/A' }}</div>
                    <small class="font-monospace fw-bold text-success">₹{{ number_format($employee->basic_salary, 2) }}</small>
                </td>
                <td>
                    @if($employee->trashed())
                        <span class="badge bg-danger text-white rounded-pill"><i class="bi bi-trash me-1"></i>Deleted</span>
                    @elseif($employee->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @elseif($employee->status === 'on_leave')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><i class="bi bi-clock me-1"></i>On Leave</span>
                    @elseif($employee->status === 'resigned')
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-person-dash me-1"></i>Resigned</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle me-1"></i>Terminated</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        @can('employee.view')
                            <a href="{{ route('admin.employee.show', $employee->id) }}" class="btn btn-light btn-sm border" title="View Profile"><i class="bi bi-eye text-secondary"></i></a>
                        @endcan

                        @if($employee->trashed())
                            @can('employee.restore')
                                <form action="{{ route('admin.employee.restore', $employee->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-light btn-sm border text-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @endcan
                        @else
                            @can('employee.edit')
                                <a href="{{ route('admin.employee.edit', $employee->id) }}" class="btn btn-light btn-sm border" title="Edit Profile"><i class="bi bi-pencil text-primary"></i></a>
                            @endcan

                            @can('employee.delete')
                                <form action="{{ route('admin.employee.destroy', $employee->id) }}" method="POST" onsubmit="return confirm('Soft delete this employee profile?');">
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
                <td colspan="6" class="text-center py-4 text-muted">No employees found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $employees->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $employees->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $employees->total() }}</span> employees
        </div>
        <div class="d-flex align-items-center">
            {{ $employees->links() }}
        </div>
    </div>
</x-ui.card>
@endsection
