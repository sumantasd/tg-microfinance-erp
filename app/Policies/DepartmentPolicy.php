<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('department.view');
    }

    public function view(User $user, Department $department): bool
    {
        if (!$user->hasPermissionTo('department.view')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('department.create');
    }

    public function update(User $user, Department $department): bool
    {
        if (!$user->hasPermissionTo('department.edit')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        return true;
    }

    public function toggleStatus(User $user, Department $department): bool
    {
        if (!$user->hasPermissionTo('department.toggle_status')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Department $department): bool
    {
        if (!$user->hasPermissionTo('department.delete')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        return true;
    }

    public function restore(User $user, Department $department): bool
    {
        if (!$user->hasPermissionTo('department.restore')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        return true;
    }
}
