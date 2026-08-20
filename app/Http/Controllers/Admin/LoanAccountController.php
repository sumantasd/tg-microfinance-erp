<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordDownPaymentRequest;
use App\Http\Requests\Admin\SanctionLoanAccountRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Services\LoanAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanAccountController extends Controller
{
    public function __construct(protected LoanAccountService $accountService) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'loan_type', 'borrower_type', 'branch_id', 'status']);
        $accounts = $this->accountService->getPaginatedAccounts($filters);

        $companies = Company::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        return view('admin.loans.accounts.index', compact('accounts', 'filters', 'companies', 'branches'));
    }

    public function show(LoanAccount $loanAccount): View
    {
        return view('admin.loans.accounts.show', ['account' => $loanAccount]);
    }

    public function statement(LoanAccount $loanAccount): View
    {
        return view('admin.loans.accounts.statement', ['account' => $loanAccount]);
    }

    public function sanction(SanctionLoanAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $account = $this->accountService->sanctionLoanFromApplication(
            $data['loan_application_id'],
            (float) ($data['down_payment_amount'] ?? 0.00),
            (float) ($data['other_charges_amount'] ?? 0.00),
            $data['sanction_date'] ?? null
        );

        return redirect()->route('admin.loan-account.show', $account->id)
            ->with('success', "Loan Account '{$account->loan_number}' sanctioned successfully. Repayment schedule generated.");
    }

    public function recordDownPayment(RecordDownPaymentRequest $request, LoanAccount $loanAccount): RedirectResponse
    {
        $data = $request->validated();
        $this->accountService->recordDownPayment(
            $loanAccount,
            (float) $data['amount'],
            $data['payment_method'],
            $data['reference_number'] ?? null,
            $data['remarks'] ?? null
        );

        return redirect()->back()->with('success', "Down payment of ₹" . number_format($data['amount'], 2) . " recorded successfully.");
    }

    public function recordUpfrontPayment(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bank_transfer,upi,cheque',
            'payment_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ]);

        $payment = $this->accountService->recordUpfrontPayment($loanAccount, $validated);

        return redirect()->back()->with('success', "Upfront charges payment of ₹" . number_format($payment->amount, 2) . " recorded successfully (Receipt: {$payment->receipt_number}).");
    }

    public function disburseCash(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|string|in:cash,bank_transfer,cheque',
            'reference_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->accountService->disburseCashLoan(
            $loanAccount,
            $request->input('payment_method'),
            $request->input('reference_number'),
            $request->input('remarks')
        );

        return redirect()->back()->with('success', "Cash loan #{$loanAccount->loan_number} disbursed successfully.");
    }

    public function issueProduct(Request $request, LoanAccount $loanAccount): RedirectResponse
    {
        $request->validate(['remarks' => 'nullable|string|max:255']);
        $this->accountService->issueProductLoan($loanAccount, $request->input('remarks'));

        return redirect()->back()->with('success', "Product issued & inventory stock deducted for Loan #{$loanAccount->loan_number}.");
    }

    public function recordRepayment(\App\Http\Requests\Admin\RecordLoanRepaymentRequest $request, LoanAccount $loanAccount): RedirectResponse
    {
        $data = $request->validated();
        $this->accountService->recordRepayment(
            $loanAccount,
            (float) $data['amount'],
            $data['payment_method'],
            $data['reference_number'] ?? null,
            $data['adjustment_mode'],
            $data['remarks'] ?? null,
            $data['payment_date'] ?? null
        );

        return redirect()->back()->with('success', "Loan repayment of ₹" . number_format($data['amount'], 2) . " recorded & schedule updated successfully.");
    }
}
