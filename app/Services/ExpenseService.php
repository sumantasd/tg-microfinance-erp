<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseApprovalSetting;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Generate unique Expense Number: EXP-YYYY-XXXXXX
     */
    public function generateExpenseNumber(int $companyId): string
    {
        $year = date('Y');
        $prefix = "EXP-{$year}-";

        $lastNumber = Expense::where('company_id', $companyId)
            ->where('expense_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('expense_number');

        if ($lastNumber) {
            $seq = (int) substr($lastNumber, strlen($prefix)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Payment Number: EXPPAY-YYYY-XXXXXX
     */
    public function generatePaymentNumber(): string
    {
        $year = date('Y');
        $prefix = "EXPPAY-{$year}-";

        $lastNumber = ExpensePayment::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('payment_number');

        if ($lastNumber) {
            $seq = (int) substr($lastNumber, strlen($prefix)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string)$seq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create Expense Record.
     */
    public function createExpense(array $data, User $user, ?UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($data, $user, $attachment) {
            $companyId = $data['company_id'] ?? ($user->company_id ?? 1);
            $branchId = (int) $data['branch_id'];

            // Guard: Branch non-warehouse check
            $branch = Branch::findOrFail($branchId);
            if (!empty($branch->is_warehouse)) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Central Warehouse cannot be selected as a retail expense branch.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            $taxAmount = round((float) ($data['tax_amount'] ?? 0), 2);
            $totalAmount = round($amount + $taxAmount, 2);

            $expenseNumber = $this->generateExpenseNumber($companyId);

            $expense = Expense::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'expense_number' => $expenseNumber,
                'expense_date' => $data['expense_date'] ?? now()->toDateString(),
                'expense_category_id' => $data['expense_category_id'],
                'supplier_id' => !empty($data['supplier_id']) ? $data['supplier_id'] : null,
                'payee_name' => $data['payee_name'] ?? null,
                'description' => $data['description'],
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'outstanding_amount' => $totalAmount,
                'status' => Expense::STATUS_DRAFT,
                'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
                'requested_by' => $data['requested_by'] ?? $user->id,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            if ($attachment) {
                $this->storeAttachment($expense, $attachment, $user);
            }

            // Auto-submit if requested
            if (!empty($data['auto_submit'])) {
                $this->submitExpense($expense, $user);
            }

            $this->activityLogService->log('expense_created', $expense, null, [
                'expense_number' => $expense->expense_number,
                'total_amount' => $expense->total_amount,
            ]);

            return $expense;
        });
    }

    /**
     * Update Expense Record.
     */
    public function updateExpense(Expense $expense, array $data, User $user, ?UploadedFile $attachment = null): Expense
    {
        if (!$expense->isEditable()) {
            throw ValidationException::withMessages([
                'status' => "Cannot edit expense '{$expense->expense_number}' in status '{$expense->status}'. Only DRAFT or REJECTED expenses can be edited.",
            ]);
        }

        return DB::transaction(function () use ($expense, $data, $user, $attachment) {
            $branchId = (int) ($data['branch_id'] ?? $expense->branch_id);
            $branch = Branch::findOrFail($branchId);
            if (!empty($branch->is_warehouse)) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Central Warehouse cannot be selected as a retail expense branch.',
                ]);
            }

            $amount = round((float) ($data['amount'] ?? $expense->amount), 2);
            $taxAmount = round((float) ($data['tax_amount'] ?? $expense->tax_amount), 2);
            $totalAmount = round($amount + $taxAmount, 2);

            $oldValues = $expense->toArray();

            $expense->update([
                'branch_id' => $branchId,
                'expense_date' => $data['expense_date'] ?? $expense->expense_date,
                'expense_category_id' => $data['expense_category_id'] ?? $expense->expense_category_id,
                'supplier_id' => !empty($data['supplier_id']) ? $data['supplier_id'] : null,
                'payee_name' => $data['payee_name'] ?? $expense->payee_name,
                'description' => $data['description'] ?? $expense->description,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'outstanding_amount' => $totalAmount - $expense->paid_amount,
                'requested_by' => $data['requested_by'] ?? $expense->requested_by,
                'reference_number' => $data['reference_number'] ?? $expense->reference_number,
                'notes' => $data['notes'] ?? $expense->notes,
                'updated_by' => $user->id,
            ]);

            if ($attachment) {
                $this->storeAttachment($expense, $attachment, $user);
            }

            $this->activityLogService->log('expense_updated', $expense, $oldValues, $expense->fresh()->toArray());

            return $expense;
        });
    }

    /**
     * Submit Expense for Approval (DRAFT -> PENDING_APPROVAL).
     */
    public function submitExpense(Expense $expense, User $user): Expense
    {
        if (!$expense->isSubmittable()) {
            throw ValidationException::withMessages([
                'status' => "Expense '{$expense->expense_number}' is not in DRAFT status.",
            ]);
        }

        $oldStatus = $expense->status;
        $expense->update([
            'status' => Expense::STATUS_PENDING_APPROVAL,
            'updated_by' => $user->id,
        ]);

        $this->activityLogService->log('expense_submitted', $expense, ['status' => $oldStatus], ['status' => Expense::STATUS_PENDING_APPROVAL]);

        return $expense;
    }

    /**
     * Approve Expense.
     */
    public function approveExpense(Expense $expense, User $user): Expense
    {
        if (!$expense->isApprovable()) {
            throw ValidationException::withMessages([
                'status' => "Expense '{$expense->expense_number}' is not PENDING APPROVAL.",
            ]);
        }

        // Check configurable approval limits if present
        $setting = ExpenseApprovalSetting::where('company_id', $expense->company_id)
            ->where('min_amount', '<=', $expense->total_amount)
            ->where(function ($q) use ($expense) {
                $q->whereNull('max_amount')
                  ->orWhere('max_amount', '>=', $expense->total_amount);
            })
            ->first();

        if ($setting && !empty($setting->required_role_or_permission)) {
            $req = $setting->required_role_or_permission;
            if (!$user->hasRole($req) && !$user->hasPermissionTo($req) && !$user->hasRole('Super Admin') && !$user->hasRole('Admin')) {
                throw ValidationException::withMessages([
                    'approval' => "You do not meet the required approval authority ('{$req}') for expenses of ₹" . number_format($expense->total_amount, 2),
                ]);
            }
        }

        $oldStatus = $expense->status;
        $expense->update([
            'status' => Expense::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'updated_by' => $user->id,
        ]);

        $this->activityLogService->log('expense_approved', $expense, ['status' => $oldStatus], [
            'status' => Expense::STATUS_APPROVED,
            'approved_by' => $user->id,
        ]);

        return $expense;
    }

    /**
     * Reject Expense.
     */
    public function rejectExpense(Expense $expense, User $user, string $reason): Expense
    {
        if (!$expense->isApprovable()) {
            throw ValidationException::withMessages([
                'status' => "Expense '{$expense->expense_number}' is not PENDING APPROVAL.",
            ]);
        }

        $oldStatus = $expense->status;
        $expense->update([
            'status' => Expense::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'updated_by' => $user->id,
        ]);

        $this->activityLogService->log('expense_rejected', $expense, ['status' => $oldStatus], [
            'status' => Expense::STATUS_REJECTED,
            'reason' => $reason,
        ]);

        return $expense;
    }

    /**
     * Record Expense Payment (Full or Partial).
     * Enforces Row Locking, Overpayment Prevention & Double-Entry Accounting Voucher posting.
     */
    public function recordPayment(Expense $expense, array $paymentData, User $user, ?UploadedFile $attachment = null): ExpensePayment
    {
        return DB::transaction(function () use ($expense, $paymentData, $user, $attachment) {
            // Pessimistic Row Lock on Expense
            $lockedExpense = Expense::where('id', $expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedExpense->isPayable()) {
                throw ValidationException::withMessages([
                    'status' => "Expense '{$lockedExpense->expense_number}' in status '{$lockedExpense->status}' cannot receive payment.",
                ]);
            }

            $paidAmount = round((float) ($paymentData['paid_amount'] ?? 0), 2);
            if ($paidAmount <= 0) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $outstanding = round((float) $lockedExpense->outstanding_amount, 2);
            if ($paidAmount > $outstanding + 0.001) {
                throw ValidationException::withMessages([
                    'paid_amount' => "Payment amount (₹" . number_format($paidAmount, 2) . ") cannot exceed the outstanding amount (₹" . number_format($outstanding, 2) . ").",
                ]);
            }

            $paymentDate = $paymentData['payment_date'] ?? now()->toDateString();
            $paymentMethod = $paymentData['payment_method'] ?? 'cash';
            $bankAccountId = !empty($paymentData['bank_account_id']) ? (int)$paymentData['bank_account_id'] : null;

            // Resolve Payment Account for GL
            $creditGlAccount = $this->accountingService->resolvePaymentMethodAccount(
                $lockedExpense->company_id,
                $lockedExpense->branch_id,
                $paymentMethod
            );

            // Resolve Category GL Account for Expense Debit
            $category = $lockedExpense->category;
            $debitGlAccount = $category?->chartOfAccount;
            if (!$debitGlAccount) {
                // Fallback to General Expense COA (5300 or 5000)
                $debitGlAccount = \App\Models\ChartOfAccount::where('company_id', $lockedExpense->company_id)
                    ->whereIn('account_code', ['5300', '5000'])
                    ->first();
            }

            if (!$debitGlAccount) {
                $this->accountingService->seedDefaultChartOfAccounts($lockedExpense->company_id);
                $debitGlAccount = \App\Models\ChartOfAccount::where('company_id', $lockedExpense->company_id)
                    ->whereIn('account_code', ['5300', '5000'])
                    ->first();
            }

            // Post Double-Entry Journal Voucher (PV Payment Voucher)
            $voucherData = [
                'company_id' => $lockedExpense->company_id,
                'branch_id' => $lockedExpense->branch_id,
                'voucher_type' => 'payment',
                'voucher_date' => $paymentDate,
                'narration' => "Expense payment for {$lockedExpense->expense_number} ({$category->category_name})",
                'reference_type' => 'expense_payment',
                'reference_id' => $lockedExpense->id,
            ];

            $voucherEntries = [
                [
                    'account_id' => $debitGlAccount->id,
                    'debit' => $paidAmount,
                    'credit' => 0.00,
                    'description' => "Expense: {$lockedExpense->description}",
                ],
                [
                    'account_id' => $creditGlAccount->id,
                    'debit' => 0.00,
                    'credit' => $paidAmount,
                    'description' => "Paid via " . ucfirst(str_replace('_', ' ', $paymentMethod)),
                ],
            ];

            $voucher = $this->accountingService->createVoucher($voucherData, $voucherEntries, true);

            $paymentNumber = $this->generatePaymentNumber();

            $payment = ExpensePayment::create([
                'expense_id' => $lockedExpense->id,
                'payment_number' => $paymentNumber,
                'payment_date' => $paymentDate,
                'paid_amount' => $paidAmount,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'chart_of_account_id' => $creditGlAccount->id,
                'voucher_id' => $voucher->id,
                'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'paid_by' => $user->id,
            ]);

            if ($attachment) {
                $attachRecord = $this->storeAttachment($lockedExpense, $attachment, $user, $payment);
                $payment->update(['attachment_path' => $attachRecord->file_path]);
            }

            // Update Expense totals and status
            $newPaidAmount = round((float) $lockedExpense->paid_amount + $paidAmount, 2);
            $newOutstanding = round((float) $lockedExpense->total_amount - $newPaidAmount, 2);
            if ($newOutstanding < 0) {
                $newOutstanding = 0.00;
            }

            $newStatus = ($newOutstanding <= 0) ? Expense::STATUS_PAID : Expense::STATUS_PARTIALLY_PAID;
            $newPaymentStatus = ($newOutstanding <= 0) ? Expense::PAYMENT_STATUS_PAID : Expense::PAYMENT_STATUS_PARTIALLY_PAID;

            $lockedExpense->update([
                'paid_amount' => $newPaidAmount,
                'outstanding_amount' => $newOutstanding,
                'status' => $newStatus,
                'payment_status' => $newPaymentStatus,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log('expense_paid', $lockedExpense, null, [
                'payment_number' => $payment->payment_number,
                'paid_amount' => $paidAmount,
                'remaining_outstanding' => $newOutstanding,
                'voucher_number' => $voucher->voucher_number,
            ]);

            return $payment;
        });
    }

    /**
     * Cancel Expense Record & Reverse Vouchers if paid.
     */
    public function cancelExpense(Expense $expense, User $user, string $reason): Expense
    {
        if (!$expense->isCancellable()) {
            throw ValidationException::withMessages([
                'status' => "Expense '{$expense->expense_number}' is already CANCELLED.",
            ]);
        }

        return DB::transaction(function () use ($expense, $user, $reason) {
            $lockedExpense = Expense::where('id', $expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Reverse all associated payment accounting vouchers
            foreach ($lockedExpense->payments as $payment) {
                if ($payment->voucher && $payment->voucher->status === 'posted') {
                    $this->accountingService->reverseVoucher(
                        $payment->voucher,
                        "Reversal due to cancellation of Expense #{$lockedExpense->expense_number}. Reason: {$reason}"
                    );
                }
            }

            $oldStatus = $lockedExpense->status;
            $lockedExpense->update([
                'status' => Expense::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log('expense_cancelled', $lockedExpense, ['status' => $oldStatus], [
                'status' => Expense::STATUS_CANCELLED,
                'reason' => $reason,
            ]);

            return $lockedExpense;
        });
    }

    /**
     * Hard Delete Expense (only DRAFT without payments).
     */
    public function deleteExpense(Expense $expense, User $user): bool
    {
        if (!$expense->isDeletable()) {
            throw ValidationException::withMessages([
                'status' => "Expense '{$expense->expense_number}' cannot be deleted because it is in status '{$expense->status}' or has recorded payments.",
            ]);
        }

        return DB::transaction(function () use ($expense, $user) {
            // Delete attachments
            foreach ($expense->attachments as $att) {
                Storage::delete($att->file_path);
                $att->delete();
            }

            $this->activityLogService->log('expense_deleted', $expense, $expense->toArray(), null);

            return $expense->delete();
        });
    }

    /**
     * Upload Attachment securely.
     */
    public function storeAttachment(Expense $expense, UploadedFile $file, User $user, ?ExpensePayment $payment = null): ExpenseAttachment
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $forbiddenExts = ['php', 'phtml', 'exe', 'bat', 'sh', 'js', 'py', 'pl', 'cgi', 'dll', 'com'];
        if (in_array($ext, $forbiddenExts)) {
            throw ValidationException::withMessages([
                'attachment' => 'Forbidden file format. Executable files are not allowed.',
            ]);
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        if (!in_array($ext, $allowedExts)) {
            throw ValidationException::withMessages([
                'attachment' => "File type '.{$ext}' is not supported. Allowed formats: PDF, JPG, PNG, DOC, XLS.",
            ]);
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            throw ValidationException::withMessages([
                'attachment' => 'File size exceeds maximum limit of 10MB.',
            ]);
        }

        $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . time() . '.' . $ext;
        $filePath = $file->storeAs('expenses/' . date('Y/m'), $fileName, 'private');

        return ExpenseAttachment::create([
            'expense_id' => $expense->id,
            'expense_payment_id' => $payment?->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath ?: "expenses/" . date('Y/m') . "/{$fileName}",
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);
    }
}
