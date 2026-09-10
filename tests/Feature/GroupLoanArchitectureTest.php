<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupMember;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\LoanAccountService;
use App\Services\LoanApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GroupLoanArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $adminUser;
    protected CustomerGroup $group;
    protected Customer $memberA;
    protected Customer $memberB;
    protected Customer $memberC;
    protected LoanScheme $scheme;
    protected LoanApplicationService $appService;
    protected LoanAccountService $accountService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Base Setup
        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Private Limited',
            'code' => 'GLF001',
            'email' => 'info@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna, Bihar',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'BR-001',
            'phone' => '06122345678',
            'address' => 'Patna Main Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'admin@test.com',
        ]);
        $this->adminUser->assignRole($superAdminRole);

        // 2. Create Group & 3 Members
        $this->group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'SHG-001',
            'name' => 'Lakshmi Self Help Group',
            'formation_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->memberA = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-A',
            'first_name' => 'Anita',
            'last_name' => 'Devi',
            'mobile_number' => '9800000001',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->memberB = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-B',
            'first_name' => 'Sunita',
            'last_name' => 'Kumari',
            'mobile_number' => '9800000002',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->memberC = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-C',
            'first_name' => 'Pooja',
            'last_name' => 'Singh',
            'mobile_number' => '9800000003',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        CustomerGroupMember::create(['group_id' => $this->group->id, 'customer_id' => $this->memberA->id, 'joined_at' => now()->toDateString(), 'status' => 'active']);
        CustomerGroupMember::create(['group_id' => $this->group->id, 'customer_id' => $this->memberB->id, 'joined_at' => now()->toDateString(), 'status' => 'active']);
        CustomerGroupMember::create(['group_id' => $this->group->id, 'customer_id' => $this->memberC->id, 'joined_at' => now()->toDateString(), 'status' => 'active']);

        // 3. Scheme
        $this->scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'code' => 'SCH-SHG-12M',
            'name' => 'SHG Group Micro Loan Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'group',
            'min_amount' => 5000.00,
            'max_amount' => 500000.00,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 0.00,
            'insurance_fee_percentage' => 0.00,
            'is_active' => true,
        ]);

        $this->appService = app(LoanApplicationService::class);
        $this->accountService = app(LoanAccountService::class);
    }

    public function test_group_contains_multiple_members(): void
    {
        $this->assertEquals(3, $this->group->activeMembers()->count());
    }

    public function test_sanctioning_group_application_creates_separate_individual_loan_account_per_member(): void
    {
        // Application Total = ₹1,00,000 (Member A: ₹50,000, Member B: ₹30,000, Member C: ₹20,000)
        $app = $this->appService->createApplication([
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 100000.00,
            'tenure_months' => 12,
        ], [
            ['customer_id' => $this->memberA->id, 'requested_amount' => 50000.00],
            ['customer_id' => $this->memberB->id, 'requested_amount' => 30000.00],
            ['customer_id' => $this->memberC->id, 'requested_amount' => 20000.00],
        ]);

        $app->update(['status' => 'submitted']);
        $this->appService->approveApplication($app, 100000.00);

        // Sanction
        $this->accountService->sanctionLoanFromApplication($app->fresh());

        // MUST create 3 distinct LoanAccount records linked to the group, NOT one shared loan account!
        $accounts = LoanAccount::where('customer_group_id', $this->group->id)->get();
        $this->assertCount(3, $accounts);

        // Verify each member got their own loan account with exact sanctioned amount
        $accountA = LoanAccount::where('customer_id', $this->memberA->id)->first();
        $accountB = LoanAccount::where('customer_id', $this->memberB->id)->first();
        $accountC = LoanAccount::where('customer_id', $this->memberC->id)->first();

        $this->assertNotNull($accountA);
        $this->assertNotNull($accountB);
        $this->assertNotNull($accountC);

        $this->assertEquals(50000.00, (float)$accountA->sanctioned_amount);
        $this->assertEquals(30000.00, (float)$accountB->sanctioned_amount);
        $this->assertEquals(20000.00, (float)$accountC->sanctioned_amount);

        // Loan numbers must be distinct
        $this->assertNotEquals($accountA->loan_number, $accountB->loan_number);
        $this->assertNotEquals($accountB->loan_number, $accountC->loan_number);
    }

    public function test_member_loan_amounts_and_emi_schedules_remain_independent(): void
    {
        $app = $this->appService->createApplication([
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 100000.00,
            'tenure_months' => 12,
        ], [
            ['customer_id' => $this->memberA->id, 'requested_amount' => 50000.00],
            ['customer_id' => $this->memberB->id, 'requested_amount' => 30000.00],
            ['customer_id' => $this->memberC->id, 'requested_amount' => 20000.00],
        ]);

        $app->update(['status' => 'submitted']);
        $this->appService->approveApplication($app, 100000.00);
        $this->accountService->sanctionLoanFromApplication($app->fresh());

        $accountA = LoanAccount::where('customer_id', $this->memberA->id)->first();
        $accountB = LoanAccount::where('customer_id', $this->memberB->id)->first();

        $this->assertCount(12, $accountA->installments);
        $this->assertCount(12, $accountB->installments);

        // EMI amount for Member A (₹50k @ 12% 12M) vs Member B (₹30k @ 12% 12M) must be different & independent!
        $emiA = (float) $accountA->installments->first()->installment_amount;
        $emiB = (float) $accountB->installments->first()->installment_amount;

        $this->assertGreaterThan($emiB, $emiA);
    }

    public function test_repayment_on_member_a_does_not_affect_member_b(): void
    {
        $app = $this->appService->createApplication([
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 80000.00,
            'tenure_months' => 12,
        ], [
            ['customer_id' => $this->memberA->id, 'requested_amount' => 50000.00],
            ['customer_id' => $this->memberB->id, 'requested_amount' => 30000.00],
        ]);

        $app->update(['status' => 'submitted']);
        $this->appService->approveApplication($app, 80000.00);
        $this->accountService->sanctionLoanFromApplication($app->fresh());

        $accountA = LoanAccount::where('customer_id', $this->memberA->id)->first();
        $accountB = LoanAccount::where('customer_id', $this->memberB->id)->first();

        $this->accountService->disburseCashLoan($accountA, 'cash');
        $this->accountService->disburseCashLoan($accountB, 'cash');

        $initialOutstandingB = (float) $accountB->fresh()->total_outstanding;

        // Record Repayment ONLY for Member A
        $emiAmountA = (float) $accountA->installments->first()->installment_amount;
        $this->accountService->recordRepayment($accountA, $emiAmountA, 'cash');

        // Member A's outstanding decreases
        $this->assertLessThan(56000.00, (float)$accountA->fresh()->total_outstanding);

        // Member B's outstanding MUST remain completely unchanged!
        $this->assertEquals($initialOutstandingB, (float)$accountB->fresh()->total_outstanding);
    }

    public function test_group_totals_equal_sum_of_individual_member_loans(): void
    {
        $app = $this->appService->createApplication([
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 100000.00,
            'tenure_months' => 12,
        ], [
            ['customer_id' => $this->memberA->id, 'requested_amount' => 50000.00],
            ['customer_id' => $this->memberB->id, 'requested_amount' => 30000.00],
            ['customer_id' => $this->memberC->id, 'requested_amount' => 20000.00],
        ]);

        $app->update(['status' => 'submitted']);
        $this->appService->approveApplication($app, 100000.00);
        $this->accountService->sanctionLoanFromApplication($app->fresh());

        $accountA = LoanAccount::where('customer_id', $this->memberA->id)->first();
        $accountB = LoanAccount::where('customer_id', $this->memberB->id)->first();
        $accountC = LoanAccount::where('customer_id', $this->memberC->id)->first();

        $this->accountService->disburseCashLoan($accountA, 'cash');
        $this->accountService->disburseCashLoan($accountB, 'cash');

        $sumDisbursed = (float)$accountA->disbursed_amount + (float)$accountB->disbursed_amount + (float)$accountC->disbursed_amount;
        $sumOutstanding = (float)$accountA->total_outstanding + (float)$accountB->total_outstanding + (float)$accountC->total_outstanding;

        $this->assertEquals(80000.00, $sumDisbursed);
        $this->assertEquals($sumDisbursed, (float)$this->group->fresh()->total_disbursed);
        $this->assertEquals($sumOutstanding, (float)$this->group->fresh()->total_outstanding);
    }

    public function test_closing_one_member_loan_does_not_close_the_group_or_other_member_loans(): void
    {
        $app = $this->appService->createApplication([
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 80000.00,
            'tenure_months' => 12,
        ], [
            ['customer_id' => $this->memberA->id, 'requested_amount' => 50000.00],
            ['customer_id' => $this->memberB->id, 'requested_amount' => 30000.00],
        ]);

        $app->update(['status' => 'submitted']);
        $this->appService->approveApplication($app, 80000.00);
        $this->accountService->sanctionLoanFromApplication($app->fresh());

        $accountA = LoanAccount::where('customer_id', $this->memberA->id)->first();
        $accountB = LoanAccount::where('customer_id', $this->memberB->id)->first();

        // Close Member A loan
        $accountA->update(['status' => 'closed', 'total_outstanding' => 0.00, 'principal_outstanding' => 0.00]);

        // Group status & Member B loan status must remain active / ready_for_disbursement!
        $this->assertEquals('active', $this->group->fresh()->status);
        $this->assertNotEquals('closed', $accountB->fresh()->status);
    }
}
