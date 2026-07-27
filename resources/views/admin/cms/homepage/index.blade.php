@extends('layouts.admin')

@section('title', 'Homepage Sections - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-house-gear text-primary me-2"></i>Homepage CMS Sections</h4>
        <p class="text-muted small mb-0">Manage customizable content sections on the public portal homepage.</p>
    </div>
    <a href="{{ route('admin.cms.homepage.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Homepage Section
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search & Filter Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.cms.homepage.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search sections by title or key...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <button type="button" class="btn btn-outline-secondary w-100 rounded-3" title="Export Sections Data"><i class="bi bi-download"></i></button>
            <a href="{{ route('admin.cms.homepage.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Key & Title', 'Subtitle / Description', 'Image', 'Button CTA', 'Sort Order', 'Status', 'Actions']">
        @forelse($sections as $section)
            <tr>
                <td>
                    <span class="badge bg-secondary-subtle text-secondary font-monospace border mb-1">{{ $section->section_key }}</span>
                    <div class="fw-bold text-dark">{{ $section->title ?? 'Untitled Section' }}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-secondary mb-0.5">{{ Str::limit($section->subtitle, 40) }}</div>
                    <small class="text-muted d-block opacity-75">{{ Str::limit($section->description, 60) }}</small>
                </td>
                <td>
                    @if($section->image)
                        <img src="{{ asset('storage/' . $section->image) }}" alt="Section Image" class="rounded border" style="width: 50px; height: 35px; object-fit: cover;">
                    @else
                        <span class="badge bg-light text-muted border">No Image</span>
                    @endif
                </td>
                <td>
                    @if($section->button_text)
                        <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-link-45deg me-1"></i>{{ $section->button_text }}</span>
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace fw-bold">{{ $section->sort_order }}</span>
                </td>
                <td>
                    <form action="{{ route('admin.cms.homepage.toggle-status', $section->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($section->status === 'active')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Deactivate">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Activate">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.homepage.edit', $section->id) }}" class="btn btn-light btn-sm border" title="Edit Section"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.homepage.destroy', $section->id) }}" method="POST" onsubmit="return confirm('Delete this section permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Section"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">No homepage sections found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $sections->links() }}
    </div>
</x-ui.card>
@endsection
