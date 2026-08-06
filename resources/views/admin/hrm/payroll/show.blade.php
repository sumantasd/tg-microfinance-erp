@extends('layouts.admin')

@section('title', 'Payroll Batch Details - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>Payroll Batch: {{ date('F', mktime(0,0,0,$payroll->month,1)) }} {{ $payroll->year }}</h4>
        <p class="text-muted small mb-0">Branch: <span class="fw-bold text-dark">{{ $payroll->branch->name ?? 'N/A' }}</span> | UUID: <span class="font-monospace text-muted small">{{ $payroll->uuid }}</span></p>
    </div>
    <div class="d-flex gap-2">
        @if($payroll->status !== 'disbursed')
            @can('payroll.disburse')
                <form action="{{ route('admin.hrm.payroll.disburse', $payroll->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" onclick="return confirm('Disburse monthly salary to all staff members?');">
                        <i class="bi bi-check-all me-1"></i> Disburse All Pay Slips
                    </button>
                </form>
            @endcan
        @else
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fs-6"><i class="bi bi-check-circle me-1"></i>Disbursed on {{ $payroll->updated_at->format('M d, Y') }}</span>
        @endif
        <a href="{{ route('admin.hrm.payroll.index') }}" class="btn btn-outline-secondary rounded-pill px-3.5 py-2 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Payrolls
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-primary border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Total Employees</small>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $payroll->total_employees }} Staff</div>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-info border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Total Gross Salary</small>
            <div class="fs-4 fw-bold text-dark mt-1">₹{{ number_format($payroll->total_gross, 2) }}</div>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-danger border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Total Statutory Deductions</small>
            <div class="fs-4 fw-bold text-danger mt-1">₹{{ number_format($payroll->total_deductions, 2) }}</div>
        </x-ui.card>
    </div>
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-start border-success border-4">
            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.725rem;">Net Payout Amount</small>
            <div class="fs-4 fw-bold text-success mt-1">₹{{ number_format($payroll->total_net_payout, 2) }}</div>
        </x-ui.card>
    </div>
</div>

<!-- Salary Slips Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden mb-4">
    <x-ui.data-table :headers="['Employee', 'Designation', 'Basic Salary', 'Gross Salary', 'Total Deductions', 'Net Salary', 'Status', 'Action']">
        @foreach($payroll->salarySlips as $slip)
            <tr>
                <td>
                    <div class="fw-bold text-dark">{{ $slip->employee->full_name }}</div>
                    <div class="font-monospace small text-primary">{{ $slip->employee->employee_code }}</div>
                </td>
                <td><span class="small fw-semibold text-secondary">{{ $slip->employee->designation->title ?? 'Staff' }}</span></td>
                <td><span class="font-monospace small text-dark">₹{{ number_format($slip->basic_salary, 2) }}</span></td>
                <td><span class="font-monospace small text-dark">₹{{ number_format($slip->gross_salary, 2) }}</span></td>
                <td><span class="font-monospace small text-danger">₹{{ number_format($slip->total_deductions, 2) }}</span></td>
                <td><span class="font-monospace fw-bold text-success">₹{{ number_format($slip->net_salary, 2) }}</span></td>
                <td>
                    @if($slip->payment_status === 'paid')
                        <span class="badge bg-success-subtle text-success border rounded-pill">PAID</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border rounded-pill">UNPAID</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.hrm.payroll.slip', $slip->uuid) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold">
                        <i class="bi bi-printer me-1"></i> Pay Slip
                    </a>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
</x-ui.card>
@endsection
