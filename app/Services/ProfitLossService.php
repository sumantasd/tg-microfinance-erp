<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\LoanAccount;
use App\Models\ProductLoanMargin;
use App\Models\Sale;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Calculate cost, selling price, and potential margin for a Product Loan.
     * Rule: Margin = Selling Price (Product Price / Sanctioned + Down Payment) - Cost Price (COGS)
     */
    public function calculateProductLoanMargin(LoanAccount $loan): array
    {
        if ($loan->loan_type !== 'product') {
            return [
                'cost_price_total' => 0.00,
                'selling_price_total' => 0.00,
                'margin_amount' => 0.00,
            ];
        }

        $application = $loan->application;
        $totalCost = 0.00;
        $totalSelling = 0.00;

        if ($application && $application->products->count() > 0) {
            foreach ($application->products as $item) {
                $product = $item->product;
                $qty = (int) ($item->quantity ?: 1);
                $costPrice = (float) ($product?->cost_price ?? 0.00);
                $unitPrice = (float) ($item->unit_price_snapshot ?: ($product?->unit_price ?? 0.00));

                $totalCost += ($qty * $costPrice);
                $totalSelling += ($qty * $unitPrice);
            }
        }

        $sanctionedPrincipal = (float) $loan->sanctioned_amount;
        $downPayment = (float) $loan->down_payment_amount;
        $productPrice = (float) $loan->product_price_amount;

        $effectiveSellingPrice = $productPrice > 0 ? $productPrice : ($sanctionedPrincipal + $downPayment);
        if ($effectiveSellingPrice <= 0 && $totalSelling > 0) {
            $effectiveSellingPrice = $totalSelling;
        }

        $margin = max(0.00, round($effectiveSellingPrice - $totalCost, 2));

        return [
            'cost_price_total' => round($totalCost, 2),
            'selling_price_total' => round($effectiveSellingPrice, 2),
            'margin_amount' => $margin,
        ];
    }

    /**
     * Record Pending Margin for a Product Loan upon sanction/disbursement.
     * Status remains 'pending' while the loan is active/partially repaid.
     */
    public function recordPendingMargin(LoanAccount $loan): ?ProductLoanMargin
    {
        if ($loan->loan_type !== 'product') {
            return null;
        }

        $calc = $this->calculateProductLoanMargin($loan);

        $initialStatus = 'pending';
        $finalizedAt = null;
        $finalizedBy = null;

        if ($loan->status === 'closed' && $loan->closure_type !== 'write_off') {
            $initialStatus = 'finalized';
            $finalizedAt = $loan->closed_at ?: now();
            $finalizedBy = $loan->closure_approved_by ?: Auth::id();
        } elseif (in_array($loan->status, ['cancelled'])) {
            $initialStatus = 'cancelled';
        }

        return ProductLoanMargin::updateOrCreate(
            ['loan_account_id' => $loan->id],
            [
                'company_id' => $loan->company_id,
                'branch_id' => $loan->branch_id,
                'cost_price_total' => $calc['cost_price_total'],
                'selling_price_total' => $calc['selling_price_total'],
                'margin_amount' => $calc['margin_amount'],
                'status' => $initialStatus,
                'finalized_at' => $finalizedAt,
                'finalized_by' => $finalizedBy,
            ]
        );
    }

    /**
     * Idempotently finalize product loan margin upon legitimate full loan closure.
     * Transfers margin from Pending Profit to Final Profit exactly once.
     */
    public function finalizeProductLoanMargin(LoanAccount $loan, ?int $userId = null): ?ProductLoanMargin
    {
        if ($loan->loan_type !== 'product') {
            return null;
        }

        // Must be closed and NOT a write-off or cancellation
        if ($loan->status !== 'closed' || $loan->closure_type === 'write_off') {
            return null;
        }

        $margin = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
        if (!$margin) {
            $margin = $this->recordPendingMargin($loan);
        }

        if (!$margin) {
            return null;
        }

        // Idempotency: If already finalized, do not re-finalize or double count
        if ($margin->status === 'finalized') {
            return $margin;
        }

        $margin->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => $userId ?: (Auth::id() ?? $loan->closure_approved_by),
        ]);

        $this->activityLogService->log('product_loan_margin_finalized', $loan, [
            'margin_amount' => $margin->margin_amount,
            'cost_price' => $margin->cost_price_total,
            'selling_price' => $margin->selling_price_total,
        ]);

        return $margin->fresh();
    }

    /**
     * Safely handle loan reversal / reopening.
     * Reverts margin back to pending or reversed.
     */
    public function reverseProductLoanMargin(LoanAccount $loan, ?int $userId = null, ?string $reason = null): ?ProductLoanMargin
    {
        if ($loan->loan_type !== 'product') {
            return null;
        }

        $margin = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
        if (!$margin) {
            return null;
        }

        $newStatus = ($loan->status === 'cancelled' || $loan->closure_type === 'write_off') ? 'cancelled' : 'pending';

        $margin->update([
            'status' => $newStatus,
            'reversed_at' => now(),
            'reversed_by' => $userId ?: Auth::id(),
            'notes' => $reason ?? 'Loan status reverted or reopened',
        ]);

        $this->activityLogService->log('product_loan_margin_reversed', $loan, [
            'previous_status' => 'finalized',
            'new_status' => $margin->status,
            'reason' => $reason,
        ]);

        return $margin->fresh();
    }

    /**
     * Synchronize margin records for all existing product loans for a company.
     * Ensures historical and existing loans have complete and accurate margin tracking.
     */
    public function syncProductLoanMargins(int $companyId, ?int $branchId = null): void
    {
        $loans = LoanAccount::with(['application.products.product'])
            ->where('company_id', $companyId)
            ->where('loan_type', 'product')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        foreach ($loans as $loan) {
            $existing = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
            if (!$existing) {
                $this->recordPendingMargin($loan);
            } elseif ($loan->status === 'closed' && $loan->closure_type !== 'write_off' && $existing->status !== 'finalized') {
                $this->finalizeProductLoanMargin($loan);
            } elseif (($loan->status === 'cancelled' || $loan->closure_type === 'write_off') && $existing->status !== 'cancelled') {
                $existing->update(['status' => 'cancelled']);
            }
        }
    }

    /**
     * Calculate Direct Product Sales Gross Profit using actual sale revenue and inventory purchase cost/COGS.
     */
    public function getDirectSalesProfit(int $companyId, ?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = Sale::with(['items.product', 'customer', 'branch', 'invoice'])
            ->where('company_id', $companyId)
            ->whereDoesntHave('invoice', fn($q) => $q->where('status', 'cancelled'))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($fromDate, fn($q) => $q->whereDate('sale_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->whereDate('sale_date', '<=', $toDate));

        $sales = $query->orderBy('sale_date', 'desc')->get();

        $totalRevenue = 0.00;
        $totalCogs = 0.00;
        $totalGrossProfit = 0.00;
        $salesDetails = [];

        foreach ($sales as $sale) {
            // Net selling price before tax = subtotal - discount
            $saleRevenue = max(0.00, round((float) $sale->subtotal - (float) $sale->discount_amount, 2));
            $saleCogs = 0.00;

            foreach ($sale->items as $item) {
                $costPrice = (float) ($item->product?->cost_price ?? 0.00);
                $itemCogs = round($item->quantity * $costPrice, 2);
                $saleCogs += $itemCogs;
            }

            $saleGrossProfit = round($saleRevenue - $saleCogs, 2);

            $totalRevenue += $saleRevenue;
            $totalCogs += $saleCogs;
            $totalGrossProfit += $saleGrossProfit;

            $salesDetails[] = [
                'id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'sale_date' => $sale->sale_date ? $sale->sale_date->format('Y-m-d') : 'N/A',
                'customer_name' => $sale->customer_name ?: ($sale->customer?->full_name ?? 'Walk-in'),
                'branch_name' => $sale->branch?->name ?? 'Head Office',
                'items_count' => $sale->items->count(),
                'net_revenue' => $saleRevenue,
                'cogs' => $saleCogs,
                'gross_profit' => $saleGrossProfit,
                'payment_status' => $sale->payment_status,
                'paid_amount' => (float) $sale->paid_amount,
                'balance_amount' => (float) $sale->balance_amount,
            ];
        }

        return [
            'total_sales_revenue' => round($totalRevenue, 2),
            'total_cogs' => round($totalCogs, 2),
            'total_gross_profit' => round($totalGrossProfit, 2),
            'sales_count' => count($sales),
            'sales_list' => $salesDetails,
        ];
    }

    /**
     * Calculate Cash Loan Revenue & Business Expenses from Posted Accounting Entries (GL).
     * Excludes principal disbursements from expenses and principal repayments from income.
     */
    public function getAccountingRevenueAndExpenses(int $companyId, ?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $baseQuery = VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fromDate, $toDate) {
            $q->where('company_id', $companyId)->where('status', 'posted');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
            if ($fromDate) {
                $q->whereDate('voucher_date', '>=', $fromDate);
            }
            if ($toDate) {
                $q->whereDate('voucher_date', '<=', $toDate);
            }
        });

        // 1. REVENUE (4xxx)
        // Grouped by account code to ensure strict classification
        $revenueAccounts = ChartOfAccount::where('company_id', $companyId)
            ->where('account_type', 'revenue')
            ->get();

        $interestCashLoan = 0.00;
        $interestProductLoan = 0.00;
        $processingFees = 0.00;
        $insuranceFees = 0.00;
        $penaltyIncome = 0.00;
        $foreclosureFees = 0.00;
        $otherOperatingRevenue = 0.00;
        $revenueDetails = [];

        foreach ($revenueAccounts as $acc) {
            // Skip 4310 (Product Loan Retail Sales) to prevent double counting with Product Loan Margins
            if ($acc->account_code === '4310' || $acc->account_code === '4300') {
                continue;
            }

            $amount = (float) (clone $baseQuery)->where('account_id', $acc->id)
                ->selectRaw('SUM(credit - debit) as net')
                ->value('net');

            $amount = round($amount, 2);
            if ($amount != 0) {
                $revenueDetails[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $amount,
                ];
            }

            match ($acc->account_code) {
                '4110' => $interestCashLoan += $amount,
                '4120' => $interestProductLoan += $amount,
                '4210' => $processingFees += $amount,
                '4220' => $insuranceFees += $amount,
                '4230' => $penaltyIncome += $amount,
                '4240' => $foreclosureFees += $amount,
                default => $otherOperatingRevenue += $amount,
            };
        }

        $totalCashLoanRevenue = round(
            $interestCashLoan + $processingFees + $insuranceFees + $penaltyIncome + $foreclosureFees,
            2
        );

        // 2. EXPENSES (5xxx)
        $expenseAccounts = ChartOfAccount::where('company_id', $companyId)
            ->where('account_type', 'expense')
            ->get();

        $personnelExpense = 0.00;
        $rentExpense = 0.00;
        $utilitiesExpense = 0.00;
        $stationeryExpense = 0.00;
        $travelExpense = 0.00;
        $bankChargesExpense = 0.00;
        $writeOffExpense = 0.00;
        $otherExpenses = 0.00;
        $expenseDetails = [];

        foreach ($expenseAccounts as $acc) {
            // Skip 5110 (COGS Product Loans) to avoid double-counting with Product Loan Margins
            if ($acc->account_code === '5110') {
                continue;
            }

            $amount = (float) (clone $baseQuery)->where('account_id', $acc->id)
                ->selectRaw('SUM(debit - credit) as net')
                ->value('net');

            $amount = round($amount, 2);
            if ($amount != 0) {
                $expenseDetails[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $amount,
                ];
            }

            match ($acc->account_code) {
                '5210', '5220' => $personnelExpense += $amount,
                '5310' => $rentExpense += $amount,
                '5320' => $utilitiesExpense += $amount,
                '5330' => $stationeryExpense += $amount,
                '5340' => $travelExpense += $amount,
                '5350' => $bankChargesExpense += $amount,
                '5120' => $writeOffExpense += $amount,
                default => $otherExpenses += $amount,
            };
        }

        $totalOperatingExpenses = round(
            $personnelExpense + $rentExpense + $utilitiesExpense + $stationeryExpense +
            $travelExpense + $bankChargesExpense + $writeOffExpense + $otherExpenses,
            2
        );

        return [
            'interest_cash_loan' => $interestCashLoan,
            'interest_product_loan' => $interestProductLoan,
            'processing_fees' => $processingFees,
            'insurance_fees' => $insuranceFees,
            'penalty_income' => $penaltyIncome,
            'foreclosure_fees' => $foreclosureFees,
            'other_operating_revenue' => round($otherOperatingRevenue, 2),
            'total_cash_loan_revenue' => $totalCashLoanRevenue,
            'revenue_details' => $revenueDetails,

            'personnel_expense' => $personnelExpense,
            'rent_expense' => $rentExpense,
            'utilities_expense' => $utilitiesExpense,
            'stationery_expense' => $stationeryExpense,
            'travel_expense' => $travelExpense,
            'bank_charges_expense' => $bankChargesExpense,
            'write_off_expense' => $writeOffExpense,
            'other_expenses' => round($otherExpenses, 2),
            'total_operating_expenses' => $totalOperatingExpenses,
            'expense_details' => $expenseDetails,
        ];
    }

    /**
     * Fetch Product Loan Margins categorized into Pending and Finalized with Drill-Down Details.
     */
    public function getProductLoanMarginsSummary(int $companyId, ?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        // First sync to guarantee historical records are accurately accounted for
        $this->syncProductLoanMargins($companyId, $branchId);

        $baseQuery = ProductLoanMargin::with(['loanAccount.customer', 'loanAccount.application.products.product', 'branch', 'finalizer'])
            ->where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        // 1. Pending Product Loan Margins (Unrealized)
        $pendingQuery = (clone $baseQuery)->where('status', 'pending');
        if ($fromDate) {
            $pendingQuery->whereHas('loanAccount', fn($q) => $q->whereDate('sanction_date', '>=', $fromDate));
        }
        if ($toDate) {
            $pendingQuery->whereHas('loanAccount', fn($q) => $q->whereDate('sanction_date', '<=', $toDate));
        }
        $pendingMargins = $pendingQuery->get();
        $pendingTotal = round($pendingMargins->sum('margin_amount'), 2);

        // 2. Finalized Product Loan Margins (Realized)
        $finalizedQuery = (clone $baseQuery)->where('status', 'finalized');
        if ($fromDate) {
            $finalizedQuery->whereDate('finalized_at', '>=', $fromDate);
        }
        if ($toDate) {
            $finalizedQuery->whereDate('finalized_at', '<=', $toDate);
        }
        $finalizedMargins = $finalizedQuery->get();
        $finalizedTotal = round($finalizedMargins->sum('margin_amount'), 2);

        // 3. Total Generated Margin = Pending + Finalized (Zero double-counting)
        $totalGeneratedMargin = round($pendingTotal + $finalizedTotal, 2);

        $formatDetails = function ($marginsCollection) {
            return $marginsCollection->map(function (ProductLoanMargin $m) {
                $loan = $m->loanAccount;
                $productNames = $loan?->application?->products?->map(fn($p) => $p->product_name_snapshot ?: ($p->product?->name ?? 'Product'))->join(', ') ?: 'Product';

                return [
                    'id' => $m->id,
                    'loan_id' => $loan?->id,
                    'loan_number' => $loan?->loan_number ?? 'N/A',
                    'customer_name' => $loan?->customer?->full_name ?? 'N/A',
                    'branch_name' => $m->branch?->name ?? 'Head Office',
                    'products' => $productNames,
                    'cost_price' => (float) $m->cost_price_total,
                    'selling_price' => (float) $m->selling_price_total,
                    'margin_amount' => (float) $m->margin_amount,
                    'status' => $m->status,
                    'sanction_date' => $loan?->sanction_date ? $loan->sanction_date->format('Y-m-d') : 'N/A',
                    'closed_at' => $loan?->closed_at ? $loan->closed_at->format('Y-m-d') : ($m->finalized_at ? $m->finalized_at->format('Y-m-d') : 'N/A'),
                    'finalized_by' => $m->finalizer?->name ?? 'System',
                ];
            })->all();
        };

        return [
            'pending_profit' => $pendingTotal,
            'final_profit' => $finalizedTotal,
            'total_generated_margin' => $totalGeneratedMargin,
            'pending_count' => $pendingMargins->count(),
            'finalized_count' => $finalizedMargins->count(),
            'pending_list' => $formatDetails($pendingMargins),
            'finalized_list' => $formatDetails($finalizedMargins),
        ];
    }

    /**
     * Compute Comprehensive Enterprise Profit & Loss Statement.
     */
    public function getComprehensiveProfitLoss(int $companyId, ?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        // 1. Product Loan Margins
        $productLoanSummary = $this->getProductLoanMarginsSummary($companyId, $branchId, $fromDate, $toDate);

        // 2. Direct Sales Profit
        $directSalesSummary = $this->getDirectSalesProfit($companyId, $branchId, $fromDate, $toDate);

        // 3. Cash Loan Revenue & Business Expenses from GL
        $accountingSummary = $this->getAccountingRevenueAndExpenses($companyId, $branchId, $fromDate, $toDate);

        // 4. Recognized Operating Income
        // Recognized Income = Finalized Product Margin + Direct Sales Gross Profit + Cash Loan Revenue + Interest on Product Loans + Other Operating Revenue
        // NOTE: Pending Product Margin is specifically kept UNREALIZED and NOT included in recognized Net Profit.
        $totalRecognizedIncome = round(
            $productLoanSummary['final_profit'] +
            $directSalesSummary['total_gross_profit'] +
            $accountingSummary['total_cash_loan_revenue'] +
            $accountingSummary['interest_product_loan'] +
            $accountingSummary['other_operating_revenue'],
            2
        );

        // 5. Total Expenses
        $totalExpenses = $accountingSummary['total_operating_expenses'];

        // 6. Realized Net Profit / (Loss)
        $netProfitOrLoss = round($totalRecognizedIncome - $totalExpenses, 2);

        return [
            'product_loan' => $productLoanSummary,
            'direct_sales' => $directSalesSummary,
            'accounting' => $accountingSummary,

            // Core KPI Indicators
            'pending_product_profit' => $productLoanSummary['pending_profit'],
            'final_product_profit' => $productLoanSummary['final_profit'],
            'total_generated_margin' => $productLoanSummary['total_generated_margin'],
            'direct_sales_gross_profit' => $directSalesSummary['total_gross_profit'],
            'cash_loan_revenue' => $accountingSummary['total_cash_loan_revenue'],
            'interest_product_loan' => $accountingSummary['interest_product_loan'],
            'other_operating_revenue' => $accountingSummary['other_operating_revenue'],
            'total_recognized_income' => $totalRecognizedIncome,
            'total_operating_expenses' => $totalExpenses,
            'net_profit_or_loss' => $netProfitOrLoss,

            'summary' => [
                'net_profit_loss' => $netProfitOrLoss,
                'pending_margin' => $productLoanSummary['pending_profit'],
                'final_margin' => $productLoanSummary['final_profit'],
                'total_generated_margin' => $productLoanSummary['total_generated_margin'],
                'direct_sales_gross_profit' => $directSalesSummary['total_gross_profit'],
                'cash_loan_revenue' => $accountingSummary['total_cash_loan_revenue'],
                'total_recognized_income' => $totalRecognizedIncome,
                'total_operating_expenses' => $totalExpenses,
            ],

            'filters' => [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ];
    }
}
