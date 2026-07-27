@extends('layouts.admin')

@section('title', 'Why Choose Us CMS - TG Microfinance ERP')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-patch-check text-primary me-2"></i>Why Choose Us CMS</h4>
        <p class="text-muted small mb-0">Manage institutional strengths cards displayed on the public homepage.</p>
    </div>
    <a href="{{ route('admin.cms.why-choose-us.create') }}" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5">
        <i class="bi bi-plus-circle fs-6"></i> Add Strength Card
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
    <form action="{{ route('admin.cms.why-choose-us.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-7">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control bg-light border-start-0" placeholder="Search by title or description...">
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
            <a href="{{ route('admin.cms.why-choose-us.index') }}" class="btn btn-light border w-100 rounded-3" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-ui.card>

<!-- Data Table Card -->
<x-ui.card class="p-0 shadow-sm overflow-hidden">
    <x-ui.data-table :headers="['Icon', 'Title', 'Description Excerpt', 'Sort Order', 'Status', 'Actions']">
        @forelse($items as $item)
            <tr>
                <td>
                    <div class="bg-{{ $item->badge_color ?? 'primary' }}-subtle text-{{ $item->badge_color ?? 'primary' }} rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi {{ $item->icon ?? 'bi-shield-check' }} fs-5"></i>
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-dark mb-0.5">{{ $item->title }}</div>
                </td>
                <td>
                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $item->description }}</small>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">{{ $item->sort_order }}</span>
                </td>
                <td>
                    <form action="{{ route('admin.cms.why-choose-us.toggle-status', $item->id) }}" method="POST" class="d-inline">
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
                        <a href="{{ route('admin.cms.why-choose-us.edit', $item->id) }}" class="btn btn-light btn-sm border" title="Edit Card"><i class="bi bi-pencil text-primary"></i></a>
                        <form action="{{ route('admin.cms.why-choose-us.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this strength card permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light btn-sm border text-danger" title="Delete Card"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">No Why Choose Us cards found.</td>
            </tr>
        @endforelse
    </x-ui.data-table>

    <div class="p-3 border-top">
        {{ $items->links() }}
    </div>
</x-ui.card>
@endsection
