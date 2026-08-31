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
    protected User $loanOfficer;
    protected Role $loanOfficerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'customer.view', 'customer.create', 'group.view', 'loan.view',
            'loan.disburse', 'collection.collect', 'inventory.view',
            'cashbook.view', 'cashbook.create_entry', 'cashbook.close_register',
            'customer.kyc_upload', 'customer.kyc_view'
        ];

        foreach ($permissions as $p) {
            if (!Permission::where('name', $p)->where('guard_name', 'web')->exists()) {
                Permission::create(['name' => $p, 'guard_name' => 'web']);
            }
        }

        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first()
            ?? Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->loanOfficerRole = Role::where('name', 'Loan Officer')->where('guard_name', 'web')->first()
            ?? Role::create(['name' => 'Loan Officer', 'guard_name' => 'web']);
        $this->loanOfficerRole->givePermissionTo([
            'customer.view', 'customer.create', 'group.view', 'loan.view',
            'collection.collect', 'inventory.view', 'cashbook.view',
            'customer.kyc_upload', 'customer.kyc_view'
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
}
