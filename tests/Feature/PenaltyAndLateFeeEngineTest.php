<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanPenaltyCharge;
use App\Models\LoanPenaltyWaiver;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\LoanAccountService;
use App\Services\OverdueService;
use App\Services\PenaltyService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenaltyAndLateFeeEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $adminUser;
    protected User $branchOfficer;
    protected User $unauthorizedUser;
    protected Customer $customer1;
    protected PenaltyService $penaltyService;
    protected LoanAccountService $loanAccountService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->penaltyService = app(PenaltyService::class);
        $this->loanAccountService = app(LoanAccountService::class);

        $this->companyA = Company::create([
            'name' => 'Company A Microfinance',
            'code' => 'COMA',
            'registration_number' => 'REG-COMA-01',
            'email' => 'finance@company-a.com',
            'phone' => '9876543210',
            'address' => 'Patna HQ, Bihar',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B Microfinance',
            'code' => 'COMB',
            'registration_number' => 'REG-COMB-01',
            'email' => 'finance@company-b.com',
            'phone' => '9876543211',
            'address' => 'Gaya HQ, Bihar',
            'is_active' => true,
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Patna Main Branch',
            'code' => 'PAT01',
            'email' => 'patna1@company-a.com',
            'phone' => '9876543212',
            'address' => 'Patna Main, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Danapur Branch',
            'code' => 'DAN01',
            'email' => 'danapur@company-a.com',
            'phone' => '9876543213',
            'address' => 'Danapur, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '801503',
            'is_active' => true,
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Gaya Branch',
            'code' => 'GAY01',
            'email' => 'gaya@company-b.com',
            'phone' => '9876543214',
            'address' => 'Gaya, Bihar',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Admin User',
            'email' => 'admin@company-a.com',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('Admin');

        $this->branchOfficer = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'name' => 'Branch Manager',
            'email' => 'manager@company-a.com',
            'password' => bcrypt('password123'),
        ]);
        $this->branchOfficer->assignRole('Branch Manager');

        $this->unauthorizedUser = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Field Officer',
            'email' => 'field@company-a.com',
            'password' => bcrypt('password123'),
        ]);
        $this->unauthorizedUser->assignRole('Field Officer');

        $this->customer1 = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'mobile_number' => '9876543220',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        // Financial Year for accounting tests
        FinancialYear::create([
            'company_id' => $this->companyA->id,
            'title' => 'FY 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);
    }

    protected function createCustomScheme(array $overrides = []): LoanScheme
    {
        return LoanScheme::create(array_merge([
            'company_id' => $this->companyA->id,
            'code' => 'SCH-' . uniqid(),
            'name' => 'Test Penalty Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'penalty_type' => 'percentage_one_time',
            'late_fee_percentage' => 2.00,
            'grace_period_days' => 5,
            'flat_penalty_amount' => 0.00,
            'max_penalty_amount' => null,
            'max_penalty_percentage' => null,
            'is_active' => true,
        ], $overrides));
    }

    protected function createLoanWithScheme(LoanScheme $scheme, Customer $customer, Branch $branch, float $principal = 12000.00): LoanAccount
    {
        $app = LoanApplication::create([
            'company_id' => $customer->company_id,
            'branch_id' => $branch->id,
            'application_number' => 'APP-' . uniqid(),
            'loan_scheme_id' => $scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $customer->id,
            'requested_amount' => $principal,
            'approved_amount' => $principal,
            'application_date' => '2026-01-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        return LoanAccount::create([
            'loan_number' => 'LN-' . uniqid(),
            'company_id' => $customer->company_id,
            'branch_id' => $branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'sanctioned_amount' => $principal,
            'disbursed_amount' => $principal,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => $principal,
            'interest_outstanding' => 1440.00,
            'fee_outstanding' => 0.00,
            'penalty_outstanding' => 0.00,
            'total_outstanding' => $principal + 1440.00,
            'status' => 'active',
            'sanction_date' => '2026-01-01',
            'disbursement_date' => '2026-01-01',
        ]);
    }

    /**
     * 1. No penalty when penalty_type = none
     */
    public function test_no_penalty_when_penalty_type_is_none(): void
    {
        $scheme = $this->createCustomScheme(['penalty_type' => 'none', 'grace_period_days' => 0]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-15'));
        $this->assertFalse($calc['is_eligible']);
        $this->assertEquals(0.00, $calc['incremental_penalty']);
    }

    /**
     * 2. Grace period prevents penalty (DPD <= Grace)
     */
    public function test_grace_period_prevents_penalty(): void
    {
        $scheme = $this->createCustomScheme(['penalty_type' => 'flat_one_time', 'flat_penalty_amount' => 100.00, 'grace_period_days' => 5]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        // Due Aug 10, As-Of Aug 14 -> DPD = 4 (Grace = 5)
        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-10',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-14'));
        $this->assertFalse($calc['is_eligible']);
        $this->assertEquals(0.00, $calc['incremental_penalty']);

        // As-Of Aug 16 -> DPD = 6 (DPD > Grace) -> Eligible!
        $calc2 = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-16'));
        $this->assertTrue($calc2['is_eligible']);
        $this->assertEquals(100.00, $calc2['incremental_penalty']);
    }

    /**
     * 3. One-time percentage calculation
     */
    public function test_one_time_percentage_calculation(): void
    {
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'percentage_one_time',
            'late_fee_percentage' => 3.00, // 3% on 1120 = 33.60
            'grace_period_days' => 2,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-10'));
        $this->assertTrue($calc['is_eligible']);
        // 1120 * 3% = 33.60
        $this->assertEquals(33.60, $calc['incremental_penalty']);
    }

    /**
     * 4. One-time flat calculation
     */
    public function test_one_time_flat_calculation(): void
    {
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'flat_one_time',
            'flat_penalty_amount' => 150.00,
            'grace_period_days' => 0,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-05'));
        $this->assertEquals(150.00, $calc['incremental_penalty']);
    }

    /**
     * 5. Per-day flat calculation
     */
    public function test_per_day_flat_calculation(): void
    {
        // Flat ₹20 per day, Grace = 3 days. Due: Aug 1. As-Of: Aug 11 -> DPD = 10, Eff Days = 7 -> 7 * 20 = ₹140
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'flat_per_day',
            'flat_penalty_amount' => 20.00,
            'grace_period_days' => 3,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-11'));
        $this->assertEquals(7, $calc['effective_penalty_days']);
        $this->assertEquals(140.00, $calc['incremental_penalty']);
    }

    /**
     * 6. Per-day percentage calculation
     */
    public function test_per_day_percentage_calculation(): void
    {
        // 0.1% per day on ₹1000 base, Grace = 0. Due: Aug 1, As-Of: Aug 6 -> DPD = 5, Eff Days = 5 -> 1000 * 0.001 * 5 = ₹5.00
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'percentage_per_day',
            'late_fee_percentage' => 0.10,
            'grace_period_days' => 0,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 0,
            'installment_amount' => 1000,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $calc = $this->penaltyService->calculateInstallmentPenalty($inst, Carbon::parse('2026-08-06'));
        $this->assertEquals(5.00, $calc['incremental_penalty']);
    }

    /**
     * 7-9. Maximum Amount Cap, Maximum Percentage Cap, and Combined Caps
     */
    public function test_penalty_caps_enforce_maximum_thresholds(): void
    {
        // Test Amount Cap: ₹50 per day * 10 days = ₹500, but cap = ₹200
        $schemeAmountCap = $this->createCustomScheme([
            'penalty_type' => 'flat_per_day',
            'flat_penalty_amount' => 50.00,
            'grace_period_days' => 0,
            'max_penalty_amount' => 200.00,
        ]);
        $loan1 = $this->createLoanWithScheme($schemeAmountCap, $this->customer1, $this->branchA1);
        $inst1 = LoanInstallment::create([
            'loan_account_id' => $loan1->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);
        $calc1 = $this->penaltyService->calculateInstallmentPenalty($inst1, Carbon::parse('2026-08-11'));
        $this->assertEquals(200.00, $calc1['cumulative_penalty']);

        // Test Percentage Cap: ₹500 flat, but max % = 5% of ₹1000 base = ₹50
        $schemePctCap = $this->createCustomScheme([
            'penalty_type' => 'flat_one_time',
            'flat_penalty_amount' => 500.00,
            'grace_period_days' => 0,
            'max_penalty_percentage' => 5.00,
        ]);
        $loan2 = $this->createLoanWithScheme($schemePctCap, $this->customer1, $this->branchA1);
        $inst2 = LoanInstallment::create([
            'loan_account_id' => $loan2->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);
        $calc2 = $this->penaltyService->calculateInstallmentPenalty($inst2, Carbon::parse('2026-08-05'));
        $this->assertEquals(50.00, $calc2['cumulative_penalty']);
    }

    /**
     * 10 & 11. Fully Paid Installments Do Not Receive Penalty & Partial Non-Compounding Penalty Base
     */
    public function test_fully_paid_and_partial_payment_penalty_base(): void
    {
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'percentage_one_time',
            'late_fee_percentage' => 10.00,
            'grace_period_days' => 0,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        // Fully Paid Inst
        $paidInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'principal_paid' => 1000,
            'total_paid' => 1000,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'paid',
        ]);
        $paidCalc = $this->penaltyService->calculateInstallmentPenalty($paidInst, Carbon::parse('2026-08-10'));
        $this->assertFalse($paidCalc['is_eligible']);
        $this->assertEquals(0.00, $paidCalc['incremental_penalty']);

        // Partial Paid Inst (Due 1000, Paid 600, Rem Base = 400). Existing penalty assessed = 0.
        $partialInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-08-01',
            'opening_principal' => 9000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'principal_paid' => 600,
            'total_paid' => 600,
            'penalty_amount' => 20.00, // already has 20 penalty
            'installment_amount' => 1020,
            'closing_principal' => 8400,
            'status' => 'partial',
        ]);
        $partialCalc = $this->penaltyService->calculateInstallmentPenalty($partialInst, Carbon::parse('2026-08-10'));
        // Penalty Base should strictly be 400 (NOT 420). 10% of 400 = 40.
        $this->assertEquals(400.00, $partialCalc['penalty_base']);
        $this->assertEquals(40.00, $partialCalc['cumulative_penalty']);
        // Incremental = 40 - 20 (already assessed) = 20.00
        $this->assertEquals(20.00, $partialCalc['incremental_penalty']);
    }

    /**
     * 12 & 13. Idempotency & Daily Incremental Charges via `loans:apply-penalties`
     */
    public function test_daily_penalty_command_is_idempotent_and_applies_incremental_charges(): void
    {
        $scheme = $this->createCustomScheme([
            'penalty_type' => 'flat_per_day',
            'flat_penalty_amount' => 10.00,
            'grace_period_days' => 0,
        ]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        // Due: 2026-08-01
        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // 1. Run on Aug 03 -> DPD = 2 -> 2 * 10 = ₹20
        $this->artisan('loans:apply-penalties', ['--company' => $this->companyA->id, '--date' => '2026-08-03'])
            ->assertSuccessful();

        $this->assertEquals(20.00, $inst->fresh()->penalty_amount);
        $this->assertEquals(1020.00, $inst->fresh()->installment_amount);
        $this->assertEquals(20.00, $loan->fresh()->penalty_outstanding);
        $this->assertEquals(1, LoanPenaltyCharge::count());

        // 2. Re-run on SAME DATE (Aug 03) -> Must be 100% idempotent (0 new charges)
        $this->artisan('loans:apply-penalties', ['--company' => $this->companyA->id, '--date' => '2026-08-03'])
            ->assertSuccessful();

        $this->assertEquals(20.00, $inst->fresh()->penalty_amount);
        $this->assertEquals(1, LoanPenaltyCharge::count());

        // 3. Run on NEXT DAY (Aug 04) -> DPD = 3 -> Cumulative = 30 -> Incremental = +10
        $this->artisan('loans:apply-penalties', ['--company' => $this->companyA->id, '--date' => '2026-08-04'])
            ->assertSuccessful();

        $this->assertEquals(30.00, $inst->fresh()->penalty_amount);
        $this->assertEquals(30.00, $loan->fresh()->penalty_outstanding);
        $this->assertEquals(2, LoanPenaltyCharge::count());
    }

    /**
     * 16-21. Penalty Waiver Workflow, Validation, Permissions, and No-GL-Reversal
     */
    public function test_penalty_waiver_workflow_and_rbac_protection(): void
    {
        $scheme = $this->createCustomScheme(['penalty_type' => 'flat_one_time', 'flat_penalty_amount' => 250.00, 'grace_period_days' => 0]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Apply penalty of ₹250
        $this->penaltyService->applyDailyPenalties($this->companyA->id, Carbon::parse('2026-08-05'));
        $this->assertEquals(250.00, $loan->fresh()->penalty_outstanding);

        // 1. Unauthorized Field Officer tries to waive -> Must fail with 403
        $this->actingAs($this->unauthorizedUser);
        $unauthRes = $this->post(route('admin.penalties.waive', $loan->id), [
            'amount' => 100.00,
            'reason' => 'Unauthorized attempt',
        ]);
        $unauthRes->assertForbidden();

        // 2. Admin waives ₹150 with justification
        $this->actingAs($this->adminUser);
        $res = $this->post(route('admin.penalties.waive', $loan->id), [
            'amount' => 150.00,
            'reason' => 'Borrower faced flood emergency - approved by Credit Committee',
            'loan_installment_id' => $inst->id,
        ]);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // Verify balance updates
        $this->assertEquals(100.00, $loan->fresh()->penalty_outstanding); // 250 - 150 = 100
        $this->assertEquals(100.00, $inst->fresh()->penalty_amount); // 250 - 150 = 100
        $this->assertEquals(1100.00, $inst->fresh()->installment_amount); // 1000 + 100 = 1100

        // Verify waiver record
        $this->assertDatabaseHas('loan_penalty_waivers', [
            'loan_account_id' => $loan->id,
            'waived_amount' => 150.00,
            'authorized_by' => $this->adminUser->id,
        ]);

        // 3. Attempting to waive more than remaining ₹100 should fail validation
        $excessRes = $this->post(route('admin.penalties.waive', $loan->id), [
            'amount' => 200.00,
            'reason' => 'Excessive amount',
        ]);
        $excessRes->assertSessionHasErrors('amount');
    }

    /**
     * 22 & 23. Repayment Waterfall Collects Penalty First (Tier 1) and Posts to GL 4230
     */
    public function test_repayment_waterfall_collects_penalty_first_and_posts_to_gl_4230(): void
    {
        $accountingService = app(AccountingService::class);
        $accountingService->seedDefaultChartOfAccounts($this->companyA->id);

        $scheme = $this->createCustomScheme(['penalty_type' => 'flat_one_time', 'flat_penalty_amount' => 100.00, 'grace_period_days' => 0]);
        $loan = $this->createLoanWithScheme($scheme, $this->customer1, $this->branchA1, 10000.00);

        $inst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1000 + 100,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Apply penalty of ₹100
        $this->penaltyService->applyDailyPenalties($this->companyA->id, Carbon::parse('2026-08-05'));
        $loan->refresh();
        $this->assertEquals(100.00, $loan->penalty_outstanding);

        // Repay ₹150 (Should pay ₹100 Penalty + ₹0 Fee + ₹50 Interest + ₹0 Principal)
        $updatedLoan = $this->loanAccountService->recordRepayment(
            $loan,
            150.00,
            'cash',
            'REF-PEN-001',
            'reduce_tenure',
            'Repayment with penalty',
            '2026-08-06'
        );

        $repayment = $updatedLoan->repayments()->latest('id')->first();
        $this->assertNotNull($repayment);
        $this->assertEquals(100.00, (float) $repayment->penalty_paid);
        $this->assertEquals(50.00, (float) $repayment->interest_paid);
        $this->assertEquals(0.00, (float) $repayment->principal_paid);

        // Loan account penalty outstanding should now be 0.00
        $this->assertEquals(0.00, $loan->fresh()->penalty_outstanding);

        // Verify GL Voucher has been posted with Credit to 4230 (Penalties & Late Overdue Charges)
        $voucher = $repayment->accountingVoucher()->with('entries.account')->first();
        $this->assertNotNull($voucher);
        $this->assertEquals('posted', $voucher->status);

        $penaltyCreditEntry = $voucher->entries->where('account.account_code', '4230')->first();
        $this->assertNotNull($penaltyCreditEntry);
        $this->assertEquals(100.00, (float) $penaltyCreditEntry->credit);
    }

    /**
     * 24 & 25. Company and Branch Isolation
     */
    public function test_company_and_branch_isolation(): void
    {
        $schemeB = LoanScheme::create([
            'company_id' => $this->companyB->id,
            'code' => 'SCH-B-' . uniqid(),
            'name' => 'Company B Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'penalty_type' => 'flat_one_time',
            'flat_penalty_amount' => 200.00,
            'grace_period_days' => 0,
            'is_active' => true,
        ]);

        $custB = Customer::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'customer_code' => 'CUST-B01',
            'first_name' => 'Bihari',
            'last_name' => 'Lal',
            'mobile_number' => '9876543299',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $loanB = $this->createLoanWithScheme($schemeB, $custB, $this->branchB1, 10000.00);
        $instB = LoanInstallment::create([
            'loan_account_id' => $loanB->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 0.00,
            'installment_amount' => 1000,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Apply penalty for Company B
        $this->penaltyService->applyDailyPenalties($this->companyB->id, Carbon::parse('2026-08-05'));
        $this->assertEquals(200.00, $loanB->fresh()->penalty_outstanding);

        // Login as Company A Admin
        $this->actingAs($this->adminUser);

        // Ledger should only show Company A, not Company B
        $response = $this->get(route('admin.penalties.ledger'));
        $response->assertOk();
        $response->assertDontSee($loanB->loan_number);

        // Cross-company waiver attempt must abort 403
        $crossWaiver = $this->post(route('admin.penalties.waive', $loanB->id), [
            'amount' => 50.00,
            'reason' => 'Unauthorized cross-company waiver',
        ]);
        $crossWaiver->assertForbidden();
    }
}
