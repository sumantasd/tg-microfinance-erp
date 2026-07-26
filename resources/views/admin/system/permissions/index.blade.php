@extends('layouts.admin')

@section('title', 'Permissions Matrix - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-key text-primary me-2"></i>Permissions Matrix</h4>
        <p class="text-muted small mb-0">Overview of all system permission nodes grouped by functional module.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-4">
            @foreach($permissions as $group => $groupPermissions)
                <div class="col-md-6">
                    <x-ui.card class="h-100 p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <h6 class="fw-bold text-uppercase text-primary mb-0"><i class="bi bi-folder2 me-2"></i>{{ strtoupper($group) }}</h6>
                            <span class="badge bg-light text-muted border font-monospace">{{ count($groupPermissions) }} Nodes</span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            @foreach($groupPermissions as $permission)
                                <div class="d-flex justify-content-between align-items-center small p-2 bg-light rounded border">
                                    <code class="fw-bold text-dark">{{ $permission->name }}</code>
                                    <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.65rem;">web</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Add Permission Form -->
    <div class="col-lg-4">
        <x-ui.card class="p-4 shadow-sm sticky-top" style="top: 80px;">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Register New Permission</h5>
            <form action="{{ route('admin.system.permissions.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Permission Name (Dot Notation) *</label>
                    <input type="text" name="name" class="form-control bg-light" placeholder="e.g. audit.export" required>
                    <small class="text-muted d-block mt-1" style="font-size: 0.725rem;">Use standard module.action notation (e.g., users.create).</small>
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Register Permission
                </button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
