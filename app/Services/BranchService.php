<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\BranchRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class BranchService
{
    public function __construct(
        protected BranchRepositoryInterface $branchRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function createBranch(array $data): Branch
    {
        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $branch = $this->branchRepository->create($data);

        $this->activityLogService->log('created', $branch, null, $branch->toArray());

        return $branch;
    }

    public function updateBranch(Branch $branch, array $data): Branch
    {
        $oldValues = $branch->toArray();

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $branch->company_id;
        }

        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $branch->is_active;

        $updatedBranch = $this->branchRepository->update($branch, $data);

        $this->activityLogService->log('updated', $updatedBranch, $oldValues, $updatedBranch->toArray());

        return $updatedBranch;
    }

    public function toggleBranchStatus(Branch $branch, bool $isActive): bool
    {
        $oldValues = ['is_active' => $branch->is_active];
        $result = $this->branchRepository->toggleStatus($branch, $isActive, Auth::id());

        $this->activityLogService->log('toggle_status', $branch, $oldValues, ['is_active' => $isActive]);

        return $result;
    }

    public function deleteBranch(Branch $branch): bool
    {
        $oldValues = $branch->toArray();
        $result = $this->branchRepository->delete($branch, Auth::id());

        $this->activityLogService->log('deleted', $branch, $oldValues, null);

        return $result;
    }

    public function restoreBranch(Branch $branch): bool
    {
        $result = $this->branchRepository->restore($branch);

        $this->activityLogService->log('restored', $branch, null, $branch->toArray());

        return $result;
    }
}
