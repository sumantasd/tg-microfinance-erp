<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Repayment Statement - {{ $account->loan_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #fff; color: #212529; }
        .statement-header { border-bottom: 2px solid #0d6efd; padding-bottom: 15px; margin-bottom: 25px; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="p-4 p-md-5">

<div class="no-print d-flex justify-content-between align-items-center mb-4">
    <a href="{{ route('admin.loan-account.show', $account->id) }}" class="btn btn-outline-secondary btn-sm">
        &larr; Back to Loan Account Profile
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold">
        Print EMI Statement
    </button>
</div>

<div class="statement-header d-flex justify-content-between align-items-start">
    <div>
        <h2 class="fw-bold text-primary mb-1">GRIHALAXMI FINANCE ERP</h2>
        <p class="text-muted small mb-0">{{ $account->branch->name ?? 'Head Office' }} | {{ $account->branch->address ?? '' }}</p>
        <p class="text-muted small mb-0">Phone: {{ $account->branch->phone ?? '' }} | Email: {{ $account->company->email ?? '' }}</p>
    </div>
    <div class="text-end">
        <h4 class="fw-bold text-dark mb-0">LOAN STATEMENT</h4>
        <p class="font-monospace text-muted mb-0">Loan #: {{ $account->loan_number }}</p>
        <p class="small text-muted mb-0">Date: {{ date('d M Y') }}</p>
    </div>
</div>

<div class="row g-3 mb-4 small">
    <div class="col-6">
        <div class="p-3 bg-light rounded border">
            <h6 class="fw-bold border-bottom pb-1 mb-2 text-dark">Borrower Details</h6>
            @if($account->borrower_type === 'individual')
                <div><strong>Name:</strong> {{ $account->customer->full_name ?? 'N/A' }}</div>
                <div><strong>Customer Code:</strong> {{ $account->customer->customer_code ?? 'N/A' }}</div>
                <div><strong>Mobile:</strong> {{ $account->customer->mobile_number ?? 'N/A' }}</div>
            @else
                <div><strong>Group Name:</strong> {{ $account->customerGroup->name ?? 'N/A' }}</div>
                <div><strong>Group Code:</strong> {{ $account->customerGroup->group_code ?? 'N/A' }}</div>
                <div><strong>Borrower Type:</strong> Group ({{ $account->members->count() }} Members)</div>
            @endif
        </div>
    </div>

    <div class="col-6">
        <div class="p-3 bg-light rounded border">
            <h6 class="fw-bold border-bottom pb-1 mb-2 text-dark">Loan & Scheme Terms</h6>
            <div><strong>Loan Scheme:</strong> {{ $account->loanScheme->name ?? 'N/A' }}</div>
            <div><strong>Loan Type:</strong> {{ ucfirst($account->loan_type) }} Loan</div>
            <div><strong>Interest Rate:</strong> {{ $account->interest_rate_per_annum }}% p.a. ({{ ucfirst(str_replace('_', ' ', $account->interest_type)) }})</div>
            <div><strong>Tenure:</strong> {{ $account->tenure_months }} Months ({{ ucfirst($account->repayment_frequency) }})</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4 small">
    <div class="col-3">
        <div class="p-2 border rounded text-center">
            <span class="text-muted d-block uppercase small">Product Valuation</span>
            <strong class="font-monospace fs-6">₹{{ number_format($account->product_price_amount, 2) }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="p-2 border rounded text-center bg-success-subtle">
            <span class="text-muted d-block uppercase small">Down Payment</span>
            <strong class="font-monospace fs-6 text-success">₹{{ number_format($account->down_payment_amount, 2) }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="p-2 border rounded text-center bg-primary-subtle">
            <span class="text-muted d-block uppercase small">Financed Principal</span>
            <strong class="font-monospace fs-6 text-primary">₹{{ number_format($account->sanctioned_amount, 2) }}</strong>
        </div>
    </div>
    <div class="col-3">
        <div class="p-2 border rounded text-center bg-danger-subtle">
            <span class="text-muted d-block uppercase small">Total Repayment</span>
            <strong class="font-monospace fs-6 text-danger">₹{{ number_format($account->total_repayment_amount, 2) }}</strong>
        </div>
    </div>
</div>

<h5 class="fw-bold text-dark mb-3">REPAYMENT SCHEDULE</h5>

<table class="table table-bordered table-striped table-sm text-center small align-middle">
    <thead class="table-dark">
        <tr>
            <th>EMI #</th>
            <th>Due Date</th>
            <th>Opening Principal (₹)</th>
            <th>Principal (₹)</th>
            <th>Interest (₹)</th>
            <th>Total EMI (₹)</th>
            <th>Closing Principal (₹)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($account->installments as $inst)
            <tr>
                <td class="fw-bold font-monospace">#{{ $inst->installment_number }}</td>
                <td>{{ $inst->due_date ? $inst->due_date->format('d M Y') : 'N/A' }}</td>
                <td class="font-monospace">₹{{ number_format($inst->opening_principal, 2) }}</td>
                <td class="font-monospace fw-bold">₹{{ number_format($inst->principal_amount, 2) }}</td>
                <td class="font-monospace text-success">₹{{ number_format($inst->interest_amount, 2) }}</td>
                <td class="font-monospace fw-bold text-primary">₹{{ number_format($inst->installment_amount, 2) }}</td>
                <td class="font-monospace">₹{{ number_format($inst->closing_principal, 2) }}</td>
                <td class="text-capitalize">{{ $inst->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="row mt-5 pt-4 border-top text-center small">
    <div class="col-4">
        <p class="mb-5">Prepared By</p>
        <p class="border-top pt-1 text-muted mb-0">Authorized Officer Signature</p>
    </div>
    <div class="col-4">
        <p class="mb-5">Verified By</p>
        <p class="border-top pt-1 text-muted mb-0">Branch Manager Signature</p>
    </div>
    <div class="col-4">
        <p class="mb-5">Borrower Acceptance</p>
        <p class="border-top pt-1 text-muted mb-0">Customer Signature / Thumbprint</p>
    </div>
</div>

</body>
</html>
