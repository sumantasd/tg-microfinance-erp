@extends('layouts.admin')

@section('title', 'Audit & Activity Logs - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-shield-check me-2 text-primary"></i>System Audit & Activity Logs</h4>
        <p class="text-muted small mb-0">Immutable tracking of system changes, financial operations, approvals, and user actions.</p>
    </div>
</div>

<x-ui.card class="p-3 shadow-sm border-0 mb-4 bg-light">
    <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Search User / Event / IP</label>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. login, loan_disbursed...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-secondary mb-1">Event Type</label>
            <select name="event" class="form-select form-select-sm">
                <option value="">All Events</option>
                @foreach($events as $ev)
                    <option value="{{ $ev }}" {{ ($filters['event'] ?? '') === $ev ? 'selected' : '' }}>{{ $ev }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (int)($filters['user_id'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-secondary mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-light border rounded-pill" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Timestamp</th>
                    <th>Actor</th>
                    <th>Event</th>
                    <th>Entity Type & ID</th>
                    <th>IP Address</th>
                    <th class="pe-3 text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activityLogs as $log)
                    <tr>
                        <td class="ps-3 font-monospace text-muted small">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td>
                            @if($log->user)
                                <div class="fw-bold text-dark">{{ $log->user->name }}</div>
                                <div class="text-muted small">{{ $log->user->email }}</div>
                            @else
                                <span class="text-muted fst-italic">System / Automated</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary fw-bold font-monospace px-2.5 py-1 rounded-pill">
                                {{ $log->event }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-secondary small">{{ class_basename($log->auditable_type) }}</div>
                            <div class="font-monospace text-muted small">#{{ $log->auditable_id }}</div>
                        </td>
                        <td class="font-monospace text-muted small">
                            {{ $log->ip_address ?: 'Internal' }}
                        </td>
                        <td class="pe-3 text-end">
                            @if(!empty($log->old_values) || !empty($log->new_values))
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5" data-bs-toggle="modal" data-bs-target="#logDetailModal{{ $log->id }}">
                                    <i class="bi bi-eye-fill me-1"></i> Diff Payload
                                </button>

                                <!-- Diff Modal -->
                                <div class="modal fade text-start" id="logDetailModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-dark text-white">
                                                <h5 class="modal-title font-monospace small"><i class="bi bi-file-diff me-2"></i>Audit Record #{{ $log->id }} Details</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 font-monospace small">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold text-danger"><i class="bi bi-dash-circle me-1"></i>Old Values</h6>
                                                        <pre class="bg-light p-3 rounded border text-wrap text-break" style="max-height: 300px; font-size: 0.78rem;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'None' }}</pre>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="fw-bold text-success"><i class="bi bi-plus-circle me-1"></i>New Values</h6>
                                                        <pre class="bg-light p-3 rounded border text-wrap text-break" style="max-height: 300px; font-size: 0.78rem;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'None' }}</pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">No payload</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-x fs-2 text-muted d-block mb-2"></i>
                            <div class="fw-bold">No activity logs recorded.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($activityLogs->hasPages())
        <div class="p-3 border-top">
            {{ $activityLogs->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
