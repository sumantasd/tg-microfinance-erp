@extends('layouts.admin')

@section('title', 'View Contact Inquiry - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Contact Inquiry Details</h4>
        <p class="text-muted small mb-0">Submitted on {{ $inquiry->created_at->format('F d, Y \a\t h:i A') }}</p>
    </div>
    <a href="{{ route('admin.cms.contact.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Messages Inbox
    </a>
</div>

<x-ui.card class="p-4 p-md-5 shadow-sm">
    <div class="row g-4">
        <div class="col-md-6">
            <label class="small text-uppercase fw-bold text-muted d-block mb-1">Sender Name</label>
            <h5 class="fw-bold text-dark mb-0">{{ $inquiry->name }}</h5>
        </div>

        <div class="col-md-6">
            <label class="small text-uppercase fw-bold text-muted d-block mb-1">Email Address</label>
            <p class="mb-0 fw-semibold text-primary"><a href="mailto:{{ $inquiry->email }}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>{{ $inquiry->email }}</a></p>
        </div>

        <div class="col-md-6">
            <label class="small text-uppercase fw-bold text-muted d-block mb-1">Phone Number</label>
            <p class="mb-0 text-dark font-monospace fw-semibold"><i class="bi bi-telephone me-1 text-muted"></i>{{ $inquiry->phone ?? 'N/A' }}</p>
        </div>

        <div class="col-md-6">
            <label class="small text-uppercase fw-bold text-muted d-block mb-1">Subject</label>
            <p class="mb-0 text-dark fw-bold">{{ $inquiry->subject ?? 'General Support Inquiry' }}</p>
        </div>

        <div class="col-12 pt-3 border-top">
            <label class="small text-uppercase fw-bold text-muted d-block mb-2">Message Body</label>
            <div class="p-3.5 bg-light rounded-3 border text-dark lh-lg" style="white-space: pre-wrap;">{{ $inquiry->message }}</div>
        </div>

        <div class="col-12 pt-3 border-top d-flex justify-content-between align-items-center">
            <a href="mailto:{{ $inquiry->email }}?subject={{ urlencode('RE: ' . ($inquiry->subject ?? 'Your Microfinance Inquiry')) }}" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-reply-fill me-1"></i> Reply via Email
            </a>
            <form action="{{ route('admin.cms.contact.destroy', $inquiry->id) }}" method="POST" onsubmit="return confirm('Delete this contact inquiry permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger rounded-pill px-3 py-1.5 small fw-bold">
                    <i class="bi bi-trash me-1"></i> Delete Inquiry
                </button>
            </form>
        </div>
    </div>
</x-ui.card>
@endsection
