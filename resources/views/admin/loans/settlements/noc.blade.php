<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Closure Certificate / NOC - {{ $nocData['loan']['loan_number'] }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #212529;
        }
        .certificate-container {
            max-width: 850px;
            margin: 30px auto;
            background: #fff;
            padding: 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 8px;
            border: 2px solid #dee2e6;
            position: relative;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 5rem;
            color: rgba(40, 167, 69, 0.08);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 10px;
            pointer-events: none;
            user-select: none;
        }
        .header-logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0d6efd;
            letter-spacing: -0.5px;
        }
        .cert-title {
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 800;
            color: #198754;
            border-bottom: 2px solid #198754;
            display: inline-block;
            padding-bottom: 5px;
        }
        @media print {
            body {
                background: #fff;
                margin: 0;
            }
            .certificate-container {
                box-shadow: none;
                border: 1px solid #999;
                margin: 0;
                max-width: 100%;
                padding: 30px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container no-print mt-3 mb-2 text-center">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
        <i class="bi bi-printer-fill me-1"></i> Print Certificate / Save as PDF
    </button>
    <a href="{{ route('admin.loan-account.show', $loanAccount->id) }}" class="btn btn-outline-secondary btn-sm px-3 ms-2">
        <i class="bi bi-arrow-left me-1"></i> Return to Account
    </a>
</div>

<div class="certificate-container">
    <div class="watermark">PAID IN FULL</div>

    <!-- Company Header -->
    <div class="row align-items-center border-bottom pb-4 mb-4">
        <div class="col-8">
            <div class="header-logo"><i class="bi bi-bank2 me-2"></i>{{ $nocData['company']['name'] }}</div>
            <div class="small text-muted">{{ $nocData['company']['address'] }}</div>
            <div class="small text-muted">Registration / CIN: <strong>{{ $nocData['company']['registration_number'] }}</strong> | Phone: {{ $nocData['company']['phone'] }}</div>
        </div>
        <div class="col-4 text-end">
            <div class="small text-muted">Branch Office:</div>
            <div class="fw-bold text-dark">{{ $nocData['branch']['name'] }}</div>
            <div class="font-monospace small text-secondary">Code: {{ $nocData['branch']['code'] }}</div>
        </div>
    </div>

    <!-- Title & Reference -->
    <div class="text-center mb-4">
        <h4 class="cert-title">No Objection Certificate (NOC)</h4>
        <div class="small text-muted mt-1">Loan Full Discharge & Liability Release Certificate</div>
        <div class="badge bg-light text-dark border font-monospace mt-2 px-3 py-1.5">
            Certificate Ref: <strong>{{ $nocData['certificate_number'] }}</strong>
        </div>
    </div>

    <!-- Borrower Details -->
    <div class="card bg-light border-0 p-3 mb-4 small">
        <div class="row g-2">
            <div class="col-6">
                <span class="text-muted d-block">Borrower Full Name:</span>
                <strong class="fs-6 text-dark">{{ $nocData['borrower']['name'] }}</strong>
            </div>
            <div class="col-6 text-end">
                <span class="text-muted d-block">Customer Code / ID:</span>
                <strong class="font-monospace text-primary fs-6">{{ $nocData['borrower']['code'] }}</strong>
            </div>
            <div class="col-6 mt-2">
                <span class="text-muted d-block">Contact Phone:</span>
                <span>{{ $nocData['borrower']['phone'] }}</span>
            </div>
            <div class="col-6 text-end mt-2">
                <span class="text-muted d-block">Borrower Address:</span>
                <span>{{ $nocData['borrower']['address'] }}</span>
            </div>
        </div>
    </div>

    <!-- Loan Financial Summary -->
    <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i class="bi bi-file-earmark-text text-primary me-1"></i>Loan Account Particulars</h6>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm small align-middle">
            <thead class="table-light">
                <tr>
                    <th>Loan Number</th>
                    <th>Sanction Date</th>
                    <th>Sanctioned Amount</th>
                    <th>Closure Date</th>
                    <th>Closure Mode</th>
                    <th class="text-end">Balance Outstanding</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold font-monospace text-primary">{{ $nocData['loan']['loan_number'] }}</td>
                    <td>{{ $nocData['loan']['sanction_date'] }}</td>
                    <td class="font-monospace fw-bold">₹{{ number_format($nocData['loan']['sanction_amount'] ?? $nocData['loan']['sanctioned_amount'], 2) }}</td>
                    <td>{{ $nocData['loan']['closed_at'] }}</td>
                    <td><span class="badge bg-success-subtle text-success border border-success">{{ $nocData['loan']['closure_type'] }}</span></td>
                    <td class="text-end font-monospace fw-bold text-success fs-6">₹0.00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Formal Discharge Statement -->
    <div class="card p-3 border-success-subtle bg-success-subtle mb-4">
        <h6 class="fw-bold text-success mb-1"><i class="bi bi-shield-fill-check me-1"></i>Official Declaration of Full Settlement</h6>
        <p class="small text-dark mb-0 leading-relaxed">
            This is to certify that the loan account referenced above has been <strong>FULLY DISCHARGED AND CLOSED</strong> with zero outstanding balance towards Principal, Interest, Fees, or Penalties. {{ $nocData['company']['name'] }} confirms having <strong>NO OBJECTION OR CLAIM</strong> whatsoever against the borrower, co-borrower, or guarantors in respect of this loan facility. Any hypothecation, guarantees, or collateral liens associated with this specific account stand released.
        </p>
    </div>

    <!-- Signatures Section -->
    <div class="row pt-4 mt-4 text-center align-items-end">
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">
                <strong>System Generated On</strong><br>
                {{ $nocData['generation_date'] }}
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">
                <strong>Branch Seal / Stamp</strong><br>
                {{ $nocData['branch']['name'] }}
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">
                <strong>Authorized Signatory</strong><br>
                {{ $nocData['loan']['approved_by'] }}
            </div>
        </div>
    </div>
</div>

</body>
</html>
