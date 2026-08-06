@extends('layouts.admin')

@section('title', $designation->title . ' Details - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-workspace text-primary me-2"></i>{{ $designation->title }}</h4>
        <p class="text-muted small mb-0">Department: <strong>{{ $designation->department->name ?? 'N/A' }}</strong> | Company: <strong>{{ $designation->company->name ?? 'N/A' }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        @can('designation.edit')
            <a href="{{ route('admin.designation.edit', $designation->id) }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Designation
            </a>
        @endcan
        <a href="{{ route('admin.designation.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<x-ui.card class="p-4 shadow-sm mb-4">
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-people text-primary me-2"></i>Employees Assigned to this Designation ({{ $designation->employees->count() }})</h6>
    <div class="table-responsive">
        <table class="table align-middle table-sm">
            <thead class="bg-light">
                <tr>
                    <th>Employee Name & Code</th>
                    <th>Branch</th>
                    <th>Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($designation->employees as $employee)
                    <tr>
                        <td>
                            <a href="{{ route('admin.employee.show', $employee->id) }}" class="fw-bold text-dark text-decoration-none">{{ $employee->full_name }}</a>
                            <div class="font-monospace small text-primary">{{ $employee->employee_code }}</div>
                        </td>
                        <td>{{ $employee->branch->name ?? 'N/A' }}</td>
                        <td>{{ $employee->phone ?? $employee->email ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-success-subtle text-success rounded-pill">{{ strtoupper($employee->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted">No staff assigned to this designation yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
