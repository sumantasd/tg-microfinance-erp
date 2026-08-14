@extends('layouts.admin')

@section('title', 'Purchase Profile - ' . $purchase->purchase_number . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $purchase->purchase_number }}</h4>
            @php
                $badgeClass = match($purchase->purchase_status) {
                    'draft' => 'bg-secondary text-white',
                    'confirmed' => 'bg-info text-white',
                    'received' => 'bg-success text-white',
                    'cancelled' => 'bg-danger text-white',
                    default => 'bg-light text-dark'
                };
            @endphp
            <span class="badge {{ $badgeClass }} px-3 py-1.5 fs-6 text-capitalize">{{ $purchase->purchase_status }}</span>
        </div>
        <p class="text-muted small mb-0">
            Supplier: <strong>{{ $purchase->supplier_name }}</strong> 
            <span class="mx-2">|</span>
            Invoice: <strong>{{ $purchase->supplier_invoice_number ?? 'N/A' }}</strong>
            <span class="mx-2">|</span>
            Branch: <strong>{{ $purchase->branch->name ?? 'N/A' }}</strong>
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.product-purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchases
        </a>
        @if($purchase->purchase_status === 'draft')
            @can('purchase.edit')
                <a href="{{ route('admin.product-purchase.edit', $purchase->id) }}" class="btn btn-outline-primary rounded-pill px-3 fw-bold">
                    <i class="bi bi-pencil me-1"></i> Edit Draft
                </a>
            @endcan
        @endif
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

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-primary-subtle">
            <div class="small text-muted fw-bold uppercase">Grand Total Amount</div>
            <div class="fs-3 fw-bold text-primary mt-1 font-monospace">₹{{ number_format($purchase->grand_total, 2) }}</div>
            <div class="small text-muted">Subtotal: ₹{{ number_format($purchase->subtotal, 2) }} + Tax</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-success-subtle">
            <div class="small text-muted fw-bold uppercase">Paid Amount</div>
            <div class="fs-3 fw-bold text-success mt-1 font-monospace">₹{{ number_format($purchase->paid_amount, 2) }}</div>
            <div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $purchase->payment_status) }}</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-danger-subtle">
            <div class="small text-muted fw-bold uppercase">Due Amount</div>
            <div class="fs-3 fw-bold text-danger mt-1 font-monospace">₹{{ number_format($purchase->due_amount, 2) }}</div>
            <div class="small text-muted">Outstanding Balance</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Inventory Integration Status</div>
            <div class="fs-5 fw-bold text-dark mt-1">
                @if($purchase->purchase_status === 'received')
                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Stock Received</span>
                @else
                    <span class="text-warning"><i class="bi bi-clock me-1"></i>Stock Pending</span>
                @endif
            </div>
            <div class="small text-muted">
                @if($purchase->received_at)
                    Received {{ $purchase->received_at->format('d M Y, h:i A') }}
                @else
                    Not added to physical stock yet
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
            @if($purchase->purchase_status === 'draft')
                <form action="{{ route('admin.product-purchase.confirm', $purchase->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold"><i class="bi bi-check-lg me-1"></i> Confirm Purchase Order</button>
                </form>
            @endif

            @if(in_array($purchase->purchase_status, ['draft', 'confirmed']))
                @can('purchase.receive')
                    <form action="{{ route('admin.product-purchase.receive', $purchase->id) }}" method="POST" onsubmit="return confirm('Receiving purchase will add product quantities into branch inventory stock. Confirm receipt?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-in-down me-1"></i> Receive Goods into Branch Inventory</button>
                    </form>
                @endcan

                @can('purchase.cancel')
                    <form action="{{ route('admin.product-purchase.cancel', $purchase->id) }}" method="POST" onsubmit="return confirm('Cancel this purchase order?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Cancel Purchase</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</x-ui.card>

<!-- Purchase Line Items Table -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-list-check text-primary me-2"></i>Purchased Line Items</h5>
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Product Name</th>
            <th scope="col" class="py-3 px-3">SKU</th>
            <th scope="col" class="py-3 px-3">Quantity</th>
            <th scope="col" class="py-3 px-3">Unit Purchase Cost</th>
            <th scope="col" class="py-3 px-3">GST Tax %</th>
            <th scope="col" class="py-3 px-3 text-end">Line Total</th>
        </x-slot:headers>

        @foreach($purchase->items as $item)
            <tr>
                <td class="px-3 py-3 fw-bold text-dark">{{ $item->product_name_snapshot }}</td>
                <td class="px-3 py-3 font-monospace text-info small">{{ $item->product_sku_snapshot }}</td>
                <td class="px-3 py-3 fs-6 fw-bold text-dark">{{ $item->quantity }} Units</td>
                <td class="px-3 py-3 font-monospace small">₹{{ number_format($item->unit_purchase_cost, 2) }}</td>
                <td class="px-3 py-3 small"><span class="badge bg-light text-secondary border">{{ $item->tax_rate }}% GST</span></td>
                <td class="px-3 py-3 text-end font-monospace fw-bold text-dark">₹{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </x-ui.data-table>
</x-ui.card>

<!-- Audit Timeline & Details -->
<x-ui.card class="shadow-sm border-0 p-4">
    <h5 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-secondary me-2"></i>Purchase Details & Audit Log</h5>
    <div class="row g-3 small">
        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Created By</label>
            <div>{{ $purchase->creator->name ?? 'System' }}</div>
            <div class="text-muted">{{ $purchase->created_at ? $purchase->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Received By</label>
            <div>{{ $purchase->receiver->name ?? 'Pending Receipt' }}</div>
            <div class="text-muted">{{ $purchase->received_at ? $purchase->received_at->format('d M Y, h:i A') : 'N/A' }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Payment Method</label>
            <div class="text-uppercase fw-bold text-dark">{{ str_replace('_', ' ', $purchase->payment_method ?? 'N/A') }}</div>
        </div>

        <div class="col-md-3">
            <label class="text-muted fw-bold d-block">Supplier Reference</label>
            <div>{{ $purchase->supplier_reference ?? 'N/A' }}</div>
        </div>

        @if($purchase->remarks)
            <div class="col-12 mt-2">
                <label class="text-muted fw-bold d-block">Remarks</label>
                <div class="p-2 bg-light rounded border">{{ $purchase->remarks }}</div>
            </div>
        @endif
    </div>
</x-ui.card>
@endsection
