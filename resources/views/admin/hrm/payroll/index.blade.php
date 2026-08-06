@extends('layouts.admin')

@section('title', 'Payroll Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-cash-stack text-success me-2"></i>Monthly Payroll Runs & Salary Slips</h4>
        <p class="text-muted small mb-0">Process monthly staff compensation, generate pay slips, and record salary disbursements.</p>
    </div>
    @can('payroll.process')
        <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#processPayrollModal">
            <i class="bi bi-play-circle me-1"></i> Run Monthly Payroll
        </button>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.hrm.payroll.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <select name="month" class="form-select bg-light">
                <option value="">All Months</option>
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ ($filters['month'] ?? date('n')) == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-3">
            <select name="year" class="form-select bg-light">
                <option value="">All Years</option>
                @for($y=2024; $y<=2030; $y++)
                    <option value="{{ $y }}" {{ ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="disbursed" {{ ($filters['status'] ?? '') === 'disbursed' ? 'selected' : '' }}>Disbursed</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.hrm.payroll.index') }}" class="btn btn-light border rounded-3"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Payroll Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden mb-4">
    <x-ui.data-table :headers="['Branch Office', 'Period', 'Staff Count', 'Gross Total', 'Total Deductions', 'Net Payout', 'Status', 'Actions']">
        @forelse($payrolls as $pay)
            <tr>
                <td>
                    <div class="fw-bold text-dark">{{ $pay->branch->name ?? 'N/A' }}</div>
                    <small class="text-muted">{{ $pay->company->name ?? 'N/A' }}</small>
                </td>
                <td><span class="fw-semibold text-primary">{{ date('F', mktime(0,0,0,$pay->month,1)) }} {{ $pay->year }}</span></td>
                <td><span class="badge bg-light text-dark border">{{ $pay->total_employees }} Employees</span></td>
                <td><span class="font-monospace fw-semibold text-dark">₹{{ number_format($pay->total_gross, 2) }}</span></td>
                <td><span class="font-monospace fw-semibold text-danger">₹{{ number_format($pay->total_deductions, 2) }}</span></td>
                <td><span class="font-monospace fw-bold text-success fs-6">₹{{ number_format($pay->total_net_payout, 2) }}</span></td>
                <td>
                    @if($pay->status === 'disbursed')
                        <span class="badge bg-success-subtle text-success border rounded-pill"><i class="bi bi-check-circle me-1"></i>Disbursed</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border rounded-pill"><i class="bi bi-clock me-1"></i>Draft</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.hrm.payroll.show', $pay->id) }}" class="btn btn-light btn-sm border" title="View Details & Slips"><i class="bi bi-eye text-primary"></i></a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">No monthly payroll runs found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $payrolls->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $payrolls->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $payrolls->total() }}</span> payroll runs
        </div>
        <div>
            {{ $payrolls->links() }}
        </div>
    </div>
</x-ui.card>

<!-- Process Payroll Modal -->
<div class="modal fade" id="processPayrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.hrm.payroll.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-play-circle me-2 text-primary"></i>Run Monthly Payroll Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Select Branch Office</label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch...</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id ?? 1 }}">
                <div class="col-6">
                    <label class="form-label small fw-bold">Month</label>
                    <select name="month" class="form-select" required>
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Year</label>
                    <input type="number" name="year" value="{{ date('Y') }}" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Generate Batch Payroll</button>
            </div>
        </form>
    </div>
</div>
@endsection
