<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;

    protected User $superAdmin;
    protected User $companyAdminA;
    protected User $branchManagerA1;
    protected User $loanOfficerA1;

    protected LoanScheme $cashScheme;
    protected Department $departmentA;
    protected Designation $designationA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        // Setup Companies & Branches
        $this->companyA = Company::create([
            'name' => 'Grihalaxmi Finance West Bengal',
            'code' => 'GFWB01',
            'registration_number' => 'REG-WB-1001',
            'email' => 'wb@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Kolkata, WB',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Grihalaxmi Finance Bihar',
            'code' => 'GFBR01',
            'registration_number' => 'REG-BR-1001',
            'email' => 'br@grihalaxmi.com',
            'phone' => '9876543220',
            'address' => 'Patna, Bihar',
            'is_active' => true,
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Kolkata Central Branch',
            'code' => 'KOL01',
            'email' => 'kolkata@grihalaxmi.com',
            'phone' => '9876543211',
            'address' => 'Park Street, Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'current_vault_balance' => 25000.00,
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Howrah Branch',
            'code' => 'HWH01',
            'email' => 'howrah@grihalaxmi.com',
            'phone' => '9876543212',
            'address' => 'Howrah Station Road',
            'city' => 'Howrah',
            'state' => 'West Bengal',
            'pincode' => '711101',
            'current_vault_balance' => 15000.00,
            'is_active' => true,
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Patna City Branch',
            'code' => 'PAT01',
            'email' => 'patna@grihalaxmi.com',
            'phone' => '9876543221',
            'address' => 'Boring Road, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'current_vault_balance' => 10000.00,
            'is_active' => true,
        ]);

        // Setup Users
        $this->superAdmin = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Super Administrator',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->companyAdminA = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Company Admin A',
            'email' => 'admin.a@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->companyAdminA->assignRole('Company Admin');

        $this->branchManagerA1 = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Branch Manager A1',
            'email' => 'bm.a1@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->branchManagerA1->assignRole('Branch Manager');

        $this->loanOfficerA1 = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Loan Officer A1',
            'email' => 'lo.a1@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->loanOfficerA1->assignRole('Loan Officer');

        // Setup Loan Scheme
        $this->cashScheme = LoanScheme::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'code' => 'MES-01',
            'name' => 'Micro-Enterprise Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'min_tenure_months' => 3,
            'max_tenure_months' => 24,
            'interest_rate_per_annum' => 18.00,
            'interest_calculation_method' => 'flat',
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 1.00,
            'documentation_charges' => 100.00,
            'foreclosure_fee_type' => 'none',
            'min_months_before_foreclosure' => 0,
            'is_active' => true,
        ]);

        // Setup Department and Designation
        $this->departmentA = Department::create([
            'company_id' => $this->companyA->id,
            'name' => 'Operations',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        $this->designationA = Designation::create([
            'company_id' => $this->companyA->id,
            'department_id' => $this->departmentA->id,
            'title' => 'Field Executive',
            'code' => 'FE',
            'is_active' => true,
        ]);
    }

    protected function createLoanWithApplication(Customer $customer, LoanScheme $scheme, array $overrides = []): LoanAccount
    {
        $app = LoanApplication::create([
            'company_id' => $customer->company_id,
            'branch_id' => $customer->branch_id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => $scheme->loan_type,
            'borrower_type' => 'individual',
            'application_number' => 'APP-' . uniqid(),
            'application_date' => date('Y-m-d'),
            'requested_amount' => $overrides['sanctioned_amount'] ?? 50000.00,
            'tenure_months' => $overrides['tenure_months'] ?? 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'purpose' => 'Business Loan',
            'status' => 'approved',
            'approved_amount' => $overrides['sanctioned_amount'] ?? 50000.00,
            'created_by' => $this->superAdmin->id,
        ]);

        return LoanAccount::create(array_merge([
            'company_id' => $customer->company_id,
            'branch_id' => $customer->branch_id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'loan_application_id' => $app->id,
            'loan_number' => 'LN-' . uniqid(),
            'loan_type' => $scheme->loan_type,
            'borrower_type' => 'individual',
            'sanctioned_amount' => 50000.00,
            'disbursed_amount' => 50000.00,
            'principal_outstanding' => 45000.00,
            'interest_outstanding' => 3000.00,
            'fee_outstanding' => 0.00,
            'penalty_outstanding' => 0.00,
            'total_interest_amount' => 3000.00,
            'total_repayment_amount' => 48000.00,
            'total_outstanding' => 48000.00,
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'repayment_frequency' => 'monthly',
            'sanction_date' => Carbon::today()->toDateString(),
            'disbursement_date' => Carbon::today()->toDateString(),
            'maturity_date' => Carbon::today()->addMonths(12)->toDateString(),
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ], $overrides));
    }

    public function test_admin_dashboard_loads_successfully_and_has_correct_page_title(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Grihalaxmi Finance ERP');
        $response->assertDontSee('SaaS Finance ERP Dashboard - TG Microfinance ERP');
    }

    public function test_dashboard_displays_empty_states_when_database_is_empty(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('No collections recorded');
        $response->assertSee('No recent activity');
        $response->assertSee('No active loans found');
        $response->assertDontSee('Robert Vance');
        $response->assertDontSee('Sarah Jenkins');
        $response->assertDontSee('Elena Rostova');
    }

    public function test_dashboard_uses_real_customer_and_loan_data(): void
    {
        // Create real customer
        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Subhash',
            'last_name' => 'Mukherjee',
            'customer_code' => 'CUST-REAL-101',
            'mobile_number' => '9876543210',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $loan = $this->createLoanWithApplication($customer, $this->cashScheme, [
            'loan_number' => 'LN-REAL-999',
            'sanctioned_amount' => 50000.00,
            'disbursed_amount' => 50000.00,
            'principal_outstanding' => 45000.00,
            'disbursement_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('₹45,000.00'); // Active portfolio amount
        $response->assertSee('₹50,000.00'); // Today disbursement
        $response->assertSee('Micro-Enterprise Scheme');
    }

    public function test_dashboard_uses_real_collection_data(): void
    {
        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Ananya',
            'last_name' => 'Roy',
            'customer_code' => 'CUST-COLLECT-01',
            'mobile_number' => '9876543219',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $loan = $this->createLoanWithApplication($customer, $this->cashScheme, [
            'loan_number' => 'LN-COLLECT-01',
            'principal_outstanding' => 20000.00,
        ]);

        $repayment = LoanRepayment::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'loan_account_id' => $loan->id,
            'receipt_number' => 'RCP-REAL-555',
            'repayment_type' => 'regular',
            'payment_method' => 'cash',
            'amount' => 3500.00,
            'principal_paid' => 3000.00,
            'interest_paid' => 500.00,
            'fee_paid' => 0.00,
            'penalty_paid' => 0.00,
            'payment_date' => Carbon::today()->toDateString(),
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('₹3,500.00');
        $response->assertSee('Ananya Roy');
        $response->assertSee('LN-COLLECT-01');
    }

    public function test_dashboard_uses_real_activity_logs(): void
    {
        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Activity',
            'last_name' => 'Customer',
            'customer_code' => 'CUST-ACT-1',
            'mobile_number' => '9876543299',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin);
        app(ActivityLogService::class)->log('member_enrolled', $customer);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Member Enrolled');
        $response->assertSee('By: Super Administrator');
    }

    public function test_global_search_finds_customer_by_name_code_and_phone(): void
    {
        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Priyanka',
            'last_name' => 'Banerjee',
            'customer_code' => 'CUST-PB-888',
            'mobile_number' => '9876543210',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Search by name
        $resName = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'Priyanka']));
        $resName->assertStatus(200);
        $resName->assertSee('Priyanka Banerjee');
        $resName->assertSee('CUST-PB-888');

        // Search by mobile
        $resMobile = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => '9876543210']));
        $resMobile->assertStatus(200);
        $resMobile->assertSee('Priyanka Banerjee');

        // Search via JSON AJAX
        $resJson = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'CUST-PB-888', 'format' => 'json']));
        $resJson->assertStatus(200);
        $resJson->assertJsonFragment(['title' => 'Priyanka Banerjee']);
    }

    public function test_global_search_finds_loan_account_and_application(): void
    {
        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Amitabh',
            'last_name' => 'Ghosh',
            'customer_code' => 'CUST-AG-101',
            'mobile_number' => '9876543213',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $loan = $this->createLoanWithApplication($customer, $this->cashScheme, [
            'loan_number' => 'LN-SEARCH-777',
            'principal_outstanding' => 10000.00,
            'interest_outstanding' => 2000.00,
            'total_outstanding' => 12000.00,
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_number' => 'APP-SEARCH-444',
            'application_date' => date('Y-m-d'),
            'requested_amount' => 20000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'purpose' => 'Business Expansion',
            'status' => 'submitted',
            'created_by' => $this->superAdmin->id,
        ]);

        $resLoan = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'LN-SEARCH-777']));
        $resLoan->assertStatus(200);
        $resLoan->assertSee('LN-SEARCH-777');
        $resLoan->assertSee('Amitabh Ghosh');

        $resApp = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'APP-SEARCH-444']));
        $resApp->assertStatus(200);
        $resApp->assertSee('APP-SEARCH-444');
    }

    public function test_global_search_finds_product_and_employee(): void
    {
        $product = Product::create([
            'company_id' => $this->companyA->id,
            'name' => 'Bajaj Mixer Grinder 750W',
            'sku' => 'SKU-MIX-750',
            'unit_price' => 3200.00,
            'cost_price' => 2500.00,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'department_id' => $this->departmentA->id,
            'designation_id' => $this->designationA->id,
            'first_name' => 'Debashis',
            'last_name' => 'Sengupta',
            'employee_code' => 'EMP-WB-0042',
            'phone' => '9123456789',
            'email' => 'debashis@grihalaxmi.com',
            'gender' => 'male',
            'dob' => '1990-01-01',
            'joining_date' => '2023-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $resProduct = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'SKU-MIX-750']));
        $resProduct->assertStatus(200);
        $resProduct->assertSee('Bajaj Mixer Grinder 750W');

        $resEmp = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'EMP-WB-0042']));
        $resEmp->assertStatus(200);
        $resEmp->assertSee('Debashis Sengupta');
    }

    public function test_branch_isolation_in_dashboard_and_search(): void
    {
        // Data in Branch A1
        $customerA1 = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Kolkata',
            'last_name' => 'Borrower',
            'customer_code' => 'CUST-A1-1',
            'mobile_number' => '9876543214',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Data in Branch A2
        $customerA2 = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'first_name' => 'Howrah',
            'last_name' => 'Borrower',
            'customer_code' => 'CUST-A2-1',
            'mobile_number' => '9876543215',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Branch Manager of Branch A1 searches
        $res = $this->actingAs($this->branchManagerA1)->get(route('admin.search', ['q' => 'Borrower']));
        $res->assertStatus(200);
        $res->assertSee('Kolkata Borrower');
        $res->assertDontSee('Howrah Borrower');
    }

    public function test_company_isolation_in_dashboard_and_search(): void
    {
        // Customer in Company A
        $custA = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'WestBengal',
            'last_name' => 'Client',
            'customer_code' => 'CUST-WB-1',
            'mobile_number' => '9876543216',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Customer in Company B
        $custB = Customer::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'first_name' => 'Bihar',
            'last_name' => 'Client',
            'customer_code' => 'CUST-BR-1',
            'mobile_number' => '9876543217',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Company Admin of Company A searches
        $res = $this->actingAs($this->companyAdminA)->get(route('admin.search', ['q' => 'Client']));
        $res->assertStatus(200);
        $res->assertSee('WestBengal Client');
        $res->assertDontSee('Bihar Client');
    }

    public function test_global_search_finds_customer_group_and_loan_scheme(): void
    {
        $group = CustomerGroup::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Lakshmi Self Help Group',
            'group_code' => 'SHG-LAKSHMI-01',
            'formation_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $resGroup = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'Lakshmi']));
        $resGroup->assertStatus(200);
        $resGroup->assertSee('Lakshmi Self Help Group');
        $resGroup->assertSee('SHG-LAKSHMI-01');

        $resScheme = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'Micro-Enterprise']));
        $resScheme->assertStatus(200);
        $resScheme->assertSee('Micro-Enterprise Scheme');
        $resScheme->assertSee('MES-01');
    }

    public function test_rbac_permissions_restrict_unpermitted_search_entities(): void
    {
        // Create user with ONLY customer.view permission (no product.view or employee.view)
        $limitedUser = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Limited Staff Member',
            'email' => 'limited@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $limitedUser->givePermissionTo('customer.view');

        // Create a customer, a product, and an employee all containing 'Special'
        Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'first_name' => 'Special',
            'last_name' => 'Borrower',
            'customer_code' => 'CUST-SPEC-1',
            'mobile_number' => '9876543233',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ]);

        Product::create([
            'company_id' => $this->companyA->id,
            'name' => 'Special Sewing Machine',
            'sku' => 'SKU-SPEC-SEW',
            'unit_price' => 5000.00,
            'cost_price' => 4000.00,
            'is_active' => true,
        ]);

        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'department_id' => $this->departmentA->id,
            'designation_id' => $this->designationA->id,
            'first_name' => 'Special',
            'last_name' => 'Officer',
            'employee_code' => 'EMP-SPEC-01',
            'phone' => '9123456700',
            'email' => 'special.officer@grihalaxmi.com',
            'gender' => 'male',
            'dob' => '1992-01-01',
            'joining_date' => '2023-01-01',
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        $res = $this->actingAs($limitedUser)->get(route('admin.search', ['q' => 'Special']));
        $res->assertStatus(200);
        $res->assertSee('Special Borrower');
        $res->assertDontSee('Special Sewing Machine');
        $res->assertDontSee('Special Officer');
    }

    public function test_search_result_limits_to_ten_per_entity(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Customer::create([
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA1->id,
                'first_name' => 'BulkMember',
                'last_name' => "Num{$i}",
                'customer_code' => "CUST-BULK-{$i}",
                'mobile_number' => "98765432" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'gender' => 'female',
                'registration_date' => date('Y-m-d'),
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        $res = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'BulkMember', 'format' => 'json']));
        $res->assertStatus(200);
        $data = $res->json();
        $this->assertCount(10, $data['categories']['Customers']);
        $this->assertEquals(10, $data['total_results']);
    }

    public function test_search_no_results_displays_friendly_empty_message(): void
    {
        $res = $this->actingAs($this->superAdmin)->get(route('admin.search', ['q' => 'NON_EXISTENT_QUERY_XYZ']));

        $res->assertStatus(200);
        $res->assertSee('No matching records found.');
    }

    public function test_unauthenticated_guest_is_redirected_from_dashboard_and_search(): void
    {
        $resDash = $this->get(route('admin.dashboard'));
        $resDash->assertRedirect(route('login'));

        $resSearch = $this->get(route('admin.search', ['q' => 'test']));
        $resSearch->assertRedirect(route('login'));
    }
}

