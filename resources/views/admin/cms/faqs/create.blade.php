@extends('layouts.admin')

@section('title', 'Add FAQ Item - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Add FAQ Item</h4>
        <p class="text-muted small mb-0">Create a new Q&A pair for the public website help center accordion.</p>
    </div>
    <a href="{{ route('admin.cms.faq.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to FAQs
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-4">
        <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix validation errors:</h6>
        <ul class="mb-0 small ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<x-ui.card class="p-4 shadow-sm">
    <form action="{{ route('admin.cms.faq.store') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Question *</label>
                <input type="text" name="question" value="{{ old('question') }}" class="form-control bg-light" placeholder="e.g. What documents are required to apply for a Micro-Enterprise Loan?" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Sort Order *</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Answer / Guidance *</label>
                <textarea name="answer" rows="5" class="form-control bg-light" placeholder="Detailed explanation and guidance for borrowers..." required>{{ old('answer') }}</textarea>
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Save FAQ Item
                </button>
                <a href="{{ route('admin.cms.faq.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
