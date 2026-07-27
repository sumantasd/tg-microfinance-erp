@extends('layouts.admin')

@section('title', 'SEO CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-search text-primary me-2"></i>SEO & Meta Tags CMS</h4>
        <p class="text-muted small mb-0">Manage page titles, meta descriptions, search keywords, and OpenGraph social preview images.</p>
    </div>
    <a href="{{ route('admin.cms.seo.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Page SEO Setting
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
    <form action="{{ route('admin.cms.seo.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search page name, meta title or keywords...">
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
            <a href="{{ route('admin.cms.seo.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Page Identifier', 'Meta Title', 'Meta Description Excerpt', 'OG Image', 'Status', 'Actions']">
        @forelse($seoList as $item)
            <tr>
                <td>
                    <span class="badge bg-primary-subtle text-primary font-monospace fw-bold fs-6">{{ $item->page_name }}</span>
                </td>
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $item->meta_title ?? 'Default Title' }}</div>
                </td>
                <td>
                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $item->meta_description ?? 'No Description' }}</small>
                </td>
                <td>
                    @if($item->og_image_url)
                        <img src="{{ $item->og_image_url }}" alt="{{ $item->page_name }}" class="rounded border shadow-sm" style="width: 60px; height: 38px; object-fit: cover;">
                    @else
                        <span class="badge bg-light text-muted border">No Image</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('admin.cms.seo.toggle-status', $item->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($item->status === 'active')
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
                        <a href="{{ route('admin.cms.seo.edit', $item->id) }}" class="btn btn-light btn-sm border" title="Edit Page SEO"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.seo.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this SEO setting permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Setting"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">No SEO page settings found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $seoList->links() }}
    </div>
</x-ui.card>
@endsection
