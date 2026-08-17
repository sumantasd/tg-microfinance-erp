<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AccountingService;
use App\Services\LoanAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase2aAutoGlPostingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected LoanScheme $scheme;
    protected Customer $customer;
    protected FinancialYear $financialYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Grihalaxmi Microfinance Ltd',
            'code' => 'GMF01',
            'registration_number' => 'REG-GMF-01',
            'email' => 'finance@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna HQ, Bihar',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Head Office Branch',
            'code' => 'HO01',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9876543211',
            'address' => 'Patna, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin' . uniqid() . '@grihalaxmi.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($this->user);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'title' => 'FY 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-CASH-01',
            'name' => 'Standard Micro Cash Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.50,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-0001',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'mobile_number' => '9876543210',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
    }

    private function createSanctionedCashLoan(float $amount = 20000): LoanAccount
    {
        $application = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-CASH-' . uniqid(),
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $loanService = app(LoanAccountService::class);
        return $loanService->sanctionLoanFromApplication($application, 0.00, 0.00, '2026-08-01');
    }

    /**
     * Test A: Cash Loan Disbursement Automatic Posting
     */
    public function test_successful_cash_loan_disbursement_creates_payment_voucher_with_correct_gl_and_amounts(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(30000.00);

        $loanService = app(LoanAccountService::class);
        $updatedAccount = $loanService->disburseCashLoan($loanAccount, 'cash', 'REF-CASH-001', 'Disbursement test');

        $this->assertEquals('active', $updatedAccount->status);
        $this->assertEquals(30000.00, (float) $updatedAccount->disbursed_amount);

        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();
        $this->assertNotNull($disbursement);

        // Verify General Ledger Voucher Creation
        $voucher = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals('payment', $voucher->voucher_type);
        $this->assertEquals('posted', $voucher->status);
        $this->assertEquals($this->company->id, $voucher->company_id);
        $this->assertEquals($this->branch->id, $voucher->branch_id);
        $this->assertEquals($this->financialYear->id, $voucher->financial_year_id);
        $this->assertEquals(30000.00, (float) $voucher->total_debit);
        $this->assertEquals(30000.00, (float) $voucher->total_credit);

        // Check Debits (1210 - Cash Microfinance Loans Receivable) and Credits (1110 - Branch Cash Vault)
        $entries = $voucher->entries()->with('account')->get();
        $this->assertCount(2, $entries);

        $debitEntry = $entries->firstWhere('debit', '>', 0);
        $creditEntry = $entries->firstWhere('credit', '>', 0);

        $this->assertEquals('1210', $debitEntry->account->account_code);
        $this->assertEquals(30000.00, (float) $debitEntry->debit);

        $this->assertEquals('1110', $creditEntry->account->account_code);
        $this->assertEquals(30000.00, (float) $creditEntry->credit);
    }

    /**
     * Test B: Bank Loan Disbursement posts to Bank GL (1130)
     */
    public function test_bank_loan_disbursement_posts_to_bank_operating_account(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(50000.00);

        $loanService = app(LoanAccountService::class);
        $updatedAccount = $loanService->disburseCashLoan($loanAccount, 'bank_transfer', 'NEFT998877', 'Bank transfer payout');

        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();
        $voucher = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        $this->assertNotNull($voucher);
        $entries = $voucher->entries()->with('account')->get();

        $creditEntry = $entries->firstWhere('credit', '>', 0);
        $this->assertEquals('1130', $creditEntry->account->account_code);
        $this->assertEquals(50000.00, (float) $creditEntry->credit);
    }

    /**
     * Test A8: Idempotent Cash Loan Disbursement Accounting
     */
    public function test_duplicate_cash_loan_disbursement_posting_is_prevented(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(25000.00);
        $loanService = app(LoanAccountService::class);
        $loanService->disburseCashLoan($loanAccount, 'cash');

        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();

        $vouchersCountBefore = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->count();
        $this->assertEquals(1, $vouchersCountBefore);

        // Manually attempt to call postCashLoanDisbursement again
        $accountingService = app(AccountingService::class);
        $duplicateVoucher = $accountingService->postCashLoanDisbursement($loanAccount, $disbursement);

        $vouchersCountAfter = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->count();
        $this->assertEquals(1, $vouchersCountAfter);
    }

    /**
     * Test A9: Accounting Failure Rolls Back Disbursement
     */
    public function test_accounting_failure_rolls_back_cash_loan_disbursement(): void
    {
        // Close Financial Year so that voucher creation throws ValidationException
        $this->financialYear->update(['is_closed' => true]);

        $loanAccount = $this->createSanctionedCashLoan(15000.00);
        $loanService = app(LoanAccountService::class);

        $thrown = false;
        try {
            $loanService->disburseCashLoan($loanAccount, 'cash');
        } catch (\Throwable $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        // Ensure database state rolled back: no disbursements created, loan account remains sanctioned
        $this->assertDatabaseMissing('loan_disbursements', [
            'loan_account_id' => $loanAccount->id,
        ]);
        $this->assertEquals('sanctioned', $loanAccount->fresh()->status);
        $this->assertEquals(0, Voucher::count());
    }

    /**
     * Test C: EMI Repayment Collection with Waterfall Accounting
     */
    public function test_successful_cash_repayment_creates_receipt_voucher_with_complete_waterfall(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(20000.00);
        $loanService = app(LoanAccountService::class);
        $loanService->disburseCashLoan($loanAccount, 'cash');

        // Set simulated fee and penalty on account
        $loanAccount->update([
            'fee_outstanding' => 100.00,
            'penalty_outstanding' => 50.00,
            'total_outstanding' => $loanAccount->total_outstanding + 150.00,
        ]);

        // Record Repayment of ₹2,500
        $loanService->recordRepayment(
            $loanAccount->fresh(),
            2500.00,
            'cash',
            'REC-001',
            'reduce_tenure',
            'Monthly installment with fee and penalty',
            '2026-09-01'
        );

        $repayment = LoanRepayment::where('loan_account_id', $loanAccount->id)->first();
        $this->assertNotNull($repayment);

        // Verify Receipt Voucher
        $voucher = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals('receipt', $voucher->voucher_type);
        $this->assertEquals('posted', $voucher->status);
        $this->assertEquals(2500.00, (float) $voucher->total_debit);
        $this->assertEquals(2500.00, (float) $voucher->total_credit);

        // Check line items
        $entries = $voucher->entries()->with('account')->get();

        // Debit: Cash Vault (1110)
        $debitEntry = $entries->firstWhere('debit', '>', 0);
        $this->assertEquals('1110', $debitEntry->account->account_code);
        $this->assertEquals(2500.00, (float) $debitEntry->debit);

        // Credits: Principal (1210), Interest (4110), Fee (4210), Penalty (4230)
        $creditPrincipal = $entries->firstWhere('account.account_code', '1210');
        $creditInterest = $entries->firstWhere('account.account_code', '4110');
        $creditFee = $entries->firstWhere('account.account_code', '4210');
        $creditPenalty = $entries->firstWhere('account.account_code', '4230');

        $this->assertEquals((float) $repayment->principal_paid, (float) ($creditPrincipal?->credit ?? 0));
        $this->assertEquals((float) $repayment->interest_paid, (float) ($creditInterest?->credit ?? 0));
        $this->assertEquals((float) $repayment->fee_paid, (float) ($creditFee?->credit ?? 0));
        $this->assertEquals((float) $repayment->penalty_paid, (float) ($creditPenalty?->credit ?? 0));

        $totalCreditSum = $entries->sum('credit');
        $this->assertEquals(2500.00, round((float) $totalCreditSum, 2));
    }

    /**
     * Test D: UPI and Bank Transfer Repayment Mapping
     */
    public function test_upi_and_bank_transfer_repayment_maps_to_bank_operating_account(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(10000.00);
        $loanService = app(LoanAccountService::class);
        $loanService->disburseCashLoan($loanAccount, 'cash');

        // Record Repayment via UPI
        $loanService->recordRepayment(
            $loanAccount->fresh(),
            1000.00,
            'upi',
            'UPI/TXN/9988',
            'reduce_tenure',
            null,
            '2026-09-05'
        );

        $repayment = LoanRepayment::where('payment_method', 'upi')->first();
        $voucher = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->first();

        $this->assertNotNull($voucher);
        $debitEntry = $voucher->entries()->with('account')->get()->firstWhere('debit', '>', 0);
        $this->assertEquals('1130', $debitEntry->account->account_code);
        $this->assertEquals(1000.00, (float) $debitEntry->debit);
    }

    /**
     * Test C18: Repayment Accounting Idempotency
     */
    public function test_repayment_accounting_is_idempotent(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(10000.00);
        $loanService = app(LoanAccountService::class);
        $loanService->disburseCashLoan($loanAccount, 'cash');

        $loanService->recordRepayment(
            $loanAccount->fresh(),
            1500.00,
            'cash',
            'REC-002',
            'reduce_tenure',
            null,
            '2026-09-01'
        );

        $repayment = LoanRepayment::where('loan_account_id', $loanAccount->id)->first();
        $countBefore = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->count();
        $this->assertEquals(1, $countBefore);

        // Trigger postLoanRepayment again manually
        $accountingService = app(AccountingService::class);
        $duplicateVoucher = $accountingService->postLoanRepayment($repayment, $loanAccount);

        $countAfter = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->count();
        $this->assertEquals(1, $countAfter);
    }

    /**
     * Test C19: Repayment Accounting Failure Rolls Back Everything
     */
    public function test_accounting_failure_rolls_back_repayment(): void
    {
        $loanAccount = $this->createSanctionedCashLoan(10000.00);
        $loanService = app(LoanAccountService::class);
        $loanService->disburseCashLoan($loanAccount, 'cash');

        $initialOutstanding = (float) $loanAccount->fresh()->total_outstanding;

        // Close Financial Year before repayment
        $this->financialYear->update(['is_closed' => true]);

        $thrown = false;
        try {
            $loanService->recordRepayment(
                $loanAccount->fresh(),
                1000.00,
                'cash',
                'REC-003',
                'reduce_tenure',
                null,
                '2026-09-01'
            );
        } catch (\Throwable $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        // Verify rollback: no repayment created, outstanding unchanged
        $this->assertDatabaseMissing('loan_repayments', [
            'loan_account_id' => $loanAccount->id,
        ]);
        $this->assertEquals($initialOutstanding, (float) $loanAccount->fresh()->total_outstanding);
    }
}
