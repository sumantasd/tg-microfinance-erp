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

            // Audit log
            $this->logAudit($cashBook->id, $userId, 'created', ['opening_balance' => $openingBalance], 'Daily Cash Book register created');

            // Sync ERP transactions
            $this->syncErpTransactions($cashBook);

            return $cashBook->fresh(['entries', 'onlineCollections']);
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

        // Sum previous day's cash payments (excluding deposit_to_bank, member_no, and borrower_death rows)
        $prevCashPayment = CashBookEntry::where('cash_book_id', $previousCashBook->id)
            ->where('entry_type', 'payment')
            ->whereNotIn('category_code', ['deposit_to_bank', 'member_no', 'borrower_death'])
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

        // Delete removed payment category rows if present
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->whereIn('category_code', ['miscellaneous', 'distribution_payment'])
            ->delete();

        // Ensure all 7 exact payment rows exist and have correct sort order
        $paymentOrderMap = [
            'loan_disbursed' => [1, 'LOAN DISBURSED AMOUNT'],
            'member_no' => [2, 'MEMBER NO'],
            'deposit_to_bank' => [3, 'DEPOSIT TO BANK'],
            'management_expense' => [4, 'MANAGEMENT EXPENSE'],
            'fund_transfer' => [5, 'FUND TRANSFER TO'],
            'gl_steel_furniture' => [6, 'GL STEEL FURNITURE BY CUSTOMER'],
            'borrower_death' => [7, 'BORROWER DEATH'],
        ];

        foreach ($paymentOrderMap as $code => $info) {
            CashBookEntry::firstOrCreate(
                [
                    'cash_book_id' => $cashBook->id,
                    'entry_type' => 'payment',
                    'category_code' => $code,
                ],
                [
                    'particulars' => $info[1],
                    'entry_date' => $date,
                    'cash_amount' => 0.00,
                    'product_amount' => 0.00,
                    'bank_amount' => 0.00,
                    'sort_order' => $info[0],
                ]
            );

            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('entry_type', 'payment')
                ->where('category_code', $code)
                ->update(['sort_order' => $info[0], 'particulars' => $info[1]]);
        }

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
            ->whereIn('status', ['active', 'disbursed', 'closed'])
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

        // 9. Payment Section: LOAN DISBURSED Row
        $loanDisbursementRecords = collect();
        if (Schema::hasTable('loan_disbursements')) {
            $loanDisbursementRecords = DB::table('loan_disbursements')
                ->join('loan_accounts', 'loan_disbursements.loan_account_id', '=', 'loan_accounts.id')
                ->where('loan_accounts.branch_id', $branchId)
                ->whereDate('loan_disbursements.disbursement_date', $date)
                ->select('loan_disbursements.*', 'loan_accounts.loan_type')
                ->get();
        }

        $loanDisbursedCash = 0.00;
        $loanDisbursedBank = 0.00;

        if ($loanDisbursementRecords->count() > 0) {
            foreach ($loanDisbursementRecords as $ld) {
                $method = strtolower($ld->payment_method ?? 'cash');
                $amt = (float)$ld->disbursed_amount;
                if (in_array($method, ['cash', ''])) {
                    $loanDisbursedCash += $amt;
                } elseif ($method !== 'product_fulfillment') {
                    $loanDisbursedBank += $amt;
                }
            }
        } else if ($disbursements->count() > 0) {
            foreach ($disbursements as $l) {
                $method = strtolower($l->disbursement_payment_method ?? $l->payment_method ?? 'cash');
                $amt = (float)($l->disbursed_amount > 0 ? $l->disbursed_amount : ($l->sanctioned_amount > 0 ? $l->sanctioned_amount : $l->amount));
                if (in_array($method, ['cash', ''])) {
                    $loanDisbursedCash += $amt;
                } elseif ($method !== 'product_fulfillment') {
                    $loanDisbursedBank += $amt;
                }
            }
        }

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'loan_disbursed')
            ->update([
                'cash_amount' => $loanDisbursedCash,
                'bank_amount' => $loanDisbursedBank,
            ]);

        // 9b. Payment Section: MEMBER NO Row
        $distinctCustomerIds = collect();
        $disbCusts = LoanAccount::whereDate('disbursement_date', $date)
            ->where('branch_id', $branchId)
            ->whereIn('status', ['active', 'disbursed', 'closed'])
            ->whereNotNull('customer_id')
            ->pluck('customer_id');
        $distinctCustomerIds = $distinctCustomerIds->merge($disbCusts);

        if (Schema::hasTable('loan_disbursements')) {
            $disbTableCusts = DB::table('loan_disbursements')
                ->join('loan_accounts', 'loan_disbursements.loan_account_id', '=', 'loan_accounts.id')
                ->where('loan_accounts.branch_id', $branchId)
                ->whereDate('loan_disbursements.disbursement_date', $date)
                ->whereNotNull('loan_accounts.customer_id')
                ->pluck('loan_accounts.customer_id');
            $distinctCustomerIds = $distinctCustomerIds->merge($disbTableCusts);
        }

        $memberCount = $distinctCustomerIds->unique()->filter()->count();

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'member_no')
            ->update([
                'cash_amount' => $memberCount,
                'product_amount' => 0.00,
                'bank_amount' => 0.00,
            ]);

        // 10. Payment Section: DEPOSIT TO BANK Row
        $approvedDeposits = BankDeposit::where('branch_id', $branchId)
            ->whereDate('deposit_date', $date)
            ->where('status', 'approved')
            ->sum('amount');

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'deposit_to_bank')
            ->update([
                'cash_amount' => $approvedDeposits,
            ]);

        // 11. Payment Section: MANAGEMENT EXPENSE Row
        $expenseCash = 0.00;
        $expenseBank = 0.00;

        if (Schema::hasTable('expense_payments')) {
            $payments = DB::table('expense_payments')
                ->join('expenses', 'expense_payments.expense_id', '=', 'expenses.id')
                ->where('expenses.branch_id', $branchId)
                ->whereDate('expense_payments.payment_date', $date)
                ->whereIn('expenses.status', ['APPROVED', 'PAID', 'PARTIALLY_PAID'])
                ->select('expense_payments.*')
                ->get();

            if ($payments->count() > 0) {
                $expenseCash = $payments->filter(fn($p) => in_array(strtolower($p->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');
                $expenseBank = $payments->reject(fn($p) => in_array(strtolower($p->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');
            }
        }

        if ($expenseCash == 0 && $expenseBank == 0 && Schema::hasTable('expenses')) {
            $expenses = DB::table('expenses')
                ->where('branch_id', $branchId)
                ->whereDate('expense_date', $date)
                ->whereIn('status', ['APPROVED', 'PAID', 'PARTIALLY_PAID'])
                ->where('paid_amount', '>', 0)
                ->get();

            if ($expenses->count() > 0) {
                $expenseCash = $expenses->filter(fn($e) => in_array(strtolower($e->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');
                $expenseBank = $expenses->reject(fn($e) => in_array(strtolower($e->payment_method ?? 'cash'), ['cash', '']))->sum('paid_amount');
            }
        }

        if ($expenseCash > 0 || $expenseBank > 0) {
            CashBookEntry::where('cash_book_id', $cashBook->id)
                ->where('category_code', 'management_expense')
                ->update([
                    'cash_amount' => $expenseCash,
                    'bank_amount' => $expenseBank,
                ]);
        }

        // 11b. Payment Section: BORROWER DEATH Row
        $deathForgivenAmount = 0.00;

        if (Schema::hasTable('loan_settlement_requests')) {
            $deathRequests = DB::table('loan_settlement_requests')
                ->where('branch_id', $branchId)
                ->where('request_type', 'borrower_death')
                ->whereIn('status', ['approved', 'completed'])
                ->where(function ($q) use ($date) {
                    $q->whereDate('as_of_date', $date)
                      ->orWhereDate('approved_at', $date);
                })
                ->get();

            if ($deathRequests->count() > 0) {
                $deathForgivenAmount += $deathRequests->sum('discount_concession_amount');
            }
        }

        $closedDeathLoans = LoanAccount::where('branch_id', $branchId)
            ->where('status', 'closed')
            ->where('closure_type', 'borrower_death')
            ->whereDate('closed_at', $date)
            ->get();

        if ($closedDeathLoans->count() > 0) {
            foreach ($closedDeathLoans as $cdl) {
                if (!Schema::hasTable('loan_settlement_requests') || DB::table('loan_settlement_requests')->where('loan_account_id', $cdl->id)->where('request_type', 'borrower_death')->doesntExist()) {
                    $deathForgivenAmount += (float)($cdl->sanctioned_amount ?? $cdl->disbursed_amount ?? 0);
                }
            }
        }

        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'borrower_death')
            ->update([
                'cash_amount' => $deathForgivenAmount,
            ]);

        // 12. Sync Online Collections sub-table (Every individual non-cash transaction remains a separate record)
        $onlineRepayments = $repayments->reject(fn($r) => in_array(strtolower($r->payment_method ?? 'cash'), ['cash', '']));
        $existingRepaymentIds = [];

        foreach ($onlineRepayments as $rep) {
            $customer = $rep->loanAccount->customer ?? null;
            $group = $rep->loanAccount->customerGroup ?? null;
            $customerName = $customer ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: $customer->name) : ('Customer #' . $rep->loan_account_id);

            CashBookOnlineCollection::updateOrCreate(
                [
                    'cash_book_id' => $cashBook->id,
                    'repayment_id' => $rep->id,
                ],
                [
                    'collection_date' => $date,
                    'customer_id' => $customer ? $customer->id : null,
                    'customer_name' => $customerName,
                    'amount' => $rep->amount,
                    'group_id' => $group ? $group->id : null,
                    'group_name' => $group ? $group->name : null,
                    'mobile_no' => $customer ? ($customer->mobile_number ?? $customer->phone ?? null) : null,
                    'payment_method' => $rep->payment_method ?? 'online',
                    'transaction_reference' => $rep->reference_number,
                    'created_by' => $cashBook->created_by,
                ]
            );

            $existingRepaymentIds[] = $rep->id;
        }

        // Clean up online collection records for repayments that no longer exist or changed payment method
        CashBookOnlineCollection::where('cash_book_id', $cashBook->id)
            ->whereNotNull('repayment_id')
            ->whereNotIn('repayment_id', $existingRepaymentIds)
            ->delete();

        // Recalculate Cash Book totals
        $this->recalculateTotals($cashBook);
    }

    /**
     * Recalculate Cash Book received/payment totals, closing cash, physical cash, and reconciliation status.
     */
    public function recalculateTotals(CashBook $cashBook): void
    {
        $cashBook->load(['entries']);

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

        // Exclude non-cash payment categories (member_no and borrower_death) from physical cash deduction
        $cashPaymentEntries = $paymentEntries->reject(fn($e) => in_array($e->category_code, ['member_no', 'borrower_death']));

        $totalCashPayment = $cashPaymentEntries->sum('cash_amount');
        $totalProductPayment = $paymentEntries->sum('product_amount');
        $totalBankPayment = $paymentEntries->sum('bank_amount');

        // Closing cash = Total Cash Received - Total Cash Payment
        $closingCash = $totalCashReceived - $totalCashPayment;

        // Physical Cash counting section is disabled; System Closing Cash is the cash in hand
        $physicalCash = $closingCash;
        $cashDifference = 0.00;
        $reconciledStatus = 'balanced';

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
