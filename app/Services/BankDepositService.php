<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
    public function submitDeposit(array $data, User $user, ?UploadedFile $receiptFile = null): BankDeposit
    {
        return DB::transaction(function () use ($data, $user, $receiptFile) {
            $branchId = $data['branch_id'] ?? $user->branch_id;
            $companyId = $data['company_id'] ?? $user->company_id ?? 1;

            if (!$branchId) {
                throw new \InvalidArgumentException('Branch ID is required for bank deposit submission.');
            }

            $depositDate = Carbon::parse($data['deposit_date'] ?? today())->format('Y-m-d');
            $amount = (float) $data['amount'];

            // 1. Resolve Bank Account
            $bankAccountId = null;
            $bankName = $data['bank_name'] ?? null;
            $accountNumber = $data['account_number'] ?? null;

            if (!empty($data['bank_account_id'])) {
                $bankAccount = BankAccount::where('id', $data['bank_account_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$bankAccount) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => 'The selected bank account is invalid or inactive.',
                    ]);
                }

                $bankAccountId = $bankAccount->id;
                $bankName = $bankAccount->bank_name;
                $accountNumber = $bankAccount->account_number;
            } elseif (empty($bankName)) {
                throw ValidationException::withMessages([
                    'bank_account_id' => 'Please select an active bank account.',
                ]);
            }

            // 2. Prevent duplicate submission from repeated clicks or retries
            $duplicateCheck = BankDeposit::where('branch_id', $branchId)
                ->where('status', 'pending')
                ->where('amount', $amount)
                ->where('deposit_date', $depositDate)
                ->where('submitted_at', '>=', now()->subSeconds(30));

            if ($bankAccountId) {
                $duplicateCheck->where('bank_account_id', $bankAccountId);
            } elseif ($bankName) {
                $duplicateCheck->where('bank_name', $bankName);
            }

            if ($duplicateCheck->exists()) {
                throw ValidationException::withMessages([
                    'amount' => 'A deposit with the exact same details was just submitted and is currently pending verification.',
                ]);
            }

            // 3. Process Receipt Image File
            $receiptPath = null;
            $receiptOriginalName = null;
            $receiptMimeType = null;
            $receiptSize = null;

            $fileToStore = $receiptFile ?? ($data['receipt'] ?? null);

            if ($fileToStore instanceof UploadedFile) {
                $receiptPath = $fileToStore->store('private/receipts/bank_deposits', 'local');
                $receiptOriginalName = $fileToStore->getClientOriginalName();
                $receiptMimeType = $fileToStore->getClientMimeType();
                $receiptSize = $fileToStore->getSize();
            } elseif (!empty($data['receipt_path'])) {
                $receiptPath = $data['receipt_path'];
                $receiptOriginalName = $data['receipt_original_name'] ?? basename($receiptPath);
                $receiptMimeType = $data['receipt_mime_type'] ?? 'image/jpeg';
                $receiptSize = $data['receipt_size'] ?? 0;
            }

            $referenceNumber = $data['reference_number'] ?? 'DEP-' . time() . '-' . rand(100, 999);

            $deposit = BankDeposit::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'bank_account_id' => $bankAccountId,
                'deposit_date' => $depositDate,
                'amount' => $amount,
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'reference_number' => $referenceNumber,
                'description' => $data['description'] ?? null,
                'receipt_path' => $receiptPath,
                'receipt_original_name' => $receiptOriginalName,
                'receipt_mime_type' => $receiptMimeType,
                'receipt_size' => $receiptSize,
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
     * Resubmit a rejected bank deposit with corrected details and new receipt.
     */
    public function resubmitDeposit(BankDeposit $deposit, array $data, User $user, ?UploadedFile $receiptFile = null): BankDeposit
    {
        return DB::transaction(function () use ($deposit, $data, $user, $receiptFile) {
            $deposit = BankDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== 'rejected') {
                throw new \Exception('Only rejected bank deposits can be resubmitted.');
            }

            $updateData = [
                'status' => 'pending',
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ];

            if (isset($data['amount'])) {
                $updateData['amount'] = $data['amount'];
            }
            if (isset($data['deposit_date'])) {
                $updateData['deposit_date'] = $data['deposit_date'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['reference_number'])) {
                $updateData['reference_number'] = $data['reference_number'];
            }
            if (!empty($data['bank_account_id'])) {
                $bankAccount = BankAccount::find($data['bank_account_id']);
                if ($bankAccount) {
                    $updateData['bank_account_id'] = $bankAccount->id;
                    $updateData['bank_name'] = $bankAccount->bank_name;
                    $updateData['account_number'] = $bankAccount->account_number;
                }
            }

            if ($receiptFile) {
                $companyId = $deposit->company_id;
                $extension = $receiptFile->getClientOriginalExtension() ?: 'jpg';
                $storedName = 'receipt_' . time() . '_' . Str::random(10) . '.' . $extension;
                $storedPath = $receiptFile->storeAs("receipts/bank_deposits/{$companyId}", $storedName, 'local');

                $updateData['receipt_path'] = $storedPath;
                $updateData['receipt_original_name'] = $receiptFile->getClientOriginalName();
                $updateData['receipt_mime_type'] = $receiptFile->getClientMimeType() ?: $receiptFile->getMimeType();
                $updateData['receipt_size'] = $receiptFile->getSize();
            }

            $deposit->update($updateData);

            $this->activityLogService->log('bank_deposit_resubmitted', $deposit, ['status' => 'rejected'], $deposit->toArray());

            Log::info("Bank deposit resubmitted: ID {$deposit->id}, Reference {$deposit->reference_number}, By User {$user->id}");

            return $deposit;
        });
    }

    /**
     * Admin approves a submitted bank deposit.
     */
    public function approveDeposit(BankDeposit $deposit, User $approver, ?string $remarks = null): BankDeposit
    {
        return DB::transaction(function () use ($deposit, $approver, $remarks) {
            $deposit = BankDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->isApproved()) {
                throw new \Exception('Bank deposit is already approved.');
            }

            if ($deposit->status !== 'pending') {
                throw new \Exception('Only pending bank deposits can be approved.');
            }

            // Branch Managers cannot approve their own deposits
            if ((int)$approver->id === (int)$deposit->submitted_by) {
                throw new \Exception('You cannot approve your own submitted bank deposit.');
            }

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

            return $deposit->fresh(['bankAccount', 'branch', 'submittedBy', 'approvedBy']);
        });
    }

    /**
     * Admin rejects a submitted bank deposit.
     */
    public function rejectDeposit(BankDeposit $deposit, User $rejector, string $reason): BankDeposit
    {
        return DB::transaction(function () use ($deposit, $rejector, $reason) {
            $deposit = BankDeposit::where('id', $deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->isApproved()) {
                throw new \Exception('Cannot reject an already approved bank deposit.');
            }

            if ($deposit->status !== 'pending') {
                throw new \Exception('Only pending bank deposits can be rejected.');
            }

            // Branch Managers cannot reject their own deposits
            if ((int)$rejector->id === (int)$deposit->submitted_by) {
                throw new \Exception('You cannot reject your own submitted bank deposit.');
            }

            $oldValues = $deposit->toArray();

            $deposit->update([
                'status' => 'rejected',
                'rejected_by' => $rejector->id,
                'rejected_at' => now(),
                'approved_by' => $rejector->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->activityLogService->log('bank_deposit_rejected', $deposit, $oldValues, $deposit->fresh()->toArray());

            Log::info("Bank deposit rejected: ID {$deposit->id}, Rejected By User {$rejector->id}, Reason: {$reason}");

            // Recalculate Cash Book in case status changed from approved/pending
            $this->cashBookService->recalculateForDateAndSubsequent($deposit->branch_id, $deposit->deposit_date->format('Y-m-d'));

            return $deposit->fresh(['bankAccount', 'branch', 'submittedBy', 'approvedBy', 'rejectedBy']);
        });
    }

    /**
     * Securely get physical file path of receipt.
     */
    public function getReceiptFilePath(BankDeposit $deposit): string
    {
        if (!$deposit->receipt_path) {
            abort(404, 'No receipt file attached to this deposit.');
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($deposit->receipt_path)) {
            abort(404, 'Receipt file not found on storage.');
        }

        return $disk->path($deposit->receipt_path);
    }

    /**
     * Retrieve active bank accounts selectable for deposits.
     */
    public function getActiveBankAccounts(int $companyId, ?int $branchId = null)
    {
        $query = BankAccount::where('company_id', $companyId)
            ->where('is_active', true);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        return $query->orderBy('bank_name')->get();
    }

    /**
     * Delete bank account with guard against historical references.
     */
    public function deleteBankAccount(BankAccount $bankAccount): void
    {
        if ($bankAccount->deposits()->exists()) {
            throw ValidationException::withMessages([
                'bank_account' => "Cannot delete bank account '{$bankAccount->bank_name} - {$bankAccount->account_name}' because historical bank deposits are linked to it. Please deactivate the account instead.",
            ]);
        }

        if ($bankAccount->chartOfAccount && $bankAccount->chartOfAccount->voucherEntries()->exists()) {
            throw ValidationException::withMessages([
                'bank_account' => "Cannot delete bank account '{$bankAccount->bank_name}' because transactions are recorded under its linked ledger account. Please deactivate the account instead.",
            ]);
        }

        $bankAccount->delete();
    }
}
