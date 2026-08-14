<?php

namespace App\Repositories;

use App\Models\LoanAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LoanAccountRepositoryInterface
{
    public function getPaginatedAccounts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?LoanAccount;

    public function findByLoanNumber(string $loanNumber): ?LoanAccount;

    public function createLoanAccount(array $masterData, array $membersData = [], array $installmentsData = []): LoanAccount;

    public function updateLoanStatus(LoanAccount $loanAccount, string $status, array $additionalData = []): LoanAccount;

    public function recordDownPayment(LoanAccount $loanAccount, array $paymentData): LoanAccount;

    public function recordDisbursement(LoanAccount $loanAccount, array $disbursementData): LoanAccount;

    public function generateLoanNumber(int $branchId): string;

    public function generateDisbursementNumber(int $branchId): string;

    public function generateReceiptNumber(int $branchId): string;

    public function recordRepayment(LoanAccount $loanAccount, array $repaymentData): LoanAccount;
}
