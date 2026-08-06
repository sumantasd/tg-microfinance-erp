@extends('layouts.admin')

@section('title', $branch->name . ' Details - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-building text-warning me-2"></i>{{ $branch->name }}</h4>
        <p class="text-muted small mb-0">Branch Location Code: <span class="font-monospace fw-bold text-primary">{{ $branch->code }}</span> | Parent: <strong>{{ $branch->company->name ?? 'N/A' }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        @can('branch.edit')
            <a href="{{ route('admin.branch.edit', $branch->id) }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Branch
            </a>
        @endcan
        <a href="{{ route('admin.branch.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <x-ui.card class="p-4 text-center shadow-sm h-100">
            <div class="bg-warning-subtle text-warning rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 70px; height: 70px;">
                <i class="bi bi-building fs-2"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">{{ $branch->name }}</h5>
            <div class="font-monospace text-primary fw-bold mb-3">{{ $branch->code }}</div>
            
            <div class="mb-4">
                @if($branch->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-6"><i class="bi bi-check-circle me-1"></i>Operational Branch</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fs-6"><i class="bi bi-pause-circle me-1"></i>Inactive Branch</span>
                @endif
            </div>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Parent Company:</span>
                    <span class="fw-semibold text-dark">{{ $branch->company->name ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Registered On:</span>
                    <span class="fw-semibold text-dark">{{ $branch->created_at ? $branch->created_at->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Created By:</span>
                    <span class="fw-semibold text-dark">{{ $branch->creator->name ?? 'System Admin' }}</span>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="col-lg-8">
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Location, Contact & Vault Reserve Details</h6>
            
            <div class="row g-3 text-dark">
                <div class="col-md-6">
                    <small class="text-muted d-block">Branch Manager</small>
                    <span class="fw-semibold">{{ $branch->manager->name ?? 'Not Assigned' }}</span>
                    @if($branch->manager)
                        <small class="text-muted d-block">{{ $branch->manager->email }}</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Branch Phone / Contact</small>
                    <span class="fw-semibold">{{ $branch->phone }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Branch Email Address</small>
                    <span class="fw-semibold">{{ $branch->email ?? 'N/A' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Location City & State</small>
                    <span class="fw-semibold">{{ $branch->city }}, {{ $branch->state }} ({{ $branch->pincode }})</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Vault Cash Limit</small>
                    <span class="font-monospace fw-bold text-dark fs-6">₹{{ number_format($branch->vault_cash_limit, 2) }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Current Vault Cash Balance</small>
                    <span class="font-monospace fw-bold text-success fs-6">₹{{ number_format($branch->current_vault_balance, 2) }}</span>
                </div>
                <div class="col-12 border-top pt-2">
                    <small class="text-muted d-block">Full Branch Office Address</small>
                    <span class="fw-medium">{{ $branch->address }}</span>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
