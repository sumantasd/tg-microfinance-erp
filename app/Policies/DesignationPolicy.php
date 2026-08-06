<?php

namespace App\Policies;

use App\Models\Designation;
use App\Models\User;

class DesignationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('designation.view');
    }

    public function view(User $user, Designation $designation): bool
    {
        if (!$user->hasPermissionTo('designation.view')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $designation->company_id) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('designation.create');
    }

    public function update(User $user, Designation $designation): bool
    {
        if (!$user->hasPermissionTo('designation.edit')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $designation->company_id) {
            return false;
        }

        return true;
    }

    public function toggleStatus(User $user, Designation $designation): bool
    {
        if (!$user->hasPermissionTo('designation.toggle_status')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $designation->company_id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Designation $designation): bool
    {
        if (!$user->hasPermissionTo('designation.delete')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $designation->company_id) {
            return false;
        }

        return true;
    }

    public function restore(User $user, Designation $designation): bool
    {
        if (!$user->hasPermissionTo('designation.restore')) {
            return false;
        }

        if ($user->company_id && (int) $user->company_id !== (int) $designation->company_id) {
            return false;
        }

        return true;
    }
}
