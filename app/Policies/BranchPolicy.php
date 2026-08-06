<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any branches.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branch.view');
    }

    /**
     * Determine whether the user can view the specific branch.
     */
    public function view(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branch.view')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $branch->id || (int) $user->id === (int) $branch->manager_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $branch->company_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create branches.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('branch.create');
    }

    /**
     * Determine whether the user can update the branch.
     */
    public function update(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branch.edit')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $branch->id || (int) $user->id === (int) $branch->manager_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $branch->company_id;
        }

        return true;
    }

    /**
     * Determine whether the user can toggle the operational status of the branch.
     */
    public function toggleStatus(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branch.toggle_status')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return (int) $user->branch_id === (int) $branch->id || (int) $user->id === (int) $branch->manager_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $branch->company_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the branch.
     */
    public function delete(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branch.delete')) {
            return false;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $branch->company_id;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the soft-deleted branch.
     */
    public function restore(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branch.restore')) {
            return false;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return (int) $user->company_id === (int) $branch->company_id;
        }

        return true;
    }
}
