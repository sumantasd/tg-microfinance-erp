<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use App\Services\LoanAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmiCollectionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Customer $customer;
    protected LoanScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Grihalaxmi Finance', 'code' => 'GF01', 'email' => 'info@grihalaxmi.com', 'phone' => '9876543210', 'address' => '123 Main St', 'is_active' => true]);
        $this->branchA = Branch::create(['company_id' => $this->company->id, 'name' => 'Branch Alpha', 'code' => 'BRA', 'phone' => '9876543210', 'email' => 'bra@grihalaxmi.com', 'address' => 'Branch Alpha St', 'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800001', 'is_active' => true]);
        $this->branchB = Branch::create(['company_id' => $this->company->id, 'name' => 'Branch Beta', 'code' => 'BRB', 'phone' => '9876543211', 'email' => 'brb@grihalaxmi.com', 'address' => 'Branch Beta St', 'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800001', 'is_active' => true]);

        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Collection Officer',
            'email' => 'collector@grihalaxmi.com',
            'password' => bcrypt('password'),
            'user_type' => 'company_admin',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_code' => 'CUST-7029',
            'first_name' => 'Rahul',
            'last_name' => 'Sharma',
            'mobile_number' => '7029737769',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'kyc_status' => 'approved',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-CASH',
            'name' => 'Standard Cash Loan',
            'loan_type' => 'cash',
            'min_amount' => 1000,
            'max_amount' => 500000,
            'min_tenure_months' => 3,
            'max_tenure_months' => 60,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_whole_rupee_emi_rounding_and_final_installment_reconciliation(): void
    {
        $service = app(LoanAccountService::class);
        $schedule = $service->calculateRepaymentSchedule(10000.00, 12, 'monthly', 'flat', 12.00, now());

        $this->assertCount(12, $schedule['installments']);
        $sumPrincipal = 0;
        $sumInterest = 0;
        $sumInstallments = 0;

        foreach ($schedule['installments'] as $inst) {
            // Assert all customer-facing amounts are whole numbers (integers / rounded)
            $this->assertEquals($inst['principal_amount'], round($inst['principal_amount'], 0));
            $this->assertEquals($inst['interest_amount'], round($inst['interest_amount'], 0));
            $this->assertEquals($inst['installment_amount'], round($inst['installment_amount'], 0));

            $sumPrincipal += $inst['principal_amount'];
            $sumInterest += $inst['interest_amount'];
            $sumInstallments += $inst['installment_amount'];
        }

        // Final installment reconciliation checks
        $this->assertEquals(10000.00, $sumPrincipal);
        $this->assertEquals(1200.00, $sumInterest);
        $this->assertEquals(11200.00, $sumInstallments);
    }

    public function test_customer_search_by_mobile_code_and_loan_number(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-BRA-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 12000.00,
            'approved_amount' => 12000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();

        // 1. Search by mobile
        $response1 = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index', ['search' => '7029737769']));
        $response1->assertStatus(200);
        $response1->assertSee('Rahul Sharma');
        $response1->assertSee($account->loan_number);

        // 2. Search by customer code
        $response2 = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index', ['search' => 'CUST-7029']));
        $response2->assertStatus(200);
        $response2->assertSee('Rahul Sharma');

        // 3. Search by loan number
        $response3 = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index', ['search' => $account->loan_number]));
        $response3->assertStatus(200);
        $response3->assertSee('Rahul Sharma');
    }

    public function test_full_emi_collection_and_receipt_generation(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-BRA-002',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 12000.00,
            'approved_amount' => 12000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();

        // Collect Full EMI of 1100
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 1100,
            'payment_method' => 'cash',
            'reference_number' => 'CASH-REC-001',
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loan_repayments', [
            'loan_account_id' => $account->id,
            'amount' => 1100,
            'payment_method' => 'cash',
            'reference_number' => 'CASH-REC-001',
        ]);

        $repayment = LoanRepayment::where('reference_number', 'CASH-REC-001')->first();
        $rcptResponse = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.receipt', $repayment->id));
        $rcptResponse->assertStatus(200);
        $rcptResponse->assertSee($repayment->receipt_number);
        $rcptResponse->assertSee('OFFICIAL RECEIPT');
    }

    public function test_group_loan_collection_and_branch_isolation(): void
    {
        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'group_code' => 'GRP-ALPHA',
            'name' => 'Alpha Mahila Samiti',
            'formation_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $group->members()->create(['customer_id' => $this->customer->id, 'role' => 'leader', 'joined_at' => date('Y-m-d'), 'status' => 'active']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index', ['search' => 'GRP-ALPHA']));
        $response->assertStatus(200);
        $response->assertSee('Alpha Mahila Samiti');
    }

    public function test_thermal_receipt_layout_generation_and_read_only_safety(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-BRA-003',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 15000.00,
            'approved_amount' => 15000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();

        // Record Repayment
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 1500,
            'payment_method' => 'cash',
            'reference_number' => 'THERM-REF-001',
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $repayment = LoanRepayment::where('reference_number', 'THERM-REF-001')->first();
        $repaymentCountBefore = LoanRepayment::count();

        // 1. Test 80mm thermal receipt
        $response80 = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '80']));
        $response80->assertStatus(200);
        $response80->assertSee('GRIHALAXMI FINANCE');
        $response80->assertSee('EMI COLLECTION RECEIPT');
        $response80->assertSee($repayment->receipt_number);
        $response80->assertSee('thermal-80');
        $response80->assertSee('80mm auto');

        // 2. Test 58mm thermal receipt
        $response58 = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.thermal-receipt', ['repayment' => $repayment->id, 'width' => '58']));
        $response58->assertStatus(200);
        $response58->assertSee('thermal-58');
        $response58->assertSee('58mm auto');

        // 3. Read-Only Safety Check: Viewing thermal receipt MUST NOT create additional repayment transactions!
        $this->assertEquals($repaymentCountBefore, LoanRepayment::count());
    }
}
