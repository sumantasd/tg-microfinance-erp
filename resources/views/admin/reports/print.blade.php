<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['title'] }} - Print Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            padding: 24px;
            font-size: 12px;
        }
        .report-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .report-title {
            font-size: 15px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
        }
        .meta-pill {
            font-size: 11px;
            color: #64748b;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        table.report-table th, table.report-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 11px;
        }
        table.report-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kpi-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .sign-box {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            table.report-table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>
    <!-- Top Action Bar for screen view -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-sm btn-primary fw-bold px-3">
            Print Document
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-outline-secondary px-3">
            Close Window
        </button>
    </div>

    <!-- Official Report Header -->
    <div class="report-header d-flex justify-content-between align-items-start">
        <div>
            <div class="company-title">{{ $company->name ?? 'TG Microfinance ERP' }}</div>
            <div class="meta-pill">
                @if(!empty($company->registration_number)) Reg: {{ $company->registration_number }} &bull; @endif
                @if($branch) Branch: <strong>{{ $branch->name }}</strong> ({{ $branch->code }}) @else Organization: Head Office Consolidated @endif
            </div>
            @if(!empty($company->address))
                <div class="meta-pill">{{ $company->address }}</div>
            @endif
        </div>
        <div class="text-end">
            <div class="report-title">{{ $reportData['title'] }}</div>
            <div class="meta-pill">Generated: {{ date('d M Y, h:i A') }}</div>
            <div class="meta-pill">Period: {{ $filters['date_from'] ?? 'All Past' }} to {{ $filters['date_to'] ?? 'Current' }}</div>
        </div>
    </div>

    <!-- Summary KPI Strip -->
    @if(!empty($reportData['kpis']))
    <div class="row g-2 mb-3">
        @foreach($reportData['kpis'] as $kpi)
            <div class="col">
                <div class="kpi-card">
                    <div class="text-muted small text-uppercase" style="font-size: 9px;">{{ $kpi['label'] }}</div>
                    <div class="fw-bold fs-6 text-dark">{{ $kpi['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Tabular Report Data -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 35px;">#</th>
                @foreach($reportData['columns'] as $colKey => $colLabel)
                    <th class="{{ str_contains(strtolower($colLabel), '(₹)') || str_contains(strtolower($colLabel), 'amount') || str_contains(strtolower($colLabel), 'principal') || str_contains(strtolower($colLabel), 'balance') ? 'text-end' : '' }}">
                        {{ $colLabel }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reportData['rows'] as $row)
                <tr>
                    <td class="text-muted text-center">{{ $loop->iteration }}</td>
                    @foreach($reportData['columns'] as $colKey => $colLabel)
                        @php
                            $val = $row[$colKey] ?? '';
                            $isMonetary = str_contains(strtolower($colLabel), '(₹)') || str_contains(strtolower($colLabel), 'amount') || str_contains(strtolower($colLabel), 'principal') || str_contains(strtolower($colLabel), 'balance') || str_starts_with((string)$val, '₹');
                        @endphp
                        <td class="{{ $isMonetary ? 'text-end' : '' }}">
                            @if(is_numeric($val) && $isMonetary)
                                ₹{{ number_format((float)$val, 2) }}
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($reportData['columns']) + 1 }}" class="text-center py-4 text-muted">
                        No records found for the selected reporting parameters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Sign-off Strip -->
    <div class="row mt-5 pt-3">
        <div class="col-4">
            <div class="sign-box">Prepared By (Staff)</div>
        </div>
        <div class="col-4">
            <div class="sign-box">Verified By (Branch / Accountant)</div>
        </div>
        <div class="col-4">
            <div class="sign-box">Authorized Signatory (Manager / Audit)</div>
        </div>
    </div>
</body>
</html>
