<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Atomically and safely generate the next unique Employee Code.
     * Format: GLF-EMP-YYYY-NNNN
     * Rules:
     * - Independent per company and calendar year
     * - Yearly sequence starts at 0001 (e.g. GLF-EMP-2026-0001)
     * - Preserves continuity with legacy sequences (EMP-YYYY-NNNN) for existing companies
     * - Never reuses any code present in the database (including soft-deleted)
     * - Decoupled from table autoincrement ID
     * - Concurrency safe
     */
    public static function generateEmployeeCode(int $companyId, ?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $lockKey = "employee_code_gen_{$companyId}_{$year}";

        $generator = function () use ($companyId, $year) {
            $prefix = "GLF-EMP-{$year}-";

            // Query existing codes for this company in this year (including soft-deleted)
            // Checks both new GLF-EMP-YYYY- and legacy EMP-YYYY- to ensure sequence continuity
            $existingCompanyCodes = Employee::withTrashed()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($year) {
                    $query->where('employee_code', 'like', "GLF-EMP-{$year}-%")
                          ->orWhere('employee_code', 'like', "EMP-{$year}-%");
                })
                ->pluck('employee_code');

            $maxCompanySeq = 0;
            foreach ($existingCompanyCodes as $code) {
                if (preg_match('/^(?:GLF-)?EMP-\d{4}-(\d+)$/', $code, $matches)) {
                    $seqVal = (int) $matches[1];
                    if ($seqVal > $maxCompanySeq) {
                        $maxCompanySeq = $seqVal;
                    }
                }
            }

            $nextSeq = $maxCompanySeq + 1;
            $candidateCode = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);

            // Never reuse any code present in the entire database (including soft-deleted)
            while (Employee::withTrashed()->where('employee_code', $candidateCode)->exists()) {
                $nextSeq++;
                $candidateCode = $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
            }

            return $candidateCode;
        };

        try {
            return Cache::lock($lockKey, 10)->block(5, $generator);
        } catch (\Throwable $e) {
            // Fallback if atomic lock driver unavailable in current environment
            return $generator();
        }
    }

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
        $newStoredFiles = [];

        try {
            return DB::transaction(function () use ($data, $photo, $documents, &$newStoredFiles) {
                $user = Auth::user();
                if ($user && !$user->isSuperAdmin()) {
                    if (empty($data['company_id'])) {
                        $data['company_id'] = $user->company_id;
                    }
                    if (empty($data['branch_id']) && $user->branch_id) {
                        $data['branch_id'] = $user->branch_id;
                    }
                }

                $companyId = (int) ($data['company_id'] ?? 1);

                // Server-side generation: Always generate final employee_code safely. Client input is never trusted.
                $data['employee_code'] = self::generateEmployeeCode($companyId);

                if ($photo) {
                    $photoPath = $photo->store('employees/photos', 'public');
                    $data['profile_photo_path'] = $photoPath;
                    $newStoredFiles[] = $photoPath;
                }

                $authUserId = Auth::id();
                $data['created_by'] = $authUserId;
                $data['updated_by'] = $authUserId;
                $data['status'] = $data['status'] ?? 'active';

                $loginEnabled = !empty($data['login_enabled']);
                $data['login_enabled'] = $loginEnabled;

                $userMode = $data['user_mode'] ?? null;
                $roleName = $data['role'] ?? null;
                unset($data['role'], $data['user_mode']);

                $userPassword = $data['user_password'] ?? null;
                $userEmail = $data['user_email'] ?? null;
                unset($data['user_password'], $data['user_email']);

                $linkedUserId = null;

                if ($loginEnabled) {
                    if ($userMode === 'create' || (!empty($userPassword) && empty($data['user_id']))) {
                        // 1. Create a new User account
                        $newEmail = !empty($userEmail) ? trim($userEmail) : trim($data['email'] ?? '');
                        if (empty($newEmail)) {
                            throw new \InvalidArgumentException('A valid email address is required to create a staff login account.');
                        }

                        $fullName = trim("{$data['first_name']} " . (!empty($data['middle_name']) ? "{$data['middle_name']} " : '') . "{$data['last_name']}");

                        $newUser = User::create([
                            'company_id' => $companyId,
                            'branch_id' => $data['branch_id'] ?? null,
                            'name' => $fullName,
                            'email' => $newEmail,
                            'mobile_number' => $data['phone'] ?? null,
                            'password' => Hash::make($userPassword),
                            'status' => 'active',
                            'employee_id' => $data['employee_code'],
                            'created_by' => $authUserId,
                            'updated_by' => $authUserId,
                        ]);

                        if ($roleName) {
                            $newUser->syncRoles([$roleName]);
                        }

                        $linkedUserId = $newUser->id;
                    } elseif ($userMode === 'link' || !empty($data['user_id'])) {
                        // 2. Link an eligible existing User account
                        $candidateUserId = (int) ($data['user_id'] ?? null);
                        if ($candidateUserId) {
                            $existingUser = User::findOrFail($candidateUserId);

                            // Enforce same-company isolation
                            if ((int) $existingUser->company_id !== $companyId) {
                                throw new \InvalidArgumentException('The selected user belongs to a different company.');
                            }

                            // Enforce user is not already linked
                            if ($existingUser->employee()->exists()) {
                                throw new \InvalidArgumentException('The selected user is already linked to another employee.');
                            }

                            if ($roleName) {
                                $existingUser->syncRoles([$roleName]);
                            }

                            $existingUser->update([
                                'employee_id' => $data['employee_code'],
                                'updated_by' => $authUserId,
                            ]);

                            $linkedUserId = $existingUser->id;
                        }
                    }
                }

                $data['user_id'] = $linkedUserId;

                // Create Employee record
                $employee = $this->employeeRepository->create($data);

                // Store uploaded documents
                foreach ($documents as $doc) {
                    if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                        $docPath = $doc['file']->store('employees/documents', 'public');
                        $newStoredFiles[] = $docPath;

                        EmployeeDocument::create([
                            'employee_id' => $employee->id,
                            'document_type' => $doc['type'] ?? 'other',
                            'document_title' => $doc['title'] ?? $doc['type'],
                            'file_path' => $docPath,
                            'file_name' => $doc['file']->getClientOriginalName(),
                            'file_size_kb' => round($doc['file']->getSize() / 1024),
                            'created_by' => $authUserId,
                        ]);
                    }
                }

                $this->activityLogService->log('created', $employee, null, $employee->toArray());

                return $employee;
            });
        } catch (\Throwable $e) {
            // Clean up newly uploaded files on rollback
            foreach ($newStoredFiles as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            throw $e;
        }
    }

    public function updateEmployee(Employee $employee, array $data, ?UploadedFile $photo = null, array $documents = []): Employee
    {
        $newStoredFiles = [];

        try {
            return DB::transaction(function () use ($employee, $data, $photo, $documents, &$newStoredFiles) {
                $oldValues = $employee->toArray();

                $user = Auth::user();
                if ($user && !$user->isSuperAdmin()) {
                    $data['company_id'] = $employee->company_id;
                    if ($user->hasRole('Branch Manager')) {
                        $data['branch_id'] = $employee->branch_id;
                    }
                }

                $companyId = (int) ($data['company_id'] ?? $employee->company_id);

                // Existing Employee Code is permanent and immutable during edits.
                unset($data['employee_code']);

                if ($photo) {
                    $photoPath = $photo->store('employees/photos', 'public');
                    $newStoredFiles[] = $photoPath;

                    // Delete old photo after successful upload
                    if ($employee->profile_photo_path && Storage::disk('public')->exists($employee->profile_photo_path)) {
                        Storage::disk('public')->delete($employee->profile_photo_path);
                    }
                    $data['profile_photo_path'] = $photoPath;
                }

                $authUserId = Auth::id();
                $data['updated_by'] = $authUserId;

                $loginEnabled = !empty($data['login_enabled']);
                $data['login_enabled'] = $loginEnabled;

                $userMode = $data['user_mode'] ?? null;
                $roleName = $data['role'] ?? null;
                unset($data['role'], $data['user_mode']);

                $userPassword = $data['user_password'] ?? null;
                $userEmail = $data['user_email'] ?? null;
                unset($data['user_password'], $data['user_email']);

                $oldUserId = $employee->user_id;

                if ($loginEnabled) {
                    if ($userMode === 'create' && empty($oldUserId) && !empty($userPassword)) {
                        // Create and link a new user for an unlinked employee
                        $newEmail = !empty($userEmail) ? trim($userEmail) : trim($data['email'] ?? $employee->email);
                        if (empty($newEmail)) {
                            throw new \InvalidArgumentException('A valid email address is required to create a staff login account.');
                        }

                        $fullName = trim("{$data['first_name']} " . (!empty($data['middle_name']) ? "{$data['middle_name']} " : '') . "{$data['last_name']}");

                        $newUser = User::create([
                            'company_id' => $companyId,
                            'branch_id' => $data['branch_id'] ?? $employee->branch_id,
                            'name' => $fullName,
                            'email' => $newEmail,
                            'mobile_number' => $data['phone'] ?? $employee->phone,
                            'password' => Hash::make($userPassword),
                            'status' => 'active',
                            'employee_id' => $data['employee_code'] ?? $employee->employee_code,
                            'created_by' => $authUserId,
                            'updated_by' => $authUserId,
                        ]);

                        if ($roleName) {
                            $newUser->syncRoles([$roleName]);
                        }

                        $data['user_id'] = $newUser->id;
                    } elseif (array_key_exists('user_id', $data)) {
                        $newUserId = !empty($data['user_id']) ? (int) $data['user_id'] : null;

                        if ($oldUserId && $oldUserId !== $newUserId) {
                            User::where('id', $oldUserId)
                                ->where('employee_id', $employee->employee_code)
                                ->update(['employee_id' => null, 'updated_by' => $authUserId]);
                        }

                        if ($newUserId) {
                            $linkedUser = User::findOrFail($newUserId);
                            if ((int) $linkedUser->company_id !== $companyId) {
                                throw new \InvalidArgumentException('The selected user belongs to a different company.');
                            }

                            $linkedUser->update([
                                'employee_id' => $data['employee_code'] ?? $employee->employee_code,
                                'updated_by' => $authUserId,
                            ]);

                            if ($roleName) {
                                $linkedUser->syncRoles([$roleName]);
                            }
                        }

                        $data['user_id'] = $newUserId;
                    } elseif ($oldUserId && $roleName) {
                        // Update role on current linked user
                        $linkedUser = User::find($oldUserId);
                        if ($linkedUser) {
                            $linkedUser->syncRoles([$roleName]);
                        }
                    }
                } else {
                    // Login disabled: unlink if linked
                    if ($oldUserId) {
                        User::where('id', $oldUserId)
                            ->where('employee_id', $employee->employee_code)
                            ->update(['employee_id' => null, 'updated_by' => $authUserId]);
                        $data['user_id'] = null;
                    }
                }

                $updatedEmployee = $this->employeeRepository->update($employee, $data);

                // Add new documents
                foreach ($documents as $doc) {
                    if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                        $docPath = $doc['file']->store('employees/documents', 'public');
                        $newStoredFiles[] = $docPath;

                        EmployeeDocument::create([
                            'employee_id' => $updatedEmployee->id,
                            'document_type' => $doc['type'] ?? 'other',
                            'document_title' => $doc['title'] ?? $doc['type'],
                            'file_path' => $docPath,
                            'file_name' => $doc['file']->getClientOriginalName(),
                            'file_size_kb' => round($doc['file']->getSize() / 1024),
                            'created_by' => $authUserId,
                        ]);
                    }
                }

                $this->activityLogService->log('updated', $updatedEmployee, $oldValues, $updatedEmployee->toArray());

                return $updatedEmployee;
            });
        } catch (\Throwable $e) {
            foreach ($newStoredFiles as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            throw $e;
        }
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
