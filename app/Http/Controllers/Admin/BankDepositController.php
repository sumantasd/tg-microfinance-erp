<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Display listing of bank deposits.
     */
    public function index(Request $request)
    {
        Gate::authorize('bank_deposit.view');

        $user = auth()->user();
        
        // Branch isolation: Branch Managers see only their assigned branch
        $userBranchId = $user->branch_id;
        $selectedBranchId = $userBranchId ?? $request->input('branch_id');
        $selectedStatus = $request->input('status');

        $branches = Branch::where('is_active', true)->get();

        $query = BankDeposit::with(['branch', 'submittedBy', 'approvedBy'])
            ->orderByDesc('deposit_date')
            ->orderByDesc('id');

        if ($selectedBranchId) {
            $query->where('branch_id', $selectedBranchId);
        }

        if ($selectedStatus) {
            $query->where('status', $selectedStatus);
        }

        $deposits = $query->paginate(15);

        return view('admin.bank-deposits.index', compact('deposits', 'branches', 'selectedBranchId', 'selectedStatus', 'userBranchId'));
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
            'bank_name' => 'required|string|max:150',
            'account_number' => 'nullable|string|max:50',
            'reference_number' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        // Enforce branch isolation for Branch Managers
        if ($user->branch_id && (int)$validated['branch_id'] !== (int)$user->branch_id) {
            return redirect()->back()->with('error', 'You can only submit bank deposits for your assigned branch.');
        }

        try {
            $this->bankDepositService->submitDeposit($validated, $user);
            return redirect()->route('admin.bank-deposits.index')
                ->with('success', 'Bank deposit submitted successfully. Pending Admin verification and approval.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
}
