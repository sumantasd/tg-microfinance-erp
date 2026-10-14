<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Branch;
use App\Services\BankDepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BankDepositController extends Controller
{
    protected BankDepositService $bankDepositService;

    public function __construct(BankDepositService $bankDepositService)
    {
        $this->bankDepositService = $bankDepositService;
    }

    /**
     * Display listing of bank deposits and admin bank accounts.
     */
    public function index(Request $request)
    {
        Gate::authorize('bank_deposit.view');

        $user = auth()->user();
        
        // Branch isolation: Branch Managers see only their assigned branch
        $userBranchId = $user->branch_id;
        $selectedBranchId = $userBranchId ?? $request->input('branch_id');
        $selectedStatus = $request->input('status');
        $activeTab = $request->input('tab', 'deposits');

        $branches = Branch::where('is_active', true)->get();

        $query = BankDeposit::with(['bankAccount', 'branch', 'submittedBy', 'approvedBy', 'rejectedBy'])
            ->orderByDesc('deposit_date')
            ->orderByDesc('id');

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $deposits = $query->paginate(15);

        // Active bank accounts for deposit submission dropdown
        $companyId = $user->company_id ?? 1;
        $activeBankAccounts = $this->bankDepositService->getActiveBankAccounts($companyId, $userBranchId);

        // All bank accounts for Admin management section
        $canManageAccounts = $user->can('bank_accounts.view') || $user->can('bank_deposit.approve') || $user->isSuperAdmin() || $user->isCompanyAdmin();
        $adminBankAccounts = collect();
        if ($canManageAccounts) {
            $adminBankAccounts = BankAccount::where('company_id', $companyId)
                ->orderBy('bank_name')
                ->get();
        }

        return view('admin.bank-deposits.index', compact(
            'deposits',
            'branches',
            'selectedBranchId',
            'selectedStatus',
            'userBranchId',
            'activeBankAccounts',
            'adminBankAccounts',
            'canManageAccounts',
            'activeTab'
        ));
    }

    /**
     * Store a new bank deposit submission.
     */
    public function store(Request $request)
    {
        Gate::authorize('bank_deposit.create');

        $user = auth()->user();

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_name' => 'nullable|string|max:150',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'receipt' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'receipt.required' => 'A photo or image of the bank deposit receipt/slip is mandatory.',
            'receipt.image' => 'The receipt must be a valid image file (JPEG, PNG, or WebP).',
            'receipt.mimes' => 'The receipt must be a file of type: jpeg, png, jpg, webp.',
            'receipt.max' => 'The receipt image size may not exceed 5 MB.',
            'bank_account_id.required' => 'Please select an active bank account.',
        ]);

        // Enforce branch isolation for Branch Managers
        if ($user->branch_id && (int)$validated['branch_id'] !== (int)$user->branch_id) {
            return redirect()->back()->with('error', 'You can only submit bank deposits for your assigned branch.');
        }

        try {
            $this->bankDepositService->submitDeposit($validated, $user, $request->file('receipt'));
            return redirect()->route('admin.bank-deposits.index')
                ->with('success', 'Bank deposit submitted successfully. Pending Admin verification and approval.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Securely stream receipt image to authorized viewers.
     */
    public function showReceipt($id)
    {
        Gate::authorize('bank_deposit.view');

        $deposit = BankDeposit::findOrFail($id);
        $user = auth()->user();

        // Enforce branch isolation: Branch Managers cannot view receipts from other branches
        if ($user->branch_id && (int)$user->branch_id !== (int)$deposit->branch_id && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            abort(403, 'Unauthorized access to bank deposit receipt belonging to another branch.');
        }

        $filePath = $this->bankDepositService->getReceiptFilePath($deposit);

        return response()->file($filePath, [
            'Content-Type' => $deposit->receipt_mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="' . ($deposit->receipt_original_name ?: "deposit-receipt-{$deposit->id}.jpg") . '"',
        ]);
    }

    /**
     * Approve a submitted bank deposit.
     */
    public function approve(Request $request, $id)
    {
        Gate::authorize('bank_deposit.approve');

        $deposit = BankDeposit::findOrFail($id);

        try {
            $this->bankDepositService->approveDeposit($deposit, auth()->user(), $request->input('remarks'));
            return redirect()->route('admin.bank-deposits.index')
                ->with('success', 'Bank deposit approved successfully! Physical cash balance updated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a submitted bank deposit.
     */
    public function reject(Request $request, $id)
    {
        Gate::authorize('bank_deposit.approve');

        $deposit = BankDeposit::findOrFail($id);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->bankDepositService->rejectDeposit($deposit, auth()->user(), $request->input('rejection_reason'));
            return redirect()->route('admin.bank-deposits.index')
                ->with('success', 'Bank deposit rejected successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store a new bank account from Admin Bank Account Management.
     */
    public function storeAccount(Request $request)
    {
        $user = auth()->user();
        if (!$user->can('bank_accounts.create') && !$user->can('bank_deposit.approve') && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            abort(403, 'Unauthorized to add bank accounts.');
        }

        if (!$request->has('account_name') && $request->has('account_holder_name')) {
            $request->merge(['account_name' => $request->input('account_holder_name')]);
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
            'branch_name' => 'required|string|max:100',
            'account_nickname' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $companyId = $user->company_id ?? 1;

        $exists = BankAccount::where('company_id', $companyId)
            ->where('account_number', $validated['account_number'])
            ->exists();

        if ($exists) {
            return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
                ->with('error', "A bank account with number '{$validated['account_number']}' already exists.");
        }

        $account = BankAccount::create([
            'company_id' => $companyId,
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'ifsc_code' => strtoupper($validated['ifsc_code']),
            'branch_name' => $validated['branch_name'],
            'account_nickname' => $validated['account_nickname'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
            ->with('success', "Bank account '{$account->bank_name} - {$account->masked_account_number}' added successfully.");
    }

    /**
     * Update an existing bank account.
     */
    public function updateAccount(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->can('bank_accounts.edit') && !$user->can('bank_deposit.approve') && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            abort(403, 'Unauthorized to update bank accounts.');
        }

        if (!$request->has('account_name') && $request->has('account_holder_name')) {
            $request->merge(['account_name' => $request->input('account_holder_name')]);
        }

        $account = BankAccount::findOrFail($id);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
            'branch_name' => 'required|string|max:100',
            'account_nickname' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $account->update([
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'ifsc_code' => strtoupper($validated['ifsc_code']),
            'branch_name' => $validated['branch_name'],
            'account_nickname' => $validated['account_nickname'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : false,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
            ->with('success', "Bank account '{$account->bank_name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status of bank account.
     */
    public function toggleAccountStatus($id)
    {
        $user = auth()->user();
        if (!$user->can('bank_accounts.edit') && !$user->can('bank_deposit.approve') && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            abort(403, 'Unauthorized to change bank account status.');
        }

        $account = BankAccount::findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->updated_by = $user->id;
        $account->save();

        $statusStr = $account->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
            ->with('success', "Bank account '{$account->bank_name}' {$statusStr} successfully.");
    }

    /**
     * Delete bank account with safe guard against historical references.
     */
    public function destroyAccount($id)
    {
        $user = auth()->user();
        if (!$user->can('bank_accounts.edit') && !$user->can('bank_deposit.approve') && !$user->isSuperAdmin() && !$user->isCompanyAdmin()) {
            abort(403, 'Unauthorized to delete bank accounts.');
        }

        $account = BankAccount::findOrFail($id);

        try {
            $this->bankDepositService->deleteBankAccount($account);
            return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
                ->with('success', "Bank account deleted successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.bank-deposits.index', ['tab' => 'accounts'])
                ->with('error', $e->getMessage());
        }
    }
}

