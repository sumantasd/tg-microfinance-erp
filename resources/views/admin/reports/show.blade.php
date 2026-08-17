@extends('layouts.admin')

@section('title', $reportData['title'] . ' - Reports Center')

@section('content')
<!-- Navigation & Action Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}" class="text-decoration-none"><i class="bi bi-bar-chart-line me-1"></i>Reports Center</a></li>
                <li class="breadcrumb-item text-muted">{{ $categoryMeta['title'] }}</li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page">{{ $reportMeta['title'] }}</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark font-heading mb-1">{{ $reportData['title'] }}</h4>
        <p class="text-muted small mb-0">{{ $reportMeta['desc'] }}</p>
    </div>

    <!-- Action Toolbar -->
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('admin.reports.print', array_merge(['category' => $category, 'type' => $type], request()->all())) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
            <i class="bi bi-printer me-1"></i> Print
        </a>

        @can('reports.export')
        <a href="{{ route('admin.reports.export', array_merge(['category' => $category, 'type' => $type], request()->all())) }}" class="btn btn-sm btn-success text-white rounded-pill px-3.5 fw-bold shadow-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        @endcan

        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-light border rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- Dynamic Filter Panel -->
<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="GET" action="{{ route('admin.reports.show', ['category' => $category, 'type' => $type]) }}" class="row g-2 align-items-end">
        @if(auth()->user()->isSuperAdmin() && count($companies) > 1)
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Company</label>
                <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($companies as $comp)
                        <option value="{{ $comp->id }}" {{ (int)($filters['company_id'] ?? $companyId) === $comp->id ? 'selected' : '' }}>
                            {{ $comp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if(count($branches) > 1)
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $br)
                        <option value="{{ $br->id }}" {{ (int)($filters['branch_id'] ?? $branchId) === $br->id ? 'selected' : '' }}>
                            {{ $br->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-select-sm">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-select-sm">
        </div>

        @if(in_array($category, ['overdue']))
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">As-of Date</label>
                <input type="date" name="as_of_date" value="{{ $filters['as_of_date'] ?? date('Y-m-d') }}" class="form-control form-select-sm">
            </div>
        @endif

        @if(in_array($category, ['loan', 'management']) && count($loanSchemes) > 0)
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Loan Scheme</label>
                <select name="loan_scheme_id" class="form-select form-select-sm">
                    <option value="">All Schemes</option>
                    @foreach($loanSchemes as $scheme)
                        <option value="{{ $scheme->id }}" {{ (int)($filters['loan_scheme_id'] ?? 0) === $scheme->id ? 'selected' : '' }}>
                            {{ $scheme->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if(in_array($category, ['collection']))
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All Methods</option>
                    <option value="cash" {{ ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="upi" {{ ($filters['payment_method'] ?? '') === 'upi' ? 'selected' : '' }}>UPI / Digital</option>
                    <option value="bank_transfer" {{ ($filters['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ ($filters['payment_method'] ?? '') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                </select>
            </div>
        @endif

        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Search Keywords</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-select-sm" placeholder="e.g. Loan # / Name...">
        </div>

        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3.5 fw-bold">
                <i class="bi bi-funnel-fill me-1"></i> Apply
            </button>
            <a href="{{ route('admin.reports.show', ['category' => $category, 'type' => $type]) }}" class="btn btn-sm btn-light border rounded-pill px-3" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</x-ui.card>

<!-- KPI Summary Cards Strip (if present) -->
@if(!empty($reportData['kpis']))
<div class="row g-3 mb-4">
    @foreach($reportData['kpis'] as $kpi)
        <div class="col-sm-6 col-lg-{{ 12 / min(4, count($reportData['kpis'])) }}">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted fw-bold text-uppercase" style="font-size: 0.75rem;">{{ $kpi['label'] }}</span>
                    <div class="rounded-circle p-2 bg-{{ $kpi['color'] }}-subtle text-{{ $kpi['color'] }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi {{ $kpi['icon'] ?? 'bi-graph-up' }} fs-6"></i>
                    </div>
                </div>
                <div class="fs-4 fw-bold text-{{ $kpi['color'] }} font-heading">{{ $kpi['value'] }}</div>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- Data Table Card -->
<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light bg-opacity-50">
        <h6 class="fw-bold text-dark mb-0 font-heading">
            <i class="bi bi-table me-1.5 text-primary"></i> Data Records ({{ is_array($reportData['rows']) ? count($reportData['rows']) : (is_object($reportData['rows']) ? $reportData['rows']->count() : 0) }} Items)
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-muted text-uppercase small" style="width: 50px;">#</th>
                    @foreach($reportData['columns'] as $colKey => $colLabel)
                        <th class="text-muted text-uppercase small {{ str_contains(strtolower($colLabel), '(₹)') || str_contains(strtolower($colLabel), 'amount') || str_contains(strtolower($colLabel), 'principal') || str_contains(strtolower($colLabel), 'balance') ? 'text-end' : '' }}">
                            {{ $colLabel }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rows'] as $row)
                    <tr>
                        <td class="ps-3 text-muted font-monospace small">
                            {{ $reportData['paginator'] ? (($reportData['paginator']->currentPage() - 1) * $reportData['paginator']->perPage() + $loop->iteration) : $loop->iteration }}
                        </td>
                        @foreach($reportData['columns'] as $colKey => $colLabel)
                            @php
                                $val = $row[$colKey] ?? '';
                                $isMonetary = str_contains(strtolower($colLabel), '(₹)') || str_contains(strtolower($colLabel), 'amount') || str_contains(strtolower($colLabel), 'principal') || str_contains(strtolower($colLabel), 'balance') || str_starts_with((string)$val, '₹');
                            @endphp
                            <td class="{{ $isMonetary ? 'text-end font-monospace fw-semibold' : '' }}">
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
                        <td colspan="{{ count($reportData['columns']) + 1 }}" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                            <div class="fw-bold">No records found matching your filter criteria.</div>
                            <small>Try clearing or modifying the applied filters above.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reportData['paginator'] && $reportData['paginator']->hasPages())
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Showing {{ $reportData['paginator']->firstItem() ?? 0 }} to {{ $reportData['paginator']->lastItem() ?? 0 }} of {{ $reportData['paginator']->total() }} entries
            </div>
            <div>
                {{ $reportData['paginator']->links() }}
            </div>
        </div>
    @endif
</x-ui.card>
@endsection
