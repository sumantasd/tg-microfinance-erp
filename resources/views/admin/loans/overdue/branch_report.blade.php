@extends('layouts.admin')

@section('title', 'Branch Aging & PAR Report - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header & Filter Form -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Branch-Wise Aging & Portfolio at Risk (PAR) Report</h4>
            <p class="text-muted small mb-0">Cross-branch delinquency benchmark, aging bucket comparison & recovery risk tracking</p>
        </div>
        <form method="GET" action="{{ route('admin.overdue.branch-aging') }}" class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white"><i class="bi bi-calendar"></i> As of Date:</span>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}" onchange="this.form.submit()">
            </div>
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
        </form>
    </div>

    <!-- Branch Comparison Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-buildings text-primary me-2"></i>Branch Portfolio Performance Comparison (As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M, Y') }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Branch Name</th>
                            <th class="text-center">Active Loans</th>
                            <th class="text-end">Total Portfolio</th>
                            <th class="text-end">Total Overdue</th>
                            <th class="text-center">Overdue Rate</th>
                            <th class="text-end">Current (0 DPD)</th>
                            <th class="text-end">1–30 DPD</th>
                            <th class="text-end">31–60 DPD</th>
                            <th class="text-end">61–90 DPD</th>
                            <th class="text-end">90+ DPD</th>
                            <th class="text-center text-danger">PAR 30 (%)</th>
                            <th class="text-center text-danger">PAR 60 (%)</th>
                            <th class="text-center text-danger pe-3">PAR 90 (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comparison as $b)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    {{ $b['branch_name'] }}
                                    <div class="small text-muted font-monospace">{{ $b['branch_code'] }}</div>
                                </td>
                                <td class="text-center fw-semibold">{{ $b['total_active_loans'] }}</td>
                                <td class="text-end fw-semibold">₹{{ number_format($b['total_active_portfolio'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($b['total_overdue_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $b['overdue_rate_pct'] > 10 ? 'bg-danger' : ($b['overdue_rate_pct'] > 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $b['overdue_rate_pct'] }}%
                                    </span>
                                </td>
                                <td class="text-end text-success">₹{{ number_format($b['aging_breakdown']['current']['principal'], 2) }}</td>
                                <td class="text-end">₹{{ number_format($b['aging_breakdown']['1_30']['principal'], 2) }}</td>
                                <td class="text-end">₹{{ number_format($b['aging_breakdown']['31_60']['principal'], 2) }}</td>
                                <td class="text-end">₹{{ number_format($b['aging_breakdown']['61_90']['principal'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($b['aging_breakdown']['90_plus']['principal'], 2) }}</td>
                                <td class="text-center fw-bold text-danger">{{ $b['par_30_pct'] }}%</td>
                                <td class="text-center fw-bold text-danger">{{ $b['par_60_pct'] }}%</td>
                                <td class="text-center fw-bold text-dark pe-3">{{ $b['par_90_pct'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-4 text-muted">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
