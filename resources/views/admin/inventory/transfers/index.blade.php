@extends('layouts.admin')

@section('title', 'Branch Inventory Transfers - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-arrow-left-right text-warning me-2"></i>Branch-to-Branch Stock Transfers
        </h4>
        <p class="text-muted small mb-0">Manage inter-branch product requests, approvals, dispatch, in-transit tracking, and stock receipts.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-boxes me-1"></i> Branch Stock
        </a>
        @can('inventory.transfer.create')
            <a href="{{ route('admin.inventory-transfer.create') }}" class="btn btn-warning text-dark fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Create New Transfer
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

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.inventory-transfer.index') }}" class="row g-3">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Search Transfer</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Transfer #, Remarks..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Source Branch</label>
            <select name="source_branch_id" class="form-select">
                <option value="">All Source Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($filters['source_branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Destination Branch</label>
            <select name="destination_branch_id" class="form-select">
                <option value="">All Destination Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ ($filters['destination_branch_id'] ?? '') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="requested" {{ ($filters['status'] ?? '') === 'requested' ? 'selected' : '' }}>Requested</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="in_transit" {{ ($filters['status'] ?? '') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                <option value="received" {{ ($filters['status'] ?? '') === 'received' ? 'selected' : '' }}>Received</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.inventory-transfer.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Filter</button>
        </div>
    </form>
</x-ui.card>

<!-- Transfers Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Transfer # & Date</th>
            <th scope="col" class="py-3 px-3">Source Branch ➔ Destination</th>
            <th scope="col" class="py-3 px-3">Items & Quantity</th>
            <th scope="col" class="py-3 px-3">Total Value</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($transfers as $t)
            <tr>
                <td class="px-3 py-3">
                    <a href="{{ route('admin.inventory-transfer.show', $t->id) }}" class="fw-bold font-monospace text-primary text-decoration-none hover-primary">{{ $t->transfer_number }}</a>
                    <div class="small text-muted">{{ $t->created_at ? $t->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark"><i class="bi bi-geo-alt text-danger me-1"></i>{{ $t->sourceBranch->name ?? 'N/A' }}</div>
                    <div class="text-muted"><i class="bi bi-arrow-down-short"></i> <i class="bi bi-geo-alt-fill text-success me-1"></i>{{ $t->destinationBranch->name ?? 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <span class="badge bg-light text-dark border">{{ $t->total_items }} Products</span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle ms-1 fw-bold">{{ $t->total_quantity }} Units</span>
                </td>
                <td class="px-3 py-3 small font-monospace fw-bold text-dark">
                    ₹{{ number_format($t->total_value, 2) }}
                </td>
                <td class="px-3 py-3">
                    @php
                        $badgeClass = match($t->status) {
                            'draft' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            'requested' => 'bg-info-subtle text-info border-info-subtle',
                            'approved' => 'bg-primary-subtle text-primary border-primary-subtle',
                            'in_transit' => 'bg-warning-subtle text-dark border-warning-subtle',
                            'received' => 'bg-success-subtle text-success border-success-subtle',
                            'rejected', 'cancelled' => 'bg-danger-subtle text-danger border-danger-subtle',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }} border px-2.5 py-1 text-capitalize fw-bold">
                        {{ str_replace('_', ' ', $t->status) }}
                    </span>
                </td>
                <td class="px-3 py-3 text-end">
                    <a href="{{ route('admin.inventory-transfer.show', $t->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                        <i class="bi bi-eye"></i> Profile
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-arrow-left-right fs-1 d-block text-secondary mb-2"></i>
                    No branch stock transfers found.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($transfers->hasPages())
        <div class="p-3 border-top">
            {{ $transfers->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
