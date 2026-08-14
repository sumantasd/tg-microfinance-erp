<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupMember;
use App\Repositories\CustomerGroupRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ValidationException;

class CustomerGroupService
{
    public function __construct(
        protected CustomerGroupRepositoryInterface $groupRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedGroups(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('Super Admin')) {
            if ($user->company_id) {
                $filters['company_id'] = $user->company_id;
            }
            if ($user->branch_id) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->groupRepository->getPaginatedGroups($filters, $perPage);
    }

    public function getAllActiveGroups(array $filters = []): Collection
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('Super Admin')) {
            if ($user->company_id) {
                $filters['company_id'] = $user->company_id;
            }
            if ($user->branch_id) {
                $filters['branch_id'] = $user->branch_id;
            }
        }

        return $this->groupRepository->getAllActiveGroups($filters);
    }

    public function getGroupById(int $id): ?CustomerGroup
    {
        return $this->groupRepository->findById($id);
    }

    public function createGroup(array $data): CustomerGroup
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            if ($user) {
                $data['created_by'] = $user->id;
                $data['updated_by'] = $user->id;

                if (!$user->hasRole('Super Admin')) {
                    $data['company_id'] = $user->company_id ?? $data['company_id'];
                    $data['branch_id'] = $user->branch_id ?? $data['branch_id'];
                }
            }

            $group = $this->groupRepository->createGroup($data);

            $this->activityLogService->log(
                'group_created',
                $group
            );

            return $group;
        });
    }

    public function updateGroup(CustomerGroup $group, array $data): CustomerGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $user = Auth::user();
            if ($user) {
                $data['updated_by'] = $user->id;
            }

            $oldStatus = $group->status;
            $updatedGroup = $this->groupRepository->updateGroup($group, $data);

            if (isset($data['status']) && $data['status'] !== $oldStatus) {
                $this->activityLogService->log(
                    'group_status_changed',
                    $updatedGroup,
                    ['status' => $oldStatus],
                    ['status' => $updatedGroup->status]
                );
            } else {
                $this->activityLogService->log(
                    'group_updated',
                    $updatedGroup
                );
            }

            return $updatedGroup;
        });
    }

    public function deleteGroup(CustomerGroup $group): bool
    {
        return DB::transaction(function () use ($group) {
            $groupName = $group->name;
            $deleted = $this->groupRepository->deleteGroup($group);

            if ($deleted) {
                $this->activityLogService->log(
                    'group_deleted',
                    $group
                );
            }

            return $deleted;
        });
    }

    public function addMemberToGroup(CustomerGroup $group, int $customerId, string $role = 'member'): CustomerGroupMember
    {
        return DB::transaction(function () use ($group, $customerId, $role) {
            $customer = Customer::findOrFail($customerId);

            if ($customer->status !== 'active') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'customer_id' => "Cannot add inactive or closed customer '{$customer->full_name}' to a group.",
                ]);
            }

            // Check duplicate active membership
            $existingMembership = CustomerGroupMember::where('group_id', $group->id)
                ->where('customer_id', $customerId)
                ->where('status', 'active')
                ->first();

            if ($existingMembership) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'customer_id' => "Customer '{$customer->full_name}' is already an active member of this group.",
                ]);
            }

            $user = Auth::user();
            $member = CustomerGroupMember::create([
                'group_id' => $group->id,
                'customer_id' => $customer->id,
                'role' => $role,
                'joined_at' => now(),
                'status' => 'active',
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            // If role is group_leader, update leader_customer_id on group
            if ($role === 'group_leader') {
                $group->update(['leader_customer_id' => $customer->id]);
            }

            $this->activityLogService->log(
                'group_member_added',
                $group,
                null,
                ['customer_id' => $customer->id, 'role' => $role]
            );

            return $member;
        });
    }

    public function removeMemberFromGroup(CustomerGroup $group, int $customerId): bool
    {
        return DB::transaction(function () use ($group, $customerId) {
            $membership = CustomerGroupMember::where('group_id', $group->id)
                ->where('customer_id', $customerId)
                ->where('status', 'active')
                ->firstOrFail();

            $customer = $membership->customer;
            $membership->update([
                'status' => 'left',
                'left_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $membership->delete();

            // If removed member was the group leader, reset leader_customer_id
            if ($group->leader_customer_id === $customerId) {
                $group->update(['leader_customer_id' => null]);
            }

            $this->activityLogService->log(
                'group_member_removed',
                $group,
                ['customer_id' => $customer->id],
                null
            );

            return true;
        });
    }

    public function assignGroupLeader(CustomerGroup $group, int $customerId): CustomerGroup
    {
        return DB::transaction(function () use ($group, $customerId) {
            // Verify member exists in group and is active
            $isMember = CustomerGroupMember::where('group_id', $group->id)
                ->where('customer_id', $customerId)
                ->where('status', 'active')
                ->exists();

            if (!$isMember) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'leader_customer_id' => "Selected leader must be an active member of this group.",
                ]);
            }

            // Reset existing leaders' role to 'member'
            CustomerGroupMember::where('group_id', $group->id)
                ->where('role', 'group_leader')
                ->update(['role' => 'member']);

            // Set new leader role
            CustomerGroupMember::where('group_id', $group->id)
                ->where('customer_id', $customerId)
                ->update(['role' => 'group_leader']);

            $group->update(['leader_customer_id' => $customerId]);
            $leaderCustomer = Customer::find($customerId);

            $this->activityLogService->log(
                'group_leader_assigned',
                $group,
                null,
                ['leader_customer_id' => $customerId]
            );

            return $group->fresh(['leader']);
        });
    }
}
