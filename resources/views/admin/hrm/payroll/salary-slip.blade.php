<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pay Slip - {{ $slip->employee->full_name }} - {{ date('F Y', mktime(0,0,0,$slip->payroll->month,1)) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .slip-box { max-width: 800px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        @media print { body { background: #fff; } .slip-box { box-shadow: none; padding: 0; margin: 0; max-width: 100%; } .no-print { display: none; } }
    </style>
</head>
<body>

<div class="container py-3 no-print text-end max-width-800" style="max-width: 800px;">
    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
        <i class="bi bi-printer me-1"></i> Print Pay Slip
    </button>
</div>

<div class="slip-box border">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">{{ $slip->payroll->company->name ?? 'TG Microfinance ERP' }}</h3>
            <p class="text-muted small mb-0">{{ $slip->payroll->branch->name ?? 'Head Office Branch' }} | {{ $slip->payroll->branch->address ?? 'Branch Address' }}</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary text-white fs-6 px-3 py-2 rounded-pill uppercase">SALARY PAY SLIP</span>
            <div class="small fw-bold text-dark mt-1">Period: {{ date('F Y', mktime(0,0,0,$slip->payroll->month, 1)) }}</div>
        </div>
    </div>

    <!-- Employee Details -->
    <div class="row g-3 small mb-4 bg-light p-3 rounded-3 border">
        <div class="col-6">
            <span class="text-muted d-block">Employee Name:</span>
            <strong class="text-dark fs-6">{{ $slip->employee->full_name }}</strong>
        </div>
        <div class="col-6">
            <span class="text-muted d-block">Employee Code:</span>
            <strong class="font-monospace text-primary fs-6">{{ $slip->employee->employee_code }}</strong>
        </div>
        <div class="col-6">
            <span class="text-muted d-block">Designation & Dept:</span>
            <span class="fw-semibold text-dark">{{ $slip->employee->designation->title ?? 'N/A' }} ({{ $slip->employee->department->name ?? 'N/A' }})</span>
        </div>
        <div class="col-6">
            <span class="text-muted d-block">Bank Account:</span>
            <span class="font-monospace fw-bold text-dark">{{ $slip->employee->bank_name ?? 'SBI' }} - {{ $slip->employee->bank_account_number ?? 'N/A' }}</span>
        </div>
    </div>

    <!-- Salary Breakdown Table -->
    <div class="row g-4 mb-4">
        <!-- EARNINGS -->
        <div class="col-6">
            <h6 class="fw-bold text-success border-bottom pb-2 mb-2">EARNINGS</h6>
            <table class="table table-sm text-dark small">
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->basic_salary, 2) }}</td>
                </tr>
                <tr>
                    <td>House Rent Allowance (HRA)</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->hra, 2) }}</td>
                </tr>
                <tr>
                    <td>Conveyance Allowance</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->conveyance_allowance, 2) }}</td>
                </tr>
                <tr>
                    <td>Special Allowance</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->special_allowance, 2) }}</td>
                </tr>
                <tr class="fw-bold bg-light">
                    <td>Gross Earnings</td>
                    <td class="text-end font-monospace text-success">₹{{ number_format($slip->gross_salary, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- DEDUCTIONS -->
        <div class="col-6">
            <h6 class="fw-bold text-danger border-bottom pb-2 mb-2">DEDUCTIONS</h6>
            <table class="table table-sm text-dark small">
                <tr>
                    <td>Provident Fund (PF)</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->pf_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Professional Tax / TDS</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->tax_deduction, 2) }}</td>
                </tr>
                <tr>
                    <td>Other Deductions</td>
                    <td class="text-end font-monospace">₹{{ number_format($slip->other_deduction, 2) }}</td>
                </tr>
                <tr><td colspan="2">&nbsp;</td></tr>
                <tr class="fw-bold bg-light">
                    <td>Total Deductions</td>
                    <td class="text-end font-monospace text-danger">₹{{ number_format($slip->total_deductions, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- NET PAYOUT -->
    <div class="bg-success-subtle p-3 rounded-3 border border-success-subtle d-flex justify-content-between align-items-center mb-5">
        <div>
            <span class="text-muted d-block small fw-bold uppercase">NET SALARY PAYOUT</span>
            <span class="badge bg-success text-white">Payment Status: {{ strtoupper($slip->payment_status) }}</span>
        </div>
        <div class="font-monospace fs-3 fw-bold text-success">
            ₹{{ number_format($slip->net_salary, 2) }}
        </div>
    </div>

    <!-- SIGNATURES -->
    <div class="d-flex justify-content-between pt-4 text-center small text-muted">
        <div>
            <div style="height: 40px;"></div>
            <div class="border-top pt-1 fw-bold text-dark" style="min-width: 160px;">Employee Signature</div>
        </div>
        <div>
            <div style="height: 40px;"></div>
            <div class="border-top pt-1 fw-bold text-dark" style="min-width: 160px;">Authorized HR Signatory</div>
        </div>
    </div>
</div>

</body>
</html>
