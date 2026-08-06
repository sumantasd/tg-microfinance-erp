<?php

namespace App\Services;

use App\Models\Designation;
use App\Repositories\DesignationRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DesignationService
{
    public function __construct(
        protected DesignationRepositoryInterface $designationRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function createDesignation(array $data): Designation
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && empty($data['company_id'])) {
            $data['company_id'] = $user->company_id;
        }

        if (empty($data['code'])) {
            $nextId = (Designation::max('id') ?? 0) + 1;
            $data['code'] = 'DSG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $designation = $this->designationRepository->create($data);

        $this->activityLogService->log('created', $designation, null, $designation->toArray());

        return $designation;
    }

    public function updateDesignation(Designation $designation, array $data): Designation
    {
        $oldValues = $designation->toArray();

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $designation->company_id;
        }

        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $designation->is_active;

        $updatedDesignation = $this->designationRepository->update($designation, $data);

        $this->activityLogService->log('updated', $updatedDesignation, $oldValues, $updatedDesignation->toArray());

        return $updatedDesignation;
    }

    public function toggleDesignationStatus(Designation $designation, bool $isActive): bool
    {
        $oldValues = ['is_active' => $designation->is_active];
        $result = $this->designationRepository->toggleStatus($designation, $isActive, Auth::id());

        $this->activityLogService->log('toggle_status', $designation, $oldValues, ['is_active' => $isActive]);

        return $result;
    }

    public function deleteDesignation(Designation $designation): bool
    {
        $oldValues = $designation->toArray();
        $result = $this->designationRepository->delete($designation, Auth::id());

        $this->activityLogService->log('deleted', $designation, $oldValues, null);

        return $result;
    }

    public function restoreDesignation(Designation $designation): bool
    {
        $result = $this->designationRepository->restore($designation);

        $this->activityLogService->log('restored', $designation, null, $designation->toArray());

        return $result;
    }
}
