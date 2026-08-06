<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave.view');
    }

    public function view(User $user, Leave $leave): bool
    {
        if (!$user->can('leave.view')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $user->branch_id === $leave->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return $user->company_id === $leave->company_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('leave.create');
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $user->can('leave.approve') && $this->view($user, $leave);
    }
}
