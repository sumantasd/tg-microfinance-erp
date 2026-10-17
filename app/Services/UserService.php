<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function createUser(array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if (!empty($data['branch_id']) && empty($data['company_id'])) {
            $branch = \App\Models\Branch::find($data['branch_id']);
            if ($branch && $branch->company_id) {
                $data['company_id'] = $branch->company_id;
            }
        }

        $roleName = $data['role'] ?? null;
        unset($data['role']);

        $employeeRecordId = $data['employee_record_id'] ?? null;
        unset($data['employee_record_id']);

        $autoCreateEmployee = !empty($data['auto_create_employee']);
        unset($data['auto_create_employee']);

        $user = $this->userRepository->create($data);

        if ($roleName) {
            $user->syncRoles([$roleName]);
        }

        // Link to existing employee profile or safely auto-create one
        if (!empty($employeeRecordId)) {
            $employee = Employee::findOrFail($employeeRecordId);
            $this->linkEmployeeToUser($user, $employee);
        } elseif ($autoCreateEmployee) {
            $this->createEmployeeProfileForUser($user);
        } elseif (!empty($user->employee_id)) {
            $matchedEmp = Employee::where('employee_code', $user->employee_id)->whereNull('user_id')->first();
            if ($matchedEmp) {
                $this->linkEmployeeToUser($user, $matchedEmp);
            }
        }

        return $user->fresh(['employee', 'roles', 'branch', 'company']);
    }

    public function updateUser(User $user, array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['updated_by'] = Auth::id();

        if (!empty($data['branch_id']) && empty($data['company_id'])) {
            $branch = \App\Models\Branch::find($data['branch_id']);
            if ($branch && $branch->company_id) {
                $data['company_id'] = $branch->company_id;
            }
        }

        $roleName = $data['role'] ?? null;
        unset($data['role']);

        $employeeRecordId = array_key_exists('employee_record_id', $data) ? $data['employee_record_id'] : null;
        unset($data['employee_record_id']);

        $unlinkEmployee = !empty($data['unlink_employee']);
        unset($data['unlink_employee']);

        $autoCreateEmployee = !empty($data['auto_create_employee']);
        unset($data['auto_create_employee']);

        $updatedUser = $this->userRepository->update($user, $data);

        if ($roleName) {
            $updatedUser->syncRoles([$roleName]);
        }

        // Handle profile linking / unlinking
        if ($unlinkEmployee) {
            $this->unlinkEmployeeFromUser($updatedUser);
        } elseif (!empty($employeeRecordId)) {
            $employee = Employee::findOrFail($employeeRecordId);
            $this->linkEmployeeToUser($updatedUser, $employee);
        } elseif ($autoCreateEmployee && !$updatedUser->hasEmployeeProfile()) {
            $this->createEmployeeProfileForUser($updatedUser);
        } elseif (!empty($updatedUser->employee_id) && !$updatedUser->employee) {
            $matchedEmp = Employee::where('employee_code', $updatedUser->employee_id)->whereNull('user_id')->first();
            if ($matchedEmp) {
                $this->linkEmployeeToUser($updatedUser, $matchedEmp);
            }
        }

        return $updatedUser->fresh(['employee', 'roles', 'branch', 'company']);
    }

    public function toggleUserStatus(User $user, string $status): bool
    {
        return $this->userRepository->toggleStatus($user, $status);
    }

    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }

    /**
     * Link an existing unlinked Employee record to a User account safely.
     */
    public function linkEmployeeToUser(User $user, Employee $employee): void
    {
        // 1. Prevent duplicate mapping: cannot steal an employee already linked to another user
        if (!empty($employee->user_id) && (int) $employee->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'employee_record_id' => ["Employee {$employee->employee_code} is already linked to another user account."],
            ]);
        }

        // 2. Prevent cross-branch assignment
        if (!empty($user->branch_id) && !empty($employee->branch_id) && (int) $user->branch_id !== (int) $employee->branch_id) {
            throw ValidationException::withMessages([
                'employee_record_id' => ["Employee {$employee->employee_code} belongs to a different branch than the user."],
            ]);
        }

        DB::transaction(function () use ($user, $employee) {
            // Unlink any previous employee assigned to this user
            Employee::where('user_id', $user->id)
                ->where('id', '!=', $employee->id)
                ->update(['user_id' => null]);

            // Link employee to user
            $employee->update([
                'user_id' => $user->id,
                'updated_by' => Auth::id() ?? $user->id,
            ]);

            // Synchronize user's employee_id code
            $user->update([
                'employee_id' => $employee->employee_code,
                'updated_by' => Auth::id() ?? $user->id,
            ]);
        });
    }

    /**
     * Safely unlink a User account from their associated Employee record.
     */
    public function unlinkEmployeeFromUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            Employee::where('user_id', $user->id)
                ->update([
                    'user_id' => null,
                    'updated_by' => Auth::id() ?? $user->id,
                ]);

            $user->update([
                'employee_id' => null,
                'updated_by' => Auth::id() ?? $user->id,
            ]);
        });
    }

    /**
     * Safely create a new Employee record and link it to the User account.
     * Prevents duplicate employee creation if one already exists.
     */
    public function createEmployeeProfileForUser(User $user, array $overrides = []): Employee
    {
        $existing = $user->resolveEmployeeProfile();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $overrides) {
            $companyId = $user->company_id ?: ($user->branch?->company_id ?: 1);
            $branchId = $user->branch_id ?: 1;

            // Resolve or create default Department for company
            $department = Department::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'OPS'],
                [
                    'name' => 'Branch Operations',
                    'is_active' => true,
                    'created_by' => Auth::id() ?? $user->id,
                ]
            );

            // Resolve or create default Designation for role
            $roleName = $user->roles->first()?->name ?? 'Branch Manager';
            $cleanedCode = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $roleName), 0, 4)) ?: 'BM';

            $designation = Designation::firstOrCreate(
                ['company_id' => $companyId, 'title' => $roleName],
                [
                    'department_id' => $department->id,
                    'code' => $cleanedCode,
                    'is_active' => true,
                    'created_by' => Auth::id() ?? $user->id,
                ]
            );

            // Split user's name
            $nameParts = preg_split('/\s+/', trim($user->name), 2);
            $firstName = $nameParts[0] ?: 'Staff';
            $lastName = !empty($nameParts[1]) ? $nameParts[1] : 'Manager';

            // Generate next employee code using centralized generator
            $employeeCode = EmployeeService::generateEmployeeCode($companyId);

            $employee = Employee::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'employee_code' => $employeeCode,
                'first_name' => $overrides['first_name'] ?? $firstName,
                'last_name' => $overrides['last_name'] ?? $lastName,
                'email' => $user->email,
                'phone' => $user->mobile_number,
                'gender' => $overrides['gender'] ?? 'male',
                'joining_date' => $overrides['joining_date'] ?? now()->toDateString(),
                'status' => 'active',
                'created_by' => Auth::id() ?? $user->id,
                'updated_by' => Auth::id() ?? $user->id,
            ]);

            $user->update([
                'employee_id' => $employee->employee_code,
                'updated_by' => Auth::id() ?? $user->id,
            ]);

            return $employee;
        });
    }
}
