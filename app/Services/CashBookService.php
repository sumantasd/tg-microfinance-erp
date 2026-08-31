<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookAudit;
use App\Models\CashBookCategory;
use App\Models\CashBookDenomination;
use App\Models\CashBookEntry;
use App\Models\CashBookOnlineCollection;
use App\Models\LoanAccount;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

            if ($cashBook) {
                // Perform ERP transaction sync if cash book is open
                if ($cashBook->isOpen()) {
                    $this->syncErpTransactions($cashBook);
                }
                return $cashBook;
            }

            // Determine opening balance from previous day's closing balance
            $previousCashBook = CashBook::where('branch_id', $branchId)
                ->where('date', '<', $formattedDate)
                ->orderByDesc('date')
                ->first();

            $openingBalance = $previousCashBook ? $previousCashBook->closing_cash : 0.00;

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

            // Seed default Received entries
            $receivedDefaults = [
                ['code' => 'cash_opening_balance', 'particulars' => 'CASH OPENING BALANCE', 'cash' => $openingBalance],
                ['code' => 'weekly_collection', 'particulars' => 'WEEKLY COLLECTION', 'cash' => 0],
                ['code' => 'processing_fee', 'particulars' => 'PROCESSING FEE', 'cash' => 0],
                ['code' => 'card_fee', 'particulars' => 'CARD FEE', 'cash' => 0],
                ['code' => 'new_loan_advance', 'particulars' => 'NEW LOAN ADVANCE', 'cash' => 0],
                ['code' => 'principal_payment', 'particulars' => 'P. PAYMENT', 'cash' => 0],
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

            // Initial sync with ERP transactions
            $this->syncErpTransactions($cashBook);

            return $cashBook->fresh(['entries', 'onlineCollections', 'denominations']);
        });
    }

    /**
     * Automatically sync ERP transactions into Cash Book entries for the given date & branch.
     */
    public function syncErpTransactions(CashBook $cashBook): void
    {
        $date = $cashBook->date->format('Y-m-d');
        $branchId = $cashBook->branch_id;

        // 1. Weekly Repayment Collections
        $repayments = LoanRepayment::whereDate('payment_date', $date)
            ->whereHas('loanAccount', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->with(['loanAccount.customer', 'loanAccount.customerGroup'])
            ->get();

        $cashCollection = $repayments->whereIn('payment_method', ['cash', 'CASH', null])->sum('amount');
        $bankCollection = $repayments->whereIn('payment_method', ['bank_transfer', 'cheque', 'neft', 'rtgs'])->sum('amount');

        // Update Weekly Collection Entry
        $weeklyEntry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'weekly_collection')
            ->first();

        if ($weeklyEntry && $weeklyEntry->cash_amount == 0 && $weeklyEntry->bank_amount == 0) {
            $weeklyEntry->update([
                'cash_amount' => $cashCollection,
                'bank_amount' => $bankCollection,
            ]);
        }

        // Sync Online Collections sub-table
        $onlineRepayments = $repayments->whereIn('payment_method', ['upi', 'online', 'qr', 'card', 'bank_transfer', 'neft']);
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

        // 2. Loan Disbursements
        $disbursements = LoanAccount::whereDate('disbursement_date', $date)
            ->where('branch_id', $branchId)
            ->get();

        $disbursedCash = $disbursements->sum('disbursed_amount');
        $disbursedBank = 0.00;

        $disburseEntry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'loan_disbursed')
            ->first();

        if ($disburseEntry && $disburseEntry->cash_amount == 0 && $disburseEntry->bank_amount == 0) {
            $disburseEntry->update([
                'cash_amount' => $disbursedCash,
                'bank_amount' => $disbursedBank,
            ]);
        }

        // 3. Processing Fee & Upfront Payments
        $processingFeeTotal = $disbursements->sum('processing_fee_amount');
        $processingEntry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'processing_fee')
            ->first();

        if ($processingEntry && $processingEntry->cash_amount == 0) {
            $processingEntry->update([
                'cash_amount' => $processingFeeTotal,
            ]);
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

        // Update opening cash entry amount if present
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

        // Closing cash = Total Cash Received - Total Cash Payment (since Opening Cash is included in Total Cash Received)
        $hasOpeningEntry = $openingEntry !== null;
        $closingCash = $hasOpeningEntry 
            ? ($totalCashReceived - $totalCashPayment) 
            : ($cashBook->opening_balance + $totalCashReceived - $totalCashPayment);

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
