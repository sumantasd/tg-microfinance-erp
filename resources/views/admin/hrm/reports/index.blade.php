@extends('layouts.admin')

@section('title', 'HR Analytics & Reports - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-bar-graph text-success me-2"></i>Enterprise HR Analytics & Reports</h4>
        <p class="text-muted small mb-0">Overview of staff headcount, attendance statistics, leave balances, and payroll disbursements.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-primary border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Total Headcount</small>
            <div class="fs-3 fw-bold text-dark mt-1">{{ $stats['total_employees'] }} Staff</div>
            <span class="badge bg-success-subtle text-success mt-1">{{ $stats['active_employees'] }} Active</span>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-success border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Present Today</small>
            <div class="fs-3 fw-bold text-success mt-1">{{ $stats['total_present_today'] }} Present</div>
            <span class="small text-muted mt-1 d-block">{{ date('d M Y') }}</span>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-warning border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Pending Leave Requests</small>
            <div class="fs-3 fw-bold text-warning mt-1">{{ $stats['pending_leaves'] }} Applications</div>
            <a href="{{ route('admin.hrm.leave.index') }}" class="small text-primary text-decoration-none mt-1 d-block">Review Requests &rarr;</a>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-info border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Total Payroll Disbursed</small>
            <div class="fs-3 fw-bold text-info mt-1">₹{{ number_format($stats['total_payroll_disbursed'], 2) }}</div>
            <a href="{{ route('admin.hrm.payroll.index') }}" class="small text-primary text-decoration-none mt-1 d-block">View Payroll Batches &rarr;</a>
        </x-ui.card>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <x-ui.card class="p-4 shadow-sm h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-download me-2 text-primary"></i>Download HR Export Sheets</h6>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.hrm.attendance.index') }}" class="btn btn-outline-secondary text-start p-3 rounded-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-dark">Staff Daily Attendance Sheet</strong>
                        <small class="text-muted">Export monthly clock-in and attendance logs per branch</small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <a href="{{ route('admin.hrm.leave.index') }}" class="btn btn-outline-secondary text-start p-3 rounded-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-dark">Leave Usage & Ledger Report</strong>
                        <small class="text-muted">Export staff leave application status and approved balances</small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </a>
                <a href="{{ route('admin.hrm.payroll.index') }}" class="btn btn-outline-secondary text-start p-3 rounded-3 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-dark">Payroll Disburse Sheet</strong>
                        <small class="text-muted">Export monthly net payouts and bank transfer advice</small>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </x-ui.card>
    </div>

    <div class="col-md-6">
        <x-ui.card class="p-4 shadow-sm h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-shield-check me-2 text-success"></i>Multi-Branch Data Isolation Status</h6>
            <p class="text-muted small">Your current session is restricted strictly according to your assigned RBAC role and branch boundary context.</p>
            <div class="bg-light p-3 rounded-3 small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Role Assigned:</span>
                    <span class="badge bg-primary">{{ implode(', ', auth()->user()->getRoleNames()->toArray()) }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Company Scoping:</span>
                    <span class="fw-bold text-dark">{{ auth()->user()->company->name ?? 'Global (Super Admin)' }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Branch Scoping:</span>
                    <span class="fw-bold text-dark">{{ auth()->user()->branch->name ?? 'Global (Super Admin)' }}</span>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
