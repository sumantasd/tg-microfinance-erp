@extends('layouts.admin')

@section('title', 'Edit Why Choose Us Card - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Institutional Strength Card</h4>
        <p class="text-muted small mb-0">Update feature card details, icon, or status.</p>
    </div>
    <a href="{{ route('admin.cms.why-choose-us.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Cards
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
    <form action="{{ route('admin.cms.why-choose-us.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Card Title *</label>
                <input type="text" name="title" value="{{ old('title', $item->title) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Bootstrap Icon Class</label>
                <input type="text" name="icon" value="{{ old('icon', $item->icon) }}" class="form-control bg-light">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Badge Color</label>
                <select name="badge_color" class="form-select bg-light">
                    <option value="primary" {{ old('badge_color', $item->badge_color) === 'primary' ? 'selected' : '' }}>Primary (Blue)</option>
                    <option value="success" {{ old('badge_color', $item->badge_color) === 'success' ? 'selected' : '' }}>Success (Green)</option>
                    <option value="info" {{ old('badge_color', $item->badge_color) === 'info' ? 'selected' : '' }}>Info (Cyan)</option>
                    <option value="warning" {{ old('badge_color', $item->badge_color) === 'warning' ? 'selected' : '' }}>Warning (Yellow)</option>
                    <option value="danger" {{ old('badge_color', $item->badge_color) === 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Description *</label>
                <textarea name="description" rows="3" class="form-control bg-light" required>{{ old('description', $item->description) }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $item->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $item->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" class="form-control bg-light" min="0">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Card
                </button>
                <a href="{{ route('admin.cms.why-choose-us.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
