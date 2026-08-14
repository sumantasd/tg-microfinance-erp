@extends('layouts.admin')

@section('title', 'Group Profile - ' . $group->name . ' - Grihalaxmi Finance ERP')

@section('content')
<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold text-dark mb-0">{{ $group->name }}</h4>
            <span class="badge bg-light text-secondary border font-monospace fs-6">{{ $group->group_code }}</span>
            @if($group->status === 'active')
                <span class="badge bg-success text-white px-2.5 py-1">Active Group</span>
            @elseif($group->status === 'inactive')
                <span class="badge bg-warning text-dark px-2.5 py-1">Inactive</span>
            @else
                <span class="badge bg-secondary text-white px-2.5 py-1">Closed</span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            <i class="bi bi-building text-warning me-1"></i>{{ $group->branch->name ?? 'N/A' }} | {{ $group->company->name ?? 'N/A' }}
        </p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('admin.customer-group.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
        @can('group.edit')
            <a href="{{ route('admin.customer-group.edit', $group->id) }}" class="btn btn-primary rounded-pill px-3 fw-bold">
                <i class="bi bi-pencil me-1"></i> Edit Group
            </a>
        @endcan
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Summary Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-info-subtle">
            <div class="small text-muted fw-bold uppercase">Total Members</div>
            <div class="fs-3 fw-bold text-info mt-1"><i class="bi bi-people me-1"></i>{{ $group->active_members_count }}</div>
            <div class="small text-muted">Active in Group</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-warning-subtle">
            <div class="small text-muted fw-bold uppercase">Group Leader</div>
            <div class="fw-bold text-dark fs-6 mt-1 text-truncate">
                @if($group->leader)
                    <i class="bi bi-award text-warning me-1"></i>{{ $group->leader->full_name }}
                @else
                    <span class="text-muted italic">Not Assigned</span>
                @endif
            </div>
            <div class="small text-muted font-monospace">{{ $group->leader->customer_code ?? 'None' }}</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Meeting Schedule</div>
            <div class="fw-bold text-dark fs-6 mt-1">
                <i class="bi bi-calendar-event text-info me-1"></i>{{ $group->meeting_day ?? 'N/A' }} {{ $group->meeting_time ? 'at ' . $group->meeting_time : '' }}
            </div>
            <div class="small text-muted">Center Meeting Time</div>
        </x-ui.card>
    </div>

    <div class="col-md-3">
        <x-ui.card class="p-3 shadow-sm border-0 bg-light">
            <div class="small text-muted fw-bold uppercase">Formation Date</div>
            <div class="fw-bold text-dark fs-6 mt-1">
                <i class="bi bi-clock-history me-1"></i>{{ $group->formation_date ? $group->formation_date->format('d M Y') : 'N/A' }}
            </div>
            <div class="small text-muted">Group Registration</div>
        </x-ui.card>
    </div>
</div>

<!-- Members Management Section -->
<x-ui.card class="shadow-sm border-0 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-info me-2"></i>Group Members</h5>
            <p class="text-muted small mb-0">Active customer master profiles assigned to this group.</p>
        </div>
        @can('group.manage_members')
            <button class="btn btn-info text-white rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="bi bi-person-plus me-1"></i> Add Member to Group
            </button>
        @endcan
    </div>

    <x-ui.data-table>
        <x-slot:headers>
            <th scope="col" class="py-3 px-3">Member Details</th>
            <th scope="col" class="py-3 px-3">Role</th>
            <th scope="col" class="py-3 px-3">Mobile & Address</th>
            <th scope="col" class="py-3 px-3">Joined Date</th>
            <th scope="col" class="py-3 px-3">Status</th>
            <th scope="col" class="py-3 px-3 text-end">Actions</th>
        </x-slot:headers>

        @forelse($group->groupMembers as $m)
            <tr>
                <td class="px-3 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-light rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-person text-secondary"></i>
                        </div>
                        <div>
                            <a href="{{ route('admin.customer.show', $m->customer->id) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                {{ $m->customer->full_name }}
                            </a>
                            <div class="small text-muted font-monospace">{{ $m->customer->customer_code }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-3 py-3">
                    @if($m->role === 'group_leader' || $group->leader_customer_id === $m->customer_id)
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold"><i class="bi bi-award me-1"></i>Group Leader</span>
                    @else
                        <span class="badge bg-light text-secondary border">Member</span>
                    @endif
                </td>
                <td class="px-3 py-3 small">
                    <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $m->customer->mobile_number }}</div>
                    <div class="text-muted text-truncate" style="max-width: 200px;">{{ $m->customer->presentAddress->address_line ?? 'N/A' }}</div>
                </td>
                <td class="px-3 py-3 small">
                    {{ $m->joined_at ? $m->joined_at->format('d M Y') : 'N/A' }}
                </td>
                <td class="px-3 py-3">
                    @if($m->status === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">Active</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-0.5">{{ ucfirst($m->status) }}</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{{ route('admin.customer.show', $m->customer->id) }}" class="btn btn-sm btn-outline-info" title="View Profile">
                            <i class="bi bi-eye"></i> Profile
                        </a>
                        @can('group.manage_members')
                            @if($group->leader_customer_id !== $m->customer_id && $m->status === 'active')
                                <form action="{{ route('admin.customer-group.assign-leader', $group->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="leader_customer_id" value="{{ $m->customer_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Make Group Leader">
                                        <i class="bi bi-award"></i> Leader
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.customer-group.member.destroy', [$group->id, $m->customer_id]) }}" method="POST" onsubmit="return confirm('Remove customer {{ $m->customer->full_name }} from this group?');" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from Group"><i class="bi bi-trash"></i></button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    No members added to this group yet. Click "Add Member to Group" to attach existing customers.
                </td>
            </tr>
        @endforelse
    </x-ui.data-table>
</x-ui.card>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.customer-group.member.store', $group->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Existing Customer to Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">-- Choose Branch Customer --</option>
                            @foreach($availableCustomers as $ac)
                                <option value="{{ $ac->id }}">{{ $ac->full_name }} ({{ $ac->customer_code }}) - Mobile: {{ $ac->mobile_number }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Only active customers registered in {{ $group->branch->name ?? 'this branch' }} who are not already in this group are listed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Member Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="member">Regular Member</option>
                            <option value="group_leader">Group Leader</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
