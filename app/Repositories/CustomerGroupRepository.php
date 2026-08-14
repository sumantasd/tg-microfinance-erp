<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\CustomerGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerGroupRepository implements CustomerGroupRepositoryInterface
{
    public function getPaginatedGroups(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CustomerGroup::with(['company', 'branch', 'leader', 'activeMembers'])
            ->withCount('activeMembers');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('group_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('meeting_location', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getAllActiveGroups(array $filters = []): Collection
    {
        $query = CustomerGroup::where('status', 'active');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    public function findById(int $id): ?CustomerGroup
    {
        return CustomerGroup::with(['company', 'branch', 'leader', 'activeMembers.customer.presentAddress', 'groupMembers.customer'])
            ->withCount('activeMembers')
            ->find($id);
    }

    public function createGroup(array $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['group_code']) && !empty($data['branch_id'])) {
                $data['group_code'] = $this->generateGroupCode($data['branch_id']);
            }

            return CustomerGroup::create($data);
        });
    }

    public function updateGroup(CustomerGroup $group, array $data): CustomerGroup
    {
        $group->update($data);
        return $group->fresh(['company', 'branch', 'leader', 'activeMembers']);
    }

    public function deleteGroup(CustomerGroup $group): bool
    {
        return DB::transaction(function () use ($group) {
            $group->groupMembers()->delete();
            return $group->delete();
        });
    }

    public function generateGroupCode(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code));
            $year = date('Y');
            $prefix = "GRP-{$branchCode}-{$year}-";

            $lastGroup = CustomerGroup::withTrashed()
                ->where('group_code', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextSequence = 1;
            if ($lastGroup && preg_match('/-(\d{4})$/', $lastGroup->group_code, $matches)) {
                $nextSequence = ((int) $matches[1]) + 1;
            }

            $code = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

            while (CustomerGroup::where('group_code', $code)->withTrashed()->exists()) {
                $nextSequence++;
                $code = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            }

            return $code;
        });
    }
}
