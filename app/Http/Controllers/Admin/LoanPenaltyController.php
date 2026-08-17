<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LoanAccount;
use App\Models\LoanInstallment;
use App\Models\LoanPenaltyCharge;
use App\Models\LoanPenaltyWaiver;
use App\Models\LoanScheme;
use App\Services\PenaltyService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanPenaltyController extends Controller
{
    protected PenaltyService $penaltyService;

    public function __construct(PenaltyService $penaltyService)
    {
        $this->penaltyService = $penaltyService;
    }

    /**
     * Display Penalty Charges Audit Ledger with Summary & Filters.
     */
    public function ledger(Request $request): View
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $user->branch_id ?: ($request->filled('branch_id') ? (int) $request->input('branch_id') : null);

        $query = LoanPenaltyCharge::with(['loanAccount.customer', 'loanAccount.branch', 'loanAccount.loanScheme', 'loanInstallment'])
            ->whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        // Filters
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->whereHas('loanAccount', function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%")
                         ->orWhere('mobile_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('loan_scheme_id')) {
            $schemeId = (int) $request->input('loan_scheme_id');
            $query->whereHas('loanAccount', function ($q) use ($schemeId) {
                $q->where('loan_scheme_id', $schemeId);
            });
        }

        if ($request->filled('penalty_type')) {
            $query->where('calculation_type', $request->input('penalty_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('charge_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('charge_date', '<=', $request->input('date_to'));
        }

        $charges = $query->orderBy('charge_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // High-level summary metrics
        $totalCharged = (float) LoanPenaltyCharge::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->sum('charge_amount');

        $totalWaived = (float) LoanPenaltyWaiver::whereHas('loanAccount', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        })->sum('waived_amount');

        $totalPenaltyOutstanding = (float) LoanAccount::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'active')
            ->sum('penalty_outstanding');

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->get();
        $loanSchemes = LoanScheme::where('company_id', $companyId)->where('is_active', true)->get();

        return view('admin.loans.penalties.ledger', [
            'charges' => $charges,
            'totalCharged' => $totalCharged,
            'totalWaived' => $totalWaived,
            'totalPenaltyOutstanding' => $totalPenaltyOutstanding,
            'branches' => $branches,
            'loanSchemes' => $loanSchemes,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Process Authorized Penalty Waiver.
     */
    public function waive(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $user = $request->user();

        // RBAC Authorization
        if (!$user->can('loans.waive_penalty') && !$user->can('loan.waive_penalty')) {
            abort(403, 'Unauthorized. You do not have permission to waive loan penalties.');
        }

        if ($loanAccount->company_id !== $user->company_id) {
            abort(403, 'Unauthorized company access.');
        }

        if ($user->branch_id && $loanAccount->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $loanAccount->penalty_outstanding],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'loan_installment_id' => ['nullable', 'exists:loan_installments,id'],
        ]);

        $installment = null;
        if ($request->filled('loan_installment_id')) {
            $installment = LoanInstallment::where('loan_account_id', $loanAccount->id)
                ->findOrFail($request->input('loan_installment_id'));
        }

        try {
            $waiver = $this->penaltyService->waivePenalty(
                $loanAccount,
                (float) $request->input('amount'),
                $request->input('reason'),
                $user->id,
                $installment
            );

            return back()->with('success', "Successfully waived ₹" . number_format($waiver->waived_amount, 2) . " penalty for Loan #{$loanAccount->loan_number}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
