<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChartOfAccountRequest;
use App\Http\Requests\Admin\UpdateChartOfAccountRequest;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
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

        $query = ChartOfAccount::with(['parent', 'children'])
            ->withCount('voucherEntries')
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_group', 'like', "%{$search}%");
            });
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        if ($request->filled('account_group')) {
            $query->where('account_group', $request->input('account_group'));
        }

        $accounts = $query->orderBy('account_code', 'asc')->paginate(20)->withQueryString();
        $companies = Company::where('is_active', true)->get();

        return view('admin.accounting.chart-of-accounts.index', compact('accounts', 'companies', 'companyId'));
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        $companyId = $user && !$user->isSuperAdmin() ? $user->company_id : ($request->input('company_id') ?: Company::first()->id ?? 1);

        $companies = Company::where('is_active', true)->get();
        $parentAccounts = ChartOfAccount::where('company_id', $companyId)->where('is_active', true)->orderBy('account_code')->get();

        return view('admin.accounting.chart-of-accounts.create', compact('companies', 'parentAccounts', 'companyId'));
    }

    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $user->company_id;
        } elseif (empty($data['company_id'])) {
            $data['company_id'] = Company::first()->id ?? 1;
        }

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['is_system'] = false;
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $account = ChartOfAccount::create($data);

        return redirect()->route('admin.accounting.chart-of-accounts.index')
            ->with('success', "Ledger Account '{$account->account_code} - {$account->account_name}' created successfully.");
    }

    public function edit(ChartOfAccount $chartOfAccount): View
    {
        $companies = Company::where('is_active', true)->get();
        $parentAccounts = ChartOfAccount::where('company_id', $chartOfAccount->company_id)
            ->where('id', '!=', $chartOfAccount->id)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('admin.accounting.chart-of-accounts.edit', compact('chartOfAccount', 'companies', 'parentAccounts'));
    }

    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $chartOfAccount->update($data);

        return redirect()->route('admin.accounting.chart-of-accounts.index')
            ->with('success', "Ledger Account '{$chartOfAccount->account_code} - {$chartOfAccount->account_name}' updated successfully.");
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->is_system) {
            return redirect()->route('admin.accounting.chart-of-accounts.index')
                ->with('error', "Cannot delete system account '{$chartOfAccount->account_code} - {$chartOfAccount->account_name}'. System accounts are mandatory for automated double-entry postings.");
        }

        if ($chartOfAccount->voucherEntries()->exists()) {
            return redirect()->route('admin.accounting.chart-of-accounts.index')
                ->with('error', "Cannot delete account '{$chartOfAccount->account_code}' because it contains posted financial transactions / journal entries.");
        }

        if ($chartOfAccount->children()->exists()) {
            return redirect()->route('admin.accounting.chart-of-accounts.index')
                ->with('error', "Cannot delete account '{$chartOfAccount->account_code}' because sub-accounts are linked to it.");
        }

        $chartOfAccount->delete();

        return redirect()->route('admin.accounting.chart-of-accounts.index')
            ->with('success', "Account '{$chartOfAccount->account_code}' removed.");
    }
}
