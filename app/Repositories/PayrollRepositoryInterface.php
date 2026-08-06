<?php

namespace App\Repositories;

use App\Models\Payroll;
use App\Models\SalarySlip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PayrollRepositoryInterface
{
    public function getPaginatedPayrolls(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): ?Payroll;

    public function findByUuid(string $uuid): ?Payroll;

    public function findSalarySlipByUuid(string $uuid): ?SalarySlip;

    public function processMonthlyPayroll(array $data): Payroll;

    public function updateStatus(Payroll $payroll, string $status): bool;
}
