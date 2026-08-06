<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function getPaginatedEmployees(array $filters = [], int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->employeeRepository->getPaginatedEmployees($filters, $perPage);
    }

    public function getEmployeeById(int $id): ?Employee
    {
        return $this->employeeRepository->findById($id);
    }

    public function createEmployee(array $data, ?UploadedFile $photo = null, array $documents = []): Employee
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            if (empty($data['company_id'])) {
                $data['company_id'] = $user->company_id;
            }
            if (empty($data['branch_id']) && $user->branch_id) {
                $data['branch_id'] = $user->branch_id;
            }
        }

        if ($photo) {
            $data['profile_photo_path'] = $photo->store('employees/photos', 'public');
        }

        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['status'] = $data['status'] ?? 'active';

        $employee = $this->employeeRepository->create($data);

        // Assign role to linked User if selected
        if (!empty($data['user_id']) && !empty($data['role'])) {
            $linkedUser = User::find($data['user_id']);
            if ($linkedUser) {
                $linkedUser->syncRoles([$data['role']]);
            }
        }

        // Store Documents
        foreach ($documents as $doc) {
            if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                $path = $doc['file']->store('employees/documents', 'public');
                EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'document_type' => $doc['type'] ?? 'other',
                    'document_title' => $doc['title'] ?? $doc['type'],
                    'file_path' => $path,
                    'file_name' => $doc['file']->getClientOriginalName(),
                    'file_size_kb' => round($doc['file']->getSize() / 1024),
                    'created_by' => $userId,
                ]);
            }
        }

        $this->activityLogService->log('created', $employee, null, $employee->toArray());

        return $employee;
    }

    public function updateEmployee(Employee $employee, array $data, ?UploadedFile $photo = null, array $documents = []): Employee
    {
        $oldValues = $employee->toArray();

        $user = Auth::user();
        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $employee->company_id;
            if ($user->hasRole('Branch Manager')) {
                $data['branch_id'] = $employee->branch_id;
            }
        }

        if ($photo) {
            if ($employee->profile_photo_path) {
                Storage::disk('public')->delete($employee->profile_photo_path);
            }
            $data['profile_photo_path'] = $photo->store('employees/photos', 'public');
        }

        $data['updated_by'] = Auth::id();

        $updatedEmployee = $this->employeeRepository->update($employee, $data);

        // Update role on linked user
        if (!empty($data['user_id']) && !empty($data['role'])) {
            $linkedUser = User::find($data['user_id']);
            if ($linkedUser) {
                $linkedUser->syncRoles([$data['role']]);
            }
        }

        // Add new documents
        foreach ($documents as $doc) {
            if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                $path = $doc['file']->store('employees/documents', 'public');
                EmployeeDocument::create([
                    'employee_id' => $updatedEmployee->id,
                    'document_type' => $doc['type'] ?? 'other',
                    'document_title' => $doc['title'] ?? $doc['type'],
                    'file_path' => $path,
                    'file_name' => $doc['file']->getClientOriginalName(),
                    'file_size_kb' => round($doc['file']->getSize() / 1024),
                    'created_by' => Auth::id(),
                ]);
            }
        }

        $this->activityLogService->log('updated', $updatedEmployee, $oldValues, $updatedEmployee->toArray());

        return $updatedEmployee;
    }

    public function deleteDocument(EmployeeDocument $document): bool
    {
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        return (bool) $document->delete();
    }

    public function toggleEmployeeStatus(Employee $employee, string $status): bool
    {
        $oldValues = ['status' => $employee->status];
        $result = $this->employeeRepository->toggleStatus($employee, $status, Auth::id());

        $this->activityLogService->log('toggle_status', $employee, $oldValues, ['status' => $status]);

        return $result;
    }

    public function deleteEmployee(Employee $employee): bool
    {
        $oldValues = $employee->toArray();
        $result = $this->employeeRepository->delete($employee, Auth::id());

        $this->activityLogService->log('deleted', $employee, $oldValues, null);

        return $result;
    }

    public function restoreEmployee(Employee $employee): bool
    {
        $result = $this->employeeRepository->restore($employee);

        $this->activityLogService->log('restored', $employee, null, $employee->toArray());

        return $result;
    }
}
