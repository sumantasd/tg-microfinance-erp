@extends('layouts.admin')

@section('title', $company->name . ' Profile - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-building text-primary me-2"></i>{{ $company->name }}</h4>
        <p class="text-muted small mb-0">Company Code: <span class="font-monospace fw-bold text-primary">{{ $company->code }}</span></p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.company.edit', $company->id) }}" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
        <a href="{{ route('admin.company.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <x-ui.card class="p-4 text-center shadow-sm h-100">
            <div class="bg-primary-subtle text-primary rounded-circle p-3 mx-auto mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 70px; height: 70px;">
                <i class="bi bi-buildings fs-2"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">{{ $company->name }}</h5>
            <div class="font-monospace text-primary fw-bold mb-3">{{ $company->code }}</div>
            
            <div class="mb-4">
                @if($company->is_active)
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fs-6"><i class="bi bi-check-circle me-1"></i>Active Enterprise</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1.5 fs-6"><i class="bi bi-pause-circle me-1"></i>Inactive Enterprise</span>
                @endif
            </div>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Registered On:</span>
                    <span class="fw-semibold text-dark">{{ $company->created_at ? $company->created_at->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Created By:</span>
                    <span class="fw-semibold text-dark">{{ $company->creator->name ?? 'System Admin' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Base Currency:</span>
                    <span class="font-monospace fw-bold text-dark">{{ $company->currency_symbol }} {{ $company->currency_code }}</span>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="col-lg-8">
        <x-ui.card class="p-4 shadow-sm mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Corporate Identification & Contact</h6>
            
            <div class="row g-3 text-dark">
                <div class="col-md-6">
                    <small class="text-muted d-block">Registration / CIN</small>
                    <span class="fw-semibold">{{ $company->registration_number ?? 'Not Provided' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">GST / Tax Identification</small>
                    <span class="fw-semibold">{{ $company->tax_id ?? 'Not Provided' }}</span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Official Email Address</small>
                    <a href="mailto:{{ $company->email }}" class="text-decoration-none fw-semibold">{{ $company->email }}</a>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Phone / Contact</small>
                    <span class="fw-semibold">{{ $company->phone }}</span>
                </div>
                <div class="col-12 border-top pt-2">
                    <small class="text-muted d-block">Registered Headquarters Address</small>
                    <span class="fw-medium">{{ $company->address }}</span>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                @can('branch.create')
                    <a href="{{ route('admin.branch.create') }}?company_id={{ $company->id }}" class="btn btn-sm btn-primary rounded-pill"><i class="bi bi-plus-circle me-1"></i>Add Branch</a>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Branch Name & Code</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Vault Limit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($company->branches as $branch)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.branch.show', $branch->id) }}" class="fw-bold text-dark text-decoration-none">{{ $branch->name }}</a>
                                    <div class="font-monospace small text-primary">{{ $branch->code }}</div>
                                </td>
                                <td>{{ $branch->city }}, {{ $branch->state }}</td>
                                <td>{{ $branch->phone }}</td>
                                <td class="font-monospace fw-bold">₹{{ number_format($branch->vault_cash_limit, 2) }}</td>
                                <td>
                                    @if($branch->is_active)
                                        <span class="badge bg-success-subtle text-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">No branches registered under this company yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
