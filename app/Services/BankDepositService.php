<?php

namespace App\Services;

use App\Models\BankDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankDepositService
{
    protected CashBookService $cashBookService;
    protected ActivityLogService $activityLogService;

    public function __construct(CashBookService $cashBookService, ActivityLogService $activityLogService)
    {
        $this->cashBookService = $cashBookService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Branch Manager / Authorized user submits a bank deposit.
     */
    public function submitDeposit(array $data, User $user): BankDeposit
    {
        return DB::transaction(function () use ($data, $user) {
            $branchId = $data['branch_id'] ?? $user->branch_id;
            $companyId = $data['company_id'] ?? $user->company_id ?? 1;

            if (!$branchId) {
                throw new \InvalidArgumentException('Branch ID is required for bank deposit submission.');
            }

            $depositDate = Carbon::parse($data['deposit_date'] ?? today())->format('Y-m-d');

            $deposit = BankDeposit::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'deposit_date' => $depositDate,
                'amount' => $data['amount'],
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? 'DEP-' . time() . '-' . rand(100, 999),
                'description' => $data['description'] ?? null,
                'status' => 'pending',
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            $this->activityLogService->log('bank_deposit_submitted', $deposit, null, $deposit->toArray());

            Log::info("Bank deposit submitted: ID {$deposit->id}, Amount ₹{$deposit->amount}, Branch {$branchId}, Date {$depositDate}");

            return $deposit;
        });
    }

    /**
     * Admin approves a submitted bank deposit.
     */
    public function approveDeposit(BankDeposit $deposit, User $approver, ?string $remarks = null): BankDeposit
    {
        if ($deposit->isApproved()) {
            throw new \Exception('Bank deposit is already approved.');
        }

        // Branch Managers cannot approve their own deposits
        if ((int)$approver->id === (int)$deposit->submitted_by) {
            throw new \Exception('You cannot approve your own submitted bank deposit.');
        }

        return DB::transaction(function () use ($deposit, $approver, $remarks) {
            $oldValues = $deposit->toArray();

            $deposit->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'description' => $remarks ? trim(($deposit->description ?? '') . ' | Approval note: ' . $remarks) : $deposit->description,
            ]);

            $this->activityLogService->log('bank_deposit_approved', $deposit, $oldValues, $deposit->fresh()->toArray());

            Log::info("Bank deposit approved: ID {$deposit->id}, Approved By User {$approver->id}");

            // Recalculate Cash Book for deposit date and subsequent dates to update physical cash balance
            $this->cashBookService->recalculateForDateAndSubsequent($deposit->branch_id, $deposit->deposit_date->format('Y-m-d'));

            return $deposit->fresh();
        });
    }

    /**
     * Admin rejects a submitted bank deposit.
     */
    public function rejectDeposit(BankDeposit $deposit, User $rejector, string $reason): BankDeposit
    {
        if ($deposit->isApproved()) {
            throw new \Exception('Cannot reject an already approved bank deposit.');
        }

        // Branch Managers cannot reject their own deposits
        if ((int)$rejector->id === (int)$deposit->submitted_by) {
            throw new \Exception('You cannot reject your own submitted bank deposit.');
        }

        return DB::transaction(function () use ($deposit, $rejector, $reason) {
            $oldValues = $deposit->toArray();

            $deposit->update([
                'status' => 'rejected',
                'approved_by' => $rejector->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->activityLogService->log('bank_deposit_rejected', $deposit, $oldValues, $deposit->fresh()->toArray());

            Log::info("Bank deposit rejected: ID {$deposit->id}, Rejected By User {$rejector->id}, Reason: {$reason}");

            // Recalculate Cash Book in case status changed from approved/pending
            $this->cashBookService->recalculateForDateAndSubsequent($deposit->branch_id, $deposit->deposit_date->format('Y-m-d'));

            return $deposit->fresh();
        });
    }
}
