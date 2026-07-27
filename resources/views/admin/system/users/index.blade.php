@extends('layouts.admin')

@section('title', 'User Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-people text-primary me-2"></i>User Management</h4>
        <p class="text-muted small mb-0">Manage enterprise staff accounts, multi-branch assignments, and role privileges.</p>
    </div>
    @can('users.create')
        <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-person-plus fs-6"></i> Add New User
        </a>
    @endcan
</div>

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.system.users.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by name, email, employee ID, mobile...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Account Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ ($filters['status'] ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="locked" {{ ($filters['status'] ?? '') === 'locked' ? 'selected' : '' }}>Locked</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select bg-light">
                <option value="">All Roles</option>
                @foreach($roles as $roleOption)
                    <option value="{{ $roleOption->name }}" {{ ($filters['role'] ?? '') === $roleOption->name ? 'selected' : '' }}>{{ $roleOption->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <button type="button" class="btn btn-outline-secondary w-100 rounded-3" title="Export Users (CSV/Excel)"><i class="bi bi-download"></i></button>
            <a href="{{ route('admin.system.users.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Users Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Avatar & Name', 'Employee ID & Contact', 'Role', 'Branch', 'Status Badge', 'Last Login', 'Actions']">
        @forelse($users as $user)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; flex-shrink: 0;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark mb-0">{{ $user->name }}</div>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="font-monospace small fw-bold text-dark">{{ $user->employee_id ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $user->mobile_number ?? 'No Phone' }}</small>
                </td>
                <td>
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td>
                    <span class="small text-secondary fw-medium"><i class="bi bi-building me-1"></i>Head Office Branch</span>
                </td>
                <td>
                    @if($user->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                    @elseif($user->status === 'inactive')
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                    @elseif($user->status === 'suspended')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><i class="bi bi-pause-circle me-1"></i>Suspended</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-lock me-1"></i>Locked</span>
                    @endif
                </td>
                <td>
                    <small class="text-muted d-block">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</small>
                    <small class="text-muted opacity-75">{{ $user->last_login_ip ?? 'N/A' }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        @can('users.edit')
                            <a href="{{ route('admin.system.users.edit', $user->id) }}" class="btn btn-light btn-sm border" title="Edit User"><i class="bi bi-pencil text-primary"></i></a>
                        @endcan
                        @can('users.delete')
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('admin.system.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Soft delete user account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light btn-sm border text-danger" title="Soft Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No user accounts found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $users->links() }}
    </div>
</x-ui.card>
@endsection
