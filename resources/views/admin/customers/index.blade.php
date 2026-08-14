@extends('layouts.admin')

@section('title', 'Customer & Member Management - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-vcard text-primary me-2"></i>Customer & Member Management</h4>
        <p class="text-muted small mb-0">Manage borrower profiles, savings members, KYC verification, guarantors, and nominees.</p>
    </div>
    @can('customer.create')
        <a href="{{ route('admin.customer.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
            <i class="bi bi-person-plus-fill fs-6"></i> Register Customer
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Bar -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.customer.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light border-start-0" placeholder="Search by ID, name, mobile, member #...">
            </div>
        </div>

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
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="blacklisted" {{ ($filters['status'] ?? '') === 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                <option value="deceased" {{ ($filters['status'] ?? '') === 'deceased' ? 'selected' : '' }}>Deceased</option>
                <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>

        <div class="col-md-2">
            <select name="kyc_status" class="form-select bg-light">
                <option value="">KYC Status</option>
                <option value="verified" {{ ($filters['kyc_status'] ?? '') === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="pending" {{ ($filters['kyc_status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="rejected" {{ ($filters['kyc_status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="col-md-2">
            <select name="customer_type" class="form-select bg-light">
                <option value="">Customer Type</option>
                <option value="individual" {{ ($filters['customer_type'] ?? '') === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="group_member" {{ ($filters['customer_type'] ?? '') === 'group_member' ? 'selected' : '' }}>Group Member</option>
                <option value="micro_enterprise" {{ ($filters['customer_type'] ?? '') === 'micro_enterprise' ? 'selected' : '' }}>Micro Enterprise</option>
                <option value="corporate" {{ ($filters['customer_type'] ?? '') === 'corporate' ? 'selected' : '' }}>Corporate</option>
            </select>
        </div>

        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3" title="Filter"><i class="bi bi-filter"></i></button>
            <a href="{{ route('admin.customer.index') }}" class="btn btn-light border rounded-3" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Customer ID & Name', 'Type & Branch', 'Contact Details', 'Status & KYC', 'Reg. Date', 'Actions']">
        @forelse($customers as $customer)
            <tr class="{{ $customer->trashed() ? 'table-warning opacity-75' : '' }}">
                <td>
                    <div class="d-flex align-items-center gap-2.5">
                        @if($customer->profile_photo_path)
                            <img src="{{ asset('storage/' . $customer->profile_photo_path) }}" alt="{{ $customer->full_name }}" class="rounded-circle object-fit-cover shadow-sm" style="width: 40px; height: 40px;">
                        @else
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px; flex-shrink: 0;">
                                {{ strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <a href="{{ route('admin.customer.show', $customer->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">{{ $customer->full_name }}</a>
                            <div class="font-monospace small text-primary fw-semibold">{{ $customer->customer_code }}</div>
                            @if($customer->member_number)
                                <div class="small text-muted font-monospace">Member #: {{ $customer->member_number }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-semibold text-dark">{{ ucfirst(str_replace('_', ' ', $customer->customer_type)) }}</div>
                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $customer->branch->name ?? 'N/A' }}</div>
                </td>
                <td>
                    <div class="fw-semibold text-dark"><i class="bi bi-telephone text-muted me-1"></i>{{ $customer->mobile_number }}</div>
                    @if($customer->presentAddress)
                        <div class="small text-muted"><i class="bi bi-building me-1"></i>{{ $customer->presentAddress->district }}, {{ $customer->presentAddress->state }}</div>
                    @endif
                </td>
                <td>
                    <!-- Customer Status Badge -->
                    @php
                        $statusBadgeClass = match($customer->status) {
                            'active' => 'bg-success-subtle text-success border border-success-subtle',
                            'inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                            'blacklisted' => 'bg-danger-subtle text-danger border border-danger-subtle',
                            'deceased' => 'bg-dark-subtle text-dark border border-dark-subtle',
                            'closed' => 'bg-warning-subtle text-warning border border-warning-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $statusBadgeClass }} px-2.5 py-1 text-capitalize fw-bold">{{ $customer->status }}</span>

                    <!-- KYC Status Badge -->
                    @php
                        $hasVerifiedKyc = $customer->kycDocuments->contains('verification_status', 'verified');
                        $hasPendingKyc = $customer->kycDocuments->contains('verification_status', 'pending');
                    @endphp
                    @if($hasVerifiedKyc)
                        <span class="badge bg-success text-white px-2 py-0.5 small ms-1" title="KYC Verified"><i class="bi bi-shield-check"></i> Verified</span>
                    @elseif($hasPendingKyc)
                        <span class="badge bg-warning text-dark px-2 py-0.5 small ms-1" title="KYC Pending"><i class="bi bi-clock-history"></i> Pending</span>
                    @else
                        <span class="badge bg-light text-muted border px-2 py-0.5 small ms-1">No KYC</span>
                    @endif
                </td>
                <td class="text-muted small">
                    {{ $customer->registration_date ? $customer->registration_date->format('d M Y') : 'N/A' }}
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><a class="dropdown-item" href="{{ route('admin.customer.show', $customer->id) }}"><i class="bi bi-eye text-primary me-2"></i> View Profile</a></li>
                            @can('customer.edit')
                                <li><a class="dropdown-item" href="{{ route('admin.customer.edit', $customer->id) }}"><i class="bi bi-pencil text-warning me-2"></i> Edit Details</a></li>
                            @endcan
                            @can('customer.change_status')
                                <li><hr class="dropdown-divider"></li>
                                <li class="dropdown-header">Change Status</li>
                                <li>
                                    <form action="{{ route('admin.customer.toggle-status', $customer->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="dropdown-item text-success {{ $customer->status === 'active' ? 'active' : '' }}"><i class="bi bi-check-circle me-2"></i> Mark Active</button>
                                    </form>
                                </li>
                                <li>
                                    <form action="{{ route('admin.customer.toggle-status', $customer->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="inactive">
                                        <button type="submit" class="dropdown-item text-secondary {{ $customer->status === 'inactive' ? 'active' : '' }}"><i class="bi bi-pause-circle me-2"></i> Mark Inactive</button>
                                    </form>
                                </li>
                                <li>
                                    <form action="{{ route('admin.customer.toggle-status', $customer->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="blacklisted">
                                        <button type="submit" class="dropdown-item text-danger {{ $customer->status === 'blacklisted' ? 'active' : '' }}"><i class="bi bi-slash-circle me-2"></i> Mark Blacklisted</button>
                                    </form>
                                </li>
                            @endcan
                            @can('customer.delete')
                                <li><hr class="dropdown-divider"></li>
                                @if($customer->trashed())
                                    <li>
                                        <form action="{{ route('admin.customer.restore', $customer->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-success"><i class="bi bi-arrow-counterclockwise me-2"></i> Restore Customer</button>
                                        </form>
                                    </li>
                                @else
                                    <li>
                                        <form action="{{ route('admin.customer.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Trash Customer</button>
                                        </form>
                                    </li>
                                @endif
                            @endcan
                        </ul>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-person-x fs-1 d-block mb-2 text-muted"></i>
                    No customers found matching the specified filters.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-2 border-top bg-light">
        {{ $customers->links() }}
    </div>
</x-ui.card>
@endsection
