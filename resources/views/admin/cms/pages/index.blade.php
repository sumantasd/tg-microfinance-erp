@extends('layouts.admin')

@section('title', 'Pages CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-text text-primary me-2"></i>Pages Management</h4>
        <p class="text-muted small mb-0">Create and publish custom CMS web pages with rich content and images.</p>
    </div>
    <a href="{{ route('admin.cms.pages.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Create New Page
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
    <form action="{{ route('admin.cms.pages.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search pages by title or slug...">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select bg-light">
                <option value="">All Statuses</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100 rounded-3"><i class="bi bi-filter me-1"></i>Filter</button>
            <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Title & URL Slug', 'Featured Image', 'Status', 'Last Updated', 'Actions']">
        @forelse($pages as $page)
            <tr>
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $page->title }}</div>
                    <small class="text-muted font-monospace"><i class="bi bi-link me-1"></i>/{{ $page->slug }}</small>
                </td>
                <td>
                    @if($page->image)
                        <img src="{{ asset('storage/' . $page->image) }}" alt="Page Image" class="rounded border shadow-sm" style="width: 60px; height: 38px; object-fit: cover;">
                    @else
                        <span class="badge bg-light text-muted border">No Image</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('admin.cms.pages.toggle-status', $page->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        @if($page->status === 'published')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Unpublish (Draft)">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle me-1"></i>Published</span>
                            </button>
                        @elseif($page->status === 'draft')
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Publish">
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><i class="bi bi-file-earmark-medical me-1"></i>Draft</span>
                            </button>
                        @else
                            <button type="submit" class="btn btn-link p-0 text-decoration-none" title="Click to Publish">
                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill"><i class="bi bi-dash-circle me-1"></i>Inactive</span>
                            </button>
                        @endif
                    </form>
                </td>
                <td>
                    <small class="text-muted d-block">{{ $page->updated_at ? $page->updated_at->diffForHumans() : 'N/A' }}</small>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.cms.pages.edit', $page->id) }}" class="btn btn-light btn-sm border" title="Edit Page"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.pages.destroy', $page->id) }}" method="POST" onsubmit="return confirm('Delete page permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Page"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">No CMS pages found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $pages->links() }}
    </div>
</x-ui.card>
@endsection
