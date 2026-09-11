<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice_{{ $invoice->invoice_number }} - {{ $branding->system_name }}</title>
    <link rel="icon" href="{{ $branding->favicon_url }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            font-size: 13px;
        }

        .invoice-box {
            max-width: 850px;
            margin: 20px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
        }

        .company-logo {
            max-height: 55px;
            max-width: 180px;
            object-fit: contain;
        }

        .font-mono {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        }

        .table-invoice th {
            background-color: #f1f5f9 !important;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px;
        }

        .table-invoice td {
            padding: 10px;
            vertical-align: middle;
        }

        .signature-box {
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        @media print {
            body {
                background: #ffffff;
                color: #000000;
                font-size: 12px;
            }

            .invoice-box {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

<!-- Print Control Header -->
<div class="container py-3 no-print" style="max-width: 850px;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-3 border shadow-sm">
        <div>
            <span class="fw-bold text-dark"><i class="bi bi-printer me-1 text-primary"></i> A4 Printable Invoice</span>
            <span class="text-muted small ms-2">Invoice Number: <strong class="font-mono text-primary">{{ $invoice->invoice_number }}</strong></span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill px-3">Close</button>
        </div>
    </div>
</div>

<div class="invoice-box">
    <!-- Invoice Header -->
    <div class="row align-items-start border-bottom pb-3 mb-4">
        <div class="col-7">
            <img src="{{ $branding->logo_url }}" alt="Company Logo" class="company-logo mb-2">
            <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.5px;">{{ $branding->legal_name }}</h4>
            <p class="text-muted small mb-1">{{ $branding->tagline }}</p>
            <p class="text-muted extra-small mb-1">{{ $branding->full_address }}</p>
            <div class="extra-small text-muted">
                <span>Phone: <strong>{{ $branding->phone }}</strong></span> | 
                <span>Email: <strong>{{ $branding->email }}</strong></span>
                @if($branding->gstin) | <span>GSTIN: <strong class="font-mono">{{ $branding->gstin }}</strong></span>@endif
                @if($branding->pan) | <span>PAN: <strong class="font-mono">{{ $branding->pan }}</strong></span>@endif
                @if(!empty($branding->cin)) | <span>CIN: <strong class="font-mono">{{ $branding->cin }}</strong></span>@endif
            </div>
        </div>
        <div class="col-5 text-end">
            <div class="mb-2">
                <span class="badge bg-dark text-white px-3 py-2 uppercase fw-bold" style="font-size: 13px; letter-spacing: 1px;">
                    {{ $invoice->invoice_type === 'product_loan' ? 'PRODUCT LOAN INVOICE' : 'TAX INVOICE' }}
                </span>
            </div>
            <h5 class="fw-bold text-primary font-mono mb-1">{{ $invoice->invoice_number }}</h5>
            <div class="small">Date: <strong class="text-dark">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : 'N/A' }}</strong></div>
            <div class="small">Ref/Sale #: <strong class="text-dark font-mono">{{ $invoice->reference_number ?: 'N/A' }}</strong></div>
            <div class="small text-muted">Status: <strong class="text-uppercase text-dark">{{ $invoice->status }}</strong></div>
        </div>
    </div>

    <!-- Transaction Branch & Customer Section -->
    <div class="row g-3 mb-4">
        <!-- Transaction Branch Details -->
        <div class="col-6">
            <div class="p-3 bg-light rounded-3 border h-100">
                <div class="text-uppercase extra-small fw-bold text-primary mb-1">TRANSACTION BRANCH</div>
                <div class="fw-bold text-dark fs-6">{{ $invoice->branch->name ?? 'Head Office Branch' }}</div>
                <div class="small text-secondary mb-1">Code: <strong class="font-mono">{{ $invoice->branch->code ?? 'HO' }}</strong></div>
                <div class="extra-small text-muted mb-1">{{ $invoice->branch->full_address ?: ($invoice->branch->address ?: 'Branch Address') }}</div>
                <div class="extra-small text-muted">
                    Phone: {{ $invoice->branch->phone ?: 'N/A' }} | Email: {{ $invoice->branch->email ?: 'N/A' }}
                    @if($invoice->branch->gstin)<div class="fw-bold text-dark">GSTIN: {{ $invoice->branch->gstin }}</div>@endif
                </div>
            </div>
        </div>

        <!-- Billed To Customer Details -->
        <div class="col-6">
            <div class="p-3 bg-light rounded-3 border h-100">
                <div class="text-uppercase extra-small fw-bold text-primary mb-1">BILL TO (CUSTOMER / BORROWER)</div>
                <div class="fw-bold text-dark fs-6">{{ $invoice->customer->full_name ?? ($invoice->invoiceable->customer_name ?? 'Walk-in Customer') }}</div>
                <div class="small text-secondary mb-1">Customer ID: <strong class="font-mono text-dark">{{ $invoice->customer->customer_code ?? 'Walk-in' }}</strong></div>
                <div class="extra-small text-muted mb-1">{{ $invoice->customer->full_address ?? ($invoice->invoiceable->customer_address ?? 'N/A') }}</div>
                <div class="extra-small text-muted">
                    Phone: {{ $invoice->customer->phone ?? ($invoice->invoiceable->customer_phone ?? 'N/A') }}
                    @if($invoice->invoice_type === 'product_loan' && isset($invoice->invoiceable->loan_number))
                        <div class="fw-bold text-primary mt-1">Loan No: {{ $invoice->invoiceable->loan_number }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Product Line Items Table -->
    <table class="table table-bordered table-invoice mb-4">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">#</th>
                <th style="width: 44%;">Product / SKU</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 14%;" class="text-end">Unit Price</th>
                <th style="width: 11%;" class="text-end">Discount</th>
                <th style="width: 15%;" class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $item)
            <tr>
                <td class="text-center font-mono">{{ $idx + 1 }}</td>
                <td>
                    <div class="fw-bold text-dark">{{ $item->item_name }}</div>
                    @if($item->sku)<small class="text-muted font-mono extra-small">SKU: {{ $item->sku }}</small>@endif
                </td>
                <td class="text-center font-mono fw-bold">{{ $item->quantity }}</td>
                <td class="text-end font-mono">₹{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end font-mono text-danger">₹{{ number_format($item->discount_amount, 2) }}</td>
                <td class="text-end font-mono fw-bold">₹{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Summary & Financial Breakdown -->
    <div class="row align-items-start g-4 mb-4">
        <div class="col-7">
            <div class="p-3 bg-light rounded-3 border">
                <div class="fw-bold extra-small text-uppercase text-secondary mb-1">Terms & Notes:</div>
                <div class="small text-muted mb-1">{{ $invoice->notes ?: 'Thank you for choosing us.' }}</div>
                <div class="extra-small text-secondary">{{ $invoice->terms ?: 'Computer generated invoice. Signature required where specified.' }}</div>
            </div>
        </div>
        <div class="col-5">
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-end font-mono">₹{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr class="text-danger">
                    <td>Discount:</td>
                    <td class="text-end font-mono">-₹{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->tax_amount > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-end font-mono">+₹{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="border-top border-bottom fw-bold fs-6">
                    <td>Grand Total:</td>
                    <td class="text-end font-mono text-primary">₹{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
                @if($invoice->invoice_type === 'product_loan')
                <tr class="text-success">
                    <td class="ps-0">Down Payment Paid:</td>
                    <td class="text-end font-mono fw-bold">₹{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="text-danger fw-bold">
                    <td class="ps-0">Financed Loan Balance:</td>
                    <td class="text-end font-mono">₹{{ number_format($invoice->balance_due, 2) }}</td>
                </tr>
                @else
                <tr class="text-success">
                    <td class="ps-0">Amount Paid ({{ strtoupper($invoice->payment_method ?: 'Cash') }}):</td>
                    <td class="text-end font-mono fw-bold">₹{{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="text-danger fw-bold">
                    <td class="ps-0">Balance Due:</td>
                    <td class="text-end font-mono">₹{{ number_format($invoice->balance_due, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Signature Blocks -->
    <div class="row pt-4 mt-4">
        <div class="col-6">
            <div class="signature-box">
                <div>Customer Signature / Acceptance</div>
            </div>
        </div>
        <div class="col-6">
            <div class="signature-box">
                <div>For {{ $branding->legal_name }}</div>
                <div class="extra-small text-muted mt-1">(Authorized Signatory)</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
