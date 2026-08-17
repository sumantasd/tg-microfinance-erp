<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacAndSecurityEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;

    protected User $superAdmin;
    protected User $companyAdmin;
    protected User $branchManager;
    protected User $loanOfficer;
    protected User $collectionOfficer;
    protected User $accountant;
    protected User $inventoryManager;
    protected User $hrManager;
    protected User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->companyA = Company::create([
            'name' => 'Company Alpha',
            'code' => 'ALPH',
            'email' => 'alpha@test.com',
            'phone' => '9876543210',
            'address' => '123 Alpha St',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company Beta',
            'code' => 'BETA',
            'email' => 'beta@test.com',
            'phone' => '9876543211',
            'address' => '456 Beta St',
            'is_active' => true,
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha Branch 1',
            'code' => 'A-001',
            'email' => 'branch1@alpha.com',
            'phone' => '9876543220',
            'address' => '100 Branch Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha Branch 2',
            'code' => 'A-002',
            'email' => 'branch2@alpha.com',
            'phone' => '9876543221',
            'address' => '200 Branch Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700002',
            'is_active' => true,
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Beta Branch 1',
            'code' => 'B-001',
            'email' => 'branch1@beta.com',
            'phone' => '9876543222',
            'address' => '300 Beta Road',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $this->companyAdmin = User::factory()->create([
            'name' => 'Company Admin User',
            'email' => 'companyadmin@test.com',
            'company_id' => $this->companyA->id,
            'status' => 'active',
        ]);
        $this->companyAdmin->assignRole('Company Admin');

        $this->branchManager = User::factory()->create([
            'name' => 'Branch Manager User',
            'email' => 'bm@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->loanOfficer = User::factory()->create([
            'name' => 'Loan Officer User',
            'email' => 'loanofficer@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->loanOfficer->assignRole('Loan Officer');

        $this->collectionOfficer = User::factory()->create([
            'name' => 'Collection Officer User',
            'email' => 'collector@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->collectionOfficer->assignRole('Collection Officer');

        $this->accountant = User::factory()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->accountant->assignRole('Accountant');

        $this->inventoryManager = User::factory()->create([
            'name' => 'Inventory Manager User',
            'email' => 'inventory@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->inventoryManager->assignRole('Inventory Manager');

        $this->hrManager = User::factory()->create([
            'name' => 'HR Manager User',
            'email' => 'hrmanager@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->hrManager->assignRole('HR Manager');

        $this->viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->viewer->assignRole('Viewer');
    }

    /** 1. Loan Officer sees only permitted menus in sidebar */
    public function test_loan_officer_sees_only_permitted_menus_in_sidebar(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Loan Management');
        $response->assertSee('Member Management');
        $response->assertSee('EMI Collection');

        // Forbidden menus should NOT appear
        $response->assertDontSee('Accounting & Finance');
        $response->assertDontSee('Enterprise HRM');
        $response->assertDontSee('System / Settings');
        $response->assertDontSee('Product Purchases');
    }

    /** 2. Loan Officer cannot access Accounting URL directly (HTTP 403) */
    public function test_loan_officer_cannot_access_accounting_url(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin/accounting');
        $response->assertStatus(403);
    }

    /** 3. Loan Officer cannot access Inventory URL directly (HTTP 403) */
    public function test_loan_officer_cannot_access_inventory_url(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin/inventory');
        $response->assertStatus(403);
    }

    /** 4. Loan Officer can access customer creation page */
    public function test_loan_officer_can_create_customer(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin/customer/create');
        $response->assertStatus(200);
    }

    /** 5. Loan Officer can create loan application */
    public function test_loan_officer_can_create_loan_application(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin/loan-application/create');
        $response->assertStatus(200);
    }

    /** 6. Loan Officer cannot sanction loan account without permission (HTTP 403) */
    public function test_loan_officer_cannot_sanction_loan_account(): void
    {
        $response = $this->actingAs($this->loanOfficer)->post('/admin/loan-account/sanction', [
            'loan_application_id' => 999,
        ]);
        $response->assertStatus(403);
    }

    /** 7. Collection Officer can access EMI collection */
    public function test_collection_officer_can_access_emi_collection(): void
    {
        $response = $this->actingAs($this->collectionOfficer)->get('/admin/emi-collection');
        $response->assertStatus(200);
    }

    /** 8. Collection Officer cannot access Accounting (HTTP 403) */
    public function test_collection_officer_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->collectionOfficer)->get('/admin/accounting');
        $response->assertStatus(403);
    }

    /** 9. Accountant can access accounting */
    public function test_accountant_can_access_accounting(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/accounting');
        $response->assertStatus(200);
    }

    /** 10. Accountant cannot sanction loan (HTTP 403) */
    public function test_accountant_cannot_sanction_loan(): void
    {
        $response = $this->actingAs($this->accountant)->post('/admin/loan-account/sanction', [
            'loan_application_id' => 999,
        ]);
        $response->assertStatus(403);
    }

    /** 11. Inventory Manager can access inventory */
    public function test_inventory_manager_can_access_inventory(): void
    {
        $response = $this->actingAs($this->inventoryManager)->get('/admin/inventory');
        $response->assertStatus(200);
    }

    /** 12. Inventory Manager cannot access payroll (HTTP 403) */
    public function test_inventory_manager_cannot_access_payroll(): void
    {
        $response = $this->actingAs($this->inventoryManager)->get('/admin/hrm/payroll');
        $response->assertStatus(403);
    }

    /** 13. HR Manager can access HR modules */
    public function test_hr_manager_can_access_hr(): void
    {
        $response = $this->actingAs($this->hrManager)->get('/admin/employee');
        $response->assertStatus(200);

        $responseAttendance = $this->actingAs($this->hrManager)->get('/admin/hrm/attendance');
        $responseAttendance->assertStatus(200);
    }

    /** 14. HR Manager cannot access accounting (HTTP 403) */
    public function test_hr_manager_cannot_access_accounting(): void
    {
        $response = $this->actingAs($this->hrManager)->get('/admin/accounting');
        $response->assertStatus(403);
    }

    /** 15. Branch Manager cannot modify or view another company's branch or unauthorized branch */
    public function test_branch_manager_cannot_access_another_branch_via_policy(): void
    {
        $this->assertFalse($this->branchManager->can('view', $this->branchB1));
        $this->assertFalse($this->branchManager->can('update', $this->branchB1));
        $this->assertTrue($this->branchManager->can('view', $this->branchA1));
    }

    /** 16. Company Admin cannot access another company */
    public function test_company_admin_cannot_access_another_company(): void
    {
        $this->assertTrue($this->companyAdmin->can('view', $this->companyA));
        $this->assertFalse($this->companyAdmin->can('view', $this->companyB));
        $this->assertFalse($this->companyAdmin->can('update', $this->companyB));
    }

    /** 17. Super Admin has unrestricted access */
    public function test_super_admin_has_full_access(): void
    {
        $routes = [
            '/admin/company',
            '/admin/branch',
            '/admin/customer',
            '/admin/loan-scheme',
            '/admin/loan-application',
            '/admin/loan-account',
            '/admin/inventory',
            '/admin/accounting',
            '/admin/employee',
            '/admin/system/users',
            '/admin/system/roles',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get($route);
            $response->assertStatus(200);
        }
    }

    /** 18. Unauthorized action returns 403 */
    public function test_unauthorized_action_returns_403(): void
    {
        $response = $this->actingAs($this->viewer)->post('/admin/customer', [
            'first_name' => 'John',
        ]);
        $response->assertStatus(403);
    }

    /** 19. Action buttons are hidden when user lacks permission */
    public function test_action_buttons_are_not_rendered_for_unauthorized_users(): void
    {
        $scheme = LoanScheme::create([
            'company_id' => $this->companyA->id,
            'name' => 'General Micro Loan',
            'code' => 'GML-01',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'interest_rate_per_annum' => 18,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'min_amount' => 1000,
            'max_amount' => 50000,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'mobile_number' => '9876543210',
            'gender' => 'male',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_number' => 'APP-TEST-001',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => now()->toDateString(),
            'requested_amount' => 20000.00,
            'approved_amount' => 20000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
        ]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);

        // Viewer can view account but should NOT see Disburse button
        $response = $this->actingAs($this->viewer)->get('/admin/loan-account/' . $account->id);
        $response->assertStatus(200);
        $response->assertDontSee('Disburse Cash Loan');

        // Branch Manager has loan.disburse permission and should see Disburse button
        $responseBm = $this->actingAs($this->branchManager)->get('/admin/loan-account/' . $account->id);
        $responseBm->assertStatus(200);
        $responseBm->assertSee('Disburse Cash Loan');
    }

    /** 20. Reports permission controls report access */
    public function test_reports_permission_controls_report_access(): void
    {
        $this->assertTrue($this->accountant->can('reports.view'));
        $this->assertTrue($this->inventoryManager->can('reports.view'));
        $this->assertTrue($this->hrManager->can('reports.view'));
        $this->assertFalse($this->loanOfficer->can('reports.view'));
    }

    /** 21. Reports export permission distinction */
    public function test_reports_export_permission_distinction(): void
    {
        $this->assertTrue($this->accountant->can('reports.export'));
        $this->assertFalse($this->viewer->can('reports.export'));
        $this->assertTrue($this->superAdmin->can('reports.export'));
    }

    /** 22. Penalty waiver permission enforcement */
    public function test_penalty_waiver_permission_enforcement(): void
    {
        $this->assertTrue($this->branchManager->can('loans.waive_penalty'));
        $this->assertTrue($this->companyAdmin->can('loans.waive_penalty'));
        $this->assertFalse($this->loanOfficer->can('loans.waive_penalty'));
        $this->assertFalse($this->collectionOfficer->can('loans.waive_penalty'));
    }

    /** 23. Spatie user role assignment and permission verification */
    public function test_spatie_user_role_assignment_and_permissions(): void
    {
        $testUser = User::factory()->create(['status' => 'active']);
        $testUser->assignRole('HR Manager');

        $this->assertTrue($testUser->hasRole('HR Manager'));
        $this->assertTrue($testUser->can('payroll.view'));
        $this->assertFalse($testUser->can('accounting.view'));
    }
}
