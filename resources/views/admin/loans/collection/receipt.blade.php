<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Collection Receipt - {{ $repayment->receipt_number }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; color: #212529; }
        .receipt-card { background: #fff; max-width: 680px; margin: 30px auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e9ecef; }
        .receipt-header { border-bottom: 2px solid #198754; padding-bottom: 15px; margin-bottom: 20px; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff; }
            .receipt-card { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body class="p-3 p-md-4">

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-success fw-bold me-2"><i class="bi bi-printer me-1"></i> Print Receipt (A4)</button>
    <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '80']) }}" target="_blank" class="btn btn-dark fw-bold me-2"><i class="bi bi-receipt me-1"></i> Thermal Receipt (80mm)</a>
    <a href="{{ route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '58']) }}" target="_blank" class="btn btn-outline-dark fw-bold me-2"><i class="bi bi-receipt me-1"></i> Thermal Receipt (58mm)</a>
    <a href="{{ route('admin.emi-collection.index') }}" class="btn btn-outline-secondary font-monospace">&larr; Back to Collection Dashboard</a>
</div>

<div class="receipt-card p-4 p-md-5">
    <div class="receipt-header d-flex justify-content-between align-items-start">
        <div>
            <h3 class="fw-bold text-success mb-1">GRIHALAXMI FINANCE</h3>
            <p class="text-muted small mb-0">{{ $repayment->loanAccount->branch->name ?? 'Head Office' }}</p>
            <p class="text-muted small mb-0">{{ $repayment->loanAccount->branch->address ?? '' }}</p>
        </div>
        <div class="text-end">
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-1 mb-2">OFFICIAL RECEIPT</span>
            <div class="font-monospace fw-bold text-dark fs-6">{{ $repayment->receipt_number }}</div>
            <div class="small text-muted">Date: {{ $repayment->payment_date ? $repayment->payment_date->format('d M Y') : date('d M Y') }}</div>
        </div>
    </div>

    <div class="row g-3 mb-4 small">
        <div class="col-6">
            <div class="p-3 bg-light rounded border">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">Customer & Borrower Details</h6>
                <div><strong>Name:</strong> {{ $repayment->customer->full_name ?? $repayment->loanAccount->customerGroup->name ?? 'N/A' }}</div>
                <div><strong>Mobile:</strong> {{ $repayment->customer->mobile_number ?? 'N/A' }}</div>
                <div><strong>Customer Code:</strong> {{ $repayment->customer->customer_code ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-3 bg-light rounded border">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">Loan Account Info</h6>
                <div><strong>Loan Account #:</strong> {{ $repayment->loanAccount->loan_number }}</div>
                <div><strong>Scheme:</strong> {{ $repayment->loanAccount->loanScheme->name ?? 'N/A' }}</div>
                <div><strong>Payment Method:</strong> <span class="text-uppercase fw-bold">{{ $repayment->payment_method }}</span></div>
                @if($repayment->reference_number)
                    <div><strong>Txn / Ref #:</strong> {{ $repayment->reference_number }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Amount Collected Highlight Box -->
    <div class="p-3 bg-success-subtle rounded border border-success mb-4 text-center">
        <div class="small text-muted fw-bold uppercase">Total Amount Received</div>
        <div class="fs-1 fw-bold text-success font-monospace my-1">₹{{ number_format($repayment->amount, 0) }}</div>
        <div class="small text-muted">Amount in Whole Indian Rupees</div>
    </div>

    <!-- Waterfall Breakdown Table -->
    <h6 class="fw-bold text-dark mb-2">Payment Allocation Breakdown</h6>
    <table class="table table-bordered table-sm text-center small align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th>Penalties / Late Fee</th>
                <th>Fees & Charges</th>
                <th>Interest Component</th>
                <th>Principal Component</th>
                <th>Total Paid</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-monospace">₹{{ number_format($repayment->penalty_paid, 0) }}</td>
                <td class="font-monospace">₹{{ number_format($repayment->fee_paid, 0) }}</td>
                <td class="font-monospace text-primary">₹{{ number_format($repayment->interest_paid, 0) }}</td>
                <td class="font-monospace fw-bold text-dark">₹{{ number_format($repayment->principal_paid, 0) }}</td>
                <td class="font-monospace fw-bold text-success fs-6">₹{{ number_format($repayment->amount, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="p-3 bg-light rounded border mb-4 small d-flex justify-content-between">
        <div><strong>Remaining Loan Outstanding Balance:</strong></div>
        <div class="font-monospace fw-bold text-danger fs-6">₹{{ number_format($repayment->loanAccount->total_outstanding, 0) }}</div>
    </div>

    <div class="row mt-5 pt-3 border-top text-center small">
        <div class="col-6">
            <p class="mb-4 text-muted">Collected By: <strong>{{ $repayment->receiver->name ?? 'System' }}</strong></p>
            <p class="border-top pt-1 text-muted mb-0">Collector Signature</p>
        </div>
        <div class="col-6">
            <p class="mb-4">&nbsp;</p>
            <p class="border-top pt-1 text-muted mb-0">Customer Signature / Stamp</p>
        </div>
    </div>
</div>

</body>
</html>
