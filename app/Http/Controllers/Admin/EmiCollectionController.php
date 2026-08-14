<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LoanAccount;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Services\LoanAccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmiCollectionController extends Controller
{
    public function __construct(protected LoanAccountService $accountService) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $branchId = $request->input('branch_id');

        if ($user && !$user->isSuperAdmin()) {
            if ($user->branch_id && !$user->isCompanyAdmin()) {
                $branchId = $user->branch_id;
            }
        }

        $todayStr = now()->toDateString();

        // 1. Summary Metrics
        $repaymentsQuery = LoanRepayment::whereDate('payment_date', $todayStr);
        if ($branchId) {
            $repaymentsQuery->whereHas('loanAccount', fn($q) => $q->where('branch_id', $branchId));
        }

        $todayTotal = (float) $repaymentsQuery->sum('amount');
        $todayCash = (float) (clone $repaymentsQuery)->where('payment_method', 'cash')->sum('amount');
        $todayUpi = (float) (clone $repaymentsQuery)->where('payment_method', 'upi')->sum('amount');
        $todayBank = (float) (clone $repaymentsQuery)->where('payment_method', 'bank_transfer')->sum('amount');
        $todayCustomersCount = (clone $repaymentsQuery)->distinct('customer_id')->count('customer_id');

        $pendingTodayCount = LoanInstallment::whereDate('due_date', $todayStr)
            ->where('status', '!=', 'paid')
            ->when($branchId, fn($q) => $q->whereHas('loanAccount', fn($b) => $b->where('branch_id', $branchId)))
            ->count();

        $overdueQuery = LoanAccount::whereIn('status', ['active', 'defaulted'])
            ->whereHas('installments', fn($q) => $q->where('due_date', '<', $todayStr)->where('status', '!=', 'paid'));
        if ($branchId) {
            $overdueQuery->where('branch_id', $branchId);
        }
        $overdueTotal = (float) $overdueQuery->sum('total_outstanding');

        $metrics = [
            'today_total' => round($todayTotal, 0),
            'today_cash' => round($todayCash, 0),
            'today_upi' => round($todayUpi, 0),
            'today_bank' => round($todayBank, 0),
            'today_customers_count' => $todayCustomersCount,
            'pending_today_count' => $pendingTodayCount,
            'overdue_total' => round($overdueTotal, 0),
        ];

        // 2. Search Handling
        $searchResults = collect();
        $searchGroupResults = collect();
        $searchTerm = trim((string) $request->input('search'));

        if ($searchTerm !== '') {
            $custQuery = Customer::with(['branch', 'loanAccounts' => function ($q) {
                $q->whereIn('status', ['active', 'sanctioned', 'ready_for_disbursement', 'defaulted'])
                  ->with(['loanScheme', 'installments', 'application.products.product']);
            }]);

            if ($branchId) {
                $custQuery->where('branch_id', $branchId);
            }

            $custQuery->where(function ($q) use ($searchTerm) {
                $q->where('mobile_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_code', 'like', "%{$searchTerm}%")
                  ->orWhere('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('loanAccounts', function ($la) use ($searchTerm) {
                      $la->where('loan_number', 'like', "%{$searchTerm}%")
                        ->orWhereHas('application', fn($a) => $a->where('application_number', 'like', "%{$searchTerm}%"));
                  });
            });

            $searchResults = $custQuery->limit(20)->get();

            // Group search
            $grpQuery = CustomerGroup::with(['branch', 'members.customer.loanAccounts' => function ($q) {
                $q->whereIn('status', ['active', 'sanctioned', 'ready_for_disbursement', 'defaulted'])
                  ->with(['loanScheme', 'installments']);
            }]);

            if ($branchId) {
                $grpQuery->where('branch_id', $branchId);
            }

            $grpQuery->where(function ($q) use ($searchTerm) {
                $q->where('group_code', 'like', "%{$searchTerm}%")
                  ->orWhere('name', 'like', "%{$searchTerm}%");
            });

            $searchGroupResults = $grpQuery->limit(10)->get();
        }

        // 3. Collection History List
        $historyQuery = LoanRepayment::with(['loanAccount.customer', 'loanAccount.customerGroup', 'receiver']);
        if ($branchId) {
            $historyQuery->whereHas('loanAccount', fn($q) => $q->where('branch_id', $branchId));
        }

        if ($request->filled('date_from')) {
            $historyQuery->whereDate('payment_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $historyQuery->whereDate('payment_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('payment_method')) {
            $historyQuery->where('payment_method', $request->input('payment_method'));
        }

        $history = $historyQuery->orderBy('id', 'desc')->paginate(15);
        $branches = Branch::where('is_active', true)->get();

        return view('admin.loans.collection.index', compact('metrics', 'searchResults', 'searchGroupResults', 'searchTerm', 'history', 'branches', 'branchId'));
    }

    public function receipt(LoanRepayment $repayment)
    {
        $repayment->load(['loanAccount.customer', 'loanAccount.customerGroup', 'loanAccount.branch', 'loanAccount.company', 'receiver']);
        return view('admin.loans.collection.receipt', compact('repayment'));
    }

    public function thermalReceipt(Request $request, LoanRepayment $repayment)
    {
        $repayment->load([
            'loanAccount.customer',
            'loanAccount.customerGroup',
            'loanAccount.branch',
            'loanAccount.company',
            'loanAccount.installments',
            'loanAccount.application.products.product',
            'receiver'
        ]);

        $width = $request->input('width', '80');
        if (!in_array($width, ['58', '80'])) {
            $width = '80';
        }

        $nextInst = $repayment->loanAccount->installments->where('status', '!=', 'paid')->first();

        return view('admin.loans.collection.thermal_receipt', compact('repayment', 'width', 'nextInst'));
    }
}
