<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\LoanSettlementRequest;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AccountingService;
use App\Services\LoanSettlementService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoanSettlementAndForeclosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $companyAdmin;
    protected User $branchManager;
    protected User $loanOfficer;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected LoanScheme $cashScheme;
    protected LoanScheme $productScheme;
    protected AccountingService $accountingService;
    protected LoanSettlementService $settlementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna HO',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kankarbagh Branch',
            'code' => 'KBG01',
            'email' => 'kbg@grihalaxmi.com',
            'phone' => '9876543211',
            'address' => 'Kankarbagh, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800020',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Super Admin User',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->companyAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Company Admin User',
            'email' => 'companyadmin@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->companyAdmin->assignRole('Company Admin');

        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Branch Manager User',
            'email' => 'branchmanager@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->loanOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Loan Officer User',
            'email' => 'loanofficer@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->loanOfficer->assignRole('Loan Officer');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'customer_code' => 'CUST-1001',
            'mobile_number' => '9876500001',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->cashScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'code' => 'MFI-CASH-12',
            'name' => 'Microfinance Cash Loan 12M',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 1.00,
            'allow_foreclosure' => true,
            'foreclosure_fee_type' => 'percentage',
            'foreclosure_fee_percentage' => 2.00,
            'foreclosure_flat_fee' => 0.00,
            'min_months_before_foreclosure' => 0,
            'is_active' => true,
        ]);

        $this->productScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'code' => 'MFI-PROD-12',
            'name' => 'Product Financed Loan 12M',
            'loan_type' => 'product',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'allow_foreclosure' => true,
            'foreclosure_fee_type' => 'flat',
            'foreclosure_fee_percentage' => 0.00,
            'foreclosure_flat_fee' => 500.00,
            'min_months_before_foreclosure' => 3,
            'is_active' => true,
        ]);

        $this->accountingService = app(AccountingService::class);
        $this->settlementService = app(LoanSettlementService::class);
    }

    /**
     * Helper to create an active loan account with standard 12-month schedule.
     */
    protected function createActiveLoan(string $loanType = 'cash', ?LoanScheme $scheme = null, float $principal = 12000.00, int $tenure = 12): LoanAccount
    {
        $scheme = $scheme ?: ($loanType === 'product' ? $this->productScheme : $this->cashScheme);
        $annualRate = (float) $scheme->interest_rate_per_annum;
        $totalInterest = round($principal * ($annualRate / 100) * ($tenure / 12), 2); // 1440.00
        $totalRepayment = $principal + $totalInterest; // 13440.00

        $disbursedDate = Carbon::now(LoanSettlementService::TIMEZONE)->subMonths(4)->toDateString(); // 4 months ago

        $application = LoanApplication::create([
            'application_number' => 'APP-' . strtoupper(uniqid()),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => $loanType,
            'borrower_type' => 'individual',
            'application_date' => $disbursedDate,
            'requested_amount' => $principal,
            'approved_amount' => $principal,
            'tenure_months' => $tenure,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => $annualRate,
            'processing_fee_percentage' => 1.00,
            'processing_fee_amount' => 120.00,
            'purpose' => 'Test Loan',
            'status' => 'approved',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => $disbursedDate,
        ]);

        $loan = LoanAccount::create([
            'loan_number' => 'LN-' . strtoupper(uniqid()),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'loan_application_id' => $application->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => $loanType,
            'borrower_type' => 'individual',
            'sanctioned_amount' => $principal,
            'disbursed_amount' => $principal,
            'tenure_months' => $tenure,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => $annualRate,
            'processing_fee_percentage' => 1.00,
            'processing_fee_amount' => 120.00,
            'total_interest_amount' => $totalInterest,
            'total_repayment_amount' => $totalRepayment,
            'principal_outstanding' => $principal,
            'interest_outstanding' => $totalInterest,
            'fee_outstanding' => 0.00,
            'penalty_outstanding' => 0.00,
            'total_outstanding' => $totalRepayment,
            'status' => 'active',
            'sanction_date' => $disbursedDate,
            'disbursement_date' => $disbursedDate,
            'maturity_date' => Carbon::parse($disbursedDate)->addMonths($tenure)->toDateString(),
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        // Generate 12 monthly installments: each principal = 1000, interest = 120
        $instPrincipal = round($principal / $tenure, 2);
        $instInterest = round($totalInterest / $tenure, 2);
        for ($i = 1; $i <= $tenure; $i++) {
            $dueDate = Carbon::parse($disbursedDate)->addMonths($i)->toDateString();
            $opening = $principal - (($i - 1) * $instPrincipal);
            $closing = $principal - ($i * $instPrincipal);
            LoanInstallment::create([
                'loan_account_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'opening_principal' => $opening,
                'principal_amount' => $instPrincipal,
                'interest_amount' => $instInterest,
                'fee_amount' => 0.00,
                'penalty_amount' => 0.00,
                'installment_amount' => $instPrincipal + $instInterest,
                'principal_paid' => 0.00,
                'interest_paid' => 0.00,
                'fee_paid' => 0.00,
                'penalty_paid' => 0.00,
                'total_paid' => 0.00,
                'closing_principal' => max(0, $closing),
                'status' => 'pending',
            ]);
        }

        return $loan;
    }

    public function test_foreclosure_calculation_rebates_unearned_interest_pro_rata(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);
        // Loan started 4 months ago. Elapsed installments = 4 (Installments 1..4 due_date <= now)
        // Future installments = 8 (Installments 5..12 due_date > now)

        $quote = $this->settlementService->calculateForeclosure($loan, Carbon::now());

        $this->assertEquals(12000.00, $quote['principal_outstanding']);
        // Accrued interest on 4 elapsed installments = 4 * 120 = 480.00
        $this->assertEquals(480.00, $quote['accrued_interest']);
        // Unearned future interest on 8 future installments = 8 * 120 = 960.00
        $this->assertEquals(960.00, $quote['unearned_interest_rebate']);
        // Foreclosure fee = 2% of 12000 = 240.00
        $this->assertEquals(240.00, $quote['foreclosure_fee']);
        // Final payoff = 12000 (Principal) + 480 (Accrued Earned Interest) + 240 (Foreclosure Fee) = 12720.00
        $this->assertEquals(12720.00, $quote['final_settlement_amount']);
    }

    public function test_foreclosure_with_partial_installment_payment(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);
        
        // Pay 1st installment partially: principal 1000 + interest 60 (half interest)
        $inst1 = $loan->installments()->where('installment_number', 1)->first();
        $inst1->update([
            'principal_paid' => 1000.00,
            'interest_paid' => 60.00,
            'total_paid' => 1060.00,
            'status' => 'partial',
        ]);
        $loan->update([
            'principal_outstanding' => 11000.00,
            'interest_outstanding' => 1380.00, // 1440 - 60
            'total_outstanding' => 12380.00,
        ]);

        $quote = $this->settlementService->calculateForeclosure($loan, Carbon::now());

        $this->assertEquals(11000.00, $quote['principal_outstanding']);
        // Accrued interest = remaining unpaid on inst 1 (60) + inst 2..4 (3 * 120 = 360) = 420.00
        $this->assertEquals(420.00, $quote['accrued_interest']);
        // Unearned future interest = 8 * 120 = 960.00
        $this->assertEquals(960.00, $quote['unearned_interest_rebate']);
        // Foreclosure fee = 2% of 11000 = 220.00
        $this->assertEquals(220.00, $quote['foreclosure_fee']);
        // Final payoff = 11000 + 420 + 220 = 11640.00
        $this->assertEquals(11640.00, $quote['final_settlement_amount']);
    }

    public function test_foreclosure_on_overdue_loan_preserves_earned_interest(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);
        // Add penalty of ₹300 and overdue fee of ₹50
        $loan->update([
            'penalty_outstanding' => 300.00,
            'fee_outstanding' => 50.00,
            'total_outstanding' => $loan->total_outstanding + 350.00,
            'status' => 'defaulted',
        ]);

        $quote = $this->settlementService->calculateForeclosure($loan, Carbon::now());

        $this->assertEquals(12000.00, $quote['principal_outstanding']);
        $this->assertEquals(480.00, $quote['accrued_interest']);
        $this->assertEquals(50.00, $quote['fee_outstanding']);
        $this->assertEquals(300.00, $quote['penalty_outstanding']);
        $this->assertEquals(240.00, $quote['foreclosure_fee']);
        // Final payoff = 12000 + 480 + 50 + 300 + 240 = 13070.00
        $this->assertEquals(13070.00, $quote['final_settlement_amount']);
    }

    public function test_foreclosure_lock_in_period_enforcement(): void
    {
        // Product scheme has 3 months lock-in. Create loan disbursed only 1 month ago.
        $scheme = $this->productScheme;
        $loan = $this->createActiveLoan('product', $scheme, 12000.00, 12);
        $recentDisbursement = Carbon::now(LoanSettlementService::TIMEZONE)->subMonth()->toDateString();
        $loan->update(['disbursement_date' => $recentDisbursement]);

        $quote = $this->settlementService->calculateForeclosure($loan, Carbon::now());
        $this->assertFalse($quote['lock_in']['is_allowed']);
        $this->assertStringContainsString('Foreclosure not allowed before 3 months', $quote['lock_in']['message']);

        // Attempting to execute should throw ValidationException
        $this->expectException(ValidationException::class);
        $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);
    }

    public function test_execute_cash_loan_foreclosure_and_verify_gl_entries(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $result = $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'remarks' => 'Voluntary full early foreclosure',
        ], $this->superAdmin);

        $loan->refresh();
        $this->assertEquals('closed', $loan->status);
        $this->assertEquals('foreclosure', $loan->closure_type);
        $this->assertEquals(0.00, (float) $loan->total_outstanding);
        $this->assertNotNull($loan->closed_at);

        // Verify Repayment Record
        $repayment = $result['repayment'];
        $this->assertInstanceOf(LoanRepayment::class, $repayment);
        $this->assertEquals(12720.00, (float) $repayment->amount);
        $this->assertEquals(12000.00, (float) $repayment->principal_paid);
        $this->assertEquals(480.00, (float) $repayment->interest_paid);

        // Verify GL Voucher
        $voucher = $result['voucher'];
        $this->assertInstanceOf(Voucher::class, $voucher);
        $this->assertEquals('loan_foreclosure', $voucher->reference_type);

        $entries = $voucher->entries;
        // Debit: Bank Account (1130) = 12720
        $debitEntry = $entries->where('debit', 12720.00)->first();
        $this->assertNotNull($debitEntry);
        $this->assertEquals('1130', $debitEntry->account->account_code);

        // Credit: Cash Loan Principal Receivable (1210) = 12000
        $creditPrinc = $entries->where('credit', 12000.00)->first();
        $this->assertNotNull($creditPrinc);
        $this->assertEquals('1210', $creditPrinc->account->account_code);

        // Credit: Interest Income from Cash Loans (4110) = 480
        $creditInt = $entries->where('credit', 480.00)->first();
        $this->assertNotNull($creditInt);
        $this->assertEquals('4110', $creditInt->account->account_code);

        // Credit: Foreclosure & Prepayment Fee Income (4240) = 240
        $creditFee = $entries->where('credit', 240.00)->first();
        $this->assertNotNull($creditFee);
        $this->assertEquals('4240', $creditFee->account->account_code);
    }

    public function test_execute_product_loan_foreclosure_and_verify_gl_entries(): void
    {
        // 4 months elapsed satisfies 3-month lock-in
        $loan = $this->createActiveLoan('product', $this->productScheme, 12000.00, 12);

        $result = $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);

        $loan->refresh();
        $this->assertEquals('closed', $loan->status);
        $this->assertEquals('foreclosure', $loan->closure_type);

        $voucher = $result['voucher'];
        $entries = $voucher->entries;

        // Credit: Product Loans Receivable (1220) = 12000
        $creditPrinc = $entries->where('credit', 12000.00)->first();
        $this->assertNotNull($creditPrinc);
        $this->assertEquals('1220', $creditPrinc->account->account_code);

        // Credit: Interest Income from Product Loans (4120) = 480
        $creditInt = $entries->where('credit', 480.00)->first();
        $this->assertNotNull($creditInt);
        $this->assertEquals('4120', $creditInt->account->account_code);

        // Credit: Foreclosure Fee (4240) = 500 (Flat)
        $creditFee = $entries->where('credit', 500.00)->first();
        $this->assertNotNull($creditFee);
        $this->assertEquals('4240', $creditFee->account->account_code);
    }

    public function test_future_installments_are_marked_waived_with_zero_balance(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);

        $installments = $loan->installments()->orderBy('installment_number')->get();
        // First 4 installments were elapsed -> marked paid
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals('paid', $installments[$i]->status);
        }
        // Future 8 installments -> marked waived
        for ($i = 4; $i < 12; $i++) {
            $this->assertEquals('waived', $installments[$i]->status);
        }
    }

    public function test_ots_with_fee_and_penalty_collection_and_loss_gl(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);
        $loan->update([
            'fee_outstanding' => 200.00,
            'penalty_outstanding' => 300.00,
            'total_outstanding' => 12000 + 1440 + 200 + 300, // 13940.00
        ]);

        // Propose OTS settlement of ₹8,000 (Borrower pays 8k out of 13.94k demand, Concession = 5,940)
        // Recovery waterfall on 8,000:
        // Penalty: 300 recovered
        // Fee: 200 recovered
        // Interest: 1440 recovered
        // Principal: remaining 6060 recovered (Principal loss = 12000 - 6060 = 5940)
        $otsCalc = $this->settlementService->calculateSettlementOts($loan, 8000.00);
        $this->assertEquals(5940.00, $otsCalc['discount_concession_amount']);
        $this->assertEquals('Company Admin', $otsCalc['required_approval_role']);

        // Create request
        $req = $this->settlementService->createSettlementRequest($loan, [
            'request_type' => 'settlement_ots',
            'proposed_settlement_amount' => 8000.00,
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Hardship OTS compromise',
        ], $this->loanOfficer);

        $this->assertEquals('pending_approval', $req->status);

        // Approve with Company Admin
        $this->settlementService->approveSettlementRequest($req, $this->companyAdmin, 'Approved by regional head');
        $req->refresh();
        $this->assertEquals('approved', $req->status);

        // Execute payment
        $result = $this->settlementService->executeApprovedSettlement($req, [
            'payment_method' => 'cash',
            'payment_date' => Carbon::now()->toDateString(),
        ], $this->superAdmin);

        $loan->refresh();
        $this->assertEquals('closed', $loan->status);
        $this->assertEquals('settlement', $loan->closure_type);

        $voucher = $result['voucher'];
        $entries = $voucher->entries;

        // Debit: Cash (1110) = 8000
        $debitCash = $entries->where('debit', 8000.00)->first();
        $this->assertNotNull($debitCash);

        // Debit: Loan Loss Provisioning & Write-Offs (5120) = 5940 (Principal Loss)
        $debitLoss = $entries->where('debit', 5940.00)->first();
        $this->assertNotNull($debitLoss);
        $this->assertEquals('5120', $debitLoss->account->account_code);

        // Credit: Principal Receivable (1210) = 12000 (100% full principal cleared)
        $creditPrinc = $entries->where('credit', 12000.00)->first();
        $this->assertNotNull($creditPrinc);
        $this->assertEquals('1210', $creditPrinc->account->account_code);

        // Credit: Penalty Income (4230) = 300
        $creditPen = $entries->where('credit', 300.00)->first();
        $this->assertNotNull($creditPen);
        $this->assertEquals('4230', $creditPen->account->account_code);

        // Credit: Fee Income (4210) = 200
        $creditFee = $entries->where('credit', 200.00)->first();
        $this->assertNotNull($creditFee);
        $this->assertEquals('4210', $creditFee->account->account_code);

        // Credit: Interest Income (4110) = 1440
        $creditInt = $entries->where('credit', 1440.00)->first();
        $this->assertNotNull($creditInt);
        $this->assertEquals('4110', $creditInt->account->account_code);
    }

    public function test_ots_approval_thresholds_branch_manager_company_admin_super_admin(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        // Scenario 1: Concession = ₹3,000 (<= 5,000) -> Branch Manager CAN approve
        $req1 = $this->settlementService->createSettlementRequest($loan, [
            'proposed_settlement_amount' => 10440.00, // Concession = 13440 - 10440 = 3000
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Small concession',
        ], $this->loanOfficer);

        $this->assertTrue($this->settlementService->canUserApprove($this->branchManager, $req1));
        $this->settlementService->approveSettlementRequest($req1, $this->branchManager);
        $this->assertEquals('approved', $req1->fresh()->status);

        // Scenario 2: Concession = ₹10,000 (> 5,000 && <= 25,000) -> Branch Manager FAILS, Company Admin PASSES
        $loan2 = $this->createActiveLoan('cash', $this->cashScheme, 20000.00, 12);
        $req2 = $this->settlementService->createSettlementRequest($loan2, [
            'proposed_settlement_amount' => 12400.00, // Concession = 10000
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Medium concession',
        ], $this->loanOfficer);

        $this->assertFalse($this->settlementService->canUserApprove($this->branchManager, $req2));
        $this->assertTrue($this->settlementService->canUserApprove($this->companyAdmin, $req2));

        // Scenario 3: Concession = ₹30,000 (> 25,000) -> Company Admin FAILS, Super Admin PASSES
        $loan3 = $this->createActiveLoan('cash', $this->cashScheme, 50000.00, 12);
        $req3 = $this->settlementService->createSettlementRequest($loan3, [
            'proposed_settlement_amount' => 26000.00, // Concession = 30000
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Large concession',
        ], $this->loanOfficer);

        $this->assertFalse($this->settlementService->canUserApprove($this->branchManager, $req3));
        $this->assertFalse($this->settlementService->canUserApprove($this->companyAdmin, $req3));
        $this->assertTrue($this->settlementService->canUserApprove($this->superAdmin, $req3));
    }

    public function test_loan_write_off_creates_loss_voucher_and_closes_loan(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $req = $this->settlementService->createSettlementRequest($loan, [
            'request_type' => 'write_off',
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Borrower untraceable; 100% loss write off',
        ], $this->superAdmin);

        $result = $this->settlementService->executeWriteOff($req, $this->superAdmin);

        $loan->refresh();
        $this->assertEquals('closed', $loan->status);
        $this->assertEquals('write_off', $loan->closure_type);

        $voucher = $result['voucher'];
        $this->assertEquals('loan_write_off', $voucher->reference_type);

        $entries = $voucher->entries;
        // Debit: GL 5120 = 12000
        $debitLoss = $entries->where('debit', 12000.00)->first();
        $this->assertNotNull($debitLoss);
        $this->assertEquals('5120', $debitLoss->account->account_code);

        // Credit: GL 1210 = 12000
        $creditPrinc = $entries->where('credit', 12000.00)->first();
        $this->assertNotNull($creditPrinc);
        $this->assertEquals('1210', $creditPrinc->account->account_code);
    }

    public function test_duplicate_execution_protection_and_idempotency(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);

        // Attempt second foreclosure on already closed loan
        $this->expectException(ValidationException::class);
        $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);
    }

    public function test_deleted_or_deactivated_user_audit_preservation(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $req = $this->settlementService->createSettlementRequest($loan, [
            'request_type' => 'settlement_ots',
            'proposed_settlement_amount' => 11000.00,
            'as_of_date' => Carbon::now()->toDateString(),
            'remarks' => 'Settlement audit test',
        ], $this->loanOfficer);

        $requestId = $req->id;
        $this->assertEquals($this->loanOfficer->id, $req->requested_by);

        // Soft delete the requester user
        $this->loanOfficer->delete();

        $reloaded = LoanSettlementRequest::find($requestId);
        $this->assertNotNull($reloaded);
        $this->assertEquals($this->loanOfficer->id, $reloaded->requested_by); // ID is preserved
        $this->assertEquals('Loan Officer User', $reloaded->requester->name); // User data accessible via withTrashed
    }

    public function test_noc_certificate_renders_for_closed_loan(): void
    {
        $loan = $this->createActiveLoan('cash', $this->cashScheme, 12000.00, 12);

        $this->settlementService->executeForeclosure($loan, [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_method' => 'cash',
        ], $this->superAdmin);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.loan-account.noc', $loan->id));

        $response->assertStatus(200);
        $response->assertSee('No Objection Certificate (NOC)');
        $response->assertSee($loan->loan_number);
        $response->assertSee('PAID IN FULL');
    }
}
