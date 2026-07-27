@extends('layouts.admin')

@section('title', 'Edit Team Member - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Edit Leadership Team Member</h4>
        <p class="text-muted small mb-0">Update executive profile, photo, status, or social links.</p>
    </div>
    <a href="{{ route('admin.cms.team.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1.5 small fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Team Members
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
    <form action="{{ route('admin.cms.team.update', $member->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $member->name) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Designation / Title *</label>
                <input type="text" name="designation" value="{{ old('designation', $member->designation) }}" class="form-control bg-light" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Leadership Category *</label>
                <select name="type" class="form-select bg-light" required>
                    <option value="board" {{ old('type', $member->type) === 'board' ? 'selected' : '' }}>Board of Directors</option>
                    <option value="management" {{ old('type', $member->type) === 'management' ? 'selected' : '' }}>Management Team</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Status *</label>
                <select name="status" class="form-select bg-light" required>
                    <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary">Display Order</label>
                <input type="number" name="display_order" value="{{ old('display_order', $member->display_order) }}" class="form-control bg-light" min="0">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $member->email) }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">Profile Photo Upload</label>
                <input type="file" name="photo" class="form-control bg-light">
                @if($member->photo_url)
                    <div class="mt-2 p-2 bg-light border rounded d-inline-flex align-items-center gap-3">
                        <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                        <span class="small text-muted">Current Photo Thumbnail</span>
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label small fw-bold text-secondary">Professional Bio / Summary</label>
                <textarea name="bio" rows="3" class="form-control bg-light">{{ old('bio', $member->bio) }}</textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-linkedin me-1 text-primary"></i> LinkedIn Profile URL</label>
                <input type="url" name="social_links[linkedin]" value="{{ old('social_links.linkedin', $member->social_links['linkedin'] ?? '') }}" class="form-control bg-light">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary"><i class="bi bi-twitter-x me-1 text-dark"></i> Twitter/X Handle URL</label>
                <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $member->social_links['twitter'] ?? '') }}" class="form-control bg-light">
            </div>

            <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check-circle me-1"></i> Update Team Member
                </button>
                <a href="{{ route('admin.cms.team.index') }}" class="btn btn-light border rounded-pill px-4 py-2 fw-semibold">Cancel</a>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
