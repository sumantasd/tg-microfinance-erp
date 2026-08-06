<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\SalarySlip;
use App\Repositories\PayrollRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PayrollService
{
    public function __construct(
        protected PayrollRepositoryInterface $payrollRepo,
        protected ActivityLogService $activityLogger
    ) {}

    public function getPaginatedPayrolls(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->payrollRepo->getPaginatedPayrolls($filters, $perPage);
    }

    public function getPayrollById(int $id): ?Payroll
    {
        return $this->payrollRepo->findById($id);
    }

    public function getSalarySlipByUuid(string $uuid): ?SalarySlip
    {
        return $this->payrollRepo->findSalarySlipByUuid($uuid);
    }

    public function runMonthlyPayroll(array $data): Payroll
    {
        $payroll = $this->payrollRepo->processMonthlyPayroll($data);

        $this->activityLogger->log(
            'Process Payroll',
            $payroll,
            null,
            ['status' => 'draft', 'net_payout' => $payroll->total_net_payout]
        );

        return $payroll;
    }

    public function disbursePayroll(Payroll $payroll): bool
    {
        $res = $this->payrollRepo->updateStatus($payroll, 'disbursed');

        $this->activityLogger->log(
            'Disburse Payroll',
            $payroll,
            ['status' => 'draft'],
            ['status' => 'disbursed', 'net_payout' => $payroll->total_net_payout]
        );

        return $res;
    }
}
