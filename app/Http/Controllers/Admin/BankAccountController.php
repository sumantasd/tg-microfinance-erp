<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBankAccountRequest;
use App\Http\Requests\Admin\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = BankAccount::with(['company', 'branch', 'chartOfAccount']);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
            if ($user->branch_id && !$user->isCompanyAdmin()) {
                $query->where(function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('ifsc_code', 'like', "%{$search}%");
            });
        }

        $bankAccounts = $query->orderBy('bank_name', 'asc')->paginate(15)->withQueryString();
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.accounting.bank-accounts.index', compact('bankAccounts', 'companies', 'branches'));
    }

    public function create(): View
    {
        $user = Auth::user();
        $companyId = $user && !$user->isSuperAdmin() ? $user->company_id : (Company::first()->id ?? 1);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $bankAccountsCoa = ChartOfAccount::where('company_id', $companyId)
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('admin.accounting.bank-accounts.create', compact('companies', 'branches', 'bankAccountsCoa', 'companyId'));
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = Auth::user();

        if ($user && !$user->isSuperAdmin()) {
            $data['company_id'] = $user->company_id;
        } elseif (empty($data['company_id'])) {
            $data['company_id'] = Company::first()->id ?? 1;
        }

        $data['opening_balance'] = isset($data['opening_balance']) ? (float) $data['opening_balance'] : 0.00;
        $data['current_balance'] = $data['opening_balance'];
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;

        $bankAccount = BankAccount::create($data);

        return redirect()->route('admin.accounting.bank-accounts.index')
            ->with('success', "Bank Account '{$bankAccount->bank_name} - {$bankAccount->account_number}' registered successfully.");
    }

    public function edit(BankAccount $bankAccount): View
    {
        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();
        $bankAccountsCoa = ChartOfAccount::where('company_id', $bankAccount->company_id)
            ->where('account_type', 'asset')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('admin.accounting.bank-accounts.edit', compact('bankAccount', 'companies', 'branches', 'bankAccountsCoa'));
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $bankAccount->update($data);

        return redirect()->route('admin.accounting.bank-accounts.index')
            ->with('success', "Bank Account '{$bankAccount->account_name}' updated successfully.");
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        if ($bankAccount->chartOfAccount && $bankAccount->chartOfAccount->voucherEntries()->exists()) {
            return redirect()->route('admin.accounting.bank-accounts.index')
                ->with('error', "Cannot delete bank account '{$bankAccount->account_name}' because transactions are recorded under its linked ledger account.");
        }

        $bankAccount->delete();

        return redirect()->route('admin.accounting.bank-accounts.index')
            ->with('success', "Bank Account '{$bankAccount->account_name}' removed.");
    }
}
