<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if (!$user->can('attendance.view')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $user->branch_id === $attendance->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return $user->company_id === $attendance->company_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.create');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->can('attendance.edit') && $this->view($user, $attendance);
    }
}
