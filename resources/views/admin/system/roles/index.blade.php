@extends('layouts.admin')

@section('title', 'Role Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Role Management</h4>
        <p class="text-muted small mb-0">Manage global system roles and permissions matrix assignments.</p>
    </div>
    @can('roles.create')
        <a href="{{ route('admin.system.roles.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-shield-plus fs-6"></i> Add New Role
        </a>
    @endcan
</div>

<div class="row g-4">
    @foreach($roles as $role)
        <div class="col-md-6 col-lg-4">
            <x-ui.card class="h-100 p-4 shadow-sm border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">{{ $role->name }}</h5>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace small">
                            {{ $role->permissions_count }} Permissions Assigned
                        </span>
                    </div>
                    <div class="bg-primary text-white rounded-circle p-2.5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-shield-lock fs-5"></i>
                    </div>
                </div>

                <div class="small text-muted mb-4">
                    <i class="bi bi-person me-1"></i> {{ $role->users_count }} Assigned Staff Members
                </div>

                <div class="d-flex gap-2 border-top pt-3 mt-auto">
                    @can('roles.edit')
                        <a href="{{ route('admin.system.roles.edit', $role->id) }}" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-bold">
                            <i class="bi bi-pencil me-1"></i> Edit Permissions
                        </a>
                    @endcan
                    @can('roles.delete')
                        @if($role->name !== 'Super Admin')
                            <form action="{{ route('admin.system.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Delete this role?');" class="w-auto">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Delete Role"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    @endcan
                </div>
            </x-ui.card>
        </div>
    @endforeach
</div>
@endsection
