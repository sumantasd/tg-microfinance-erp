@extends('layouts.admin')

@section('title', 'Customer Groups Directory - Grihalaxmi Finance ERP')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-people-fill text-info me-2"></i>Customer & Member Groups (JLG / SHG)
        </h4>
        <p class="text-muted small mb-0">Manage joint liability groups, group leaders, meeting schedules, and member assignments.</p>
    </div>
    @can('group.create')
        <div class="mt-3 mt-md-0">
            <a href="{{ route('admin.customer-group.create') }}" class="btn btn-info text-white fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-plus-circle me-1"></i> Create New Group
            </a>
        </div>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filter Card -->
<x-ui.card class="mb-4 shadow-sm border-0">
    <form method="GET" action="{{ route('admin.customer-group.index') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Search Group</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Group Name, Code, Location..." value="{{ $filters['search'] ?? '' }}">
            </div>
        </div>

        @if(auth()->user()->hasRole('Super Admin'))
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">Company</label>
                <select name="company_id" class="form-select">
                    <option value="">All Companies</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ ($filters['company_id'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Branch</label>
            <select name="branch_id" class="form-select">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.customer-group.index') }}" class="btn btn-light border text-secondary fw-bold px-3">Reset</a>
            <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-filter me-1"></i> Apply Filters</button>
        </div>
    </form>
</x-ui.card>

<!-- Groups Directory Table -->
<x-ui.card class="shadow-sm border-0 p-0">
    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Group Details</th>
            <th scope="col" class="py-3 px-3">Branch</th>
            <th scope="col" class="py-3 px-3">Leader</th>
            <th scope="col" class="py-3 px-3">Meeting Info</th>
            <th scope="col" class="py-3 px-3">Members</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($groups as $group)
            <tr>
                <td class="px-3 py-3">
                    <div class="fw-bold text-dark fs-6">{{ $group->name }}</div>
                    <span class="badge bg-light text-secondary border font-monospace">{{ $group->group_code }}</span>
                    <div class="small text-muted mt-1">Formed: {{ $group->formation_date ? $group->formation_date->format('d M Y') : 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    <div class="fw-bold text-dark">{{ $group->branch->name ?? 'N/A' }}</div>
                    <div class="text-muted">{{ $group->company->name ?? 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    @if($group->leader)
                        <div class="fw-bold text-dark"><i class="bi bi-person-badge-fill text-warning me-1"></i>{{ $group->leader->full_name }}</div>
                        <div class="text-muted font-monospace">{{ $group->leader->customer_code }}</div>
                    @else
                        <span class="text-muted italic">Not Assigned</span>
                    @endif
                </td>
                <td class="px-3 py-3 small">
                    @if($group->meeting_day || $group->meeting_time)
                        <div><i class="bi bi-calendar-event text-info me-1"></i> {{ $group->meeting_day ?? '' }} {{ $group->meeting_time ?? '' }}</div>
                    @endif
                    @if($group->meeting_location)
                        <div class="text-muted text-truncate" style="max-width: 180px;"><i class="bi bi-geo-alt me-1"></i>{{ $group->meeting_location }}</div>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <span class="badge bg-info-subtle text-info border border-info-subtle fs-6 px-3 py-1">
                        <i class="bi bi-people me-1"></i> {{ $group->active_members_count }} Members
                    </span>
                </td>
                <td class="px-3 py-3">
                    @if($group->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Active</span>
                    @elseif($group->status === 'inactive')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1">Inactive</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1">Closed</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.customer-group.show', $group->id) }}" class="btn btn-sm btn-outline-info" title="View Group Profile">
                            <i class="bi bi-eye"></i> View
                        </a>
                        @can('group.edit')
                            <a href="{{ route('admin.customer-group.edit', $group->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Group">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-people fs-1 d-block text-secondary mb-2"></i>
                    No customer groups found matching your criteria.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>

    @if($groups->hasPages())
        <div class="p-3 border-top">
            {{ $groups->links() }}
        </div>
    @endif
</x-ui.card>
@endsection
