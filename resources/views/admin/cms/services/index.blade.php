@extends('layouts.admin')

@section('title', 'Services CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-gear text-primary me-2"></i>Services CMS</h4>
        <p class="text-muted small mb-0">Manage corporate services (Digital Banking, Field Collection, Financial Advisory) and SEO metadata.</p>
    </div>
    <a href="{{ route('admin.cms.services.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Service Page
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Search Card -->
<x-ui.card class="p-3 mb-4 shadow-sm">
    <form action="{{ route('admin.cms.services.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search service title or description...">
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
            <a href="{{ route('admin.cms.services.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Icon', 'Service Title & Slug', 'Banner Image', 'SEO Title', 'Status', 'Actions']">
        @forelse($services as $item)
            <tr>
                <td>
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi {{ $item->icon ?? 'bi-gear' }} fs-5"></i>
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $item->title }}</div>
                    <small class="text-muted font-monospace d-block">/services/{{ $item->slug }}</small>
                </td>
                <td>
                    @if($item->banner_image_url)
                        <img src="{{ $item->banner_image_url }}" alt="{{ $item->title }}" class="rounded border shadow-sm" style="width: 60px; height: 38px; object-fit: cover;">
                    @else
                        <span class="badge bg-light text-muted border">No Banner</span>
                    @endif
                </td>
                <td>
                    <small class="text-secondary d-block text-truncate" style="max-width: 220px;">{{ $item->meta_title ?? 'Default Meta Title' }}</small>
                </td>
                <td>
                    <form action="{{ route('admin.cms.services.toggle-status', $item->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($item->status === 'active')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Active</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.services.edit', $item->id) }}" class="btn btn-light btn-sm border" title="Edit Service"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.services.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this service page permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Service"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">No service pages found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $services->links() }}
    </div>
</x-ui.card>
@endsection
