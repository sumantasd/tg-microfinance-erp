<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('company.view');
    }

    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        if (!$user->hasPermissionTo('company.view')) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return (int) $user->company_id === (int) $company->id;
    }

    /**
     * Determine whether the user can create companies.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('company.create');
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        if (!$user->hasPermissionTo('company.edit')) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return (int) $user->company_id === (int) $company->id;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        if (!$user->hasPermissionTo('company.delete')) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return false; // Only Super Admin can delete companies
    }

    /**
     * Determine whether the user can restore the company.
     */
    public function restore(User $user, Company $company): bool
    {
        if (!$user->hasPermissionTo('company.restore')) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        return false;
    }
}
