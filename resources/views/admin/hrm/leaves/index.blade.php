@extends('layouts.admin')

@section('title', 'Leave Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-minus text-warning me-2"></i>Leave Applications & Approvals</h4>
        <p class="text-muted small mb-0">Manage staff leave requests, balances, and multi-status approval workflow.</p>
    </div>
    @can('leave.create')
        <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
            <i class="bi bi-plus-circle me-1"></i> Apply for Leave
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
    <form action="{{ route('admin.hrm.leave.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light" placeholder="Search staff name or code...">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="leave_type_id" class="form-select bg-light">
                <option value="">All Leave Types</option>
                @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" {{ ($filters['leave_type_id'] ?? '') == $type->id ? 'selected' : '' }}>{{ $type->name }} ({{ $type->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.hrm.leave.index') }}" class="btn btn-light border rounded-3"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Leaves Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden mb-4">
    <x-ui.data-table :headers="['Staff Member', 'Leave Type', 'Start - End Date', 'Days', 'Reason', 'Status', 'Actions']">
        @forelse($leaves as $leave)
            <tr>
                <td>
                    <div class="fw-bold text-dark">{{ $leave->employee->full_name }}</div>
                    <div class="font-monospace small text-primary">{{ $leave->employee->employee_code }}</div>
                </td>
                <td><span class="badge bg-light text-dark border">{{ $leave->leaveType->name ?? 'Leave' }}</span></td>
                <td><span class="small fw-semibold text-dark">{{ $leave->start_date->format('M d, Y') }} - {{ $leave->end_date->format('M d, Y') }}</span></td>
                <td><span class="font-monospace fw-bold text-primary">{{ $leave->total_days }}</span></td>
                <td><span class="small text-muted text-truncate d-inline-block" style="max-width: 180px;">{{ $leave->reason }}</span></td>
                <td>
                    @if($leave->status === 'approved')
                        <span class="badge bg-success-subtle text-success border rounded-pill"><i class="bi bi-check-circle me-1"></i>Approved</span>
                    @elseif($leave->status === 'rejected')
                        <span class="badge bg-danger-subtle text-danger border rounded-pill"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border rounded-pill"><i class="bi bi-clock me-1"></i>Pending</span>
                    @endif
                </td>
                <td>
                    @if($leave->status === 'pending')
                        @can('leave.approve')
                            <div class="d-flex gap-1">
                                <form action="{{ route('admin.hrm.leave.approve', $leave->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 py-0.5">Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-0.5" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}">Reject</button>
                            </div>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-sm">
                                    <form action="{{ route('admin.hrm.leave.reject', $leave->id) }}" method="POST" class="modal-content">
                                        @csrf
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Reject Leave Application</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label small fw-bold">Rejection Reason</label>
                                            <textarea name="rejection_reason" class="form-control" rows="2" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-sm btn-danger rounded-pill w-100">Reject Application</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endcan
                    @else
                        <span class="small text-muted">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No leave applications found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $leaves->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $leaves->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $leaves->total() }}</span> applications
        </div>
        <div>
            {{ $leaves->links() }}
        </div>
    </div>
</x-ui.card>

<!-- Apply Leave Modal -->
<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.hrm.leave.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-calendar-plus me-2 text-primary"></i>Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Select Employee</label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">Select Staff...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }})</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id ?? 1 }}">
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id ?? 1 }}">
                <div class="col-12">
                    <label class="form-label small fw-bold">Leave Type</label>
                    <select name="leave_type_id" class="form-select" required>
                        <option value="">Select Category...</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Start Date</label>
                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">End Date</label>
                    <input type="date" name="end_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Reason for Leave</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Provide detailed reason..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Submit Application</button>
            </div>
        </form>
    </div>
</div>
@endsection
