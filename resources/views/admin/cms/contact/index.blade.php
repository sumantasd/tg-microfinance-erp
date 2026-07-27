@extends('layouts.admin')

@section('title', 'Contact Inquiries CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-envelope-paper text-primary me-2"></i>Contact Messages & Inquiries Inbox</h4>
        <p class="text-muted small mb-0">View customer inquiry submissions, loan requests, and support messages submitted from the public portal.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.cms.contact.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search inquiries by name, email, phone or message...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.cms.contact.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Sender Details', 'Subject / Message Excerpt', 'Received Date', 'Status', 'Actions']">
        @forelse($inquiries as $item)
            <tr class="{{ $item->status === 'unread' ? 'fw-bold bg-light-subtle' : '' }}">
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $item->name }}</div>
                    <small class="text-muted d-block"><i class="bi bi-envelope me-1"></i>{{ $item->email }}</small>
                    @if($item->phone)
                        <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i>{{ $item->phone }}</small>
                    @endif
                </td>
                <td>
                    <div class="fw-semibold text-dark mb-1">{{ $item->subject ?? 'General Inquiry' }}</div>
                    <small class="text-muted d-block text-truncate" style="max-width: 380px;">{{ $item->message }}</small>
                </td>
                <td>
                    <small class="text-secondary fw-medium"><i class="bi bi-clock me-1"></i>{{ $item->created_at->format('M d, Y H:i') }}</small>
                </td>
                <td>
                    <form action="{{ route('admin.cms.contact.toggle-status', $item->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($item->status === 'unread')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Mark as Read">
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><i class="bi bi-envelope-fill me-1"></i>Unread</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Mark as Unread">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-envelope-open me-1"></i>Read</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.contact.show', $item->id) }}" class="btn btn-light btn-sm border" title="View Full Inquiry"><i class="bi bi-eye text-primary"></i></a>
                        <form action="{{ route('admin.cms.contact.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this contact inquiry permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Inquiry"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">No contact inquiries found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $inquiries->links() }}
    </div>
</x-ui.card>
@endsection
