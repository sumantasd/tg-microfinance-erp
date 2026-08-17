<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Receipt - {{ $repayment->receipt_number }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            margin: 0;
            padding: 10px;
        }

        .thermal-wrapper {
            background: #fff;
            margin: 10px auto;
            padding: 12px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            font-size: 12px;
            line-height: 1.35;
        }

        .thermal-80 {
            width: 80mm;
            max-width: 100%;
        }

        .thermal-58 {
            width: 58mm;
            max-width: 100%;
            font-size: 11px;
            padding: 8px 6px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .double-divider {
            border-top: 2px double #000;
            margin: 8px 0;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        @media print {
            .no-print { display: none !important; }
            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .thermal-wrapper {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 4px !important;
            }
            @page {
                size: {{ $width == '58' ? '58mm auto' : '80mm auto' }};
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <div class="card p-3 shadow-sm border-0 d-inline-block text-start">
        <label class="form-label fw-bold small text-muted d-block">Select Thermal Paper Size:</label>
        <div class="btn-group mb-2" role="group">
            <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '80']) }}" class="btn btn-sm {{ $width == '80' ? 'btn-primary' : 'btn-outline-primary' }} fw-bold">80mm Paper</a>
            <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '58']) }}" class="btn btn-sm {{ $width == '58' ? 'btn-primary' : 'btn-outline-primary' }} fw-bold">58mm Paper</a>
            <a href="{{ route('admin.emi-collection.receipt', $repayment->id) }}" class="btn btn-sm btn-outline-secondary fw-bold">A4 / Normal Format</a>
        </div>

        <div>
            <button onclick="window.print()" class="btn btn-success btn-sm fw-bold px-4 me-2"><i class="bi bi-printer me-1"></i> Print Thermal Receipt</button>
            <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-light btn-sm border">&larr; Back to Collection</a>
        </div>
    </div>
</div>

<div class="thermal-wrapper {{ $width == '58' ? 'thermal-58' : 'thermal-80' }}">
    <!-- Header -->
    <div class="text-center">
        <div class="fw-bold uppercase" style="font-size: 14px;">GRIHALAXMI FINANCE</div>
        <div class="uppercase small">{{ $repayment->loanAccount->branch->name ?? 'Head Office' }}</div>
        <div class="small">{{ $repayment->loanAccount->branch->address ?? '' }}</div>
        <div class="divider"></div>
        <div class="fw-bold uppercase">EMI COLLECTION RECEIPT</div>
    </div>

    <div class="divider"></div>

    <!-- Meta Details -->
    <table class="receipt-table">
        <tr>
            <td>Receipt #:</td>
            <td class="text-end fw-bold">{{ $repayment->receipt_number }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="text-end">{{ $repayment->payment_date ? $repayment->payment_date->format('d-m-Y') : date('d-m-Y') }} {{ date('H:i') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Customer & Loan Details -->
    <div class="fw-bold uppercase mb-1">BORROWER DETAILS</div>
    <table class="receipt-table">
        <tr>
            <td>Name:</td>
            <td class="text-end fw-bold">{{ $repayment->customer->full_name ?? $repayment->loanAccount->customerGroup->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Member ID:</td>
            <td class="text-end">{{ $repayment->customer->customer_code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Mobile:</td>
            <td class="text-end">{{ $repayment->customer->mobile_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Loan #:</td>
            <td class="text-end fw-bold">{{ $repayment->loanAccount->loan_number }}</td>
        </tr>
    </table>

    @if($repayment->loanAccount->loan_type === 'product' && $repayment->loanAccount->application && $repayment->loanAccount->application->products->count() > 0)
        @php $prod = $repayment->loanAccount->application->products->first(); @endphp
        <div class="divider"></div>
        <div class="fw-bold uppercase mb-1">PRODUCT DETAILS</div>
        <table class="receipt-table">
            <tr>
                <td>Item:</td>
                <td class="text-end">{{ $prod->product_name_snapshot }}</td>
            </tr>
            <tr>
                <td>SKU:</td>
                <td class="text-end">{{ $prod->product_sku_snapshot }}</td>
            </tr>
            <tr>
                <td>Product Price:</td>
                <td class="text-end">₹{{ number_format($repayment->loanAccount->product_price_amount, 0) }}</td>
            </tr>
            <tr>
                <td>Down Payment:</td>
                <td class="text-end">₹{{ number_format($repayment->loanAccount->down_payment_amount, 0) }}</td>
            </tr>
            <tr>
                <td>Financed Amt:</td>
                <td class="text-end fw-bold">₹{{ number_format($repayment->loanAccount->sanctioned_amount, 0) }}</td>
            </tr>
        </table>
    @endif

    <div class="double-divider"></div>

    <!-- Payment Collection Details -->
    <div class="fw-bold uppercase mb-1">PAYMENT DETAILS</div>
    <table class="receipt-table">
        <tr>
            <td class="fw-bold fs-6">Amount Received:</td>
            <td class="text-end fw-bold fs-6">₹{{ number_format($repayment->amount, 0) }}</td>
        </tr>
        <tr>
            <td>Payment Method:</td>
            <td class="text-end uppercase fw-bold">{{ $repayment->payment_method }}</td>
        </tr>
        @if($repayment->reference_number)
            <tr>
                <td>Ref / Txn #:</td>
                <td class="text-end">{{ $repayment->reference_number }}</td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- Waterfall Allocation -->
    <div class="fw-bold uppercase mb-1">PAYMENT ALLOCATION</div>
    <table class="receipt-table">
        <tr>
            <td>Penalty/Late Fee:</td>
            <td class="text-end">₹{{ number_format($repayment->penalty_paid, 0) }}</td>
        </tr>
        <tr>
            <td>Fees & Charges:</td>
            <td class="text-end">₹{{ number_format($repayment->fee_paid, 0) }}</td>
        </tr>
        <tr>
            <td>Interest Paid:</td>
            <td class="text-end">₹{{ number_format($repayment->interest_paid, 0) }}</td>
        </tr>
        <tr>
            <td>Principal Paid:</td>
            <td class="text-end fw-bold">₹{{ number_format($repayment->principal_paid, 0) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Outstanding Balance & Next EMI -->
    <div class="fw-bold uppercase mb-1">OUTSTANDING ACCOUNT SUMMARY</div>
    @php
        $prevOutstanding = round($repayment->loanAccount->total_outstanding + $repayment->amount, 0);
        $newOutstanding = round($repayment->loanAccount->total_outstanding, 0);
        $nextEmiAmt = $nextInst ? round($nextInst->installment_amount, 0) : 0;
        $nextDueDate = $nextInst && $nextInst->due_date ? $nextInst->due_date->format('d-m-Y') : 'COMPLETED';
    @endphp
    <table class="receipt-table">
        <tr>
            <td>Previous Outstanding:</td>
            <td class="text-end">₹{{ number_format($prevOutstanding, 0) }}</td>
        </tr>
        <tr>
            <td>Payment Received:</td>
            <td class="text-end">₹{{ number_format($repayment->amount, 0) }}</td>
        </tr>
        <tr>
            <td class="fw-bold">New Outstanding:</td>
            <td class="text-end fw-bold">₹{{ number_format($newOutstanding, 0) }}</td>
        </tr>
        @if($newOutstanding > 0)
            <tr>
                <td>Next EMI:</td>
                <td class="text-end fw-bold">₹{{ number_format($nextEmiAmt, 0) }}</td>
            </tr>
            <tr>
                <td>Next Due Date:</td>
                <td class="text-end">{{ $nextDueDate }}</td>
            </tr>
        @endif
    </table>

    <div class="divider"></div>

    <!-- Footer -->
    <table class="receipt-table mb-2">
        <tr>
            <td>Collected By:</td>
            <td class="text-end fw-bold">{{ $repayment->receiver->name ?? 'Staff' }}</td>
        </tr>
    </table>

    <div class="text-center mt-3">
        <div class="fw-bold uppercase">THANK YOU!</div>
        <div class="small">Payment Successfully Recorded</div>
        <div class="small mt-1">Computer Generated Slip</div>
    </div>
</div>

<script>
    // Store paper size preference in localStorage
    const params = new URLSearchParams(window.location.search);
    const w = params.get('width');
    if (w) {
        localStorage.setItem('thermal_paper_width', w);
    }
</script>
</body>
</html>
