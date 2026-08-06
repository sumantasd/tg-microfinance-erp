@extends('layouts.admin')

@section('title', 'Attendance Management - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Daily Staff Attendance</h4>
        <p class="text-muted small mb-0">Log, track and monitor branch employee daily attendance and working hours.</p>
    </div>
    @can('attendance.create')
        <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
            <i class="bi bi-plus-circle me-1"></i> Mark Attendance
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
    <form action="{{ route('admin.hrm.attendance.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control bg-light" placeholder="Search staff name or code...">
        </div>
        <div class="col-md-3">
            <input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="form-control bg-light">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="present" {{ ($filters['status'] ?? '') === 'present' ? 'selected' : '' }}>Present</option>
                <option value="late" {{ ($filters['status'] ?? '') === 'late' ? 'selected' : '' }}>Late</option>
                <option value="half_day" {{ ($filters['status'] ?? '') === 'half_day' ? 'selected' : '' }}>Half Day</option>
                <option value="absent" {{ ($filters['status'] ?? '') === 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="on_leave" {{ ($filters['status'] ?? '') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.hrm.attendance.index') }}" class="btn btn-light border rounded-3"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Attendance Table -->
<x-ui.card class="p-0 shadow-sm overflow-hidden mb-4">
    <x-ui.data-table :headers="['Staff Member', 'Branch', 'Date', 'Clock In', 'Clock Out', 'Status', 'Remarks']">
        @forelse($attendances as $att)
            <tr>
                <td>
                    <div class="fw-bold text-dark">{{ $att->employee->full_name }}</div>
                    <div class="font-monospace small text-primary">{{ $att->employee->employee_code }}</div>
                </td>
                <td><span class="small fw-semibold text-secondary">{{ $att->branch->name ?? 'N/A' }}</span></td>
                <td><span class="fw-semibold text-dark">{{ $att->attendance_date->format('M d, Y') }}</span></td>
                <td><span class="font-monospace small text-success">{{ $att->clock_in ?? '--:--' }}</span></td>
                <td><span class="font-monospace small text-danger">{{ $att->clock_out ?? '--:--' }}</span></td>
                <td>
                    @if($att->status === 'present')
                        <span class="badge bg-success-subtle text-success border rounded-pill">Present</span>
                    @elseif($att->status === 'late')
                        <span class="badge bg-warning-subtle text-warning border rounded-pill">Late</span>
                    @elseif($att->status === 'half_day')
                        <span class="badge bg-info-subtle text-info border rounded-pill">Half Day</span>
                    @elseif($att->status === 'absent')
                        <span class="badge bg-danger-subtle text-danger border rounded-pill">Absent</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border rounded-pill">On Leave</span>
                    @endif
                </td>
                <td><span class="small text-muted">{{ $att->remarks ?? '-' }}</span></td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No attendance logs found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="px-3 py-3 border-top bg-white d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <div class="small text-muted">
            Showing <span class="fw-semibold text-dark">{{ $attendances->firstItem() ?? 0 }}</span> to <span class="fw-semibold text-dark">{{ $attendances->lastItem() ?? 0 }}</span> of <span class="fw-semibold text-dark">{{ $attendances->total() }}</span> attendance entries
        </div>
        <div>
            {{ $attendances->links() }}
        </div>
    </div>
</x-ui.card>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.hrm.attendance.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Mark Staff Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold">Select Employee</label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">Select Staff...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_code }}) - {{ $emp->branch->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id ?? 1 }}">
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id ?? 1 }}">
                <div class="col-6">
                    <label class="form-label small fw-bold">Attendance Date</label>
                    <input type="date" name="attendance_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="half_day">Half Day</option>
                        <option value="absent">Absent</option>
                        <option value="on_leave">On Leave</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Clock In</label>
                    <input type="time" name="clock_in" value="09:30" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Clock Out</label>
                    <input type="time" name="clock_out" value="18:00" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Attendance</button>
            </div>
        </form>
    </div>
</div>
@endsection
