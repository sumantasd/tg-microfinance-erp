@extends('layouts.admin')

@section('title', 'Add New Role - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Create New Role</h4>
        <p class="text-muted small mb-0">Define role title and assign module permission matrix.</p>
    </div>
    <a href="{{ route('admin.system.roles.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Role List
    </a>
</div>

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.system.roles.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="form-label small fw-bold text-secondary">Role Title *</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control bg-light" placeholder="e.g. Loan Supervisor" required>
        </div>

        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-key text-primary me-2"></i>Module Permission Matrix</h6>

        <div class="row g-4 mb-4">
            @foreach($permissions as $group => $groupPermissions)
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded-3 p-3 bg-light h-100">
                        <h6 class="fw-bold text-uppercase small text-primary mb-2 border-bottom pb-2 me-2">{{ strtoupper($group) }} PERMISSIONS</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach($groupPermissions as $permission)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}">
                                    <label class="form-check-label small text-dark" for="perm_{{ $permission->id }}">
                                        <code>{{ $permission->name }}</code>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-check-circle me-1"></i> Create Role
            </button>
            <a href="{{ route('admin.system.roles.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
        </div>
    </form>
</x-ui.card>
@endsection
