<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\InventoryStock;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected OverdueService $overdueService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Compile All Real Scoped Metrics for Admin Dashboard.
     */
    public function getDashboardData(int $companyId, ?int $branchId = null): array
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::today()->endOfMonth()->toDateString();

        // 1. KPI Metrics
        $kpis = $this->calculateKpiMetrics($companyId, $branchId, $today, $startOfMonth, $endOfMonth);

        // 2. Module Overviews
        $loanOverview = $this->calculateLoanOverview($companyId, $branchId, $today);
        $collectionOverview = $this->calculateCollectionOverview($companyId, $branchId, $today, $startOfMonth, $endOfMonth);
        $inventoryOverview = $this->calculateInventoryOverview($companyId, $branchId);
        $accountingOverview = $this->calculateAccountingOverview($companyId, $branchId, $today, $startOfMonth);

        // 3. Recent Real Transactions & Activities
        $recentRepayments = $this->getRecentRepayments($companyId, $branchId);
        $recentActivities = $this->getRecentActivities($companyId, $branchId);
        $recentApplications = $this->getRecentApplications($companyId, $branchId);

        // 4. Chart Datasets
        $charts = $this->getChartData($companyId, $branchId, $today);

        // 5. Scoped Branch Information
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $activeBranchName = $branchId ? ($branches->firstWhere('id', $branchId)?->name ?? 'Assigned Branch') : 'All Branches (Consolidated)';

        return compact(
            'kpis',
            'loanOverview',
            'collectionOverview',
            'inventoryOverview',
            'accountingOverview',
            'recentRepayments',
            'recentActivities',
            'recentApplications',
            'charts',
            'branches',
            'activeBranchName',
            'companyId',
            'branchId'
        );
    }

    /**
     * Calculate Core Top-Level KPI Cards.
     */
    protected function calculateKpiMetrics(int $companyId, ?int $branchId, string $today, string $startOfMonth, string $endOfMonth): array
    {
        // Customers Count
        $customerQuery = Customer::where('company_id', $companyId);
        if ($branchId) {
            $customerQuery->where('branch_id', $branchId);
        }
        $totalCustomers = (clone $customerQuery)->count();
        $activeCustomers = (clone $customerQuery)->where('status', 'active')->count();

        // Loans Portfolio
        $loanQuery = LoanAccount::where('company_id', $companyId);
        if ($branchId) {
            $loanQuery->where('branch_id', $branchId);
        }
        $activeLoansCount = (clone $loanQuery)->where('status', 'active')->count();
        $activePortfolioAmount = (float) (clone $loanQuery)->where('status', 'active')->sum('principal_outstanding');
        $totalOutstanding = (float) (clone $loanQuery)->whereIn('status', ['active', 'defaulted'])->sum('total_outstanding');

        // Collections
        $repaymentQuery = LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
        $todayCollection = (float) (clone $repaymentQuery)->whereDate('payment_date', $today)->sum('amount');
        $monthCollection = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');

        // Disbursements
        $todayDisbursement = (float) (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereDate('disbursement_date', $today)
            ->sum('disbursed_amount');
        $todayDisbursedCount = (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereDate('disbursement_date', $today)
            ->count();

        // Pending EMI Due Today
        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
        $todayPendingEmiAmount = (float) (clone $installmentQuery)
            ->whereDate('due_date', $today)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(DB::raw('installment_amount - total_paid'));
        $todayPendingEmiCount = (clone $installmentQuery)
            ->whereDate('due_date', $today)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->count();

        // Overdue Metrics via OverdueService Single Source of Truth
        $parMetrics = $this->overdueService->getBranchParMetrics($companyId, $branchId, $today);
        $totalOverduePrincipal = (float) ($parMetrics['total_overdue_principal'] ?? 0.00);
        $par30Rate = (float) ($parMetrics['par_30_rate'] ?? 0.00);

        // Pending Applications Count
        $appQuery = LoanApplication::where('company_id', $companyId);
        if ($branchId) {
            $appQuery->where('branch_id', $branchId);
        }
        $pendingApplicationsCount = (clone $appQuery)->whereIn('status', ['submitted', 'under_review'])->count();

        // Active Branches Count
        $activeBranchesCount = Branch::where('company_id', $companyId)->where('is_active', true)->count();

        // Vault & Bank Liquidity from Real Database
        $branchVaultQuery = Branch::where('company_id', $companyId)->where('is_active', true);
        if ($branchId) {
            $branchVaultQuery->where('id', $branchId);
        }
        $totalVaultCash = (float) $branchVaultQuery->sum('current_vault_balance');

        // Bank Balance from GL Account 1130 or Bank Accounts
        $bankBalance = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId)->where('status', 'posted');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_code', '1130')->orWhere('account_group', 'bank_accounts');
        })->sum(DB::raw('debit - credit'));

        // YTD Net Profit / Loss from GL Entries (Revenue - Expense)
        $currentFy = FinancialYear::forDate($companyId, Carbon::today());
        $fyStart = $currentFy ? $currentFy->start_date->toDateString() : Carbon::today()->startOfYear()->toDateString();
        
        $ytdRevenue = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fyStart) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereDate('voucher_date', '>=', $fyStart);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'revenue');
        })->sum(DB::raw('credit - debit'));

        $ytdExpense = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fyStart) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereDate('voucher_date', '>=', $fyStart);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'expense');
        })->sum(DB::raw('debit - credit'));

        $ytdProfitLoss = $ytdRevenue - $ytdExpense;

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'active_loans_count' => $activeLoansCount,
            'active_portfolio_amount' => $activePortfolioAmount,
            'total_outstanding' => $totalOutstanding,
            'today_collection' => $todayCollection,
            'month_collection' => $monthCollection,
            'today_disbursement' => $todayDisbursement,
            'today_disbursed_count' => $todayDisbursedCount,
            'today_pending_emi_amount' => max(0.00, $todayPendingEmiAmount),
            'today_pending_emi_count' => $todayPendingEmiCount,
            'total_overdue_principal' => $totalOverduePrincipal,
            'par_30_rate' => $par30Rate,
            'pending_applications_count' => $pendingApplicationsCount,
            'active_branches_count' => $activeBranchesCount,
            'total_vault_cash' => $totalVaultCash,
            'total_bank_balance' => max(0.00, $bankBalance),
            'ytd_profit_loss' => $ytdProfitLoss,
        ];
    }

    /**
     * Calculate Detailed Loan Portfolio Overview.
     */
    protected function calculateLoanOverview(int $companyId, ?int $branchId, string $today): array
    {
        $loanQuery = LoanAccount::where('company_id', $companyId);
        if ($branchId) {
            $loanQuery->where('branch_id', $branchId);
        }

        $activePortfolio = (float) (clone $loanQuery)->where('status', 'active')->sum('principal_outstanding');
        
        $appQuery = LoanApplication::where('company_id', $companyId);
        if ($branchId) {
            $appQuery->where('branch_id', $branchId);
        }
        $pendingApprovals = (clone $appQuery)->whereIn('status', ['submitted', 'under_review'])->count();

        $overduePrincipal = (float) ($this->overdueService->getBranchParMetrics($companyId, $branchId, $today)['total_overdue_principal'] ?? 0.00);

        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
        $todayEmiDue = (float) (clone $installmentQuery)
            ->whereDate('due_date', $today)
            ->sum('installment_amount');

        // Product Breakdown
        $schemes = LoanScheme::where('company_id', $companyId)->get();
        $schemeBreakdown = [];
        $totalSchemePortfolio = 0;

        foreach ($schemes as $scheme) {
            $portfolio = (float) LoanAccount::where('loan_scheme_id', $scheme->id)
                ->where('status', 'active')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('principal_outstanding');
            
            $schemeBreakdown[] = [
                'scheme_name' => $scheme->name,
                'portfolio' => $portfolio,
            ];
            $totalSchemePortfolio += $portfolio;
        }

        // Compute percentages
        foreach ($schemeBreakdown as &$item) {
            $item['percentage'] = $totalSchemePortfolio > 0 ? round(($item['portfolio'] / $totalSchemePortfolio) * 100, 1) : 0;
        }

        return [
            'active_portfolio' => $activePortfolio,
            'pending_approvals' => $pendingApprovals,
            'overdue_principal' => $overduePrincipal,
            'today_emi_due' => $todayEmiDue,
            'product_breakdown' => $schemeBreakdown,
            'total_scheme_portfolio' => $totalSchemePortfolio,
        ];
    }

    /**
     * Calculate Collection Performance Metrics.
     */
    protected function calculateCollectionOverview(int $companyId, ?int $branchId, string $today, string $startOfMonth, string $endOfMonth): array
    {
        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });

        $todayTarget = (float) (clone $installmentQuery)->whereDate('due_date', $today)->sum('installment_amount');

        $repaymentQuery = LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });

        $todayCollected = (float) (clone $repaymentQuery)->whereDate('payment_date', $today)->sum('amount');
        $monthCollected = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');

        $pendingCollection = max(0.00, $todayTarget - $todayCollected);
        $recoveryRate = $todayTarget > 0 ? round(($todayCollected / $todayTarget) * 100, 1) : ($todayCollected > 0 ? 100.0 : 0.0);

        // Payment Method Breakdown
        $cashCollected = (float) (clone $repaymentQuery)->where('payment_method', 'cash')->sum('amount');
        $digitalCollected = (float) (clone $repaymentQuery)->whereIn('payment_method', ['bank_transfer', 'upi', 'cheque'])->sum('amount');
        $totalCollectedAll = $cashCollected + $digitalCollected;

        $cashPct = $totalCollectedAll > 0 ? round(($cashCollected / $totalCollectedAll) * 100, 1) : 0;
        $digitalPct = $totalCollectedAll > 0 ? round(($digitalCollected / $totalCollectedAll) * 100, 1) : 0;

        return [
            'today_target' => $todayTarget,
            'today_collected' => $todayCollected,
            'month_collected' => $monthCollected,
            'pending_collection' => $pendingCollection,
            'recovery_rate' => $recoveryRate,
            'cash_collected' => $cashCollected,
            'digital_collected' => $digitalCollected,
            'cash_percentage' => $cashPct,
            'digital_percentage' => $digitalPct,
        ];
    }

    /**
     * Calculate Inventory & Retail Goods Metrics.
     */
    protected function calculateInventoryOverview(int $companyId, ?int $branchId): array
    {
        $stockQuery = InventoryStock::where('inventory_stocks.company_id', $companyId)
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id');

        if ($branchId) {
            $stockQuery->where('inventory_stocks.branch_id', $branchId);
        }

        $totalStockValue = (float) (clone $stockQuery)->sum(DB::raw('inventory_stocks.current_stock * products.cost_price'));
        $lowStockCount = (clone $stockQuery)->whereColumn('inventory_stocks.current_stock', '<=', 'inventory_stocks.reorder_level')->count();
        $activeProductsCount = Product::where('company_id', $companyId)->where('is_active', true)->count();

        return [
            'total_stock_value' => $totalStockValue,
            'low_stock_count' => $lowStockCount,
            'active_products_count' => $activeProductsCount,
        ];
    }

    /**
     * Calculate General Ledger & Accounting Overview.
     */
    protected function calculateAccountingOverview(int $companyId, ?int $branchId, string $today, string $startOfMonth): array
    {
        $voucherQuery = Voucher::where('company_id', $companyId)->where('status', 'posted');
        if ($branchId) {
            $voucherQuery->where('branch_id', $branchId);
        }

        // Today Income (Credits to Revenue 4xxx)
        $todayIncome = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $today) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereDate('voucher_date', $today);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'revenue');
        })->sum(DB::raw('credit - debit'));

        // Today Expense (Debits to Expense 5xxx)
        $todayExpense = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $today) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereDate('voucher_date', $today);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'expense');
        })->sum(DB::raw('debit - credit'));

        $netCashFlow = $todayIncome - $todayExpense;

        return [
            'today_income' => max(0.00, $todayIncome),
            'today_expense' => max(0.00, $todayExpense),
            'net_cash_flow' => $netCashFlow,
        ];
    }

    /**
     * Fetch Recent Real Loan Repayments / EMI Collections.
     */
    public function getRecentRepayments(int $companyId, ?int $branchId, int $limit = 6)
    {
        return LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })
        ->with(['loanAccount.customer', 'loanAccount.loanScheme'])
        ->orderBy('payment_date', 'desc')
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Fetch Recent Genuine Activity Logs.
     */
    public function getRecentActivities(int $companyId, ?int $branchId, int $limit = 6)
    {
        return ActivityLog::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch Recent Loan Applications.
     */
    public function getRecentApplications(int $companyId, ?int $branchId, int $limit = 5)
    {
        return LoanApplication::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['customer', 'loanScheme'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch Chart Datasets for Trend & Portfolio Charts.
     */
    protected function getChartData(int $companyId, ?int $branchId, string $today): array
    {
        // 1. Last 6 Months Collection Trend
        $months = [];
        $collections = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::today()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth()->toDateString();
            $mEnd = $monthDate->copy()->endOfMonth()->toDateString();
            $label = $monthDate->format('M Y');

            $amount = (float) LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->whereBetween('payment_date', [$mStart, $mEnd])
            ->sum('amount');

            $months[] = $label;
            $collections[] = $amount;
        }

        // 2. Overdue DPD Buckets from OverdueService
        $parMetrics = $this->overdueService->getBranchParMetrics($companyId, $branchId, $today);
        $overdueBuckets = [
            '1-30 Days' => (float) ($parMetrics['par_1_30'] ?? 0.00),
            '31-60 Days' => (float) ($parMetrics['par_31_60'] ?? 0.00),
            '61-90 Days' => (float) ($parMetrics['par_61_90'] ?? 0.00),
            '90+ Days (NPA)' => (float) ($parMetrics['par_90_plus'] ?? 0.00),
        ];

        return [
            'collection_trend' => [
                'labels' => $months,
                'data' => $collections,
            ],
            'overdue_buckets' => $overdueBuckets,
        ];
    }
}
