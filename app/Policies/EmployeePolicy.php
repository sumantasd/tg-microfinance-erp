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
            $userEmpId = $user->employee?->id ?? Employee::where('user_id', $user->id)->value('id');
            return (int) $employee->user_id === (int) $user->id || ($userEmpId && (int) $employee->id === (int) $userEmpId);
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Branch Manager')) {
            return false;
        }

        return $user->hasPermissionTo('employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        if (!$user->hasPermissionTo('employee.edit')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            $userEmpId = $user->employee?->id ?? Employee::where('user_id', $user->id)->value('id');
            return (int) $employee->user_id === (int) $user->id || ($userEmpId && (int) $employee->id === (int) $userEmpId);
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function toggleStatus(User $user, Employee $employee): bool
    {
        if ($user->hasRole('Branch Manager')) {
            return false;
        }

        if (!$user->hasPermissionTo('employee.toggle_status')) {
            return false;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $employee->company_id;
        }

        return true;
    }

    public function delete(User $user, Employee $employee): bool
    {
        if ($user->hasRole('Branch Manager')) {
            return false;
        }

        if (!$user->hasPermissionTo('employee.delete')) {
            return false;
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
