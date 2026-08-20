@extends('layouts.admin')

@section('title', 'Supplier / Vendor Management - Grihalaxmi Finance ERP Pro')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-truck text-primary me-2"></i>Supplier & Vendor Directory
        </h4>
        <p class="text-muted small mb-0">Manage product vendors, financial ledgers, purchase history, and outstanding payables.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-cart-check me-1"></i> Product Purchases
        </a>
        @can('supplier.create')
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-person-plus-fill me-1"></i> Add Supplier
            </a>
        @endcan
    </div>
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

<!-- Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-primary border-4">
            <div class="text-muted small fw-bold text-uppercase">Total Vendors</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($metrics['total_suppliers'] ?? 0) }}</div>
            <div class="small text-success mt-1"><i class="bi bi-arrow-up-short"></i> Registered</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-success border-4">
            <div class="text-muted small fw-bold text-uppercase">Active Vendors</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ number_format($metrics['active_suppliers'] ?? 0) }}</div>
            <div class="small text-muted mt-1">Operational</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-info border-4">
            <div class="text-muted small fw-bold text-uppercase">Total Purchases</div>
            <div class="fs-5 fw-bold text-info mt-1">₹{{ number_format($metrics['total_purchase_value'] ?? 0, 2) }}</div>
            <div class="small text-muted mt-1">Lifetime Invoiced</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-danger border-4">
            <div class="text-muted small fw-bold text-uppercase">Outstanding Payable</div>
            <div class="fs-5 fw-bold text-danger mt-1">₹{{ number_format($metrics['total_outstanding'] ?? 0, 2) }}</div>
            <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>Pending Due</div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 h-100 border-start border-warning border-4">
            <div class="text-muted small fw-bold text-uppercase">Payments (This Month)</div>
            <div class="fs-5 fw-bold text-dark mt-1">₹{{ number_format($metrics['payments_this_month'] ?? 0, 2) }}</div>
            <div class="small text-muted mt-1">{{ $metrics['new_suppliers_this_month'] ?? 0 }} New Suppliers Joined</div>
        </div>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.suppliers.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Search Supplier</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Name, Company, Code, Mobile, GSTIN..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Supplier Code</label>
                <input type="text" name="supplier_code" class="form-control" placeholder="SUP-2026-..." value="{{ $filters['supplier_code'] ?? '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Mobile</label>
                <input type="text" name="mobile" class="form-control" placeholder="Mobile..." value="{{ $filters['mobile'] ?? '' }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Supplier Type</label>
                <select name="supplier_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="company" {{ ($filters['supplier_type'] ?? '') === 'company' ? 'selected' : '' }}>Company</option>
                    <option value="individual" {{ ($filters['supplier_type'] ?? '') === 'individual' ? 'selected' : '' }}>Individual</option>
                    <option value="distributor" {{ ($filters['supplier_type'] ?? '') === 'distributor' ? 'selected' : '' }}>Distributor</option>
                    <option value="manufacturer" {{ ($filters['supplier_type'] ?? '') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                    <option value="other" {{ ($filters['supplier_type'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-1 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary w-100" title="Filter"><i class="bi bi-funnel-fill"></i></button>
                <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary w-100" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Supplier Data Table -->
<div class="card border-0 shadow-sm rounded-3 table-responsive-cards">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small text-uppercase">
                <tr>
                    <th scope="col" class="ps-3 py-3">Code</th>
                    <th scope="col" class="py-3">Supplier / Company Name</th>
                    <th scope="col" class="py-3">Contact Person & Mobile</th>
                    <th scope="col" class="py-3">GSTIN / PAN</th>
                    <th scope="col" class="py-3 text-end">Total Purchase</th>
                    <th scope="col" class="py-3 text-end">Total Paid</th>
                    <th scope="col" class="py-3 text-end">Outstanding</th>
                    <th scope="col" class="py-3 text-center">Status</th>
                    <th scope="col" class="pe-3 py-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $sup)
                    <tr>
                        <td class="ps-3 py-3 font-monospace fw-bold text-primary" data-label="Supplier Code">
                            <a href="{{ route('admin.suppliers.show', $sup->id) }}" class="text-decoration-none text-primary">
                                {{ $sup->supplier_code }}
                            </a>
                        </td>
                        <td class="py-3" data-label="Supplier Name">
                            <div class="fw-bold text-dark">{{ $sup->supplier_name }}</div>
                            @if($sup->company_name && $sup->company_name !== $sup->supplier_name)
                                <div class="small text-muted"><i class="bi bi-building me-1"></i>{{ $sup->company_name }}</div>
                            @endif
                            <div class="small text-muted">{{ ucfirst($sup->supplier_type) }}</div>
                        </td>
                        <td class="py-3" data-label="Contact">
                            <div class="fw-bold text-dark"><i class="bi bi-person me-1"></i>{{ $sup->contact_person ?: 'N/A' }}</div>
                            <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $sup->mobile }}</div>
                        </td>
                        <td class="py-3 small font-monospace" data-label="GST / PAN">
                            @if($sup->gstin)
                                <div class="text-dark"><span class="badge bg-light text-dark border me-1">GST</span>{{ $sup->gstin }}</div>
                            @endif
                            @if($sup->pan)
                                <div class="text-muted"><span class="badge bg-light text-secondary border me-1">PAN</span>{{ $sup->pan }}</div>
                            @endif
                            @if(!$sup->gstin && !$sup->pan)
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td class="py-3 text-end fw-bold text-dark" data-label="Total Purchase">
                            ₹{{ number_format($sup->total_purchase, 2) }}
                        </td>
                        <td class="py-3 text-end text-success fw-bold" data-label="Total Paid">
                            ₹{{ number_format($sup->total_paid, 2) }}
                        </td>
                        <td class="py-3 text-end fw-bold {{ $sup->outstanding_payable > 0 ? 'text-danger' : 'text-secondary' }}" data-label="Outstanding">
                            ₹{{ number_format($sup->outstanding_payable, 2) }}
                        </td>
                        <td class="py-3 text-center" data-label="Status">
                            @if($sup->status === 'active')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Inactive</span>
                            @endif
                        </td>
                        <td class="pe-3 py-3 text-end" data-label="Actions">
                            <div class="btn-group w-100 w-md-auto">
                                @can('supplier.view')
                                    <a href="{{ route('admin.suppliers.show', $sup->id) }}" class="btn btn-sm btn-outline-primary fw-bold" title="View Profile">
                                        <i class="bi bi-eye me-1"></i> Profile
                                    </a>
                                @endcan
                                @can('supplier.edit')
                                    <a href="{{ route('admin.suppliers.edit', $sup->id) }}" class="btn btn-sm btn-outline-warning fw-bold" title="Edit Supplier">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                @endcan
                                @can('supplier.delete')
                                    <form action="{{ route('admin.suppliers.destroy', $sup->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Supplier">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-truck fs-1 d-block text-secondary mb-2"></i>
                            No suppliers or vendors found matching the specified filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($suppliers->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $suppliers->links() }}
        </div>
    @endif
</div>
@endsection
