@extends('layouts.admin')

@section('title', 'Overdue & DPD Dashboard - Grihalaxmi Finance ERP')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header & Filter Form -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-clock-history text-danger me-2"></i>Overdue & DPD Management</h4>
            <p class="text-muted small mb-0">Dynamic real-time portfolio delinquency tracking, Days Past Due (DPD) & Portfolio at Risk (PAR)</p>
        </div>
        <form method="GET" action="{{ route('admin.overdue.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                <select name="branch_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white"><i class="bi bi-calendar"></i></span>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}" onchange="this.form.submit()">
            </div>
            <a href="{{ route('admin.overdue.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2">
                <li class="nav-item">
                    <a class="nav-link active fw-semibold" href="{{ route('admin.overdue.dashboard') }}"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary" href="{{ route('admin.overdue.loans') }}"><i class="bi bi-wallet2 me-1"></i> Overdue Loans</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary" href="{{ route('admin.overdue.installments') }}"><i class="bi bi-list-check me-1"></i> Overdue Installments</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary" href="{{ route('admin.overdue.customers') }}"><i class="bi bi-people me-1"></i> Customer Overdue</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold text-secondary" href="{{ route('admin.overdue.branch-aging') }}"><i class="bi bi-diagram-3 me-1"></i> Branch Aging & PAR</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Active Portfolio -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Active Portfolio</span>
                            <h4 class="fw-bold mb-0 text-primary mt-1">₹{{ number_format($metrics['total_active_portfolio'], 2) }}</h4>
                            <small class="text-muted">{{ $metrics['total_active_loans'] }} Active Accounts</small>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i class="bi bi-briefcase fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Overdue Amount -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Total Overdue</span>
                            <h4 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($metrics['total_overdue_amount'], 2) }}</h4>
                            <small class="text-danger fw-semibold">{{ $metrics['delinquent_loans_count'] }} Delinquent Loans ({{ $metrics['overdue_rate_pct'] }}% Rate)</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Maximum DPD -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Maximum DPD</span>
                            <h4 class="fw-bold mb-0 text-warning mt-1">{{ $metrics['max_dpd'] }} <span class="fs-6 fw-normal text-muted">Days</span></h4>
                            <small class="text-muted">Oldest Unpaid Delay</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. PAR 30 Metric -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">PAR 30 (%)</span>
                            <h4 class="fw-bold mb-0 text-danger mt-1">{{ $metrics['par_30_pct'] }}%</h4>
                            <small class="text-muted">₹{{ number_format($metrics['par_30_amount'], 2) }} At Risk</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                            <i class="bi bi-shield-slash fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Portfolio at Risk (PAR) Summary Strip -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-exclamation text-danger me-2"></i>Portfolio at Risk (PAR) Benchmark Metrics</h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-3 text-center">
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light border">
                        <span class="text-muted small fw-bold">PAR 30 (DPD > 30)</span>
                        <h4 class="fw-bold text-danger my-1">{{ $metrics['par_30_pct'] }}%</h4>
                        <span class="text-muted small">₹{{ number_format($metrics['par_30_amount'], 2) }} Principal Outstanding</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light border">
                        <span class="text-muted small fw-bold">PAR 60 (DPD > 60)</span>
                        <h4 class="fw-bold text-danger my-1">{{ $metrics['par_60_pct'] }}%</h4>
                        <span class="text-muted small">₹{{ number_format($metrics['par_60_amount'], 2) }} Principal Outstanding</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-light border">
                        <span class="text-muted small fw-bold">PAR 90 (DPD > 90 / NPA)</span>
                        <h4 class="fw-bold text-dark my-1">{{ $metrics['par_90_pct'] }}%</h4>
                        <span class="text-muted small">₹{{ number_format($metrics['par_90_amount'], 2) }} Principal Outstanding</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Bucket Distribution Breakdown -->
    <div class="row g-3 mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-steps text-primary me-2"></i>Delinquency Aging Buckets (As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M, Y') }})</h6>
                    <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-primary">View All Delinquent Loans</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Aging Bucket</th>
                                    <th>Delinquency Range</th>
                                    <th class="text-center">Loan Count</th>
                                    <th class="text-end">Principal Outstanding</th>
                                    <th class="text-end">Overdue Amount</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-success bg-opacity-10 text-success px-2 py-1"><i class="bi bi-check-circle me-1"></i> Current</span></td>
                                    <td>0 Days (No Overdue)</td>
                                    <td class="text-center fw-bold">{{ $metrics['aging_breakdown']['current']['count'] }}</td>
                                    <td class="text-end">₹{{ number_format($metrics['aging_breakdown']['current']['principal'], 2) }}</td>
                                    <td class="text-end text-muted">₹0.00</td>
                                    <td class="text-end pe-3"><span class="badge bg-light text-muted">Healthy</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-warning bg-opacity-20 text-dark px-2 py-1"><i class="bi bi-clock me-1"></i> 1–30 Days</span></td>
                                    <td>Early Delinquency</td>
                                    <td class="text-center fw-bold">{{ $metrics['aging_breakdown']['1_30']['count'] }}</td>
                                    <td class="text-end">₹{{ number_format($metrics['aging_breakdown']['1_30']['principal'], 2) }}</td>
                                    <td class="text-end text-danger fw-bold">₹{{ number_format($metrics['aging_breakdown']['1_30']['overdue'], 2) }}</td>
                                    <td class="text-end pe-3"><a href="{{ route('admin.overdue.loans', ['dpd_bucket' => '1_30']) }}" class="btn btn-xs btn-outline-warning">Filter</a></td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-triangle me-1"></i> 31–60 Days</span></td>
                                    <td>Moderate Delinquency</td>
                                    <td class="text-center fw-bold">{{ $metrics['aging_breakdown']['31_60']['count'] }}</td>
                                    <td class="text-end">₹{{ number_format($metrics['aging_breakdown']['31_60']['principal'], 2) }}</td>
                                    <td class="text-end text-danger fw-bold">₹{{ number_format($metrics['aging_breakdown']['31_60']['overdue'], 2) }}</td>
                                    <td class="text-end pe-3"><a href="{{ route('admin.overdue.loans', ['dpd_bucket' => '31_60']) }}" class="btn btn-xs btn-outline-warning">Filter</a></td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-danger bg-opacity-75 text-white px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i> 61–90 Days</span></td>
                                    <td>High Delinquency</td>
                                    <td class="text-center fw-bold">{{ $metrics['aging_breakdown']['61_90']['count'] }}</td>
                                    <td class="text-end">₹{{ number_format($metrics['aging_breakdown']['61_90']['principal'], 2) }}</td>
                                    <td class="text-end text-danger fw-bold">₹{{ number_format($metrics['aging_breakdown']['61_90']['overdue'], 2) }}</td>
                                    <td class="text-end pe-3"><a href="{{ route('admin.overdue.loans', ['dpd_bucket' => '61_90']) }}" class="btn btn-xs btn-outline-danger">Filter</a></td>
                                </tr>
                                <tr>
                                    <td class="ps-3"><span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-shield-x me-1"></i> 90+ Days</span></td>
                                    <td>Sub-Standard / NPA</td>
                                    <td class="text-center fw-bold text-danger">{{ $metrics['aging_breakdown']['90_plus']['count'] }}</td>
                                    <td class="text-end text-danger fw-bold">₹{{ number_format($metrics['aging_breakdown']['90_plus']['principal'], 2) }}</td>
                                    <td class="text-end text-danger fw-bold">₹{{ number_format($metrics['aging_breakdown']['90_plus']['overdue'], 2) }}</td>
                                    <td class="text-end pe-3"><a href="{{ route('admin.overdue.loans', ['dpd_bucket' => '90_plus']) }}" class="btn btn-xs btn-danger">Filter</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Overdue Loans Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-stars text-danger me-2"></i>Critical Overdue Loans (Top 10 by DPD)</h6>
            <a href="{{ route('admin.overdue.loans') }}" class="btn btn-sm btn-outline-secondary">View All Overdue Loans</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Loan Number</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th class="text-end">Principal Out.</th>
                            <th class="text-end">Overdue Amount</th>
                            <th class="text-center">DPD</th>
                            <th>Aging Bucket</th>
                            <th>Oldest Due Date</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topOverdueLoans as $item)
                            @php
                                $loan = $item['loan'];
                                $det = $item['details'];
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="text-decoration-none text-primary">
                                        {{ $loan->loan_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($loan->customer)
                                        <a href="{{ route('admin.overdue.customer-profile', $loan->customer->id) }}" class="text-dark fw-semibold text-decoration-none">
                                            {{ $loan->customer->full_name }}
                                        </a>
                                        <div class="small text-muted">{{ $loan->customer->customer_code }}</div>
                                    @else
                                        <span class="text-muted">Group / Direct</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $loan->branch->name ?? 'N/A' }}</span></td>
                                <td class="text-end fw-semibold">₹{{ number_format($det['principal_outstanding'], 2) }}</td>
                                <td class="text-end text-danger fw-bold">₹{{ number_format($det['overdue_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $det['dpd'] > 60 ? 'bg-danger' : ($det['dpd'] > 30 ? 'bg-warning text-dark' : 'bg-warning bg-opacity-25 text-dark') }} px-2 py-1">
                                        {{ $det['dpd'] }} Days
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-secondary border">{{ $det['aging_bucket'] }}</span></td>
                                <td class="small">{{ $det['oldest_overdue_date'] ? \Carbon\Carbon::parse($det['oldest_overdue_date'])->format('d M, Y') : 'N/A' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.loan-account.show', $loan->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                                    No overdue loans found for the selected branch/date. Portfolio is performing cleanly!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
