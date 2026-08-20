<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\LoanAccount;
use App\Models\LoanAccountMember;
use App\Models\LoanDisbursement;
use App\Models\LoanDownPayment;
use App\Models\LoanInstallment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LoanAccountRepository implements LoanAccountRepositoryInterface
{
    public function getPaginatedAccounts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LoanAccount::with(['company', 'branch', 'customer', 'customerGroup', 'loanScheme', 'application', 'creator']);

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['loan_type'])) {
            $query->where('loan_type', $filters['loan_type']);
        }

        if (!empty($filters['borrower_type'])) {
            $query->where('borrower_type', $filters['borrower_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($c) use ($search) {
                      $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customerGroup', function ($g) use ($search) {
                      $g->where('name', 'like', "%{$search}%")
                        ->orWhere('group_code', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?LoanAccount
    {
        return LoanAccount::with([
            'company', 'branch', 'customer', 'customerGroup.members.customer',
            'loanScheme', 'application.products.product', 'members.customer',
            'installments', 'downPayments.receiver', 'disbursements.disburser',
            'repayments.receiver', 'creator', 'updater'
        ])->find($id);
    }

    public function findByLoanNumber(string $loanNumber): ?LoanAccount
    {
        return LoanAccount::where('loan_number', $loanNumber)->first();
    }

    public function createLoanAccount(array $masterData, array $membersData = [], array $installmentsData = []): LoanAccount
    {
        return DB::transaction(function () use ($masterData, $membersData, $installmentsData) {
            $loanAccount = LoanAccount::create($masterData);

            if (!empty($membersData)) {
                foreach ($membersData as $member) {
                    $member['loan_account_id'] = $loanAccount->id;
                    LoanAccountMember::create($member);
                }
            }

            if (!empty($installmentsData)) {
                $existingInstallmentsCount = LoanInstallment::where('loan_account_id', $loanAccount->id)->count();
                if ($existingInstallmentsCount === 0) {
                    foreach ($installmentsData as $inst) {
                        $inst['loan_account_id'] = $loanAccount->id;
                        LoanInstallment::create($inst);
                    }
                }
            }

            return $loanAccount->fresh(['members.customer', 'installments']);
        });
    }

    public function updateLoanStatus(LoanAccount $loanAccount, string $status, array $additionalData = []): LoanAccount
    {
        $additionalData['status'] = $status;
        $loanAccount->update($additionalData);
        return $loanAccount->fresh();
    }

    public function recordDownPayment(LoanAccount $loanAccount, array $paymentData): LoanAccount
    {
        return DB::transaction(function () use ($loanAccount, $paymentData) {
            $paymentData['loan_account_id'] = $loanAccount->id;
            LoanDownPayment::create($paymentData);

            $totalDown = (float) $loanAccount->downPayments()->sum('amount');
            $loanAccount->update(['down_payment_amount' => $totalDown]);

            return $loanAccount->fresh(['downPayments']);
        });
    }

    public function recordDisbursement(LoanAccount $loanAccount, array $disbursementData): LoanAccount
    {
        return DB::transaction(function () use ($loanAccount, $disbursementData) {
            $disbursementData['loan_account_id'] = $loanAccount->id;
            LoanDisbursement::create($disbursementData);

            $totalDisbursed = (float) $loanAccount->disbursements()->sum('disbursed_amount');
            $status = ($totalDisbursed >= $loanAccount->sanctioned_amount) ? 'active' : 'ready_for_disbursement';

            $loanAccount->update([
                'disbursed_amount' => $totalDisbursed,
                'disbursement_date' => $disbursementData['disbursement_date'] ?? now()->toDateString(),
                'status' => $status,
            ]);

            return $loanAccount->fresh(['disbursements']);
        });
    }

    public function generateLoanNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('loan_accounts')
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            return "LN-{$branchCode}-{$year}-{$nextSeq}";
        });
    }

    public function generateDisbursementNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('loan_disbursements')
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "DISB-{$branchCode}-{$year}-{$nextSeq}";
        });
    }

    public function generateReceiptNumber(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = $branch->code ?? 'BR001';
            $year = date('Y');

            $lastId = DB::table('loan_repayments')
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextSeq = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);
            return "RCPT-{$branchCode}-{$year}-{$nextSeq}";
        });
    }

    public function recordRepayment(LoanAccount $loanAccount, array $repaymentData): LoanAccount
    {
        return DB::transaction(function () use ($loanAccount, $repaymentData) {
            $repaymentData['loan_account_id'] = $loanAccount->id;
            \App\Models\LoanRepayment::create($repaymentData);
            return $loanAccount->fresh(['repayments.receiver', 'installments']);
        });
    }
}
