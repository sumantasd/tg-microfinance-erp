@extends('layouts.admin')

@section('title', 'Notification Manager - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-send-check me-2 text-primary"></i>Notification Manager</h4>
        <p class="text-muted small mb-0">Broadcast alerts, announcements, and operational notices to branches and staff roles.</p>
    </div>
    <div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back to My Notifications
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Send Notification Form -->
    <div class="col-lg-5">
        <x-ui.card class="border-0 shadow-sm p-4 bg-white">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-megaphone me-2 text-primary"></i>Broadcast New Notification</h5>
            <form method="POST" action="{{ route('admin.notifications.send') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Notification Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Day Close Reminder / Circular">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Notification Message <span class="text-danger">*</span></label>
                    <textarea name="message" rows="4" class="form-control form-control-sm" required placeholder="Enter broadcast details..."></textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Alert Level <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="info">Info / General</option>
                            <option value="warning">Warning / Warning</option>
                            <option value="urgent">Urgent / Action Required</option>
                            <option value="announcement">Announcement</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Target Role (Optional)</label>
                        <select name="target_role" class="form-select form-select-sm">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Target Branch (Optional)</label>
                    <select name="target_branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Action Link URL (Optional)</label>
                    <input type="url" name="action_url" class="form-control form-control-sm" placeholder="https://...">
                </div>

                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-sm">
                    <i class="bi bi-send-fill me-1.5"></i> Send Broadcast Notification
                </button>
            </form>
        </x-ui.card>
    </div>

    <!-- Notification History Table -->
    <div class="col-lg-7">
        <x-ui.card class="border-0 shadow-sm p-0 overflow-hidden bg-white">
            <div class="p-3 border-bottom bg-light">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Sent Broadcast History</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Title & Message</th>
                            <th>Target</th>
                            <th>Recipients</th>
                            <th>Sent Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($systemNotifications as $sysNotif)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $sysNotif->title }}</div>
                                    <div class="text-muted text-truncate small" style="max-width: 250px;">{{ $sysNotif->message }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill small">{{ $sysNotif->target_role ?: 'All Roles' }}</span>
                                    <div class="small text-muted">{{ $sysNotif->branch ? $sysNotif->branch->name : 'All Branches' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">{{ $sysNotif->recipients->count() }} users</span>
                                </td>
                                <td class="font-monospace small text-muted">
                                    {{ $sysNotif->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No notifications sent yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($systemNotifications->hasPages())
                <div class="p-3 border-top">
                    {{ $systemNotifications->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
