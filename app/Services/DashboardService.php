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
        protected AccountingService $accountingService,
        protected ProfitLossService $profitLossService
    ) {}

    /**
     * Resolve and validate a dashboard reporting date range.
     * Presets: 'today', 'yesterday', 'this_month', 'this_year', 'custom'
     * Enforces Asia/Kolkata business timezone and company Financial Year rules.
     */
    public function resolveDateRange(
        int $companyId,
        ?string $preset = 'today',
        ?string $fromDate = null,
        ?string $toDate = null,
        ?Carbon $refNow = null
    ): array {
        $timezone = config('app.business_timezone', 'Asia/Kolkata');
        $now = $refNow ? $refNow->copy()->setTimezone($timezone) : Carbon::now($timezone);
        $today = $now->toDateString();

        $preset = strtolower(trim((string) ($preset ?: 'today')));
        if (!in_array($preset, ['today', 'yesterday', 'this_month', 'this_year', 'custom'], true)) {
            $preset = 'today';
        }

        $validationError = null;
        $resolvedFrom = $today;
        $resolvedTo = $today;
        $label = 'Today';
        $rangeText = $now->format('d M Y');

        switch ($preset) {
            case 'today':
                $resolvedFrom = $today;
                $resolvedTo = $today;
                $label = 'Today';
                $rangeText = $now->format('d M Y');
                break;

            case 'yesterday':
                $yesterday = $now->copy()->subDay();
                $resolvedFrom = $yesterday->toDateString();
                $resolvedTo = $yesterday->toDateString();
                $label = 'Yesterday';
                $rangeText = $yesterday->format('d M Y');
                break;

            case 'this_month':
                $resolvedFrom = $now->copy()->startOfMonth()->toDateString();
                $resolvedTo = $today;
                $label = 'This Month';
                $rangeText = Carbon::parse($resolvedFrom)->format('d M Y') . ' – ' . $now->format('d M Y');
                break;

            case 'this_year':
                $currentFy = FinancialYear::forDate($companyId, $now);
                if ($currentFy) {
                    $fyStart = $currentFy->start_date->toDateString();
                    $fyEnd = $currentFy->end_date->toDateString();
                    $fyTitle = $currentFy->title;
                } else {
                    $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
                    $fyEndYear = $fyStartYear + 1;
                    $fyStart = "{$fyStartYear}-04-01";
                    $fyEnd = "{$fyEndYear}-03-31";
                    $fyTitle = "FY {$fyStartYear}-" . substr((string) $fyEndYear, 2);
                }
                $resolvedFrom = $fyStart;
                $resolvedTo = min($fyEnd, $today);
                $label = 'This Year (' . $fyTitle . ')';
                $rangeText = Carbon::parse($resolvedFrom)->format('d M Y') . ' – ' . Carbon::parse($resolvedTo)->format('d M Y');
                break;

            case 'custom':
                $fromParsed = null;
                $toParsed = null;

                if (!empty($fromDate)) {
                    try {
                        $fromParsed = Carbon::createFromFormat('Y-m-d', substr(trim($fromDate), 0, 10), $timezone)->startOfDay();
                    } catch (\Throwable $e) {
                        try {
                            $fromParsed = Carbon::parse($fromDate, $timezone)->startOfDay();
                        } catch (\Throwable $e2) {
                            $fromParsed = null;
                        }
                    }
                }

                if (!empty($toDate)) {
                    try {
                        $toParsed = Carbon::createFromFormat('Y-m-d', substr(trim($toDate), 0, 10), $timezone)->startOfDay();
                    } catch (\Throwable $e) {
                        try {
                            $toParsed = Carbon::parse($toDate, $timezone)->startOfDay();
                        } catch (\Throwable $e2) {
                            $toParsed = null;
                        }
                    }
                }

                if (!$fromParsed && !$toParsed) {
                    $resolvedFrom = $today;
                    $resolvedTo = $today;
                    $preset = 'today';
                    $label = 'Today';
                    $rangeText = $now->format('d M Y');
                    $validationError = 'Custom dates were not provided. Defaulting to Today.';
                } elseif ($fromParsed && !$toParsed) {
                    $resolvedFrom = $fromParsed->toDateString();
                    $resolvedTo = $today;
                    if ($resolvedFrom > $resolvedTo) {
                        $resolvedTo = $resolvedFrom;
                    }
                    $label = 'Custom Range';
                    $rangeText = $fromParsed->format('d M Y') . ' – ' . Carbon::parse($resolvedTo)->format('d M Y');
                } elseif (!$fromParsed && $toParsed) {
                    $resolvedTo = $toParsed->toDateString();
                    $resolvedFrom = $toParsed->copy()->startOfMonth()->toDateString();
                    $label = 'Custom Range';
                    $rangeText = Carbon::parse($resolvedFrom)->format('d M Y') . ' – ' . $toParsed->format('d M Y');
                } else {
                    if ($fromParsed->gt($toParsed)) {
                        $validationError = 'Start date cannot be after end date (' . $fromParsed->format('d M Y') . ' > ' . $toParsed->format('d M Y') . '). Displaying selected single day.';
                        $resolvedFrom = $toParsed->toDateString();
                        $resolvedTo = $toParsed->toDateString();
                        $label = 'Custom Range (Adjusted)';
                        $rangeText = $toParsed->format('d M Y');
                    } else {
                        $resolvedFrom = $fromParsed->toDateString();
                        $resolvedTo = $toParsed->toDateString();
                        $label = 'Custom Range';
                        $rangeText = $fromParsed->format('d M Y') . ' – ' . $toParsed->format('d M Y');
                    }
                }
                break;
        }

        return [
            'preset' => $preset,
            'from_date' => $resolvedFrom,
            'to_date' => $resolvedTo,
            'label' => $label,
            'range_text' => $rangeText,
            'is_custom' => $preset === 'custom',
            'validation_error' => $validationError,
            'now' => $now,
            'today' => $today,
        ];
    }

    /**
     * Compile All Real Scoped Metrics for Admin and Branch Manager Dashboard.
     */
    public function getDashboardData(
        int $companyId,
        ?int $branchId = null,
        bool $canViewProfitLoss = false,
        bool $canViewBankBalance = true,
        bool $isBranchManager = false,
        ?Carbon $todayDate = null,
        ?string $dateFilter = 'today',
        ?string $customFrom = null,
        ?string $customTo = null
    ): array {
        $range = $this->resolveDateRange($companyId, $dateFilter, $customFrom, $customTo, $todayDate);
        $fromDate = $range['from_date'];
        $toDate = $range['to_date'];
        $dateFilter = $range['preset'];
        $periodLabel = $range['label'];
        $periodRangeText = $range['range_text'];
        $dateFilterError = $range['validation_error'];
        $now = $range['now'];
        $today = $range['today'];
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // 1. KPI Metrics
        $kpis = $this->calculateKpiMetrics(
            $companyId,
            $branchId,
            $today,
            $fromDate,
            $toDate,
            $startOfMonth,
            $endOfMonth,
            $canViewProfitLoss,
            $canViewBankBalance,
            $now,
            $dateFilter,
            $periodLabel,
            $periodRangeText
        );

        // 2. Module Overviews
        $loanOverview = $this->calculateLoanOverview($companyId, $branchId, $today, $fromDate, $toDate);
        $collectionOverview = $this->calculateCollectionOverview(
            $companyId,
            $branchId,
            $today,
            $fromDate,
            $toDate,
            $periodLabel,
            $periodRangeText,
            $startOfMonth,
            $endOfMonth
        );
        $inventoryOverview = $this->calculateInventoryOverview($companyId, $branchId);
        $accountingOverview = $canViewProfitLoss ? $this->calculateAccountingOverview($companyId, $branchId, $fromDate, $toDate) : null;

        // 3. Recent Real Transactions & Activities
        $recentRepayments = $this->getRecentRepayments($companyId, $branchId, 6, $fromDate, $toDate);
        $recentActivities = $this->getRecentActivities($companyId, $branchId);
        $recentApplications = $this->getRecentApplications($companyId, $branchId);

        // 4. Chart Datasets
        $charts = $this->getChartData($companyId, $branchId, $today, $now);

        // 5. Scoped Branch Information
        if ($isBranchManager && $branchId > 0) {
            $branches = Branch::where('company_id', $companyId)->where('id', $branchId)->where('is_active', true)->get();
            $activeBranchName = $branches->first()?->name ?? 'Assigned Branch';
        } elseif ($isBranchManager && $branchId <= 0) {
            $branches = collect();
            $activeBranchName = 'Unassigned Branch (Restricted)';
        } else {
            $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
            $activeBranchName = ($branchId && $branchId > 0)
                ? ($branches->firstWhere('id', $branchId)?->name ?? 'Assigned Branch')
                : 'All Branches (Consolidated)';
        }

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
            'branchId',
            'canViewProfitLoss',
            'canViewBankBalance',
            'dateFilter',
            'fromDate',
            'toDate',
            'periodLabel',
            'periodRangeText',
            'dateFilterError'
        );
    }

    /**
     * Calculate Core Top-Level KPI Cards.
     */
    protected function calculateKpiMetrics(
        int $companyId,
        ?int $branchId,
        string $today,
        string $fromDate,
        string $toDate,
        string $startOfMonth,
        string $endOfMonth,
        bool $canViewProfitLoss = false,
        bool $canViewBankBalance = true,
        ?Carbon $todayDate = null,
        ?string $dateFilter = 'today',
        ?string $periodLabel = 'Today',
        ?string $periodRangeText = ''
    ): array {
        // Customers Count (Lifetime registered and current active)
        $customerQuery = Customer::where('company_id', $companyId);
        if ($branchId !== null) {
            $customerQuery->where('branch_id', $branchId);
        }
        $totalCustomers = (clone $customerQuery)->count();
        $activeCustomers = (clone $customerQuery)->where('status', 'active')->count();
        $periodRegisteredMembers = (clone $customerQuery)->whereBetween('registration_date', [$fromDate, $toDate])->count();

        // Loans Portfolio (Current Active Portfolio Position)
        $loanQuery = LoanAccount::where('company_id', $companyId);
        if ($branchId !== null) {
            $loanQuery->where('branch_id', $branchId);
        }
        $activeLoansCount = (clone $loanQuery)->where('status', 'active')->count();
        $activePortfolioAmount = (float) (clone $loanQuery)->where('status', 'active')->sum('principal_outstanding');
        $totalOutstanding = (float) (clone $loanQuery)->whereIn('status', ['active', 'defaulted'])->sum('total_outstanding');

        // Collections (Period-Sensitive)
        $repaymentQuery = LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
        });
        $periodCollection = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$fromDate, $toDate])->sum('amount');
        $periodCollectionCount = (clone $repaymentQuery)->whereBetween('payment_date', [$fromDate, $toDate])->count();
        $todayCollection = (float) (clone $repaymentQuery)->whereDate('payment_date', $today)->sum('amount');
        $monthCollection = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');

        // Disbursements (Period-Sensitive)
        $periodDisbursement = (float) (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereBetween('disbursement_date', [$fromDate, $toDate])
            ->sum('disbursed_amount');
        $periodDisbursedCount = (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereBetween('disbursement_date', [$fromDate, $toDate])
            ->count();
        $todayDisbursement = (float) (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereDate('disbursement_date', $today)
            ->sum('disbursed_amount');
        $todayDisbursedCount = (clone $loanQuery)
            ->whereIn('status', ['active', 'closed', 'defaulted'])
            ->whereDate('disbursement_date', $today)
            ->count();

        // Pending EMI Due in Selected Period (Restricted to active/defaulted loans)
        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
            $q->whereIn('status', ['active', 'defaulted']);
        });
        $periodPendingEmiAmount = (float) (clone $installmentQuery)
            ->whereBetween('due_date', [$fromDate, $toDate])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum(DB::raw('installment_amount - total_paid'));
        $periodPendingEmiCount = (clone $installmentQuery)
            ->whereBetween('due_date', [$fromDate, $toDate])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->count();

        // Overdue Metrics via OverdueService Single Source of Truth (Current Delinquent Position)
        $parMetrics = $this->overdueService->getBranchParMetrics(
            $companyId,
            $branchId !== null ? $branchId : null,
            $today
        );
        $totalOverdueAmount = (float) ($parMetrics['total_overdue_amount'] ?? 0.00);
        $par30Principal = (float) ($parMetrics['par_30_amount'] ?? 0.00);
        $par30Rate = (float) ($parMetrics['par_30_pct'] ?? 0.00);

        // Pending Applications Count
        $appQuery = LoanApplication::where('company_id', $companyId);
        if ($branchId !== null) {
            $appQuery->where('branch_id', $branchId);
        }
        $pendingApplicationsCount = (clone $appQuery)->whereIn('status', ['submitted', 'under_review'])->count();

        // Active Branches Count
        $activeBranchesCount = Branch::where('company_id', $companyId)->where('is_active', true)->count();

        // Vault Cash from Real Database (Current Balance)
        $branchVaultQuery = Branch::where('company_id', $companyId)->where('is_active', true);
        if ($branchId !== null) {
            $branchVaultQuery->where('id', $branchId);
        }
        $totalVaultCash = (float) $branchVaultQuery->sum('current_vault_balance');

        // Bank Balance from GL Account 1130 or Bank Accounts (Current Ledger Balance)
        $bankBalance = null;
        $periodBankMovement = 0.00;
        if ($canViewBankBalance) {
            $bankBalance = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)->where('status', 'posted');
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
            })->whereHas('account', function ($q) {
                $q->where('account_code', '1130')->orWhere('account_group', 'bank_accounts');
            })->sum(DB::raw('debit - credit'));
            $bankBalance = max(0.00, $bankBalance);

            // Net Bank Account Inflow/Outflow during selected period
            $periodBankMovement = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fromDate, $toDate) {
                $q->where('company_id', $companyId)->where('status', 'posted')->whereBetween('voucher_date', [$fromDate, $toDate]);
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
            })->whereHas('account', function ($q) {
                $q->where('account_code', '1130')->orWhere('account_group', 'bank_accounts');
            })->sum(DB::raw('debit - credit'));
        }

        // Comprehensive Profit / Loss (Admin-Only, strictly suppressed for Branch Managers)
        $periodProfitLoss = null;
        $ytdProfitLoss = null;
        $pendingProfit = null;
        $finalProfit = null;

        if ($canViewProfitLoss) {
            // 1. Selected Period P&L
            $pnlData = $this->profitLossService->getComprehensiveProfitLoss(
                $companyId,
                $branchId !== null && $branchId > 0 ? $branchId : null,
                $fromDate,
                $toDate
            );

            $periodProfitLoss = (float) ($pnlData['net_profit_or_loss'] ?? $pnlData['summary']['net_profit_loss'] ?? 0.00);
            $pendingProfit = (float) ($pnlData['pending_product_profit'] ?? $pnlData['summary']['pending_margin'] ?? 0.00);
            $finalProfit = (float) ($pnlData['final_product_profit'] ?? $pnlData['summary']['final_margin'] ?? 0.00);

            // 2. Full Financial Year YTD P&L
            $refDate = $todayDate ?? Carbon::today();
            $currentFy = FinancialYear::forDate($companyId, $refDate);
            if ($currentFy) {
                $fyStart = $currentFy->start_date->toDateString();
            } else {
                $fyStartYear = $refDate->month >= 4 ? $refDate->year : $refDate->year - 1;
                $fyStart = "{$fyStartYear}-04-01";
            }

            if ($fromDate === $fyStart && $toDate === $today) {
                $ytdProfitLoss = $periodProfitLoss;
            } else {
                $ytdPnl = $this->profitLossService->getComprehensiveProfitLoss(
                    $companyId,
                    $branchId !== null && $branchId > 0 ? $branchId : null,
                    $fyStart,
                    $today
                );
                $ytdProfitLoss = (float) ($ytdPnl['net_profit_or_loss'] ?? $ytdPnl['summary']['net_profit_loss'] ?? 0.00);
            }
        }

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'period_registered_members' => $periodRegisteredMembers,
            'active_loans_count' => $activeLoansCount,
            'active_portfolio_amount' => $activePortfolioAmount,
            'total_outstanding' => $totalOutstanding,
            'today_collection' => $periodCollection,
            'period_collection' => $periodCollection,
            'period_collection_count' => $periodCollectionCount,
            'daily_collection' => $todayCollection,
            'month_collection' => $monthCollection,
            'today_disbursement' => $periodDisbursement,
            'period_disbursement' => $periodDisbursement,
            'today_disbursed_count' => $periodDisbursedCount,
            'period_disbursed_count' => $periodDisbursedCount,
            'daily_disbursement' => $todayDisbursement,
            'daily_disbursed_count' => $todayDisbursedCount,
            'today_pending_emi_amount' => max(0.00, $periodPendingEmiAmount),
            'today_pending_emi_count' => $periodPendingEmiCount,
            'period_pending_emi_amount' => max(0.00, $periodPendingEmiAmount),
            'period_pending_emi_count' => $periodPendingEmiCount,
            'total_overdue_principal' => $totalOverdueAmount,
            'par_30_amount' => $par30Principal,
            'par_30_rate' => $par30Rate,
            'pending_applications_count' => $pendingApplicationsCount,
            'active_branches_count' => $activeBranchesCount,
            'total_vault_cash' => $totalVaultCash,
            'total_bank_balance' => $bankBalance,
            'period_bank_movement' => $periodBankMovement,
            'period_profit_loss' => $periodProfitLoss,
            'ytd_profit_loss' => $ytdProfitLoss,
            'pending_profit' => $pendingProfit,
            'final_profit' => $finalProfit,
            'date_filter' => $dateFilter,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'period_label' => $periodLabel,
            'period_range_text' => $periodRangeText,
        ];
    }

    /**
     * Calculate Detailed Loan Portfolio Overview.
     */
    protected function calculateLoanOverview(int $companyId, ?int $branchId, string $today, ?string $fromDate = null, ?string $toDate = null): array
    {
        $loanQuery = LoanAccount::where('company_id', $companyId);
        if ($branchId !== null) {
            $loanQuery->where('branch_id', $branchId);
        }

        $activePortfolio = (float) (clone $loanQuery)->where('status', 'active')->sum('principal_outstanding');

        $appQuery = LoanApplication::where('company_id', $companyId);
        if ($branchId !== null) {
            $appQuery->where('branch_id', $branchId);
        }
        $pendingApprovals = (clone $appQuery)->whereIn('status', ['submitted', 'under_review'])->count();

        $parMetrics = $this->overdueService->getBranchParMetrics($companyId, $branchId !== null ? $branchId : null, $today);
        $overduePrincipal = (float) ($parMetrics['total_overdue_amount'] ?? 0.00);

        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
            $q->whereIn('status', ['active', 'defaulted']);
        });
        $todayEmiDue = (float) (clone $installmentQuery)
            ->whereBetween('due_date', [$fromDate ?? $today, $toDate ?? $today])
            ->sum('installment_amount');

        // Product Breakdown
        $schemes = LoanScheme::where('company_id', $companyId)->get();
        $schemeBreakdown = [];
        $totalSchemePortfolio = 0;

        foreach ($schemes as $scheme) {
            $portfolio = (float) LoanAccount::where('loan_scheme_id', $scheme->id)
                ->where('status', 'active')
                ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
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
    protected function calculateCollectionOverview(
        int $companyId,
        ?int $branchId,
        string $today,
        string $fromDate,
        string $toDate,
        string $periodLabel,
        string $periodRangeText,
        string $startOfMonth,
        string $endOfMonth
    ): array {
        $installmentQuery = LoanInstallment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
            $q->whereIn('status', ['active', 'defaulted']);
        });

        $periodTarget = (float) (clone $installmentQuery)->whereBetween('due_date', [$fromDate, $toDate])->sum('installment_amount');

        $repaymentQuery = LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
        });

        $periodCollected = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$fromDate, $toDate])->sum('amount');
        $monthCollected = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->sum('amount');

        $pendingCollection = max(0.00, $periodTarget - $periodCollected);
        $recoveryRate = $periodTarget > 0 ? round(($periodCollected / $periodTarget) * 100, 1) : ($periodCollected > 0 ? 100.0 : 0.0);

        // Payment Method Breakdown for Period
        $cashCollected = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$fromDate, $toDate])->where('payment_method', 'cash')->sum('amount');
        $digitalCollected = (float) (clone $repaymentQuery)->whereBetween('payment_date', [$fromDate, $toDate])->whereIn('payment_method', ['bank_transfer', 'upi', 'cheque', 'neft', 'rtgs'])->sum('amount');
        $totalCollectedAll = $cashCollected + $digitalCollected;

        $cashPct = $totalCollectedAll > 0 ? round(($cashCollected / $totalCollectedAll) * 100, 1) : 0;
        $digitalPct = $totalCollectedAll > 0 ? round(($digitalCollected / $totalCollectedAll) * 100, 1) : 0;

        return [
            'today_target' => $periodTarget,
            'today_collected' => $periodCollected,
            'period_target' => $periodTarget,
            'period_collected' => $periodCollected,
            'month_collected' => $monthCollected,
            'pending_collection' => $pendingCollection,
            'recovery_rate' => $recoveryRate,
            'cash_collected' => $cashCollected,
            'digital_collected' => $digitalCollected,
            'cash_percentage' => $cashPct,
            'digital_percentage' => $digitalPct,
            'period_label' => $periodLabel,
            'period_range_text' => $periodRangeText,
        ];
    }

    /**
     * Calculate Inventory & Retail Goods Metrics.
     */
    protected function calculateInventoryOverview(int $companyId, ?int $branchId): array
    {
        $stockQuery = InventoryStock::where('inventory_stocks.company_id', $companyId)
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id');

        if ($branchId !== null) {
            $stockQuery->where('inventory_stocks.branch_id', $branchId);
        }

        $totalStockValue = (float) (clone $stockQuery)->sum(DB::raw('CASE WHEN inventory_stocks.current_stock > 0 THEN inventory_stocks.current_stock * products.cost_price ELSE 0 END'));
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
    protected function calculateAccountingOverview(int $companyId, ?int $branchId, string $fromDate, string $toDate): array
    {
        $voucherQuery = Voucher::where('company_id', $companyId)->where('status', 'posted');
        if ($branchId !== null) {
            $voucherQuery->where('branch_id', $branchId);
        }

        // Period Income (Credits to Revenue 4xxx)
        $todayIncome = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fromDate, $toDate) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereBetween('voucher_date', [$fromDate, $toDate]);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'revenue')->orWhere('account_code', 'like', '4%');
        })->sum(DB::raw('credit - debit'));

        // Period Expense (Debits to Expense 5xxx)
        $todayExpense = (float) VoucherEntry::whereHas('voucher', function ($q) use ($companyId, $branchId, $fromDate, $toDate) {
            $q->where('company_id', $companyId)->where('status', 'posted')->whereBetween('voucher_date', [$fromDate, $toDate]);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
        })->whereHas('account', function ($q) {
            $q->where('account_type', 'expense')->orWhere('account_code', 'like', '5%');
        })->sum(DB::raw('debit - credit'));

        $operatingMargin = $todayIncome - $todayExpense;

        return [
            'today_income' => max(0.00, $todayIncome),
            'today_expense' => max(0.00, $todayExpense),
            'operating_margin' => $operatingMargin,
        ];
    }

    /**
     * Fetch Recent Real Loan Repayments / EMI Collections.
     */
    public function getRecentRepayments(int $companyId, ?int $branchId, int $limit = 6, ?string $fromDate = null, ?string $toDate = null)
    {
        $baseQuery = LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId !== null) {
                $q->where('branch_id', $branchId);
            }
        });

        if ($fromDate && $toDate) {
            $periodItems = (clone $baseQuery)->whereBetween('payment_date', [$fromDate, $toDate])
                ->with(['loanAccount.customer', 'loanAccount.loanScheme'])
                ->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            if ($periodItems->isNotEmpty()) {
                return $periodItems;
            }
        }

        return $baseQuery
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
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
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
            ->when($branchId !== null, fn($q) => $q->where('branch_id', $branchId))
            ->with(['customer', 'loanScheme'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch Chart Datasets for Trend & Portfolio Charts.
     */
    protected function getChartData(int $companyId, ?int $branchId, string $today, ?Carbon $todayDate = null): array
    {
        $baseDate = $todayDate ?? Carbon::now(config('app.business_timezone', 'Asia/Kolkata'))->startOfDay();

        // 1. Last 6 Months Collection Trend
        $months = [];
        $collections = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $baseDate->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth()->toDateString();
            $mEnd = $monthDate->copy()->endOfMonth()->toDateString();
            $label = $monthDate->format('M Y');

            $amount = (float) LoanRepayment::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId);
                if ($branchId !== null) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->whereBetween('payment_date', [$mStart, $mEnd])
            ->sum('amount');

            $months[] = $label;
            $collections[] = $amount;
        }

        // 2. Overdue DPD Buckets from OverdueService
        $parMetrics = $this->overdueService->getBranchParMetrics($companyId, $branchId !== null ? $branchId : null, $today);
        $aging = $parMetrics['aging_breakdown'] ?? [];
        $overdueBuckets = [
            '1-30 Days' => (float) ($aging['1_30']['overdue'] ?? 0.00),
            '31-60 Days' => (float) ($aging['31_60']['overdue'] ?? 0.00),
            '61-90 Days' => (float) ($aging['61_90']['overdue'] ?? 0.00),
            '90+ Days (NPA)' => (float) ($aging['90_plus']['overdue'] ?? 0.00),
        ];

        return [
            'collection_trend' => [
                'labels' => $months,
                'data' => $collections,
            ],
            'overdue_buckets' => $overdueBuckets,
            'par_30_amount' => (float) ($parMetrics['par_30_amount'] ?? 0.00),
            'par_30_pct' => (float) ($parMetrics['par_30_pct'] ?? 0.00),
        ];
    }
}
