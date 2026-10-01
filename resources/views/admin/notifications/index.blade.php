@extends('layouts.admin')

@section('title', 'My Notifications - Grihalaxmi Finance')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark font-heading mb-1"><i class="bi bi-bell-fill me-2 text-primary"></i>Notification Center</h4>
        <p class="text-muted small mb-0">View system alerts, announcements, and operational notifications.</p>
    </div>
    <div class="d-flex gap-2">
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold">
                <i class="bi bi-check-all me-1"></i> Mark All as Read ({{ $unreadCount }})
            </button>
        </form>
        @endif
        @can('notifications.manage')
        <a href="{{ route('admin.notifications.manage') }}" class="btn btn-primary btn-sm rounded-pill fw-bold shadow-sm">
            <i class="bi bi-send-fill me-1"></i> Notification Manager
        </a>
        @endcan
    </div>
</div>

<x-ui.card class="border-0 shadow-sm p-0 bg-white">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.notifications.index', ['filter' => 'all']) }}" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-start-pill">All Notifications</a>
            <a href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }} rounded-end-pill">Unread Only</a>
        </div>
    </div>

    <div class="list-group list-group-flush">
        @forelse($userNotifications as $item)
            @php $n = $item->notification; @endphp
            <div class="list-group-item p-3 {{ !$item->is_read ? 'bg-primary bg-opacity-10' : '' }} d-flex justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle p-2 bg-{{ $n->type === 'urgent' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'primary') }}-subtle text-{{ $n->type === 'urgent' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'primary') }} d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                        <i class="bi {{ $n->type === 'urgent' ? 'bi-exclamation-diamond-fill' : ($n->type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') }} fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="fw-bold text-dark mb-0">{{ $n->title }}</h6>
                            <span class="badge bg-{{ $n->type === 'urgent' ? 'danger' : ($n->type === 'warning' ? 'warning' : 'info') }} rounded-pill small">{{ strtoupper($n->type) }}</span>
                            @if(!$item->is_read)
                                <span class="badge bg-primary rounded-pill small">NEW</span>
                            @endif
                        </div>
                        <p class="text-secondary small mb-1">{{ $n->message }}</p>
                        <div class="text-muted font-monospace" style="font-size: 0.75rem;">
                            <i class="bi bi-clock me-1"></i>{{ $item->created_at->diffForHumans() }}
                            @if($n->sender)
                                • Sent by: {{ $n->sender->name }}
                            @endif
                            @if($n->branch)
                                • Branch: {{ $n->branch->name }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($n->action_url)
                        <a href="{{ $n->action_url }}" class="btn btn-xs btn-outline-primary rounded-pill px-2.5">View</a>
                    @endif
                    @if(!$item->is_read)
                        <form method="POST" action="{{ route('admin.notifications.mark-read', $item->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-light border rounded-pill px-2.5" title="Mark as Read"><i class="bi bi-check2"></i></button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.notifications.destroy', $item->id) }}" onsubmit="return confirm('Delete notification?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-xs btn-outline-danger border-0 p-0" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-5 text-center text-muted">
                <i class="bi bi-bell-slash fs-1 text-muted d-block mb-2"></i>
                <div class="fw-bold">No notifications found</div>
                <small>You are all caught up!</small>
            </div>
        @endforelse
    </div>

    @if($userNotifications->hasPages())
        <div class="p-3 border-top">
            {{ $userNotifications->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
