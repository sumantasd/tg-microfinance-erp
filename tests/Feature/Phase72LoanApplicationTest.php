<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\InventoryStock;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase72LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $branchManager;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customerA;
    protected Customer $customerB;
    protected CustomerGroup $group;
    protected LoanScheme $cashScheme;
    protected LoanScheme $productScheme;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissions = [
            'loan_application.view', 'loan_application.create', 'loan_application.edit',
            'loan_application.submit', 'loan_application.review', 'loan_application.approve',
            'loan_application.reject', 'loan_application.cancel',
            'inventory.view', 'inventory.manage'
        ];
        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Grihalaxmi HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'PAT01',
            'phone' => '8888888888',
            'address' => 'Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->customerA = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-00001',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9800000001',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-00002',
            'first_name' => 'Anita',
            'last_name' => 'Kumari',
            'mobile_number' => '9800000002',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-00001',
            'name' => 'Maa Durga SHG',
            'formation_date' => date('Y-m-d'),
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->group->members()->create([
            'customer_id' => $this->customerA->id,
            'role' => 'leader',
            'joined_at' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->group->members()->create([
            'customer_id' => $this->customerB->id,
            'role' => 'member',
            'joined_at' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->cashScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-CASH01',
            'name' => 'Micro Enterprise Cash Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 10000.00,
            'max_amount' => 100000.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.50,
            'insurance_fee_percentage' => 0.50,
            'is_active' => true,
        ]);

        $this->productScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-PRD01',
            'name' => 'Goods & Machinery Product Loan',
            'loan_type' => 'product',
            'applicant_type' => 'both',
            'min_amount' => 5000.00,
            'max_amount' => 200000.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 36,
            'interest_type' => 'reducing_balance',
            'interest_rate_per_annum' => 14.00,
            'processing_fee_percentage' => 2.00,
            'insurance_fee_percentage' => 1.00,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-SEW01',
            'name' => 'Singer Heavy Duty Sewing Machine',
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);
    }

    public function test_can_create_individual_cash_loan_application(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 40000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'purpose' => 'Grocery shop expansion',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loan_applications', [
            'borrower_type' => 'individual',
            'loan_type' => 'cash',
            'customer_id' => $this->customerA->id,
            'requested_amount' => 40000.00,
            'status' => 'draft',
        ]);

        $app = LoanApplication::where('customer_id', $this->customerA->id)->first();
        $this->assertNotNull($app);
        $this->assertStringContainsString('LN-APP-PAT01-', $app->application_number);
        $this->assertEquals(600.00, $app->processing_fee_amount); // 1.5% of 40000
        $this->assertEquals(200.00, $app->insurance_fee_amount); // 0.5% of 40000
    }

    public function test_validates_loan_scheme_amount_limits(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->cashScheme->id, // Min: 10000, Max: 100000
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 5000.00, // Below minimum limit!
            'tenure_months' => 12,
        ]);

        $response->assertSessionHasErrors('requested_amount');
    }

    public function test_submit_review_approve_individual_cash_loan(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'draft',
            'created_by' => $this->adminUser->id,
        ]);

        // Submit
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.submit', $app->id))->assertRedirect();
        $this->assertEquals('submitted', $app->fresh()->status);

        // Start Review
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.start-review', $app->id))->assertRedirect();
        $this->assertEquals('under_review', $app->fresh()->status);

        // Approve
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.approve', $app->id), [
            'approved_amount' => 45000.00,
        ])->assertRedirect();

        $freshApp = $app->fresh();
        $this->assertEquals('approved', $freshApp->status);
        $this->assertEquals(45000.00, $freshApp->approved_amount);
        $this->assertEquals($this->adminUser->id, $freshApp->approved_by);
    }

    public function test_reject_loan_application_requires_rejection_reason(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000002',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'submitted',
            'created_by' => $this->adminUser->id,
        ]);

        // Reject without reason should fail
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.reject', $app->id), [
            'rejection_reason' => '',
        ])->assertSessionHasErrors('rejection_reason');

        // Reject with valid reason
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.reject', $app->id), [
            'rejection_reason' => 'Poor credit score history.',
        ])->assertRedirect();

        $freshApp = $app->fresh();
        $this->assertEquals('rejected', $freshApp->status);
        $this->assertEquals('Poor credit score history.', $freshApp->rejection_reason);
    }

    public function test_can_create_group_cash_loan_application_with_member_allocations(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'members' => [
                [
                    'customer_id' => $this->customerA->id,
                    'requested_amount' => 30000.00,
                    'remarks' => 'Tailoring shop',
                ],
                [
                    'customer_id' => $this->customerB->id,
                    'requested_amount' => 20000.00,
                    'remarks' => 'Dairy business',
                ],
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loan_applications', [
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'requested_amount' => 50000.00,
        ]);

        $app = LoanApplication::where('customer_group_id', $this->group->id)->first();
        $this->assertNotNull($app);
        $this->assertCount(2, $app->members);
    }

    public function test_validates_group_member_allocation_sum_matches_requested_amount(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'tenure_months' => 12,
            'members' => [
                [
                    'customer_id' => $this->customerA->id,
                    'requested_amount' => 30000.00,
                ],
                [
                    'customer_id' => $this->customerB->id,
                    'requested_amount' => 10000.00, // Sum = 40000, mismatch with 50000!
                ],
            ]
        ]);

        $response->assertSessionHasErrors('requested_amount');
    }

    public function test_can_create_individual_product_loan_application(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 30000.00, // 2 x 15000
            'tenure_months' => 12,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 15000.00,
                ]
            ]
        ]);

        $response->assertRedirect();

        $app = LoanApplication::where('loan_type', 'product')->first();
        $this->assertNotNull($app);
        $this->assertCount(1, $app->products);
        $this->assertEquals('Singer Heavy Duty Sewing Machine', $app->products->first()->product_name_snapshot);
    }

    /**
     * CRITICAL BUSINESS RULE TEST:
     * Product Loan Creation and Approval MUST NOT deduct physical inventory stock in Phase 7.2!
     */
    public function test_product_loan_application_and_approval_does_not_deduct_inventory_stock(): void
    {
        // Stock before application is 10
        $initialStock = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first()->current_stock;
        $this->assertEquals(10, $initialStock);

        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 45000.00, // 3 units
            'tenure_months' => 12,
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_price' => 15000.00,
                ]
            ]
        ]);
        $response->assertRedirect();

        $app = LoanApplication::where('loan_type', 'product')->first();
        $this->assertNotNull($app);

        // Submit & Approve application
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.submit', $app->id));
        $this->actingAs($this->adminUser)->post(route('admin.loan-application.approve', $app->id));

        $this->assertEquals('approved', $app->fresh()->status);

        // Physical inventory stock MUST STILL BE 10!
        $afterApprovalStock = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first()->current_stock;
        $this->assertEquals(10, $afterApprovalStock);
    }

    public function test_can_create_group_product_loan_application(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'group',
            'customer_group_id' => $this->group->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 30000.00,
            'tenure_months' => 12,
            'members' => [
                [
                    'customer_id' => $this->customerA->id,
                    'requested_amount' => 15000.00,
                ],
                [
                    'customer_id' => $this->customerB->id,
                    'requested_amount' => 15000.00,
                ],
            ],
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 15000.00,
                ]
            ]
        ]);

        $response->assertRedirect();

        $app = LoanApplication::where('loan_type', 'product')->where('borrower_type', 'group')->first();
        $this->assertNotNull($app);
        $this->assertCount(2, $app->members);
        $this->assertCount(1, $app->products);

        // Physical inventory stock MUST STILL BE 10
        $currentStock = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first()->current_stock;
        $this->assertEquals(10, $currentStock);
    }
}
