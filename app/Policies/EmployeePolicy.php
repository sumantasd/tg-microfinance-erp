<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.view')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $employee->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.edit')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $employee->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function toggleStatus(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.toggle_status')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $employee->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function delete(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.delete')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $employee->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function restore(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.restore')) {
            return false;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }
}
