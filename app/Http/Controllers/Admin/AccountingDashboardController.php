<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Voucher;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountingDashboardController extends Controller
{
    public function __construct(protected AccountingService $accountingService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $companyId = $user && !$user->isSuperAdmin() ? $user->company_id : ($request->input('company_id') ?: Company::first()->id ?? 1);

        // Auto-seed standard accounts if empty for this company
        if (ChartOfAccount::where('company_id', $companyId)->count() === 0) {
            $this->accountingService->seedDefaultChartOfAccounts($companyId);
        }

        $branchId = $user && $user->branch_id && !$user->isCompanyAdmin() ? $user->branch_id : $request->input('branch_id');

        $voucherQuery = Voucher::where('company_id', $companyId);
        if ($branchId) {
            $voucherQuery->where('branch_id', $branchId);
        }

        $totalAccounts = ChartOfAccount::where('company_id', $companyId)->count();
        $totalBankAccounts = BankAccount::where('company_id', $companyId)->count();
        $totalVouchers = (clone $voucherQuery)->count();
        $totalDebitVolume = (float) (clone $voucherQuery)->where('status', 'posted')->sum('total_debit');

        $recentVouchers = (clone $voucherQuery)
            ->with(['branch', 'entries.account', 'creator'])
            ->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $currentFy = FinancialYear::forDate($companyId, now());

        return view('admin.accounting.dashboard', compact(
            'totalAccounts',
            'totalBankAccounts',
            'totalVouchers',
            'totalDebitVolume',
            'recentVouchers',
            'companies',
            'branches',
            'currentFy',
            'companyId',
            'branchId'
        ));
    }
}
