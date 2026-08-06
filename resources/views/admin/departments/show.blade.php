@extends('layouts.admin')

@section('title', $department->name . ' Details - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-diagram-2 text-primary me-2"></i>{{ $department->name }}</h4>
        <p class="text-muted small mb-0">Department Code: <span class="font-monospace fw-bold text-primary">{{ $department->code }}</span> | Company: <strong>{{ $department->company->name ?? 'N/A' }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        @can('department.edit')
            <a href="{{ route('admin.department.edit', $department->id) }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Department
            </a>
        @endcan
        <a href="{{ route('admin.department.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<x-ui.card class="p-4 shadow-sm mb-4">
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-workspace text-primary me-2"></i>Designations in this Department ({{ $department->designations->count() }})</h6>
    <div class="table-responsive">
        <table class="table align-middle table-sm">
            <thead class="bg-light">
                <tr>
                    <th>Title & Code</th>
                    <th>Staff Count</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($department->designations as $designation)
                    <tr>
                        <td>
                            <a href="{{ route('admin.designation.show', $designation->id) }}" class="fw-bold text-dark text-decoration-none">{{ $designation->title }}</a>
                            <div class="font-monospace small text-muted">{{ $designation->code ?? 'N/A' }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $designation->employees_count ?? 0 }} Staff</span></td>
                        <td>
                            @if($designation->is_active)
                                <span class="badge bg-success-subtle text-success rounded-pill">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-3 text-muted">No designations registered under this department.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
