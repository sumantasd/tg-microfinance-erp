@extends('layouts.admin')

@section('title', 'Create Customer Group - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-people-fill text-info me-2"></i>Create Customer Group (JLG / SHG)
        </h4>
        <p class="text-muted small mb-0">Form a new joint liability group for microfinance members.</p>
    </div>
    <div>
        <a href="{{ route('admin.customer-group.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>
</div>

<x-ui.card class="shadow-sm border-0 p-4">
    <form action="{{ route('admin.customer-group.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Basic Information</h5>

            @if(auth()->user()->hasRole('Super Admin'))
                <div class="col-md-6">
                    <label class="form-label fw-bold">Company <span class="text-danger">*</span></label>
                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ auth()->user()->company_id }}">
            @endif

            <div class="col-md-6">
                <label class="form-label fw-bold">Branch <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Group Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rampur JLG 1" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Formation Date <span class="text-danger">*</span></label>
                <input type="date" name="formation_date" class="form-control @error('formation_date') is-invalid @enderror" value="{{ old('formation_date', date('Y-m-d')) }}" required>
                @error('formation_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Group Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <h5 class="fw-bold text-dark border-bottom pb-2 mt-4 mb-3">2. Meeting Schedule & Location</h5>

            <div class="col-md-4">
                <label class="form-label fw-bold">Meeting Day</label>
                <select name="meeting_day" class="form-select @error('meeting_day') is-invalid @enderror">
                    <option value="">Select Day</option>
                    <option value="Monday" {{ old('meeting_day') === 'Monday' ? 'selected' : '' }}>Monday</option>
                    <option value="Tuesday" {{ old('meeting_day') === 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                    <option value="Wednesday" {{ old('meeting_day') === 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                    <option value="Thursday" {{ old('meeting_day') === 'Thursday' ? 'selected' : '' }}>Thursday</option>
                    <option value="Friday" {{ old('meeting_day') === 'Friday' ? 'selected' : '' }}>Friday</option>
                    <option value="Saturday" {{ old('meeting_day') === 'Saturday' ? 'selected' : '' }}>Saturday</option>
                    <option value="Sunday" {{ old('meeting_day') === 'Sunday' ? 'selected' : '' }}>Sunday</option>
                </select>
                @error('meeting_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Meeting Time</label>
                <input type="text" name="meeting_time" class="form-control @error('meeting_time') is-invalid @enderror" value="{{ old('meeting_time') }}" placeholder="e.g. 10:00 AM">
                @error('meeting_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">Meeting Location</label>
                <input type="text" name="meeting_location" class="form-control @error('meeting_location') is-invalid @enderror" value="{{ old('meeting_location') }}" placeholder="e.g. Rampur Village Center">
                @error('meeting_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-3">
                <label class="form-label fw-bold">Remarks & Operational Notes</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="3" placeholder="Optional notes regarding group formation or center rules...">{{ old('remarks') }}</textarea>
                @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="{{ route('admin.customer-group.index') }}" class="btn btn-light border text-secondary fw-bold px-4">Cancel</a>
                <button type="submit" class="btn btn-info text-white fw-bold px-4"><i class="bi bi-check-circle me-1"></i> Save & Form Group</button>
            </div>
        </div>
    </form>
</x-ui.card>
@endsection
