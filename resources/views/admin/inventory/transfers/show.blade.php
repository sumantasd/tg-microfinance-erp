@extends('layouts.admin')

@section('title', 'Transfer Profile - ' . $transfer->transfer_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0 font-monospace">{{ $transfer->transfer_number }}</h4>
            @php
                $badgeClass = match($transfer->status) {
                    'draft' => 'bg-secondary text-white',
                    'requested' => 'bg-info text-white',
                    'approved' => 'bg-primary text-white',
                    'in_transit' => 'bg-warning text-dark',
                    'partially_received' => 'bg-warning text-dark fw-bold border border-dark',
                    'received' => 'bg-success text-white',
                    'discrepancy' => 'bg-danger text-white',
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following:</div>
        <ul class="mb-0 mt-1 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Progress Banner for In-Transit / Receiving -->
@if(in_array($transfer->status, ['in_transit', 'partially_received', 'received', 'discrepancy']))
    @php
        $totalDispatched = $transfer->total_quantity;
        $totalGood = $transfer->total_received_quantity;
        $totalDamaged = $transfer->total_damaged_quantity;
        $totalShortage = $transfer->total_shortage_quantity;
        $totalCancelled = $transfer->total_cancelled_quantity;
        $totalRemaining = $transfer->total_remaining_quantity;
        $percentGood = $totalDispatched > 0 ? min(100, round(($totalGood / $totalDispatched) * 100)) : 0;
    @endphp
    <x-ui.card class="mb-4 shadow-sm border-0 p-3 bg-light-subtle border-start border-4 {{ $transfer->status === 'received' ? 'border-success' : 'border-warning' }}">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
            <div>
                <span class="fw-bold text-dark fs-6">
                    <i class="bi bi-box-seam me-1 text-primary"></i>Receiving Progress: 
                    <span class="text-success">{{ $totalGood }}</span> of <span class="text-dark">{{ $totalDispatched }}</span> Good Units Received
                    ({{ $percentGood }}%)
                </span>
            </div>
            <div class="d-flex gap-2 small">
                <span class="badge bg-warning-subtle text-dark border border-warning">Pending: <strong>{{ $totalRemaining }}</strong></span>
                @if($totalCancelled > 0)
                    <span class="badge bg-danger-subtle text-danger border border-danger">Cancelled: <strong>{{ $totalCancelled }}</strong></span>
                @endif
                @if($totalDamaged > 0)
                    <span class="badge bg-danger-subtle text-danger border border-danger">Damaged: <strong>{{ $totalDamaged }}</strong></span>
                @endif
                @if($totalShortage > 0)
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary">Missing: <strong>{{ $totalShortage }}</strong></span>
                @endif
            </div>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentGood }}%;" aria-valuenow="{{ $percentGood }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </x-ui.card>
@endif

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold text-uppercase">Total Dispatched</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $transfer->total_quantity }} Units</div>
            <div class="small text-muted">{{ $transfer->total_items }} Product Lines</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-success fw-bold text-uppercase">Good Received</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ $transfer->total_received_quantity }} Units</div>
            <div class="small text-muted">Added to sellable stock</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-dark fw-bold text-uppercase">Remaining Pending</div>
            <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ $transfer->total_remaining_quantity }} Units</div>
            <div class="small text-muted">Pending arrival/inspection</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle">
            <div class="small text-info fw-bold text-uppercase">Total Valuation</div>
            <div class="fs-4 fw-bold text-info mt-1 font-monospace">₹{{ number_format($transfer->total_value, 2) }}</div>
            <div class="small text-muted">Cost Valuation</div>
        </x-ui.card>
    </div>
</div>

<!-- Workflow Action Bar -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-white">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="fw-bold text-dark"><i class="bi bi-gear-fill me-1 text-primary"></i>Available Actions:</div>
        <div class="d-flex flex-wrap gap-2">
            {{-- Dispatch Actions (for Warehouse / HQ Users) --}}
            @if(in_array($transfer->status, ['draft', 'approved', 'requested']))
                @can('inventory.transfer.dispatch')
                    @unless(auth()->user()->hasRole('Branch Manager'))
                        <form action="{{ route('admin.inventory-transfer.dispatch', $transfer->id) }}" method="POST" onsubmit="return confirm('Dispatching will deduct physical stock from Source Branch. Confirm dispatch?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm">
                                <i class="bi bi-truck me-1"></i> Dispatch Transfer (Ship Goods)
                            </button>
                        </form>
                    @endunless
                @endcan
            @endif

            {{-- Optional Intermediate Request step --}}
            @if($transfer->status === 'draft')
                @can('inventory.transfer.create')
                    @unless(auth()->user()->hasRole('Branch Manager'))
                        <form action="{{ route('admin.inventory-transfer.request', $transfer->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-info fw-bold"><i class="bi bi-send me-1"></i> Request Approval</button>
                        </form>
                    @endunless
                @endcan
            @endif

            {{-- Optional Intermediate Approve/Reject step --}}
            @if(in_array($transfer->status, ['draft', 'requested']))
                @can('inventory.transfer.approve')
                    @unless(auth()->user()->hasRole('Branch Manager'))
                        <form action="{{ route('admin.inventory-transfer.approve', $transfer->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg me-1"></i> Approve Transfer</button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject Transfer
                        </button>
                    @endunless
                @endcan
            @endif

            {{-- Branch Receiving Actions --}}
            @if(in_array($transfer->status, ['in_transit', 'partially_received']))
                @can('inventory.transfer.receive')
                    @php
                        $user = auth()->user();
                        $canReceiveThisBranch = $user->isSuperAdmin() || $user->isCompanyAdmin() || ((int)$user->branch_id === (int)$transfer->destination_branch_id);
                    @endphp
                    @if($canReceiveThisBranch)
                        {{-- Button 1: Interactive / Partial Receiving Modal --}}
                        <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#receiveModal">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Receive Goods (Partial / Inspect)
                        </button>

                        {{-- Button 2: Quick One-Click Receive All Remaining --}}
                        <form action="{{ route('admin.inventory-transfer.receive', $transfer->id) }}" method="POST" onsubmit="return confirm('Confirm receipt of ALL remaining {{ $transfer->total_remaining_quantity }} units in good condition? Stock will be added to your branch.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success fw-bold">
                                <i class="bi bi-check2-all me-1"></i> Receive All Remaining Good
                            </button>
                        </form>
                    @endif
                @endcan
            @endif

            {{-- Cancel Remaining Pending Stock (when goods will never arrive) --}}
            @if(in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy']) && $transfer->total_remaining_quantity > 0)
                @can('inventory.transfer.cancel')
                    @unless(auth()->user()->hasRole('Branch Manager'))
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#cancelRemainingModal">
                            <i class="bi bi-x-octagon me-1"></i> Cancel Pending Stock
                        </button>
                    @endunless
                @endcan
            @endif

            {{-- Cancellation of whole transfer before dispatch --}}
            @if(in_array($transfer->status, ['draft', 'requested', 'approved']))
                @can('inventory.transfer.cancel')
                    @unless(auth()->user()->hasRole('Branch Manager'))
                        <form action="{{ route('admin.inventory-transfer.cancel', $transfer->id) }}" method="POST" onsubmit="return confirm('Cancel this transfer?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-slash-circle me-1"></i> Cancel Transfer</button>
                        </form>
                    @endunless
                @endcan
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Line Items Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-check text-info me-2"></i>Transfer Line Items</h5>
        @if(in_array($transfer->status, ['in_transit', 'partially_received']))
            @can('inventory.transfer.receive')
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#receiveModal">
                    <i class="bi bi-plus-circle me-1"></i> Record Receipt
                </button>
            @endcan
        @endif
    </div>

    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Product Name & Category</th>
            <th scope="col" class="py-3 px-3">SKU</th>
            <th scope="col" class="py-3 px-3 text-center">Dispatched</th>
            <th scope="col" class="py-3 px-3 text-center">Good Recv</th>
            <th scope="col" class="py-3 px-3 text-center">Damaged</th>
            <th scope="col" class="py-3 px-3 text-center">Missing</th>
            <th scope="col" class="py-3 px-3 text-center">Cancelled</th>
            <th scope="col" class="py-3 px-3 text-center">Pending</th>
            <th scope="col" class="py-3 px-3 text-end">Line Status</th>
            <th scope="col" class="py-3 px-3 text-center">Action</th>
        </x-slot:headers>

        @foreach($transfer->items as $item)
            @php
                $itemRemaining = $item->remaining_quantity;
                $isItemComplete = $item->is_fully_received;
            @endphp
            <tr>
                <td class="px-3 py-3">
                    <div class="fw-bold text-dark">{{ $item->product->name ?? 'N/A' }}</div>
                    <div class="small text-muted">{{ $item->product->categoryRel->name ?? $item->product->category ?? 'General' }} | {{ $item->product->brand ?? 'Generic' }}</div>
                    @if($item->discrepancy_remarks)
                        <div class="small text-danger fst-italic mt-1"><i class="bi bi-info-circle me-1"></i>{{ $item->discrepancy_remarks }}</div>
                    @endif
                </td>
                <td class="px-3 py-3 font-monospace text-info small">{{ $item->product->sku ?? '' }}</td>
                <td class="px-3 py-3 text-center fw-bold text-dark fs-6">{{ $item->quantity }}</td>
                <td class="px-3 py-3 text-center fw-bold text-success">{{ $item->received_quantity }}</td>
                <td class="px-3 py-3 text-center fw-bold text-danger">{{ $item->damaged_quantity ?: '-' }}</td>
                <td class="px-3 py-3 text-center fw-bold text-secondary">{{ $item->shortage_quantity ?: '-' }}</td>
                <td class="px-3 py-3 text-center fw-bold text-danger">{{ $item->cancelled_quantity ?: '-' }}</td>
                <td class="px-3 py-3 text-center fw-bold {{ $itemRemaining > 0 ? 'text-warning-emphasis' : 'text-muted' }}">
                    {{ $itemRemaining }}
                </td>
                <td class="px-3 py-3 text-end">
                    @if($isItemComplete)
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1">Fully Received</span>
                    @elseif($item->cancelled_quantity > 0 && $itemRemaining === 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1">Cancelled</span>
                    @elseif($item->received_quantity > 0 || $item->damaged_quantity > 0 || $item->shortage_quantity > 0 || $item->cancelled_quantity > 0)
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold px-2 py-1">Partial</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Pending</span>
                    @endif
                    @if($item->cancellation_reason)
                        <div class="small text-danger fst-italic mt-1"><i class="bi bi-x-circle me-1"></i>Cancelled ({{ $item->cancelled_quantity }}): {{ $item->cancellation_reason }}</div>
                    @endif
                </td>
                <td class="px-3 py-3 text-center">
                    @if($itemRemaining > 0 && in_array($transfer->status, ['in_transit', 'partially_received', 'discrepancy']))
                        @can('inventory.transfer.cancel')
                            @unless(auth()->user()->hasRole('Branch Manager'))
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 small" title="Cancel remaining quantity" onclick="openCancelItemModal({{ $item->id }}, '{{ addslashes($item->product->name ?? 'Product') }}', {{ $itemRemaining }})">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                            @endunless
                        @endcan
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
</x-ui.card>

<!-- Receipt History -->
@if($transfer->receipts->isNotEmpty())
    <x-ui.card class="shadow-sm border-0 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="bi bi-clock-history text-success me-2"></i>Receipt History ({{ $transfer->receipts->count() }})
        </h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover">
                <thead class="table-light small">
                    <tr>
                        <th class="py-2">Receipt #</th>
                        <th class="py-2">Received Date & Time</th>
                        <th class="py-2">Received By</th>
                        <th class="py-2 text-center text-success">Good Units</th>
                        <th class="py-2 text-center text-danger">Damaged</th>
                        <th class="py-2 text-center text-secondary">Missing</th>
                        <th class="py-2">Notes</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @foreach($transfer->receipts as $rcp)
                        <tr>
                            <td class="fw-bold font-monospace text-primary">{{ $rcp->receipt_number }}</td>
                            <td>{{ $rcp->received_at ? $rcp->received_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td>{{ $rcp->receiver->name ?? 'Staff' }}</td>
                            <td class="text-center fw-bold text-success">+{{ $rcp->total_good_quantity }}</td>
                            <td class="text-center fw-bold text-danger">{{ $rcp->total_damaged_quantity ?: '-' }}</td>
                            <td class="text-center fw-bold text-secondary">{{ $rcp->total_shortage_quantity ?: '-' }}</td>
                            <td class="text-muted">{{ $rcp->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endif

<!-- Cancellation History -->
@if($transfer->cancellations->isNotEmpty())
    <x-ui.card class="shadow-sm border-0 p-4 mb-4">
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="bi bi-x-octagon text-danger me-2"></i>Item Cancellation History ({{ $transfer->cancellations->count() }})
        </h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle table-hover">
                <thead class="table-light small">
                    <tr>
                        <th class="py-2">Date & Time</th>
                        <th class="py-2">Cancelled By</th>
                        <th class="py-2">Scope</th>
                        <th class="py-2 text-center text-danger">Total Units Cancelled</th>
                        <th class="py-2">Items Affected</th>
                        <th class="py-2">Reason</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @foreach($transfer->cancellations as $canc)
                        <tr>
                            <td>{{ $canc->cancelled_at ? $canc->cancelled_at->format('d M Y, h:i A') : 'N/A' }}</td>
                            <td class="fw-bold">{{ $canc->canceller->name ?? 'Admin' }}</td>
                            <td>
                                @if($canc->scope === 'category')
                                    <span class="badge bg-info-subtle text-info border">Category: {{ $canc->category->name ?? 'N/A' }}</span>
                                @elseif($canc->scope === 'all_remaining')
                                    <span class="badge bg-warning-subtle text-dark border">All Remaining</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Specific Items</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold text-danger">-{{ $canc->total_cancelled_quantity }}</td>
                            <td>
                                @foreach($canc->items as $cItem)
                                    <div>{{ $cItem->product->name ?? 'Product' }}: <strong class="text-danger">-{{ $cItem->cancelled_quantity }}</strong></div>
                                @endforeach
                            </td>
                            <td class="text-danger fw-semibold">{{ $canc->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endif

<!-- Audit Timeline -->
<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-shield-check text-secondary me-2"></i>Workflow Audit Trail</h5>
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
            <label class="text-muted fw-bold d-block">Last Received By & Date</label>
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

{{-- RECEIVE MODAL --}}
@if(in_array($transfer->status, ['in_transit', 'partially_received']))
<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('admin.inventory-transfer.receive', $transfer->id) }}" method="POST" id="partialReceiveForm">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Receive Products - {{ $transfer->transfer_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Enter the physical quantities received in this batch. <strong>Accepted Good</strong> quantities will immediately be credited to your branch inventory. <strong>Damaged</strong> and <strong>Missing</strong> quantities will NOT enter sellable stock. Unreceived quantities remain pending for subsequent receipts.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-bold small text-dark">Line Items Pending Receipt:</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-success fw-bold" id="btnFillAllGood">
                                <i class="bi bi-check2-all me-1"></i> Fill All Remaining as Good
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearQuantities">
                                Reset All to 0
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light small">
                                <tr>
                                    <th style="width: 30%;">Product Details</th>
                                    <th class="text-center" style="width: 10%;">Pending Qty</th>
                                    <th class="text-center" style="width: 15%;"><span class="text-success fw-bold">Good Recv *</span></th>
                                    <th class="text-center" style="width: 15%;"><span class="text-danger fw-bold">Damaged</span></th>
                                    <th class="text-center" style="width: 15%;"><span class="text-secondary fw-bold">Missing</span></th>
                                    <th style="width: 15%;">Discrepancy Note</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($transfer->items as $item)
                                    @php $rem = $item->remaining_quantity; @endphp
                                    <tr class="receive-row {{ $rem === 0 ? 'table-light opacity-50' : '' }}" data-item-id="{{ $item->id }}" data-remaining="{{ $rem }}">
                                        <td>
                                            <input type="hidden" name="items[{{ $item->id }}][transfer_item_id]" value="{{ $item->id }}">
                                            <input type="hidden" name="items[{{ $item->id }}][product_id]" value="{{ $item->product_id }}">
                                            <div class="fw-bold text-dark">{{ $item->product->name ?? 'N/A' }}</div>
                                            <div class="text-muted font-monospace small">SKU: {{ $item->product->sku ?? '' }} | {{ $item->product->categoryRel->name ?? $item->product->category ?? '' }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">Dispatched: {{ $item->quantity }} | Prev Good: {{ $item->received_quantity }}</div>
                                        </td>
                                        <td class="text-center fw-bold fs-6 text-warning-emphasis">
                                            {{ $rem }}
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[{{ $item->id }}][good_quantity]" 
                                                   class="form-control form-control-sm text-center input-good fw-bold text-success border-success" 
                                                   min="0" 
                                                   max="{{ $rem }}" 
                                                   value="{{ $rem > 0 ? $rem : 0 }}" 
                                                   {{ $rem === 0 ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[{{ $item->id }}][damaged_quantity]" 
                                                   class="form-control form-control-sm text-center input-damaged text-danger border-danger" 
                                                   min="0" 
                                                   max="{{ $rem }}" 
                                                   value="0" 
                                                   {{ $rem === 0 ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="items[{{ $item->id }}][shortage_quantity]" 
                                                   class="form-control form-control-sm text-center input-shortage text-secondary" 
                                                   min="0" 
                                                   max="{{ $rem }}" 
                                                   value="0" 
                                                   {{ $rem === 0 ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="items[{{ $item->id }}][remarks]" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="Notes (optional)" 
                                                   {{ $rem === 0 ? 'disabled' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small fw-bold text-muted">Batch Receipt Notes / Summary</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Received via Vehicle #JH-01-AB-1234, Carton #2 was wet and damaged."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4" id="btnSubmitReceipt">
                        <i class="bi bi-check-circle me-1"></i> Confirm & Process Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const receiveModal = document.getElementById('receiveModal');
    if (!receiveModal) return;

    const btnFillAllGood = document.getElementById('btnFillAllGood');
    const btnClearQuantities = document.getElementById('btnClearQuantities');
    const rows = receiveModal.querySelectorAll('.receive-row');

    if (btnFillAllGood) {
        btnFillAllGood.addEventListener('click', function () {
            rows.forEach(r => {
                const rem = parseInt(r.getAttribute('data-remaining') || '0', 10);
                const goodInp = r.querySelector('.input-good');
                const damInp = r.querySelector('.input-damaged');
                const shortInp = r.querySelector('.input-shortage');
                if (goodInp && !goodInp.disabled) goodInp.value = rem;
                if (damInp && !damInp.disabled) damInp.value = 0;
                if (shortInp && !shortInp.disabled) shortInp.value = 0;
            });
        });
    }

    if (btnClearQuantities) {
        btnClearQuantities.addEventListener('click', function () {
            rows.forEach(r => {
                const goodInp = r.querySelector('.input-good');
                const damInp = r.querySelector('.input-damaged');
                const shortInp = r.querySelector('.input-shortage');
                if (goodInp && !goodInp.disabled) goodInp.value = 0;
                if (damInp && !damInp.disabled) damInp.value = 0;
                if (shortInp && !shortInp.disabled) shortInp.value = 0;
            });
        });
    }

    // Row-level validation ensuring Good + Damaged + Shortage <= Remaining
    rows.forEach(r => {
        const rem = parseInt(r.getAttribute('data-remaining') || '0', 10);
        const inputs = r.querySelectorAll('input[type="number"]');

        inputs.forEach(inp => {
            inp.addEventListener('input', function () {
                const goodVal = parseInt(r.querySelector('.input-good')?.value || '0', 10);
                const damVal = parseInt(r.querySelector('.input-damaged')?.value || '0', 10);
                const shortVal = parseInt(r.querySelector('.input-shortage')?.value || '0', 10);
                const total = goodVal + damVal + shortVal;

                if (total > rem) {
                    this.classList.add('is-invalid');
                } else {
                    inputs.forEach(i => i.classList.remove('is-invalid'));
                }
            });
        });
    });
});
</script>
@endpush
@endif

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

<!-- Cancel Remaining Pending Quantities Modal -->
<div class="modal fade" id="cancelRemainingModal" tabindex="-1" aria-labelledby="cancelRemainingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="{{ route('admin.inventory-transfer.cancel-remaining', $transfer->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="cancelRemainingModalLabel">
                        <i class="bi bi-x-octagon me-2"></i>Cancel Pending Transfer Quantities
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning small border-warning">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        This will close pending quantities that will never arrive. <strong>Already received good stock remains untouched.</strong> Cancelled quantities cannot be received later in this transfer.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Cancellation Scope <span class="text-danger">*</span></label>
                        <select name="scope" id="cancellation_scope" class="form-select form-select-sm" required onchange="toggleCancelScopeFields()">
                            <option value="item">Specific Product / Line Item</option>
                            <option value="category">Entire Product Category</option>
                            <option value="all">All Remaining Pending Items</option>
                        </select>
                    </div>

                    <div class="mb-3" id="scope_item_div">
                        <label class="form-label fw-bold small">Select Product <span class="text-danger">*</span></label>
                        <select name="item_id" id="cancellation_item_id" class="form-select form-select-sm">
                            <option value="">-- Choose Pending Product --</option>
                            @foreach($transfer->items as $it)
                                @if($it->remaining_quantity > 0)
                                    <option value="{{ $it->id }}" data-remaining="{{ $it->remaining_quantity }}">
                                        {{ $it->product->name ?? 'N/A' }} ({{ $it->remaining_quantity }} units pending)
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="scope_category_div">
                        <label class="form-label fw-bold small">Select Category <span class="text-danger">*</span></label>
                        @php
                            $pendingCategories = $transfer->items
                                ->filter(fn($it) => $it->remaining_quantity > 0 && $it->product && $it->product->categoryRel)
                                ->map(fn($it) => $it->product->categoryRel)
                                ->unique('id');
                        @endphp
                        <select name="category_id" id="cancellation_category_id" class="form-select form-select-sm">
                            <option value="">-- Choose Category --</option>
                            @foreach($pendingCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="scope_quantity_div">
                        <label class="form-label fw-bold small">Quantity to Cancel (Leave blank to cancel all remaining)</label>
                        <input type="number" name="quantity" id="cancellation_quantity" class="form-control form-control-sm" min="1" placeholder="All remaining">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" id="cancellation_reason" class="form-control form-control-sm" rows="3" required placeholder="e.g. Lost in transit, shortage confirmed by warehouse, stock no longer available"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold">
                        <i class="bi bi-x-circle me-1"></i>Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openCancelItemModal(itemId, productName, remainingQty) {
    const scopeSelect = document.getElementById('cancellation_scope');
    if (scopeSelect) {
        scopeSelect.value = 'item';
        toggleCancelScopeFields();
    }
    const itemSelect = document.getElementById('cancellation_item_id');
    if (itemSelect) {
        itemSelect.value = itemId;
    }
    const qtyInput = document.getElementById('cancellation_quantity');
    if (qtyInput) {
        qtyInput.value = remainingQty;
        qtyInput.max = remainingQty;
    }
    const modalEl = document.getElementById('cancelRemainingModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function toggleCancelScopeFields() {
    const scope = document.getElementById('cancellation_scope').value;
    const itemDiv = document.getElementById('scope_item_div');
    const catDiv = document.getElementById('scope_category_div');
    const qtyDiv = document.getElementById('scope_quantity_div');

    if (scope === 'item') {
        itemDiv.classList.remove('d-none');
        catDiv.classList.add('d-none');
        qtyDiv.classList.remove('d-none');
    } else if (scope === 'category') {
        itemDiv.classList.add('d-none');
        catDiv.classList.remove('d-none');
        qtyDiv.classList.add('d-none');
    } else {
        itemDiv.classList.add('d-none');
        catDiv.classList.add('d-none');
        qtyDiv.classList.add('d-none');
    }
}
</script>
@endpush
@endsection
