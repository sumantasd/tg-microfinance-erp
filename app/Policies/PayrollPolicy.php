<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if (!$user->can('payroll.view')) {
            return false;
        }

        if ($user->hasRole('Branch Manager')) {
            return $user->branch_id === $payroll->branch_id;
        }

        if ($user->hasRole('Company Admin') || $user->company_id) {
            return $user->company_id === $payroll->company_id;
        }

        return true;
    }

    public function process(User $user): bool
    {
        return $user->can('payroll.process');
    }

    public function disburse(User $user, Payroll $payroll): bool
    {
        return $user->can('payroll.disburse') && $this->view($user, $payroll);
    }
}
