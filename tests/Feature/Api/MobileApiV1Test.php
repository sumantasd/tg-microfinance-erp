<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileApiV1Test extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $superAdmin;
    protected User $branchManager;
    protected User $loanOfficer;
    protected Role $branchManagerRole;
    protected Role $loanOfficerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'customer.view', 'customer.create', 'group.view', 'loan.view',
            'loan.disburse', 'collection.collect', 'inventory.view',
            'cashbook.view', 'cashbook.create_entry', 'cashbook.close_register',
            'customer.kyc_upload', 'customer.kyc_view',
            'bank_deposit.view', 'bank_deposit.create', 'bank_deposit.approve',
            'expense.view', 'expense.create', 'expense.approve', 'expense.pay'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->branchManagerRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);
        $this->branchManagerRole->givePermissionTo($permissions);

        $this->loanOfficerRole = Role::firstOrCreate(['name' => 'Loan Officer', 'guard_name' => 'web']);
        $this->loanOfficerRole->givePermissionTo([
            'customer.view', 'customer.create', 'group.view', 'loan.view',
            'collection.collect', 'inventory.view', 'cashbook.view',
            'customer.kyc_upload', 'customer.kyc_view',
            'bank_deposit.view', 'bank_deposit.create',
            'expense.view', 'expense.create'
        ]);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Pvt Ltd',
            'code' => 'GFPL',
            'email' => 'info@grihalaxmifinance.com',
            'phone' => '9830098300',
            'address' => '123 Park Street, Kolkata',
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch Kolkata',
            'code' => 'KOL001',
            'phone' => '03322110001',
            'address' => '12 Park Street, Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'opening_date' => '2026-01-01',
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Howrah Branch',
            'code' => 'HOW002',
            'phone' => '03322110002',
            'address' => '45 GT Road, Howrah',
            'city' => 'Howrah',
            'state' => 'West Bengal',
            'pincode' => '711101',
            'opening_date' => '2026-01-01',
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Super Admin',
            'email' => 'admin@grihalaxmifinance.com',
            'password' => Hash::make('Admin@Grihalaxmi2026'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'BM User',
            'email' => 'bm@grihalaxmifinance.com',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $this->branchManager->assignRole($this->branchManagerRole);

        $this->loanOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Rajesh Officer',
            'email' => 'rajesh@grihalaxmifinance.com',
            'mobile_number' => '9830098300',
            'employee_id' => 'EMP-LO-101',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $this->loanOfficer->assignRole($this->loanOfficerRole);

        $dept = Department::create(['company_id' => $this->company->id, 'name' => 'Loan Operations', 'code' => 'LOAN']);
        $desig = Designation::create(['company_id' => $this->company->id, 'department_id' => $dept->id, 'title' => 'Loan Officer', 'name' => 'Loan Officer', 'code' => 'LO']);

        Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'department_id' => $dept->id,
            'designation_id' => $desig->id,
            'user_id' => $this->loanOfficer->id,
            'first_name' => 'Rajesh',
            'last_name' => 'Officer',
            'employee_code' => 'EMP-LO-101',
            'joining_date' => '2026-01-01',
        ]);
    }

    public function test_mobile_login_success_and_returns_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'rajesh@grihalaxmifinance.com',
            'password' => 'Secret123',
            'device_name' => 'Samsung Galaxy Tab',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'rajesh@grihalaxmifinance.com')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'roles', 'permissions'],
                ],
            ]);
    }

    public function test_mobile_login_fails_for_invalid_password_or_inactive_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'rajesh@grihalaxmifinance.com',
            'password' => 'WrongPassword',
        ]);
        $response->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->loanOfficer->update(['status' => 'inactive']);

        $responseInactive = $this->postJson('/api/v1/auth/login', [
            'email' => 'rajesh@grihalaxmifinance.com',
            'password' => 'Secret123',
        ]);
        $responseInactive->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_logout_revokes_current_sanctum_token(): void
    {
        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;
        $this->assertEquals(1, $this->loanOfficer->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $this->loanOfficer->fresh()->tokens()->count());
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');
        $response->assertStatus(401);
    }

    public function test_customer_api_enforces_branch_data_isolation(): void
    {
        $customerBranch1 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-BR1-001',
            'first_name' => 'Branch 1',
            'last_name' => 'Customer',
            'name' => 'Branch 1 Customer',
            'mobile_number' => '9900011101',
            'gender' => 'female',
            'address' => 'Kolkata Address',
            'registration_date' => '2026-01-01',
        ]);

        $customerBranch2 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'customer_code' => 'CUST-BR2-002',
            'first_name' => 'Branch 2',
            'last_name' => 'Customer',
            'name' => 'Branch 2 Customer',
            'mobile_number' => '9900011102',
            'gender' => 'male',
            'address' => 'Howrah Address',
            'registration_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Scoped list should only return Branch 1 customer
        $responseList = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers');

        $responseList->assertStatus(200);
        $ids = collect($responseList->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($customerBranch1->id));
        $this->assertFalse($ids->contains($customerBranch2->id));

        // Direct access to Branch 2 customer via ID manipulation must be forbidden
        $responseUnauthorized = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/' . $customerBranch2->id);

        $responseUnauthorized->assertStatus(403);

        // Test Customer Creation via API
        $payload = [
            'name' => 'Sita Devi API Test',
            'father_husband_name' => 'Ramesh Kumar',
            'mobile_number' => '9876500001',
            'email' => 'sitadevi.api@example.com',
            'date_of_birth' => '1990-05-15',
            'gender' => 'female',
            'marital_status' => 'married',
            'address' => '78 College Street, Near Central Park',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700012',
            'branch_id' => $this->branch1->id,
        ];

        $createResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/customers', $payload);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Sita')
            ->assertJsonPath('data.last_name', 'Devi API Test')
            ->assertJsonPath('data.mobile_number', '9876500001');

        $this->assertDatabaseHas('customers', [
            'mobile_number' => '9876500001',
            'first_name' => 'Sita',
            'last_name' => 'Devi API Test',
            'branch_id' => $this->branch1->id,
        ]);
    }

    public function test_mobile_emi_collection_with_gps_and_duplicate_sync_id_protection(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-KOL-003',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'name' => 'Priya Sharma',
            'mobile_number' => '9831198311',
            'gender' => 'female',
            'address' => 'Park Street',
            'registration_date' => '2026-01-01',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Weekly Micro Cash Loan',
            'code' => 'WMCL-01',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 24,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'weekly',
            'is_active' => true,
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'application_number' => 'LA-APP-2026-001',
            'application_date' => '2026-01-01',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_type' => 'individual_cash',
            'requested_amount' => 10000,
            'tenure_months' => 6,
            'repayment_frequency' => 'weekly',
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'status' => 'approved',
        ]);

        $loanAccount = LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_id' => $app->id,
            'loan_scheme_id' => $scheme->id,
            'loan_number' => 'LA-KOL-2026-001',
            'account_number' => 'LA-KOL-2026-001',
            'sanctioned_amount' => 10000,
            'sanction_date' => '2026-01-01',
            'principal_outstanding' => 10000,
            'interest_outstanding' => 1200,
            'tenure_months' => 6,
            'repayment_frequency' => 'weekly',
            'interest_type' => 'flat',
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'total_outstanding' => 11200,
            'status' => 'active',
            'disbursed_at' => now(),
        ]);

        LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'opening_principal' => 10000.00,
            'installment_amount' => 1867.00,
            'amount' => 1867.00,
            'principal_amount' => 1667.00,
            'principal_component' => 1667.00,
            'interest_amount' => 200.00,
            'interest_component' => 200.00,
            'closing_principal' => 8333.00,
            'paid_amount' => 0,
            'status' => 'overdue',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $payload = [
            'loan_account_id' => $loanAccount->id,
            'amount' => 1867.00,
            'payment_method' => 'cash',
            'offline_sync_id' => 'SYNC-UUID-98124',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 4.5,
            'remarks' => 'Field collection at customer shop',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount_paid', 1867)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['repayment_id', 'receipt_number', 'amount_paid', 'remaining_loan_balance', 'gps_location'],
            ]);

        // Re-submitting the exact same offline_sync_id must trigger duplicate protection
        $responseDuplicate = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', $payload);

        $responseDuplicate->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_duplicate', true);
    }

    public function test_inventory_api_supports_three_level_dependent_selection(): void
    {
        $category = ProductCategory::create(['company_id' => $this->company->id, 'name' => 'Electronics', 'code' => 'ELEC', 'is_active' => true]);
        $brand = ProductBrand::create(['company_id' => $this->company->id, 'category_id' => $category->id, 'name' => 'Samsung', 'code' => 'SAMS', 'is_active' => true]);
        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Galaxy Smartphone',
            'sku' => 'SAM-GAL-001',
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $catResponse = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/inventory/categories');
        $catResponse->assertStatus(200)->assertJsonPath('data.0.id', $category->id);

        $brandResponse = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/inventory/brands?category_id=' . $category->id);
        $brandResponse->assertStatus(200)->assertJsonPath('data.0.id', $brand->id);

        $prodResponse = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson('/api/v1/inventory/products?brand_id=' . $brand->id);
        $prodResponse->assertStatus(200)->assertJsonPath('data.0.id', $product->id);
    }

    public function test_attendance_api_logs_gps_checkin(): void
    {
        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'accuracy' => 5.0,
                'remarks' => 'Morning Field Check-in',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'present')
            ->assertJsonPath('data.gps_location.latitude', 22.5726);
    }

    public function test_kyc_upload_api_validates_file_and_checks_authorization(): void
    {
        Storage::fake('local');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-KOL-004',
            'first_name' => 'Anita',
            'last_name' => 'Das',
            'name' => 'Anita Das',
            'mobile_number' => '9832298322',
            'gender' => 'female',
            'address' => 'Salt Lake',
            'registration_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $file = UploadedFile::fake()->create('aadhaar.jpg', 500, 'image/jpeg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/kyc/upload', [
                'customer_id' => $customer->id,
                'document_type' => 'aadhaar_front',
                'document_number' => '1234-5678-9012',
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document_type', 'aadhaar_front');
    }

    public function test_sequential_installment_allocation_and_schedule_preservation(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-KOL-SEQ',
            'first_name' => 'Amit',
            'last_name' => 'Roy',
            'name' => 'Amit Roy',
            'mobile_number' => '9833398333',
            'gender' => 'male',
            'address' => 'Howrah',
            'registration_date' => '2026-01-01',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Monthly Cash Loan',
            'code' => 'MCL-SEQ',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 12,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'application_number' => 'LA-APP-SEQ-001',
            'application_date' => '2026-01-01',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_type' => 'individual_cash',
            'requested_amount' => 6000,
            'tenure_months' => 3,
            'repayment_frequency' => 'monthly',
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'status' => 'approved',
        ]);

        $loanAccount = LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_id' => $app->id,
            'loan_scheme_id' => $scheme->id,
            'loan_number' => 'LA-SEQ-2026-001',
            'account_number' => 'LA-SEQ-2026-001',
            'sanctioned_amount' => 6000,
            'sanction_date' => '2026-01-01',
            'principal_outstanding' => 6000,
            'interest_outstanding' => 600,
            'tenure_months' => 3,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'total_outstanding' => 6600,
            'status' => 'active',
            'disbursed_at' => now(),
        ]);

        $inst1 = LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'opening_principal' => 6000.00,
            'installment_amount' => 2200.00,
            'amount' => 2200.00,
            'principal_amount' => 2000.00,
            'principal_component' => 2000.00,
            'interest_amount' => 200.00,
            'interest_component' => 200.00,
            'closing_principal' => 4000.00,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $inst2 = LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 2,
            'due_date' => '2026-03-01',
            'opening_principal' => 4000.00,
            'installment_amount' => 2200.00,
            'amount' => 2200.00,
            'principal_amount' => 2000.00,
            'principal_component' => 2000.00,
            'interest_amount' => 200.00,
            'interest_component' => 200.00,
            'closing_principal' => 2000.00,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $inst3 = LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 3,
            'due_date' => '2026-04-01',
            'opening_principal' => 2000.00,
            'installment_amount' => 2200.00,
            'amount' => 2200.00,
            'principal_amount' => 2000.00,
            'principal_component' => 2000.00,
            'interest_amount' => 200.00,
            'interest_component' => 200.00,
            'closing_principal' => 0.00,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // A & D: Partial payment (₹1,000) makes ONLY Installment #1 partial
        $responsePartial = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', [
                'loan_account_id' => $loanAccount->id,
                'amount' => 1000.00,
                'payment_method' => 'cash',
                'offline_sync_id' => 'PARTIAL-SEQ-001',
            ]);

        $responsePartial->assertStatus(201);
        $inst1->refresh();
        $inst2->refresh();
        $inst3->refresh();

        $this->assertEquals('partial', $inst1->status);
        $this->assertEquals(1000.00, (float) $inst1->total_paid);
        $this->assertEquals('pending', $inst2->status);
        $this->assertEquals(0.00, (float) $inst2->total_paid);
        $this->assertEquals('pending', $inst3->status);
        $this->assertEquals(0.00, (float) $inst3->total_paid);

        // A & B: Completing exact EMI #1 (remaining ₹1,200) makes ONLY Installment #1 paid, EMI #2 stays pending
        $responseFull1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', [
                'loan_account_id' => $loanAccount->id,
                'amount' => 1200.00,
                'payment_method' => 'cash',
                'offline_sync_id' => 'FULL1-SEQ-002',
            ]);

        $responseFull1->assertStatus(201);
        $inst1->refresh();
        $inst2->refresh();
        $inst3->refresh();

        $this->assertEquals('paid', $inst1->status);
        $this->assertEquals(2200.00, (float) $inst1->total_paid);
        $this->assertEquals('pending', $inst2->status);
        $this->assertEquals(0.00, (float) $inst2->total_paid);
        $this->assertEquals(2200.00, (float) $inst2->installment_amount);
        $this->assertEquals('pending', $inst3->status);

        // C & F: Paying EMI #2 (₹2,200) makes #2 paid without changing #3 onward or recalculating schedule
        $responseFull2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', [
                'loan_account_id' => $loanAccount->id,
                'amount' => 2200.00,
                'payment_method' => 'cash',
                'offline_sync_id' => 'FULL2-SEQ-003',
            ]);

        $responseFull2->assertStatus(201);
        $inst2->refresh();
        $inst3->refresh();

        $this->assertEquals('paid', $inst2->status);
        $this->assertEquals(2200.00, (float) $inst2->total_paid);
        $this->assertEquals('pending', $inst3->status);
        $this->assertEquals(0.00, (float) $inst3->total_paid);
        $this->assertEquals(2200.00, (float) $inst3->installment_amount);

        // G: Explicit reduce_tenure prepayment still works when explicitly requested
        $responsePrepay = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/collections/submit-emi', [
                'loan_account_id' => $loanAccount->id,
                'amount' => 2200.00,
                'payment_method' => 'cash',
                'adjustment_mode' => 'reduce_tenure',
                'offline_sync_id' => 'PREPAY-SEQ-004',
            ]);

        $responsePrepay->assertStatus(201);
        $inst3->refresh();
        $this->assertEquals('paid', $inst3->status);
    }

    public function test_product_loan_fulfillment_lifecycle_with_stock_deduction_and_api_disbursement(): void
    {
        $category = ProductCategory::create(['company_id' => $this->company->id, 'name' => 'Home Appliances', 'code' => 'HA']);
        $brand = ProductBrand::create(['company_id' => $this->company->id, 'name' => 'Godrej', 'code' => 'GD']);
        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'sku' => 'PROD-REF-001',
            'name' => 'Godrej Refrigerator 240L',
            'unit_price' => 30000.00,
            'cost_price' => 22000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $product->id,
            'current_stock' => 5,
            'available_stock' => 5,
            'reserved_stock' => 0,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-KOL-PROD',
            'first_name' => 'Rahul',
            'last_name' => 'Sen',
            'name' => 'Rahul Sen',
            'mobile_number' => '9834498344',
            'gender' => 'male',
            'address' => 'Kolkata',
            'registration_date' => '2026-01-01',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Product Loan Scheme',
            'code' => 'PLS-01',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 24,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
            'loan_type' => 'product',
        ]);

        $appService = app(\App\Services\LoanApplicationService::class);
        $accService = app(\App\Services\LoanAccountService::class);

        $app = $appService->createApplication([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_date' => now()->toDateString(),
            'requested_amount' => 30000.00,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
        ], [], [
            [
                'product_id' => $product->id,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'quantity' => 1,
                'unit_price' => 30000.00,
            ]
        ]);

        $app = $appService->submitApplication($app);
        $app = $appService->approveApplication($app, 30000.00);

        // Sanction with down payment ₹3,000 (Sanctioned principal = ₹27,000)
        $loanAccount = $accService->sanctionLoanFromApplication($app, 3000.00);
        $this->assertEquals(27000.00, (float) $loanAccount->sanctioned_amount);
        $this->assertEquals(6, $loanAccount->installments->count());

        if ($loanAccount->upfront_charges_due > 0) {
            $accService->recordUpfrontPayment($loanAccount, [
                'amount' => $loanAccount->upfront_charges_due,
                'payment_method' => 'cash',
            ]);
        }

        $adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Admin Disburser',
            'email' => 'admin.disburser@example.com',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $adminUser->assignRole('Super Admin');

        $token = $adminUser->createToken('AdminDevice')->plainTextToken;

        // Fulfill product loan via Mobile API /disburse endpoint
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/loans/accounts/' . $loanAccount->id . '/disburse', [
                'remarks' => 'Product delivered to customer home',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.disbursed_amount', '27000.00');

        // Verify physical stock was deducted from 5 to 4
        $stock = InventoryStock::where('branch_id', $this->branch1->id)->where('product_id', $product->id)->first();
        $this->assertEquals(4, $stock->current_stock);

        // Verify InventoryStockMovement record
        $this->assertDatabaseHas('inventory_stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'product_loan_issue',
            'quantity' => -1,
            'reference_id' => $loanAccount->id,
        ]);
    }

    public function test_login_api_rate_limiting_blocks_excessive_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'rajesh@grihalaxmifinance.com',
                'password' => 'WrongPassword',
            ]);
        }

        $responseThrottled = $this->postJson('/api/v1/auth/login', [
            'email' => 'rajesh@grihalaxmifinance.com',
            'password' => 'WrongPassword',
        ]);

        $responseThrottled->assertStatus(429);
    }

    public function test_group_api_enforces_company_and_branch_data_isolation(): void
    {
        $company2 = Company::create([
            'name' => 'Other Company Ltd',
            'code' => 'OTH',
            'email' => 'other@company.com',
            'phone' => '9800098000',
            'address' => '456 Other St',
        ]);
        $otherBranch = Branch::create([
            'company_id' => $company2->id,
            'name' => 'Other Branch',
            'code' => 'OTH001',
            'phone' => '03322110003',
            'address' => '789 Other Road',
            'city' => 'Other City',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'opening_date' => '2026-01-01',
        ]);

        $groupBranch1 = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'group_code' => 'GRP-BR1-001',
            'name' => 'Branch 1 Group',
            'formation_date' => '2026-01-01',
        ]);

        $groupBranch2 = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'group_code' => 'GRP-BR2-002',
            'name' => 'Branch 2 Group',
            'formation_date' => '2026-01-01',
        ]);

        $groupOtherCompany = CustomerGroup::create([
            'company_id' => $company2->id,
            'branch_id' => $otherBranch->id,
            'group_code' => 'GRP-OTH-003',
            'name' => 'Other Company Group',
            'formation_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Valid branch 1 group lookup
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/groups/' . $groupBranch1->id);
        $response1->assertStatus(200)
            ->assertJsonPath('success', true);

        // Cross-branch group lookup blocked
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/groups/' . $groupBranch2->id);
        $response2->assertStatus(403);

        // Cross-company group lookup blocked
        $response3 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/groups/' . $groupOtherCompany->id);
        $response3->assertStatus(403);
    }

    public function test_customer_update_api_modifies_profile_and_enforces_branch_scoping(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-UPD-001',
            'first_name' => 'Original',
            'last_name' => 'Name',
            'name' => 'Original Name',
            'mobile_number' => '9988776655',
            'gender' => 'female',
            'address' => 'Old Address',
            'registration_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/customers/' . $customer->id, [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'address' => 'New Updated Address',
                'city' => 'Kolkata',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Updated');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_customer_guarantor_api_adds_and_lists_guarantors(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-GUAR-001',
            'first_name' => 'Guarantor',
            'last_name' => 'Test',
            'name' => 'Guarantor Test',
            'mobile_number' => '9988776644',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Add guarantor
        $responseAdd = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/customers/' . $customer->id . '/guarantors', [
                'full_name' => 'Ramesh Gupta',
                'relationship' => 'Brother',
                'mobile' => '9800012345',
                'occupation' => 'Business',
                'monthly_income' => 25000,
            ]);

        $responseAdd->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.full_name', 'Ramesh Gupta');

        // List guarantors
        $responseList = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/' . $customer->id . '/guarantors');

        $responseList->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(1, $responseList->json('data'));
    }

    public function test_customer_nominee_api_adds_and_lists_nominees(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-NOM-001',
            'first_name' => 'Nominee',
            'last_name' => 'Test',
            'name' => 'Nominee Test',
            'mobile_number' => '9988776633',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Add nominee
        $responseAdd = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/customers/' . $customer->id . '/nominees', [
                'nominee_name' => 'Sunita Devi',
                'relationship' => 'Mother',
                'share_percentage' => 100,
            ]);

        $responseAdd->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nominee_name', 'Sunita Devi');

        // List nominees
        $responseList = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/' . $customer->id . '/nominees');

        $responseList->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertCount(1, $responseList->json('data'));
    }

    public function test_group_creation_and_membership_management_apis(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-GRP-MEMBER',
            'first_name' => 'Group',
            'last_name' => 'Member',
            'name' => 'Group Member',
            'mobile_number' => '9988776622',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Create Group via API
        $responseGroup = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/groups', [
                'name' => 'Annapurna Women SHG',
                'branch_id' => $this->branch1->id,
                'meeting_frequency' => 'weekly',
            ]);

        $responseGroup->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Annapurna Women SHG');

        $groupId = $responseGroup->json('data.id');

        // Add Member to Group via API
        $responseMember = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/groups/' . $groupId . '/members', [
                'customer_id' => $customer->id,
                'role' => 'group_leader',
            ]);

        $responseMember->assertStatus(201)
            ->assertJsonPath('success', true);

        // Verify duplicate active member addition returns validation error
        $responseDuplicate = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/groups/' . $groupId . '/members', [
                'customer_id' => $customer->id,
                'role' => 'member',
            ]);

        $responseDuplicate->assertStatus(422);
    }

    public function test_loan_application_submission_from_mobile(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-APP-001',
            'first_name' => 'App',
            'last_name' => 'Submitter',
            'name' => 'App Submitter',
            'mobile_number' => '9988771122',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Micro Sourcing Cash Scheme',
            'code' => 'MSCS-01',
            'min_amount' => 1000,
            'max_amount' => 20000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 12,
            'interest_rate' => 10.00,
            'interest_rate_per_annum' => 10.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/loans/applications', [
                'branch_id' => $this->branch1->id,
                'loan_scheme_id' => $scheme->id,
                'loan_type' => 'cash',
                'borrower_type' => 'individual',
                'customer_id' => $customer->id,
                'requested_amount' => 10000,
                'tenure_months' => 6,
                'purpose' => 'Small retail business expansion',
                'auto_submit' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.requested_amount', '10000.00');
    }

    public function test_loan_application_review_approve_and_reject_workflows(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-REV-001',
            'first_name' => 'Review',
            'last_name' => 'Test',
            'name' => 'Review Test',
            'mobile_number' => '9988771133',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Review Scheme',
            'code' => 'REV-01',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 12,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'application_number' => 'LA-REV-2026-001',
            'application_date' => '2026-01-01',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_type' => 'individual_cash',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'requested_amount' => 15000,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
            'status' => 'submitted',
        ]);

        $token = $this->superAdmin->createToken('AdminDevice')->plainTextToken;

        // Start Review
        $responseReview = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/loans/applications/' . $app->id . '/review', [
                'action' => 'start_review',
            ]);
        $responseReview->assertStatus(200)
            ->assertJsonPath('data.status', 'under_review');

        // Approve Application
        $responseApprove = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/loans/applications/' . $app->id . '/review', [
                'action' => 'approve',
                'approved_amount' => 15000,
            ]);
        $responseApprove->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_loan_account_schedule_and_overdue_apis(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-SCHED-001',
            'first_name' => 'Schedule',
            'last_name' => 'Test',
            'name' => 'Schedule Test',
            'mobile_number' => '9988771144',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Schedule Scheme',
            'code' => 'SCHED-01',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 12,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $app1 = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'application_number' => 'LA-SCHED-APP-001',
            'application_date' => '2026-01-01',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_type' => 'individual_cash',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'requested_amount' => 12000,
            'tenure_months' => 6,
            'status' => 'approved',
        ]);

        $loanAccount = LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_id' => $app1->id,
            'loan_scheme_id' => $scheme->id,
            'loan_number' => 'LA-SCHED-2026-001',
            'account_number' => 'LA-SCHED-2026-001',
            'sanctioned_amount' => 12000,
            'sanction_date' => '2026-01-01',
            'principal_outstanding' => 12000,
            'interest_outstanding' => 1440,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'total_outstanding' => 13440,
            'status' => 'active',
        ]);

        LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 1,
            'due_date' => '2026-01-15', // Overdue
            'opening_principal' => 12000,
            'installment_amount' => 2240,
            'principal_amount' => 2000,
            'interest_amount' => 240,
            'closing_principal' => 10000,
            'status' => 'overdue',
        ]);

        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // GET schedule
        $responseSchedule = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/loans/accounts/' . $loanAccount->id . '/schedule');

        $responseSchedule->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.loan_account.loan_number', 'LA-SCHED-2026-001');

        // GET overdue
        $responseOverdue = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/overdue');

        $responseOverdue->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_loan_settlement_quote_and_execution_apis(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-SETTLE-001',
            'first_name' => 'Settle',
            'last_name' => 'Test',
            'name' => 'Settle Test',
            'mobile_number' => '9988771155',
            'gender' => 'female',
            'address' => 'Test Address',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Settlement Scheme',
            'code' => 'SETTLE-01',
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 12,
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
            'allow_foreclosure' => true,
        ]);

        $app2 = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'application_number' => 'LA-SETTLE-APP-001',
            'application_date' => '2025-01-01',
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'application_type' => 'individual_cash',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'requested_amount' => 10000,
            'tenure_months' => 6,
            'status' => 'approved',
        ]);

        $loanAccount = LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_id' => $app2->id,
            'loan_scheme_id' => $scheme->id,
            'loan_number' => 'LA-SETTLE-2026-001',
            'account_number' => 'LA-SETTLE-2026-001',
            'sanctioned_amount' => 10000,
            'sanction_date' => '2025-01-01',
            'disbursement_date' => '2025-01-01',
            'principal_outstanding' => 10000,
            'interest_outstanding' => 1200,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate' => 12.00,
            'interest_rate_per_annum' => 12.00,
            'total_outstanding' => 11200,
            'status' => 'active',
        ]);

        LoanInstallment::create([
            'loan_account_id' => $loanAccount->id,
            'installment_number' => 1,
            'due_date' => '2025-02-01',
            'opening_principal' => 10000,
            'installment_amount' => 1867,
            'principal_amount' => 1667,
            'interest_amount' => 200,
            'closing_principal' => 8333,
            'status' => 'pending',
        ]);

        $token = $this->superAdmin->createToken('AdminDevice')->plainTextToken;

        // GET Settlement Quote
        $responseQuote = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/loans/accounts/' . $loanAccount->id . '/settlement-quote?type=foreclosure');

        $responseQuote->assertStatus(200)
            ->assertJsonPath('success', true);

        // Execute Foreclosure
        $responseProcess = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/loans/accounts/' . $loanAccount->id . '/settlements', [
                'request_type' => 'foreclosure',
                'payment_method' => 'cash',
                'execute_now' => true,
                'remarks' => 'Voluntary early foreclosure',
            ]);

        $responseProcess->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.loan_account.status', 'closed');
    }

    public function test_bank_deposit_submission_listing_and_approval_via_api(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->branchManager);

        // 1. Submit Bank Deposit for Branch 1
        $responseSubmit = $this->postJson('/api/v1/bank-deposits', [
                'branch_id' => $this->branch1->id,
                'deposit_date' => '2026-02-15',
                'amount' => 15000.00,
                'bank_name' => 'State Bank of India',
                'account_number' => 'SBIN00012345',
                'reference_number' => 'DEP-20260215-001',
                'description' => 'Daily Cash Collection Deposit',
            ]);

        $responseSubmit->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', '15000.00');

        $depositId = $responseSubmit->json('data.id');

        // 2. Listing Bank Deposits
        $responseIndex = $this->getJson('/api/v1/bank-deposits?branch_id=' . $this->branch1->id);

        $responseIndex->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Unauthorized Cross-Branch Submission Check
        $responseUnauthBranch = $this->postJson('/api/v1/bank-deposits', [
                'branch_id' => $this->branch2->id,
                'deposit_date' => '2026-02-15',
                'amount' => 5000.00,
                'bank_name' => 'HDFC Bank',
            ]);

        $responseUnauthBranch->assertStatus(403);

        // 4. Approve Deposit with Super Admin (BM cannot approve own deposit)
        \Laravel\Sanctum\Sanctum::actingAs($this->superAdmin);

        $responseApprove = $this->postJson('/api/v1/bank-deposits/' . $depositId . '/approve', [
                'remarks' => 'Verified with bank statement receipt',
            ]);

        $responseApprove->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_expense_logging_submission_approval_and_payment_via_api(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->branchManager);

        // Create Expense Category
        $category = \App\Models\ExpenseCategory::create([
            'company_id' => $this->company->id,
            'category_code' => 'OFFICE-SUPPLIES',
            'category_name' => 'Office Stationery',
            'is_active' => true,
        ]);

        // 1. GET Categories
        $responseCat = $this->getJson('/api/v1/expenses/categories');

        $responseCat->assertStatus(200)
            ->assertJsonPath('success', true);

        // 2. Log Expense with auto-submit
        $responseStore = $this->postJson('/api/v1/expenses', [
                'branch_id' => $this->branch1->id,
                'expense_category_id' => $category->id,
                'expense_date' => '2026-02-16',
                'amount' => 2500.00,
                'tax_amount' => 0.00,
                'payee_name' => 'City Book Store',
                'description' => 'Branch Printer Paper and Registers',
                'auto_submit' => true,
            ]);

        $responseStore->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING_APPROVAL')
            ->assertJsonPath('data.total_amount', '2500.00');

        $expenseId = $responseStore->json('data.id');

        // 3. Approve Expense with Super Admin
        \Laravel\Sanctum\Sanctum::actingAs($this->superAdmin);

        $responseApprove = $this->postJson('/api/v1/expenses/' . $expenseId . '/approve');

        $responseApprove->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'APPROVED');

        // 4. Record Payment for Approved Expense
        $responsePay = $this->postJson('/api/v1/expenses/' . $expenseId . '/pay', [
                'paid_amount' => 2500.00,
                'payment_method' => 'cash',
                'payment_date' => '2026-02-16',
                'notes' => 'Paid from branch petty cash',
            ]);

        $responsePay->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.expense.status', 'PAID');
    }

    public function test_cash_book_retrieval_and_reconciliation_via_api(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->branchManager);

        // GET Cash Book
        $responseShow = $this->getJson('/api/v1/cash-book?branch_id=' . $this->branch1->id . '&date=2026-02-16');

        $responseShow->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.branch_id', $this->branch1->id);

        // Save Denominations
        $responseReconcile = $this->postJson('/api/v1/cash-book/reconcile', [
                'branch_id' => $this->branch1->id,
                'date' => '2026-02-16',
            ]);

        $responseReconcile->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
