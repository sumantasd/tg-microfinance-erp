<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Book Register - {{ $cashBook->branch->name }} - {{ $cashBook->date->format('d/m/Y') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #fff; color: #000; font-size: 11px; }
        .register-table { border: 2px solid #000 !important; width: 100%; }
        .register-table th, .register-table td { border: 1px solid #000 !important; padding: 4px 6px; }
        .header-title { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .subheader { font-size: 12px; font-weight: bold; }
        @media print {
            .no-print { display: none !important; }
            @page { size: landscape; margin: 10mm; }
            body { padding: 0; background: #fff; }
        }
    </style>
</head>
<body class="p-3">
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-primary btn-sm px-4 rounded-pill">Print Register Sheet</button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm px-3 rounded-pill">Close</button>
    </div>

    <!-- Header Section -->
    <div class="text-center mb-3">
        <h4 class="fw-bold mb-0 header-title">{{ $cashBook->company->name ?? 'GRIHALAXMI FINANCE PRIVATE LIMITED' }}</h4>
        <h5 class="fw-bold text-uppercase subheader mb-1">DAILY CASH BOOK REGISTER</h5>
        <div class="d-flex justify-content-between border-bottom border-2 border-dark pb-1 mt-2">
            <span>BRANCH: <strong>{{ strtoupper($cashBook->branch->name) }}</strong></span>
            <span>DATE: <strong>{{ $cashBook->date->format('d/m/Y') }}</strong></span>
            <span>RESPONSIBLE STAFF: <strong>{{ strtoupper($cashBook->responsibleStaff->name ?? 'STAFF') }}</strong></span>
            <span>STATUS: <strong>{{ strtoupper($cashBook->status) }}</strong></span>
        </div>
    </div>

    <!-- 2-Column Physical Register Layout -->
    <div class="row g-0 mb-3 border border-2 border-dark">
        <!-- LEFT PAGE: RECEIVED -->
        <div class="col-6 border-end border-2 border-dark p-0">
            <div class="text-center fw-bold bg-light border-bottom border-dark py-1 text-uppercase">RECEIVED</div>
            <table class="table table-bordered table-sm mb-0 register-table">
                <thead class="text-center bg-light">
                    <tr>
                        <th style="width: 15%;">DATE</th>
                        <th>PARTICULARS</th>
                        <th style="width: 22%;">AMOUNT RS</th>
                        <th style="width: 20%;">PRODUCT AMT</th>
                        <th style="width: 20%;">BANK AMT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashBook->receivedEntries as $entry)
                        <tr>
                            <td class="text-center">{{ $entry->entry_date->format('d/m') }}</td>
                            <td class="fw-bold text-uppercase">{{ $entry->particulars }}</td>
                            <td class="text-end fw-bold">{{ $entry->cash_amount > 0 ? number_format($entry->cash_amount, 2) : '' }}</td>
                            <td class="text-end">{{ $entry->product_amount > 0 ? number_format($entry->product_amount, 2) : '' }}</td>
                            <td class="text-end">{{ $entry->bank_amount > 0 ? number_format($entry->bank_amount, 2) : '' }}</td>
                        </tr>
                    @endforeach
                    @for($i = 0; $i < max(0, 10 - $cashBook->receivedEntries->count()); $i++)
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                    <tr class="fw-bold bg-light">
                        <td colspan="2" class="text-end">TOTAL RECEIVED AMOUNT</td>
                        <td class="text-end">{{ number_format($cashBook->total_cash_received, 2) }}</td>
                        <td class="text-end">{{ number_format($cashBook->total_product_received, 2) }}</td>
                        <td class="text-end">{{ number_format($cashBook->total_bank_received, 2) }}</td>
                    </tr>
                    <tr class="fw-bold bg-dark text-white">
                        <td colspan="2" class="text-end">CASH IN HAND (RECEIVED - PAYMENT)</td>
                        <td class="text-end">{{ number_format($cashBook->closing_cash, 2) }}</td>
                        <td colspan="2" class="text-center">₹{{ number_format($cashBook->closing_cash, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- RIGHT PAGE: PAYMENT -->
        <div class="col-6 p-0">
            <div class="text-center fw-bold bg-light border-bottom border-dark py-1 text-uppercase">PAYMENT</div>
            <table class="table table-bordered table-sm mb-0 register-table">
                <thead class="text-center bg-light">
                    <tr>
                        <th style="width: 15%;">DATE</th>
                        <th>PARTICULARS</th>
                        <th style="width: 22%;">AMOUNT RS</th>
                        <th style="width: 20%;">PRODUCT AMT</th>
                        <th style="width: 20%;">BANK AMT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashBook->paymentEntries as $entry)
                        <tr>
                            <td class="text-center">{{ $entry->entry_date->format('d/m') }}</td>
                            <td class="fw-bold text-uppercase">{{ $entry->particulars }}</td>
                            <td class="text-end fw-bold">{{ $entry->cash_amount > 0 ? number_format($entry->cash_amount, 2) : '' }}</td>
                            <td class="text-end">{{ $entry->product_amount > 0 ? number_format($entry->product_amount, 2) : '' }}</td>
                            <td class="text-end">{{ $entry->bank_amount > 0 ? number_format($entry->bank_amount, 2) : '' }}</td>
                        </tr>
                    @endforeach
                    @for($i = 0; $i < max(0, 11 - $cashBook->paymentEntries->count()); $i++)
                        <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
                    @endfor
                    <tr class="fw-bold bg-light">
                        <td colspan="2" class="text-end">TOTAL PAYMENT RS.</td>
                        <td class="text-end">{{ number_format($cashBook->total_cash_payment, 2) }}</td>
                        <td class="text-end">{{ number_format($cashBook->total_product_payment, 2) }}</td>
                        <td class="text-end">{{ number_format($cashBook->total_bank_payment, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LOWER SECTION: CASH DENOMINATION (LEFT) & ONLINE COLLECTION DETAILS (RIGHT) -->
    <div class="row g-2 mb-4">
        <!-- DENOMINATION -->
        <div class="col-6">
            <div class="fw-bold mb-1 text-uppercase">CASH DENOMINATION DETAILS</div>
            <table class="table table-bordered table-sm mb-0 register-table">
                <thead class="text-center bg-light">
                    <tr>
                        <th>DENOMINATION</th>
                        <th>QUANTITY</th>
                        <th>AMOUNT (RS)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $denomMap = $cashBook->denominations->keyBy('denomination'); @endphp
                    @foreach([500, 200, 100, 50, 20, 10, 5, 2, 1] as $denom)
                        @php $count = $denomMap[$denom]->count ?? 0; @endphp
                        <tr>
                            <td class="text-center fw-bold">{{ $denom }} X</td>
                            <td class="text-center">{{ $count > 0 ? $count : '' }}</td>
                            <td class="text-end fw-bold">{{ $count > 0 ? number_format($denom * $count, 2) : '' }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold bg-light">
                        <td colspan="2" class="text-end">PHYSICAL CASH TOTAL:</td>
                        <td class="text-end">₹{{ number_format($cashBook->physical_cash, 2) }}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">CASH DIFFERENCE:</td>
                        <td class="text-end">₹{{ number_format($cashBook->cash_difference, 2) }} ({{ strtoupper($cashBook->reconciled_status) }})</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ONLINE COLLECTIONS -->
        <div class="col-6">
            <div class="fw-bold mb-1 text-uppercase">ONLINE COLLECTION DETAILS</div>
            <table class="table table-bordered table-sm mb-0 register-table">
                <thead class="text-center bg-light">
                    <tr>
                        <th style="width: 15%;">DATE</th>
                        <th>CUSTOMER NAME</th>
                        <th style="width: 20%;">AMOUNT</th>
                        <th>GROUP NAME</th>
                        <th>MOBILE NO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashBook->onlineCollections as $oc)
                        <tr>
                            <td class="text-center">{{ $oc->collection_date->format('d/m') }}</td>
                            <td class="fw-bold">{{ $oc->customer_name }}</td>
                            <td class="text-end fw-bold">{{ number_format($oc->amount, 2) }}</td>
                            <td>{{ $oc->group_name ?? '-' }}</td>
                            <td>{{ $oc->mobile_no ?? '-' }}</td>
                        </tr>
                    @empty
                        @for($i = 0; $i < 6; $i++)
                            <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
                        @endfor
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SIGNATURES FOOTER -->
    <div class="row text-center mt-5 pt-4">
        <div class="col-4">
            <div class="border-top border-dark pt-1 font-monospace fw-bold">PREPARED BY (STAFF)</div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-1 font-monospace fw-bold">CHECKED BY (ACCOUNTANT)</div>
        </div>
        <div class="col-4">
            <div class="border-top border-dark pt-1 font-monospace fw-bold">APPROVED BY (BRANCH MANAGER)</div>
        </div>
    </div>
</body>
</html>
