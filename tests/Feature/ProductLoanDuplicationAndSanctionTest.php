<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductLoanDuplicationAndSanctionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;
    protected LoanScheme $productScheme;
    protected ProductCategory $category;
    protected ProductBrand $brand;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissions = [
            'loan_application.view', 'loan_application.create', 'loan_application.edit',
            'loan_application.submit', 'loan_application.review', 'loan_application.approve',
            'loan.view', 'loan.sanction', 'loan.disburse', 'loan.issue_product',
            'inventory.view', 'inventory.manage'
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Grihalaxmi Test HO',
            'code' => 'HO-TEST',
            'registration_number' => 'REG-TEST-001',
            'email' => 'ho.test@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Main Head Office Address',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Test Branch',
            'code' => 'BR-TEST',
            'phone' => '8888888888',
            'address' => 'Branch Test Address',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin.test@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-TEST-001',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9811111111',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->productScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-PROD-TEST',
            'name' => 'Consumer Product Loan',
            'loan_type' => 'product',
            'applicant_type' => 'individual',
            'min_amount' => 5000.00,
            'max_amount' => 200000.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.00,
            'insurance_fee_percentage' => 1.00,
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'company_id' => $this->company->id,
            'code' => 'CAT-TEST',
            'name' => 'Furniture & Appliances',
            'is_active' => true,
        ]);

        $this->brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'code' => 'BRD-TEST',
            'name' => 'Tata Steel Furniture',
            'is_active' => true,
        ]);

        // Product A: Almirah (₹32,000)
        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'PROD-ALMIRAH-01',
            'name' => 'Tata Steel Almirah 2-Door Premium',
            'cost_price' => 25000.00,
            'unit_price' => 32000.00,
            'is_active' => true,
        ]);

        // Product B: Executive Desk (₹20,000)
        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'sku' => 'PROD-DESK-02',
            'name' => 'Tata Steel Executive Desk Table',
            'cost_price' => 15000.00,
            'unit_price' => 20000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productA->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);
    }

    /**
     * TEST 1: Select Product A only. Sanctioned loan has Product A only.
     */
    public function test_select_product_a_only_produces_sanctioned_loan_with_product_a_only(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 32000.00,
            'products' => [
                [
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                    'unit_price' => 32000.00,
                ]
            ]
        ]);

        $response->assertRedirect();
        $app = LoanApplication::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($app);
        $this->assertEquals(1, $app->products->count());
        $this->assertEquals($this->productA->id, $app->products->first()->product_id);

        // Approve
        $app->update(['status' => 'approved', 'approved_amount' => 32000.00]);

        // Sanction
        $sanctionRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3200.00,
        ]);
        $sanctionRes->assertRedirect();

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(1, $account->application->products->count());
        $this->assertEquals('Tata Steel Almirah 2-Door Premium', $account->application->products->first()->product_name_snapshot);
    }

    /**
     * TEST 2: Select Product B only. Sanctioned loan has Product B only.
     */
    public function test_select_product_b_only_produces_sanctioned_loan_with_product_b_only(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 20000.00,
            'products' => [
                [
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->productB->id,
                    'quantity' => 1,
                    'unit_price' => 20000.00,
                ]
            ]
        ]);

        $response->assertRedirect();
        $app = LoanApplication::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($app);
        $this->assertEquals(1, $app->products->count());
        $this->assertEquals($this->productB->id, $app->products->first()->product_id);

        // Approve & Sanction
        $app->update(['status' => 'approved', 'approved_amount' => 20000.00]);
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id, 'down_payment_amount' => 2000.00]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(1, $account->application->products->count());
        $this->assertEquals('Tata Steel Executive Desk Table', $account->application->products->first()->product_name_snapshot);
    }

    /**
     * TEST 3: Select Product A + Product B. Sanctioned loan has exactly A + B.
     */
    public function test_select_product_a_and_b_produces_sanctioned_loan_with_both_products(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 52000.00,
            'products' => [
                [
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                    'unit_price' => 32000.00,
                ],
                [
                    'category_id' => $this->category->id,
                    'brand_id' => $this->brand->id,
                    'product_id' => $this->productB->id,
                    'quantity' => 1,
                    'unit_price' => 20000.00,
                ],
            ]
        ]);

        $response->assertRedirect();
        $app = LoanApplication::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($app);
        $this->assertEquals(2, $app->products->count());

        // Approve & Sanction
        $app->update(['status' => 'approved', 'approved_amount' => 52000.00]);
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id, 'down_payment_amount' => 5200.00]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(2, $account->application->products->count());
        $this->assertEquals(52000.00, $account->product_price_amount);
        $this->assertEquals(46800.00, $account->sanctioned_amount);
    }

    /**
     * TEST 4: Run sanction twice. Idempotent protection against duplicate line items.
     */
    public function test_running_sanction_twice_is_prevented_and_does_not_duplicate_line_items(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-IDEM-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 32000.00,
            'approved_amount' => 32000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 32000.00,
            'total_value' => 32000.00,
        ]);

        // First sanction call succeeds
        $res1 = $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3200.00,
        ]);
        $res1->assertRedirect();

        $accountCount = LoanAccount::where('loan_application_id', $app->id)->count();
        $this->assertEquals(1, $accountCount);
        $this->assertEquals(1, $app->products()->count());

        // Second sanction call fails gracefully with validation error
        $res2 = $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3200.00,
        ]);
        $res2->assertSessionHasErrors('loan_application_id');
        $this->assertEquals(1, LoanAccount::where('loan_application_id', $app->id)->count());
        $this->assertEquals(1, $app->products()->count());
    }

    /**
     * TEST 5: Issue/fulfill product loan. Only selected products affect inventory stock.
     */
    public function test_product_fulfillment_only_deducts_selected_product_stock(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-FULFILL-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 32000.00,
            'approved_amount' => 32000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 32000.00,
            'total_value' => 32000.00,
        ]);

        $account = LoanAccount::create([
            'loan_number' => 'LN-FULFILL-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'product_price_amount' => 32000.00,
            'down_payment_amount' => 3200.00,
            'sanctioned_amount' => 28800.00,
            'disbursed_amount' => 0.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => 28800.00,
            'total_outstanding' => 32256.00,
            'status' => 'sanctioned',
            'sanction_date' => date('Y-m-d'),
        ]);

        // Stock before: Product A = 10, Product B = 10
        $stockABefore = InventoryStock::where('branch_id', $this->branch->id)->where('product_id', $this->productA->id)->first()->current_stock;
        $stockBBefore = InventoryStock::where('branch_id', $this->branch->id)->where('product_id', $this->productB->id)->first()->current_stock;
        $this->assertEquals(10, $stockABefore);
        $this->assertEquals(10, $stockBBefore);

        // Issue Product
        $issueRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.issue-product', $account->id));
        $issueRes->assertRedirect();

        // Product A stock MUST deduct by 1 (10 -> 9). Product B stock MUST REMAIN 10!
        $stockAAfter = InventoryStock::where('branch_id', $this->branch->id)->where('product_id', $this->productA->id)->first()->current_stock;
        $stockBAfter = InventoryStock::where('branch_id', $this->branch->id)->where('product_id', $this->productB->id)->first()->current_stock;

        $this->assertEquals(9, $stockAAfter);
        $this->assertEquals(10, $stockBAfter);
    }

    /**
     * TEST 6: Verify loan financial calculation matches selected product valuation only.
     */
    public function test_loan_financial_calculations_use_selected_product_valuation_only(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-FIN-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 32000.00,
            'approved_amount' => 32000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 32000.00,
            'total_value' => 32000.00,
        ]);

        // Sanction with Down Payment = ₹3,200
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3200.00,
        ]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);

        // Product Price = 32000 (NOT 52000!)
        $this->assertEquals(32000.00, $account->product_price_amount);
        $this->assertEquals(3200.00, $account->down_payment_amount);

        // Financed Principal = 32000 - 3200 = 28800 (NOT 48800!)
        $this->assertEquals(28800.00, $account->sanctioned_amount);
        $this->assertEquals(28800.00, $account->principal_outstanding);

        // Flat interest @ 12% on 28800 for 1 yr = 3456. Total Repayment = 32256.
        $this->assertEquals(3456.00, $account->total_interest_amount);
        $this->assertEquals(32256.00, $account->total_repayment_amount);
    }
}
