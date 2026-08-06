<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\SalarySlip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollRepository implements PayrollRepositoryInterface
{
    /**
     * Apply strict multi-company and branch-level data isolation based on role and context.
     */
    protected function applyPayrollScope($query)
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager')) {
                $assignedBranchId = $user->branch_id;
                $userId = $user->id;

                if ($assignedBranchId) {
                    $query->where('branch_id', $assignedBranchId);
                } else {
                    $managedBranchIds = Branch::where('manager_id', $userId)->pluck('id')->toArray();
                    if (!empty($managedBranchIds)) {
                        $query->whereIn('branch_id', $managedBranchIds);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
            } elseif ($user->hasRole('Company Admin') || $user->company_id) {
                if ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                if ($user->branch_id) {
                    $query->where('branch_id', $user->branch_id);
                } elseif ($user->company_id) {
                    $query->where('company_id', $user->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query;
    }

    public function getPaginatedPayrolls(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payroll::with(['company', 'branch', 'processor', 'salarySlips']);
        $query = $this->applyPayrollScope($query);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            if (!empty($filters['company_id'])) {
                $query->where('company_id', $filters['company_id']);
            }
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
        }

        if (!empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Payroll
    {
        $query = Payroll::with(['company', 'branch', 'processor', 'salarySlips.employee.designation']);
        return $this->applyPayrollScope($query)->find($id);
    }

    public function findByUuid(string $uuid): ?Payroll
    {
        $query = Payroll::with(['company', 'branch', 'processor', 'salarySlips.employee.designation'])->where('uuid', $uuid);
        return $this->applyPayrollScope($query)->first();
    }

    public function findSalarySlipByUuid(string $uuid): ?SalarySlip
    {
        $slip = SalarySlip::with(['payroll.company', 'payroll.branch', 'employee.designation', 'employee.department'])->where('uuid', $uuid)->first();
        if (!$slip) {
            return null;
        }

        // Apply data isolation on parent payroll
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            if ($user->hasRole('Branch Manager') && $slip->payroll->branch_id !== $user->branch_id) {
                return null;
            }
            if (($user->hasRole('Company Admin') || $user->company_id) && $slip->payroll->company_id !== $user->company_id) {
                return null;
            }
        }

        return $slip;
    }

    public function processMonthlyPayroll(array $data): Payroll
    {
        return DB::transaction(function () use ($data) {
            $branchId = $data['branch_id'];
            $companyId = $data['company_id'];
            $month = $data['month'];
            $year = $data['year'];
            $userId = auth()->id();

            // Fetch active branch employees
            $employees = Employee::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->get();

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            $payroll = Payroll::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'month' => $month,
                'year' => $year,
                'total_employees' => $employees->count(),
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net_payout' => 0,
                'status' => 'draft',
                'processed_by' => $userId,
                'processed_at' => now(),
            ]);

            foreach ($employees as $emp) {
                $basic = $emp->basic_salary ?: 25000.00;
                $hra = $basic * 0.20;
                $conveyance = 1600.00;
                $special = 2000.00;
                $gross = $basic + $hra + $conveyance + $special;

                $pf = $basic * 0.12;
                $tax = $gross > 50000 ? 1500.00 : 200.00;
                $otherDed = 0.00;
                $totalDed = $pf + $tax + $otherDed;
                $net = $gross - $totalDed;

                SalarySlip::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $emp->id,
                    'basic_salary' => $basic,
                    'hra' => $hra,
                    'conveyance_allowance' => $conveyance,
                    'special_allowance' => $special,
                    'pf_deduction' => $pf,
                    'tax_deduction' => $tax,
                    'other_deduction' => $otherDed,
                    'gross_salary' => $gross,
                    'total_deductions' => $totalDed,
                    'net_salary' => $net,
                    'payment_status' => 'unpaid',
                ]);

                $totalGross += $gross;
                $totalDeductions += $totalDed;
                $totalNet += $net;
            }

            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net_payout' => $totalNet,
            ]);

            return $payroll->fresh('salarySlips');
        });
    }

    public function updateStatus(Payroll $payroll, string $status): bool
    {
        $payroll->status = $status;
        if ($status === 'disbursed') {
            $payroll->salarySlips()->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        }
        return $payroll->save();
    }
}
