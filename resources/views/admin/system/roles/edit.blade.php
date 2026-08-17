@extends('layouts.admin')

@section('title', 'Edit Role - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Role: {{ $role->name }}</h4>
        <p class="text-muted small mb-0">Modify role title and permission matrix assignments.</p>
    </div>
    <a href="{{ route('admin.system.roles.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Role List
    </a>
</div>

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.system.roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="form-label small fw-bold text-secondary">Role Title *</label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control bg-light" required {{ $role->name === 'Super Admin' ? 'readonly' : '' }}>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Module Permission Matrix</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleAllPermissions(true)">
                    <i class="bi bi-check-all me-1"></i> Select All
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="toggleAllPermissions(false)">
                    <i class="bi bi-x-lg me-1"></i> Deselect All
                </button>
            </div>
        </div>

        <div class="row g-4 mb-4">
            @foreach($permissions as $group => $groupPermissions)
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <h6 class="fw-bold text-uppercase small text-primary mb-0">
                                <i class="bi bi-folder-fill me-1 text-primary"></i> {{ strtoupper(str_replace('_', ' ', $group)) }}
                            </h6>
                            <div class="form-check m-0">
                                <input class="form-check-input group-toggle" type="checkbox" id="group_{{ $group }}" onchange="toggleGroupPermissions('{{ $group }}', this.checked)">
                                <label class="form-check-label text-muted small" for="group_{{ $group }}" style="font-size: 0.75rem;">All</label>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            @foreach($groupPermissions as $permission)
                                <div class="form-check">
                                    <input class="form-check-input perm-check perm-group-{{ $group }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}" {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label small text-dark d-flex align-items-center justify-content-between pe-2" for="perm_{{ $permission->id }}">
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
                <i class="bi bi-check-circle me-1"></i> Update Role Permissions
            </button>
            <a href="{{ route('admin.system.roles.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
        </div>
    </form>
</x-ui.card>

<script>
    function toggleAllPermissions(checked) {
        document.querySelectorAll('.perm-check').forEach(cb => cb.checked = checked);
        document.querySelectorAll('.group-toggle').forEach(cb => cb.checked = checked);
    }

    function toggleGroupPermissions(group, checked) {
        document.querySelectorAll('.perm-group-' + group).forEach(cb => cb.checked = checked);
    }
</script>
@endsection
