<?php

namespace App\Services;

use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookAudit;
use App\Models\CashBookCategory;
use App\Models\CashBookDenomination;
use App\Models\CashBookEntry;
use App\Models\CashBookOnlineCollection;
use App\Models\Invoice;
use App\Models\LoanAccount;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashBookService
{
    /**
     * Get or create a Daily Cash Book record for a specific branch and date.
     */
    public function getOrCreateCashBook(int $companyId, int $branchId, string $date, ?int $userId = null): CashBook
    {
        return DB::transaction(function () use ($companyId, $branchId, $date, $userId) {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');

            $cashBook = CashBook::where('branch_id', $branchId)
                ->where('date', $formattedDate)
                ->first();

            $openingBalance = $this->getOpeningBalanceForDate($branchId, $formattedDate);

            if ($cashBook) {
                if ($cashBook->opening_balance != $openingBalance) {
                    $cashBook->update(['opening_balance' => $openingBalance]);
                }

                if ($cashBook->isOpen()) {
                    $this->syncErpTransactions($cashBook);
                } else {
                    $this->recalculateTotals($cashBook);
                }
                return $cashBook->fresh(['entries', 'onlineCollections', 'denominations']);
            }

            $cashBook = CashBook::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'date' => $formattedDate,
                'responsible_staff_id' => $userId,
                'opening_balance' => $openingBalance,
                'status' => 'open',
                'reconciled_status' => 'balanced',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Seed default Received entries (8 exact rows in order)
            $receivedDefaults = [
                ['code' => 'cash_opening_balance', 'particulars' => 'CASH OPENING BALANCE', 'cash' => $openingBalance],
                ['code' => 'weekly_collection', 'particulars' => 'WEEKLY COLLECTION', 'cash' => 0],
                ['code' => 'processing_fee', 'particulars' => 'PROCESSING FEE', 'cash' => 0],
                ['code' => 'insurance_fee', 'particulars' => 'INSURANCE FEE', 'cash' => 0],
                ['code' => 'new_loan_advance', 'particulars' => 'NEW LOAN ADVANCE', 'cash' => 0],
                ['code' => 'pre_payment', 'particulars' => 'PRE PAYMENT', 'cash' => 0],
                ['code' => 'cash_selling', 'particulars' => 'CASH SELLING', 'cash' => 0],
                ['code' => 'od_collection', 'particulars' => 'OD COLLECTION', 'cash' => 0],
            ];

            foreach ($receivedDefaults as $index => $item) {
                CashBookEntry::create([
                    'cash_book_id' => $cashBook->id,
                    'entry_type' => 'received',
                    'category_code' => $item['code'],
                    'particulars' => $item['particulars'],
                    'entry_date' => $formattedDate,
                    'cash_amount' => $item['cash'],
                    'product_amount' => 0.00,
                    'bank_amount' => 0.00,
                    'sort_order' => $index + 1,
                    'created_by' => $userId,
                ]);
            }

            // Seed default Payment entries
            $paymentDefaults = [
                ['code' => 'loan_disbursed', 'particulars' => 'LOAN DISBURSED AMOUNT'],
                ['code' => 'member_no', 'particulars' => 'MEMBER NO'],
                ['code' => 'deposit_to_bank', 'particulars' => 'DEPOSIT TO BANK'],
                ['code' => 'management_expense', 'particulars' => 'MANAGEMENT EXPENSE'],
                ['code' => 'fund_transfer', 'particulars' => 'FUND TRANSFER TO'],
                ['code' => 'miscellaneous', 'particulars' => 'MISCELLANEOUS'],
                ['code' => 'distribution_payment', 'particulars' => 'DISTRIBUTION PAYMENT'],
                ['code' => 'gl_steel_furniture', 'particulars' => 'GL STEEL FURNITURE BY CUSTOMER'],
                ['code' => 'borrower_death', 'particulars' => 'BORROWER DEATH'],
            ];

            foreach ($paymentDefaults as $index => $item) {
                CashBookEntry::create([
                    'cash_book_id' => $cashBook->id,
                    'entry_type' => 'payment',
                    'category_code' => $item['code'],
                    'particulars' => $item['particulars'],
                    'entry_date' => $formattedDate,
                    'cash_amount' => 0.00,
                    'product_amount' => 0.00,
                    'bank_amount' => 0.00,
                    'sort_order' => $index + 1,
                    'created_by' => $userId,
                ]);
            }

            // Seed Denominations (500, 200, 100, 50, 20, 10, 5, 2, 1)
            $denominations = [500, 200, 100, 50, 20, 10, 5, 2, 1];
            foreach ($denominations as $denom) {
                CashBookDenomination::create([
                    'cash_book_id' => $cashBook->id,
                    'denomination' => $denom,
                    'count' => 0,
                    'amount' => 0.00,
                ]);
            }

            // Audit log
            $this->logAudit($cashBook->id, $userId, 'created', ['opening_balance' => $openingBalance], 'Daily Cash Book register created');

            // Sync ERP transactions
            $this->syncErpTransactions($cashBook);

            return $cashBook->fresh(['entries', 'onlineCollections', 'denominations']);
        });
    }

    /**
     * Compute Opening Cash for a date based on previous business day's final physical cash after APPROVED bank deposits.
     */
    public function getOpeningBalanceForDate(int $branchId, string $date): float
    {
        $formattedDate = Carbon::parse($date)->format('Y-m-d');

        $previousCashBook = CashBook::where('branch_id', $branchId)
            ->where('date', '<', $formattedDate)
            ->orderByDesc('date')
            ->first();

        if (!$previousCashBook) {
            return 0.00;
        }

        // Calculate previous day's approved bank deposits
        $prevApprovedDeposits = BankDeposit::where('branch_id', $branchId)
            ->whereDate('deposit_date', $previousCashBook->date->format('Y-m-d'))
            ->where('status', 'approved')
            ->sum('amount');

        // Sum previous day's cash receipts (including opening balance row)
        $prevCashReceived = CashBookEntry::where('cash_book_id', $previousCashBook->id)
            ->where('entry_type', 'received')
            ->sum('cash_amount');

        // Sum previous day's cash payments (excluding deposit_to_bank row to prevent double-deduction)
        $prevCashPayment = CashBookEntry::where('cash_book_id', $previousCashBook->id)
            ->where('entry_type', 'payment')
            ->where('category_code', '!=', 'deposit_to_bank')
            ->sum('cash_amount');

        // Final Physical Cash = prevCashReceived - prevCashPayment - prevApprovedDeposits
        $finalPhysicalCash = max(0, $prevCashReceived - $prevCashPayment - $prevApprovedDeposits);

        return round($finalPhysicalCash, 2);
    }

    /**
     * Recalculate CashBook for a given start date and cascade through all subsequent CashBooks for that branch.
     */
    public function recalculateForDateAndSubsequent(int $branchId, string $startDate): void
    {
        $formattedDate = Carbon::parse($startDate)->format('Y-m-d');

        $cashBooks = CashBook::where('branch_id', $branchId)
            ->where('date', '>=', $formattedDate)
            ->orderBy('date')
            ->get();

        foreach ($cashBooks as $cashBook) {
            $cbDate = $cashBook->date->format('Y-m-d');

            $hasPrevious = CashBook::where('branch_id', $branchId)
                ->where('date', '<', $cbDate)
                ->exists();

            if ($hasPrevious) {
                $newOpening = $this->getOpeningBalanceForDate($branchId, $cbDate);
                $cashBook->opening_balance = $newOpening;
                $cashBook->save();
            }

            if ($cashBook->isOpen()) {
                $this->syncErpTransactions($cashBook);
            } else {
                $this->recalculateTotals($cashBook);
            }
        }
    }

    /**
     * Automatically sync ERP transactions into Cash Book entries for the given date & branch.
     */
    public function syncErpTransactions(CashBook $cashBook): void
    {
        $date = $cashBook->date->format('Y-m-d');
        $branchId = $cashBook->branch_id;

        // Ensure legacy category codes are updated to new exact names
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'card_fee')
            ->update(['category_code' => 'insurance_fee', 'particulars' => 'INSURANCE FEE']);

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'principal_payment')
            ->update(['category_code' => 'pre_payment', 'particulars' => 'PRE PAYMENT']);

        // 1. CASH OPENING BALANCE Row
        $openingEntry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'cash_opening_balance')
            ->first();
        if ($openingEntry) {
            $openingEntry->update(['cash_amount' => $cashBook->opening_balance]);
        }

        // Fetch today's loan repayments for this branch
        $repayments = LoanRepayment::whereDate('payment_date', $date)
            ->whereHas('loanAccount', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with(['loanAccount.customer', 'loanAccount.customerGroup'])
            ->get();

        // 2. WEEKLY COLLECTION Row
        // Regular installment collections (excluding prepayments and overdue/penalty payments)
        $weeklyRepayments = $repayments->reject(function ($r) {
            return ($r->adjustment_mode ?? '') === 'prepayment' || ($r->penalty_paid ?? 0) > 0;
        });

        if ($weeklyRepayments->count() > 0) {
            $weeklyCash = $weeklyRepayments->filter(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');
            $weeklyBank = $weeklyRepayments->reject(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'weekly_collection')
                ->update([
                    'cash_amount' => $weeklyCash,
                    'bank_amount' => $weeklyBank,
                ]);
        }

        // 3. PROCESSING FEE Row
        $disbursements = LoanAccount::whereDate('disbursement_date', $date)
            ->where('branch_id', $branchId)
            ->get();

        $branchLoanIds = LoanAccount::where('branch_id', $branchId)->pluck('id');
        $hasUpfronts = false;
        $upfronts = collect();

        if (Schema::hasTable('loan_upfront_payments')) {
            $upfronts = DB::table('loan_upfront_payments')
                ->whereDate('payment_date', $date)
                ->whereIn('loan_account_id', $branchLoanIds)
                ->get();
            $hasUpfronts = $upfronts->count() > 0;
        }

        if ($disbursements->count() > 0 || $hasUpfronts) {
            $processingFeeCash = $disbursements->sum('processing_fee_amount');
            $processingFeeBank = 0.00;

            if ($hasUpfronts) {
                $processingFeeCash += $upfronts->filter(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum('processing_fee_paid');
                $processingFeeBank += $upfronts->reject(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum('processing_fee_paid');
            }

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'processing_fee')
                ->update([
                    'cash_amount' => $processingFeeCash,
                    'bank_amount' => $processingFeeBank,
                ]);
        }

        // 4. INSURANCE FEE Row
        if ($disbursements->count() > 0 || $hasUpfronts) {
            $insuranceFeeCash = $disbursements->sum('insurance_fee_amount');
            $insuranceFeeBank = 0.00;

            if ($hasUpfronts) {
                $insuranceFeeCash += $upfronts->filter(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum('insurance_fee_paid');
                $insuranceFeeBank += $upfronts->reject(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum('insurance_fee_paid');
            }

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'insurance_fee')
                ->update([
                    'particulars' => 'INSURANCE FEE',
                    'cash_amount' => $insuranceFeeCash,
                    'bank_amount' => $insuranceFeeBank,
                ]);
        }

        // 5. NEW LOAN ADVANCE Row
        if ($hasUpfronts) {
            $newLoanAdvanceCash = $upfronts->filter(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum(fn($u) => max(0, $u->amount - $u->processing_fee_paid - $u->insurance_fee_paid));
            $newLoanAdvanceBank = $upfronts->reject(fn($u) => in_array(strtolower($u->payment_method ?? 'cash'), ['cash', '']))->sum(fn($u) => max(0, $u->amount - $u->processing_fee_paid - $u->insurance_fee_paid));

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'new_loan_advance')
                ->update([
                    'cash_amount' => $newLoanAdvanceCash,
                    'bank_amount' => $newLoanAdvanceBank,
                ]);
        }

        // 6. PRE PAYMENT Row
        $prepaymentRepayments = $repayments->filter(fn($r) => ($r->adjustment_mode ?? '') === 'prepayment');
        if ($prepaymentRepayments->count() > 0) {
            $prepaymentCash = $prepaymentRepayments->filter(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');
            $prepaymentBank = $prepaymentRepayments->reject(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'pre_payment')
                ->update([
                    'particulars' => 'PRE PAYMENT',
                    'cash_amount' => $prepaymentCash,
                    'bank_amount' => $prepaymentBank,
                ]);
        }

        // 7. CASH SELLING Row (Direct Product Sales)
        $directSales = Invoice::whereDate('invoice_date', $date)
            ->where('branch_id', $branchId)
            ->where('invoice_type', 'direct_sale')
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($directSales->count() > 0) {
            $cashSellingCash = $directSales->filter(fn($i) => in_array(strtolower($i->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');
            $cashSellingBank = $directSales->reject(fn($i) => in_array(strtolower($i->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'cash_selling')
                ->update([
                    'cash_amount' => $cashSellingCash,
                    'bank_amount' => $cashSellingBank,
                ]);
        }

        // 8. OD COLLECTION Row
        $odRepayments = $repayments->filter(fn($r) => ($r->penalty_paid ?? 0) > 0);
        if ($odRepayments->count() > 0) {
            $odCash = $odRepayments->filter(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');
            $odBank = $odRepayments->reject(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']))->sum('amount');

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'od_collection')
                ->update([
                    'cash_amount' => $odCash,
                    'bank_amount' => $odBank,
                ]);
        }

        // 9. Payment Section: DEPOSIT TO BANK Row
        $approvedDeposits = BankDeposit::where('branch_id', $branchId)
            ->whereDate('deposit_date', $date)
            ->where('status', 'approved')
            ->sum('amount');

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'deposit_to_bank')
            ->update([
                'cash_amount' => $approvedDeposits,
            ]);

        // 10. Sync Online Collections sub-table
        $onlineRepayments = $repayments->reject(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']));
        foreach ($onlineRepayments as $rep) {
            $customer = $rep->loanAccount->customer ?? null;
            $group = $rep->loanAccount->customerGroup ?? null;

            CashBookOnlineCollection::firstOrCreate(
                [
                    'cash_book_id' => $cashBook->id,
                    'repayment_id' => $rep->id,
                ],
                [
                    'collection_date' => $date,
                    'customer_id' => $customer ? $customer->id : null,
                    'customer_name' => $customer ? $customer->name : 'Customer #' . $rep->loan_account_id,
                    'amount' => $rep->amount,
                    'group_id' => $group ? $group->id : null,
                    'group_name' => $group ? $group->name : null,
                    'mobile_no' => $customer ? $customer->mobile_number : null,
                    'payment_method' => $rep->payment_method ?? 'online',
                    'transaction_reference' => $rep->reference_number,
                    'created_by' => $cashBook->created_by,
                ]
            );
        }

        // Recalculate Cash Book totals
        $this->recalculateTotals($cashBook);
    }

    /**
     * Recalculate Cash Book received/payment totals, closing cash, physical cash, and reconciliation status.
     */
    public function recalculateTotals(CashBook $cashBook): void
    {
        $cashBook->load(['entries', 'denominations']);

        // Sync opening cash entry with cashBook->opening_balance
        $openingEntry = $cashBook->entries->where('category_code', 'cash_opening_balance')->first();
        if ($openingEntry) {
            $openingEntry->cash_amount = $cashBook->opening_balance;
            $openingEntry->save();
        }

        $receivedEntries = $cashBook->entries->where('entry_type', 'received');
        $paymentEntries = $cashBook->entries->where('entry_type', 'payment');

        $totalCashReceived = $receivedEntries->sum('cash_amount');
        $totalProductReceived = $receivedEntries->sum('product_amount');
        $totalBankReceived = $receivedEntries->sum('bank_amount');

        $totalCashPayment = $paymentEntries->sum('cash_amount');
        $totalProductPayment = $paymentEntries->sum('product_amount');
        $totalBankPayment = $paymentEntries->sum('bank_amount');

        // Closing cash = Total Cash Received - Total Cash Payment (since Opening Cash is in Total Cash Received)
        $closingCash = $totalCashReceived - $totalCashPayment;

        // Physical Cash Total = Sum of denominations
        $physicalCash = $cashBook->denominations->sum('amount');
        $cashDifference = $physicalCash - $closingCash;

        $reconciledStatus = 'balanced';
        if ($cashDifference < -0.01) {
            $reconciledStatus = 'cash_short';
        } elseif ($cashDifference > 0.01) {
            $reconciledStatus = 'cash_excess';
        }

        $cashBook->update([
            'total_cash_received' => $totalCashReceived,
            'total_product_received' => $totalProductReceived,
            'total_bank_received' => $totalBankReceived,
            'total_cash_payment' => $totalCashPayment,
            'total_product_payment' => $totalProductPayment,
            'total_bank_payment' => $totalBankPayment,
            'closing_cash' => $closingCash,
            'physical_cash' => $physicalCash,
            'cash_difference' => $cashDifference,
            'reconciled_status' => $reconciledStatus,
        ]);
    }

    /**
     * Add or update an entry item in the Cash Book.
     */
    public function addOrUpdateEntry(CashBook $cashBook, array $data, ?int $userId = null): CashBookEntry
    {
        if ($cashBook->isClosed()) {
            throw new \Exception('Cannot modify a closed Cash Book register.');
        }

        return DB::transaction(function () use ($cashBook, $data, $userId) {
            $entry = null;

            if (isset($data['id']) && $data['id']) {
                $entry = CashBookEntry::where('cash_book_id', $cashBook->id)->where('id', $data['id'])->first();
            }

            if (!$entry && isset($data['category_code']) && $data['category_code']) {
                $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
                    ->where('category_code', $data['category_code'])
                    ->first();
            }

            if ($entry) {
                $oldValues = $entry->only(['cash_amount', 'product_amount', 'bank_amount', 'particulars']);
                $entry->update([
                    'particulars' => $data['particulars'] ?? $entry->particulars,
                    'cash_amount' => $data['cash_amount'] ?? 0.00,
                    'product_amount' => $data['product_amount'] ?? 0.00,
                    'bank_amount' => $data['bank_amount'] ?? 0.00,
                    'remarks' => $data['remarks'] ?? $entry->remarks,
                    'updated_by' => $userId,
                ]);

                $this->logAudit($cashBook->id, $userId, 'entry_updated', [
                    'entry_id' => $entry->id,
                    'old' => $oldValues,
                    'new' => $entry->only(['cash_amount', 'product_amount', 'bank_amount', 'particulars']),
                ]);
            } else {
                $entry = CashBookEntry::create([
                    'cash_book_id' => $cashBook->id,
                    'entry_type' => $data['entry_type'],
                    'category_code' => $data['category_code'] ?? 'other',
                    'particulars' => $data['particulars'],
                    'entry_date' => $cashBook->date->format('Y-m-d'),
                    'cash_amount' => $data['cash_amount'] ?? 0.00,
                    'product_amount' => $data['product_amount'] ?? 0.00,
                    'bank_amount' => $data['bank_amount'] ?? 0.00,
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $this->logAudit($cashBook->id, $userId, 'entry_added', [
                    'entry_id' => $entry->id,
                    'particulars' => $entry->particulars,
                    'cash_amount' => $entry->cash_amount,
                ]);
            }

            $this->recalculateTotals($cashBook);

            return $entry;
        });
    }

    /**
     * Add an Online Collection Details record.
     */
    public function addOnlineCollection(CashBook $cashBook, array $data, ?int $userId = null): CashBookOnlineCollection
    {
        if ($cashBook->isClosed()) {
            throw new \Exception('Cannot modify a closed Cash Book register.');
        }

        return DB::transaction(function () use ($cashBook, $data, $userId) {
            $online = CashBookOnlineCollection::create([
                'cash_book_id' => $cashBook->id,
                'collection_date' => $data['collection_date'] ?? $cashBook->date->format('Y-m-d'),
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'amount' => $data['amount'],
                'group_id' => $data['group_id'] ?? null,
                'group_name' => $data['group_name'] ?? null,
                'mobile_no' => $data['mobile_no'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'online',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->logAudit($cashBook->id, $userId, 'online_collection_added', [
                'customer_name' => $online->customer_name,
                'amount' => $online->amount,
            ]);

            return $online;
        });
    }

    /**
     * Update physical cash denomination counts.
     */
    public function updateDenominations(CashBook $cashBook, array $denominationCounts, ?int $userId = null): void
    {
        if ($cashBook->isClosed()) {
            throw new \Exception('Cannot modify a closed Cash Book register.');
        }

        DB::transaction(function () use ($cashBook, $denominationCounts, $userId) {
            foreach ($denominationCounts as $denom => $count) {
                $denomInt = (int)$denom;
                $countInt = max(0, (int)$count);
                $amount = $denomInt * $countInt;

                CashBookDenomination::updateOrCreate(
                    [
                        'cash_book_id' => $cashBook->id,
                        'denomination' => $denomInt,
                    ],
                    [
                        'count' => $countInt,
                        'amount' => $amount,
                    ]
                );
            }

            $this->recalculateTotals($cashBook);

            $this->logAudit($cashBook->id, $userId, 'denominations_updated', [
                'physical_cash' => $cashBook->physical_cash,
                'difference' => $cashBook->cash_difference,
            ]);
        });
    }

    /**
     * Close the daily cash book register.
     */
    public function closeCashBook(CashBook $cashBook, int $userId, ?string $remarks = null): CashBook
    {
        if ($cashBook->isClosed()) {
            throw new \Exception('Cash Book register is already closed.');
        }

        return DB::transaction(function () use ($cashBook, $userId, $remarks) {
            $this->recalculateTotals($cashBook);

            $cashBook->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $userId,
                'remarks' => $remarks ?? $cashBook->remarks,
            ]);

            $this->logAudit($cashBook->id, $userId, 'closed', [
                'closing_cash' => $cashBook->closing_cash,
                'physical_cash' => $cashBook->physical_cash,
                'cash_difference' => $cashBook->cash_difference,
            ], $remarks ?? 'Daily Cash Book closed successfully');

            return $cashBook;
        });
    }

    /**
     * Reopen a closed cash book register.
     */
    public function reopenCashBook(CashBook $cashBook, int $userId, ?string $reason = null): CashBook
    {
        return DB::transaction(function () use ($cashBook, $userId, $reason) {
            $cashBook->update([
                'status' => 'open',
                'closed_at' => null,
                'closed_by' => null,
            ]);

            $this->logAudit($cashBook->id, $userId, 'reopened', [], $reason ?? 'Cash Book reopened for modifications');

            return $cashBook;
        });
    }

    /**
     * Helper to write audit log.
     */
    private function logAudit(int $cashBookId, ?int $userId, string $action, array $changes = [], ?string $notes = null): void
    {
        CashBookAudit::create([
            'cash_book_id' => $cashBookId,
            'user_id' => $userId,
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
