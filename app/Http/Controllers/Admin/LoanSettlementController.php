<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanAccount;
use App\Models\LoanSettlementRequest;
use App\Services\LoanSettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanSettlementController extends Controller
{
    protected LoanSettlementService $settlementService;

    public function __construct(LoanSettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    /**
     * Display a listing of settlement and foreclosure requests.
     */
    public function index(Request $request): View
    {
        $this->authorize('loan_closure.view');

        $user = auth()->user();
        $query = LoanSettlementRequest::with(['loanAccount.customer', 'branch', 'requester', 'approver'])
            ->orderBy('id', 'desc');

        if ($user && $user->branch_id && !$user->hasRole('Super Admin') && !$user->hasRole('Admin')) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('loanAccount', function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('admin.loans.settlements.index', compact('requests'));
    }

    /**
     * Return Real-Time Calculation Quote (JSON or View Partial).
     */
    public function quote(Request $request, LoanAccount $loanAccount): JsonResponse
    {
        $this->authorize('loan_closure.calculate');

        $asOfDate = $request->filled('as_of_date')
            ? Carbon::parse($request->as_of_date)
            : now();

        $type = $request->input('type', 'foreclosure');

        if ($type === 'ots') {
            $proposed = (float) $request->input('proposed_amount', 0.00);
            $data = $this->settlementService->calculateSettlementOts($loanAccount, $proposed, $asOfDate);
        } elseif ($type === 'write_off') {
            $data = $this->settlementService->calculateWriteOff($loanAccount, $asOfDate);
        } else {
            $data = $this->settlementService->calculateForeclosure($loanAccount, $asOfDate);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Execute Standard Voluntary Foreclosure Payment.
     */
    public function foreclose(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $this->authorize('loan_foreclosure.process');

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,upi',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->settlementService->executeForeclosure($loanAccount, $validated, auth()->user());

            return redirect()->route('admin.loan-account.show', $loanAccount->id)
                ->with('success', "Loan #{$loanAccount->loan_number} foreclosed successfully! Receipt #{$result['repayment']->receipt_number} generated.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Submit One-Time Settlement (OTS / Compromise) Proposal.
     */
    public function requestOts(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $this->authorize('loan_settlement.request');

        $validated = $request->validate([
            'proposed_settlement_amount' => 'required|numeric|min:0',
            'as_of_date' => 'required|date',
            'valid_until_date' => 'nullable|date|after_or_equal:as_of_date',
            'remarks' => 'required|string|max:1000',
        ]);

        $validated['request_type'] = 'settlement_ots';

        try {
            $settlementRequest = $this->settlementService->createSettlementRequest($loanAccount, $validated, auth()->user());

            return redirect()->route('admin.loan-settlement.show', $settlementRequest->id)
                ->with('success', "OTS Proposal #{$settlementRequest->id} submitted for management approval.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Submit Bad Debt Write-Off Proposal.
     */
    public function requestWriteOff(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $this->authorize('loan_write_off.request');

        $validated = $request->validate([
            'as_of_date' => 'required|date',
            'remarks' => 'required|string|max:1000',
        ]);

        $validated['request_type'] = 'write_off';

        try {
            $settlementRequest = $this->settlementService->createSettlementRequest($loanAccount, $validated, auth()->user());

            return redirect()->route('admin.loan-settlement.show', $settlementRequest->id)
                ->with('success', "Bad Debt Write-Off Proposal #{$settlementRequest->id} submitted.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show Details of a Settlement Request.
     */
    public function show(LoanSettlementRequest $settlementRequest): View
    {
        $this->authorize('loan_closure.view');

        $settlementRequest->load(['loanAccount.customer', 'loanAccount.loanScheme', 'branch', 'requester', 'approver', 'repayment', 'voucher']);
        $canApprove = $this->settlementService->canUserApprove(auth()->user(), $settlementRequest);

        return view('admin.loans.settlements.show', compact('settlementRequest', 'canApprove'));
    }

    /**
     * Approve Pending Settlement Request.
     */
    public function approve(Request $request, LoanSettlementRequest $settlementRequest): RedirectResponse
    {
        $this->authorize('loan_settlement.approve');

        $validated = $request->validate([
            'approval_remarks' => 'nullable|string|max:500',
        ]);

        try {
            $this->settlementService->approveSettlementRequest($settlementRequest, auth()->user(), $validated['approval_remarks'] ?? null);

            return redirect()->route('admin.loan-settlement.show', $settlementRequest->id)
                ->with('success', "Request #{$settlementRequest->id} has been approved.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject Pending Settlement Request.
     */
    public function reject(Request $request, LoanSettlementRequest $settlementRequest): RedirectResponse
    {
        $this->authorize('loan_settlement.approve');

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->settlementService->rejectSettlementRequest($settlementRequest, auth()->user(), $validated['rejection_reason']);

            return redirect()->route('admin.loan-settlement.show', $settlementRequest->id)
                ->with('success', "Request #{$settlementRequest->id} has been rejected.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Execute Approved Settlement / Write-Off.
     */
    public function execute(Request $request, LoanSettlementRequest $settlementRequest): RedirectResponse
    {
        if ($settlementRequest->request_type === 'write_off') {
            $this->authorize('loan_write_off.approve');

            try {
                $this->settlementService->executeWriteOff($settlementRequest, auth()->user());

                return redirect()->route('admin.loan-account.show', $settlementRequest->loan_account_id)
                    ->with('success', "Loan #{$settlementRequest->loanAccount->loan_number} written off and closed successfully.");
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $this->authorize('loan_foreclosure.process');

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque,upi',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->settlementService->executeApprovedSettlement($settlementRequest, $validated, auth()->user());

            return redirect()->route('admin.loan-account.show', $settlementRequest->loan_account_id)
                ->with('success', "Settlement executed! Loan #{$settlementRequest->loanAccount->loan_number} closed with Receipt #{$result['repayment']->receipt_number}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Render Printable No Objection Certificate (NOC) / Loan Closure Certificate.
     */
    public function noc(LoanAccount $loanAccount): View
    {
        $this->authorize('loan_closure.certificate');

        $nocData = $this->settlementService->generateNocData($loanAccount);

        return view('admin.loans.settlements.noc', compact('loanAccount', 'nocData'));
    }
}
