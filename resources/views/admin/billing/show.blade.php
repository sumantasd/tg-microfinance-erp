@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number . ' - ' . config('app.name'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-receipt text-primary me-2"></i>Invoice Details: {{ $invoice->invoice_number }}
        </h4>
        <p class="text-muted small mb-0">Created on {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }} | Type: {{ strtoupper(str_replace('_', ' ', $invoice->invoice_type)) }}</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.billing.invoices.index') }}" class="btn btn-outline-secondary rounded-pill px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoices
        </a>
        @can('billing.print')
        <a href="{{ route('admin.billing.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-primary rounded-pill px-3 fw-bold shadow-sm">
            <i class="bi bi-printer me-1"></i> Print Invoice
        </a>
        @endcan
        @can('billing.pdf')
        <a href="{{ route('admin.billing.invoices.pdf', $invoice->id) }}" target="_blank" class="btn btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        @endcan
        @can('billing.cancel')
        @if(!$invoice->isCancelled())
        <button type="button" class="btn btn-outline-danger rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#cancelInvoiceModal">
            <i class="bi bi-x-circle me-1"></i> Cancel Invoice
        </button>
        @endif
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($invoice->isCancelled())
    <div class="alert alert-danger rounded-3 shadow-sm mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i> INVOICE CANCELLED</h6>
        <p class="mb-0 small">This invoice was cancelled by <strong>{{ $invoice->canceller->name ?? 'Admin' }}</strong> on {{ $invoice->cancelled_at ? $invoice->cancelled_at->format('d M Y, h:i A') : '' }}. Reason: {{ $invoice->cancellation_reason }}</p>
    </div>
@endif

<!-- Screen Invoice Container -->
<x-ui.card class="p-4 p-md-5 shadow-sm border-0 bg-white mb-4">
    <!-- Invoice Header -->
    <div class="row align-items-center border-bottom pb-4 mb-4">
        <div class="col-md-6">
            <img src="{{ $invoice->company->logo ? asset('storage/' . $invoice->company->logo) : asset('images/logo.png') }}" alt="Logo" style="max-height: 50px;" class="mb-2">
            <h5 class="fw-bold text-dark mb-0">{{ $invoice->company->legal_name ?: ($invoice->company->name ?: config('app.name')) }}</h5>
            <p class="text-muted small mb-0">{{ $invoice->company->tagline ?: 'Empowering Financial Growth' }}</p>
            <div class="extra-small text-muted mt-1">
                @if($invoice->company->gstin) <span>GSTIN: <strong>{{ $invoice->company->gstin }}</strong></span> | @endif
                @if($invoice->company->cin) <span>CIN: <strong>{{ $invoice->company->cin }}</strong></span> @endif
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill uppercase mb-2">
                {{ $invoice->invoice_type === 'product_loan' ? 'PRODUCT LOAN INVOICE' : 'TAX INVOICE' }}
            </span>
            <h4 class="fw-bold text-dark font-monospace mb-1">{{ $invoice->invoice_number }}</h4>
            <div class="small text-muted">Date: <strong>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }}</strong></div>
            <div class="small text-muted">Ref #: <strong>{{ $invoice->reference_number ?: 'N/A' }}</strong></div>
        </div>
    </div>

    <!-- Branch & Customer Information -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-3 border h-100">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-2 extra-small text-uppercase tracking-wider">TRANSACTION BRANCH</h6>
                <h6 class="fw-bold text-dark mb-1">{{ $invoice->branch->name ?? 'Head Office' }} ({{ $invoice->branch->code ?? 'HO' }})</h6>
                <p class="text-muted small mb-1">{{ $invoice->branch->full_address ?: ($invoice->branch->address ?: 'Branch Address') }}</p>
                <div class="extra-small text-secondary">
                    Phone: {{ $invoice->branch->phone ?: 'N/A' }} | Email: {{ $invoice->branch->email ?: 'N/A' }}
                    @if($invoice->branch->gstin)<div class="fw-bold text-dark">Branch GSTIN: {{ $invoice->branch->gstin }}</div>@endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-3 border h-100">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-2 extra-small text-uppercase tracking-wider">CUSTOMER / BORROWER DETAILS</h6>
                <h6 class="fw-bold text-dark mb-1">{{ $invoice->customer->full_name ?? ($invoice->invoiceable->customer_name ?? 'Walk-in Customer') }}</h6>
                <p class="text-muted small mb-1">{{ $invoice->customer->full_address ?? ($invoice->invoiceable->customer_address ?? 'N/A') }}</p>
                <div class="extra-small text-secondary">
                    Customer ID: <strong class="font-monospace text-dark">{{ $invoice->customer->customer_code ?? 'N/A' }}</strong> | Phone: {{ $invoice->customer->phone ?? ($invoice->invoiceable->customer_phone ?? 'N/A') }}
                    @if($invoice->invoice_type === 'product_loan' && isset($invoice->invoiceable->loan_number))
                        <div class="mt-1 text-primary fw-bold">Loan #: {{ $invoice->invoiceable->loan_number }} | Sanctioned: ₹{{ number_format($invoice->invoiceable->sanctioned_amount, 2) }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Product Line Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light text-uppercase extra-small fw-bold text-muted">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Item Description / SKU</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-end">Unit Price</th>
                    <th style="width: 10%;" class="text-end">Discount</th>
                    <th style="width: 15%;" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody class="small">
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center font-monospace">{{ $index + 1 }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                        @if($item->sku)<small class="text-muted font-monospace extra-small">SKU: {{ $item->sku }}</small>@endif
                    </td>
                    <td class="text-center font-monospace fw-bold">{{ $item->quantity }}</td>
                    <td class="text-end font-monospace">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end font-monospace text-danger">₹{{ number_format($item->discount_amount, 2) }}</td>
                    <td class="text-end font-monospace fw-bold text-dark">₹{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Financial Totals Summary -->
    <div class="row align-items-start g-4 mb-4">
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-3 border">
                <h6 class="fw-bold text-dark small mb-2">Payment & Terms Notes:</h6>
                <p class="small text-muted mb-1">{{ $invoice->notes }}</p>
                <small class="text-secondary extra-small d-block">{{ $invoice->terms }}</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 bg-light rounded-3 border">
                <div class="d-flex justify-content-between small mb-2">
                    <span>Subtotal:</span>
                    <strong class="font-monospace">₹{{ number_format($invoice->subtotal, 2) }}</strong>
                </div>
                @if($invoice->discount_amount > 0)
                <div class="d-flex justify-content-between small mb-2 text-danger">
                    <span>Discount:</span>
                    <strong class="font-monospace">-₹{{ number_format($invoice->discount_amount, 2) }}</strong>
                </div>
                @endif
                @if($invoice->tax_amount > 0)
                <div class="d-flex justify-content-between small mb-2">
                    <span>Tax:</span>
                    <strong class="font-monospace">+₹{{ number_format($invoice->tax_amount, 2) }}</strong>
                </div>
                @endif
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-5 fw-bold text-dark mb-2">
                    <span>Grand Total:</span>
                    <strong class="font-monospace text-primary">₹{{ number_format($invoice->grand_total, 2) }}</strong>
                </div>
                @if($invoice->invoice_type === 'product_loan')
                    <div class="d-flex justify-content-between small text-success mb-1">
                        <span>Down Payment Paid:</span>
                        <strong class="font-monospace">₹{{ number_format($invoice->paid_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-danger fw-bold">
                        <span>Financed Balance Amount:</span>
                        <strong class="font-monospace">₹{{ number_format($invoice->balance_due, 2) }}</strong>
                    </div>
                @else
                    <div class="d-flex justify-content-between small text-success mb-1">
                        <span>Amount Paid ({{ strtoupper($invoice->payment_method ?: 'cash') }}):</span>
                        <strong class="font-monospace">₹{{ number_format($invoice->paid_amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between small text-danger fw-bold">
                        <span>Balance Due:</span>
                        <strong class="font-monospace">₹{{ number_format($invoice->balance_due, 2) }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-ui.card>

@can('billing.cancel')
@if(!$invoice->isCancelled())
<!-- Cancel Invoice Modal -->
<div class="modal fade" id="cancelInvoiceModal" tabindex="-1" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.billing.invoices.cancel', $invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger" id="cancelInvoiceModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Cancel Invoice #{{ $invoice->invoice_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Are you sure you want to void/cancel this invoice? Financial audit trail will be retained.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="Provide reason for invoice cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger fw-bold">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan
@endsection
