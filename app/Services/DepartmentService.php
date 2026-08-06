<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\DepartmentRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class DepartmentService
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function createDepartment(array $data): Department
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && empty($data['company_id'])) {
            $data['company_id'] = $user->company_id;
        }

        if (empty($data['code'])) {
            $nextId = (Department::max('id') ?? 0) + 1;
            $data['code'] = 'DEPT-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $department = $this->departmentRepository->create($data);

        $this->activityLogService->log('created', $department, null, $department->toArray());

        return $department;
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        $oldValues = $department->toArray();

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $department->company_id;
        }

        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : $department->is_active;

        $updatedDepartment = $this->departmentRepository->update($department, $data);

        $this->activityLogService->log('updated', $updatedDepartment, $oldValues, $updatedDepartment->toArray());

        return $updatedDepartment;
    }

    public function toggleDepartmentStatus(Department $department, bool $isActive): bool
    {
        $oldValues = ['is_active' => $department->is_active];
        $result = $this->departmentRepository->toggleStatus($department, $isActive, Auth::id());

        $this->activityLogService->log('toggle_status', $department, $oldValues, ['is_active' => $isActive]);

        return $result;
    }

    public function deleteDepartment(Department $department): bool
    {
        $oldValues = $department->toArray();
        $result = $this->departmentRepository->delete($department, Auth::id());

        $this->activityLogService->log('deleted', $department, $oldValues, null);

        return $result;
    }

    public function restoreDepartment(Department $department): bool
    {
        $result = $this->departmentRepository->restore($department);

        $this->activityLogService->log('restored', $department, null, $department->toArray());

        return $result;
    }
}
