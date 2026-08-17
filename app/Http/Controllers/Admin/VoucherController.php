<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReverseVoucherRequest;
use App\Http\Requests\Admin\StoreVoucherRequest;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Voucher;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(protected AccountingService $accountingService) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Voucher::with(['company', 'branch', 'financialYear', 'creator', 'entries.account']);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
            if ($user->branch_id && !$user->isCompanyAdmin()) {
                $query->where('branch_id', $user->branch_id);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%");
            });
        }

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->input('voucher_type'));
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('start_date')) {
            $query->where('voucher_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('voucher_date', '<=', $request->input('end_date'));
        }

        $vouchers = $query->orderBy('voucher_date', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $financialYears = FinancialYear::orderBy('start_date', 'desc')->get();

        return view('admin.accounting.vouchers.index', compact('vouchers', 'companies', 'branches', 'financialYears'));
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        $companyId = $user && !$user->isSuperAdmin() ? $user->company_id : ($request->input('company_id') ?: Company::first()->id ?? 1);

        // Auto-seed standard accounts if empty for this company
        if (ChartOfAccount::where('company_id', $companyId)->count() === 0) {
            $this->accountingService->seedDefaultChartOfAccounts($companyId);
        }

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $accounts = ChartOfAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('admin.accounting.vouchers.create', compact('companies', 'branches', 'accounts', 'companyId'));
    }

    public function store(StoreVoucherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $user->company_id;
        } elseif (empty($data['company_id'])) {
            $data['company_id'] = Company::first()->id ?? 1;
        }

        $entries = $request->input('entries', []);

        $voucher = $this->accountingService->createVoucher($data, $entries, true);

        return redirect()->route('admin.accounting.vouchers.show', $voucher->id)
            ->with('success', "Journal Voucher '{$voucher->voucher_number}' posted successfully.");
    }

    public function show(Voucher $voucher): View
    {
        $voucher->load(['company', 'branch', 'financialYear', 'creator', 'entries.account', 'reversedVoucher', 'reversalVouchers']);
        return view('admin.accounting.vouchers.show', compact('voucher'));
    }

    public function reverse(ReverseVoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $data = $request->validated();

        $reversal = $this->accountingService->reverseVoucher(
            $voucher,
            $data['reversal_reason'],
            $data['reversal_date'] ?? now()->toDateString()
        );

        return redirect()->route('admin.accounting.vouchers.show', $reversal->id)
            ->with('success', "Reversal Voucher '{$reversal->voucher_number}' created successfully.");
    }
}
