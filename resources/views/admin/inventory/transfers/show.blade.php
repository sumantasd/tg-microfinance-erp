@extends('layouts.admin')

@section('title', 'Transfer Profile - ' . $transfer->transfer_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $transfer->transfer_number }}</h4>
            @php
                $badgeClass = match($transfer->status) {
                    'draft' => 'bg-secondary text-white',
                    'requested' => 'bg-info text-white',
                    'approved' => 'bg-primary text-white',
                    'in_transit' => 'bg-warning text-dark',
                    'received' => 'bg-success text-white',
                    'rejected', 'cancelled' => 'bg-danger text-white',
                    default => 'bg-light text-dark'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ str_replace('_', ' ', $transfer->status) }}</span>
        </div>
        <p class="text-muted small mb-0">
            <i class="bi bi-geo-alt text-danger me-1"></i>From: <strong>{{ $transfer->sourceBranch->name ?? 'N/A' }}</strong> 
            <i class="bi bi-arrow-right mx-2 text-muted"></i>
            <i class="bi bi-geo-alt-fill text-success me-1"></i>To: <strong>{{ $transfer->destinationBranch->name ?? 'N/A' }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.inventory-transfer.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
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

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Total Products</div>
            <div class="fs-3 fw-bold text-dark mt-1">{{ $transfer->total_items }} Items</div>
            <div class="small text-muted">{{ $transfer->total_quantity }} Total Quantity</div>
        </x-ui.card>
    </div>

    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle">
            <div class="small text-muted fw-bold uppercase">Total Transfer Value</div>
            <div class="fs-3 fw-bold text-info mt-1 font-monospace">₹{{ number_format($transfer->total_value, 2) }}</div>
            <div class="small text-muted">Cost Valuation</div>
        </x-ui.card>
    </div>

    <div class="col-md-4">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-muted fw-bold uppercase">Current Status</div>
            <div class="fs-4 fw-bold text-dark mt-1 text-capitalize">{{ str_replace('_', ' ', $transfer->status) }}</div>
            <div class="small text-muted">
                @if($transfer->status === 'in_transit')
                    Stock deducted from Source, pending receipt
                @elseif($transfer->status === 'received')
                    Stock added to Destination
                @else
                    Workflow active
                @endif
            </div>
        </x-ui.card>
    </div>
</div>

<!-- Workflow Action Bar -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-bold text-dark"><i class="bi bi-gear-fill me-1 text-primary"></i>Available Actions:</div>
        <div class="d-flex flex-wrap gap-2">
            @if($transfer->status === 'draft')
                @can('inventory.transfer.create')
                    <form action="{{ route('admin.inventory-transfer.request', $transfer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info text-white fw-bold"><i class="bi bi-send me-1"></i> Request Approval</button>
                    </form>
                @endcan
            @endif

            @if(in_array($transfer->status, ['draft', 'requested']))
                @can('inventory.transfer.approve')
                    <form action="{{ route('admin.inventory-transfer.approve', $transfer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg me-1"></i> Approve Transfer</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject Transfer
                    </button>
                @endcan
            @endif

            @if($transfer->status === 'approved')
                @can('inventory.transfer.dispatch')
                    <form action="{{ route('admin.inventory-transfer.dispatch', $transfer->id) }}" method="POST" onsubmit="return confirm('Dispatching will deduct physical stock from Source Branch. Confirm dispatch?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold"><i class="bi bi-truck me-1"></i> Dispatch Transfer (Deduct Source Stock)</button>
                    </form>
                @endcan
            @endif

            @if($transfer->status === 'in_transit')
                @can('inventory.transfer.receive')
                    <form action="{{ route('admin.inventory-transfer.receive', $transfer->id) }}" method="POST" onsubmit="return confirm('Receiving will add physical stock to Destination Branch. Confirm stock receipt?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Receive Stock at Destination</button>
                    </form>
                @endcan
            @endif

            @if(in_array($transfer->status, ['draft', 'requested', 'approved']))
                @can('inventory.transfer.cancel')
                    <form action="{{ route('admin.inventory-transfer.cancel', $transfer->id) }}" method="POST" onsubmit="return confirm('Cancel this transfer?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-slash-circle me-1"></i> Cancel Transfer</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Line Items Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-list-check text-info me-2"></i>Transfer Line Items</h5>
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Product Name</th>
            <th scope="col" class="py-3 px-3">SKU</th>
            <th scope="col" class="py-3 px-3">Quantity</th>
            <th scope="col" class="py-3 px-3">Unit Valuation</th>
            <th scope="col" class="py-3 px-3 text-end">Total Valuation</th>
        </x-slot:headers>

        @foreach($transfer->items as $item)
            <tr>
                <td class="px-3 py-3 fw-bold text-dark">{{ $item->product->name ?? 'N/A' }}</td>
                <td class="px-3 py-3 font-monospace text-info small">{{ $item->product->sku ?? '' }}</td>
                <td class="px-3 py-3 fs-6 fw-bold text-dark">{{ $item->quantity }} Units</td>
                <td class="px-3 py-3 font-monospace small">₹{{ number_format($item->unit_price, 2) }}</td>
                <td class="px-3 py-3 text-end font-monospace fw-bold text-dark">₹{{ number_format($item->total_value, 2) }}</td>
            </tr>
        @endforeach
    </x-ui.data-table>
</x-ui.card>

<!-- Audit Timeline -->
<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary me-2"></i>Workflow Audit Trail</h5>
    <div class="row g-3 small">
        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Created By & Date</label>
            <div>{{ $transfer->requester->name ?? 'System' }}</div>
            <div class="text-muted">{{ $transfer->created_at ? $transfer->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Approved By & Date</label>
            <div>{{ $transfer->approver->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $transfer->approved_at ? $transfer->approved_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Dispatched By & Date</label>
            <div>{{ $transfer->dispatcher->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Received By & Date</label>
            <div>{{ $transfer->receiver->name ?? 'N/A' }}</div>
            <div class="text-muted">{{ $transfer->received_at ? $transfer->received_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        @if($transfer->remarks)
            <div class="col-12 mt-2">
                <label class="text-muted fw-bold d-block">Remarks</label>
                <div class="p-2 bg-light rounded border">{{ $transfer->remarks }}</div>
            </div>
        @endif

        @if($transfer->rejection_reason)
            <div class="col-12 mt-2">
                <label class="text-danger fw-bold d-block">Rejection Reason</label>
                <div class="p-2 bg-danger-subtle text-danger rounded border border-danger-subtle">{{ $transfer->rejection_reason }}</div>
            </div>
        @endif
    </div>
</x-ui.card>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.inventory-transfer.reject', $transfer->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">Reject Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Specify why this transfer is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
