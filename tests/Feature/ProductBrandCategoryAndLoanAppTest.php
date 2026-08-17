<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
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

class ProductBrandCategoryAndLoanAppTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $permissions = [
            'loan_scheme.view', 'loan_scheme.create', 'loan_scheme.edit', 'loan_scheme.delete',
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'inventory.view', 'inventory.manage', 'inventory.adjust',
            'loan_application.view', 'loan_application.create', 'loan_application.edit', 'loan_application.delete'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $role->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO, Bihar',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR001',
            'phone' => '8888888888',
            'address' => 'Main Road, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->adminUser->assignRole('Super Admin');
    }

    public function test_can_create_and_view_product_brands(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.product-brand.store'), [
            'company_id' => $this->company->id,
            'name' => 'Usha International',
            'code' => 'USHA',
            'description' => 'Sewing machines and home appliances',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.product-brand.index'));
        $this->assertDatabaseHas('product_brands', [
            'name' => 'Usha International',
            'code' => 'USHA',
        ]);

        $indexRes = $this->actingAs($this->adminUser)->get(route('admin.product-brand.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Usha International');
    }

    public function test_can_update_product_brand(): void
    {
        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Bajaj',
            'code' => 'BAJ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.product-brand.update', $brand->id), [
            'name' => 'Bajaj Electricals',
            'code' => 'BAJAJ-ELEC',
            'description' => 'Updated brand description',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.product-brand.index'));
        $this->assertDatabaseHas('product_brands', [
            'id' => $brand->id,
            'name' => 'Bajaj Electricals',
            'code' => 'BAJAJ-ELEC',
        ]);
    }

    public function test_safe_delete_prevents_deleting_brand_with_products(): void
    {
        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Tata Solar',
            'code' => 'TATA',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00010',
            'name' => 'Tata Solar Home Kit 50W',
            'brand_id' => $brand->id,
            'brand' => 'Tata Solar',
            'unit_price' => 15000,
            'cost_price' => 12000,
            'is_active' => true,
        ]);

        // Attempt deletion of brand
        $response = $this->actingAs($this->adminUser)->delete(route('admin.product-brand.destroy', $brand->id));
        $response->assertRedirect(route('admin.product-brand.index'));
        $response->assertSessionHas('error');

        // Verify brand was NOT deleted
        $this->assertDatabaseHas('product_brands', ['id' => $brand->id, 'deleted_at' => null]);

        // Now remove/reassign product and delete brand
        $product->delete();
        $delResponse = $this->actingAs($this->adminUser)->delete(route('admin.product-brand.destroy', $brand->id));
        $delResponse->assertRedirect(route('admin.product-brand.index'));
        $delResponse->assertSessionHas('success');
        $this->assertSoftDeleted('product_brands', ['id' => $brand->id]);
    }

    public function test_can_create_and_view_product_categories(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.product-category.store'), [
            'company_id' => $this->company->id,
            'name' => 'Solar Energy Equipment',
            'code' => 'SOLAR',
            'description' => 'Solar panels, lighting systems and inverters',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.product-category.index'));
        $this->assertDatabaseHas('product_categories', [
            'name' => 'Solar Energy Equipment',
            'code' => 'SOLAR',
        ]);

        $indexRes = $this->actingAs($this->adminUser)->get(route('admin.product-category.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('Solar Energy Equipment');
    }

    public function test_safe_delete_prevents_deleting_category_with_products(): void
    {
        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Sewing & Tailoring',
            'code' => 'SEW',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00020',
            'name' => 'Usha Tailor Deluxe Machine',
            'category_id' => $category->id,
            'category' => 'Sewing & Tailoring',
            'unit_price' => 8500,
            'cost_price' => 6800,
            'is_active' => true,
        ]);

        // Attempt deletion of category
        $response = $this->actingAs($this->adminUser)->delete(route('admin.product-category.destroy', $category->id));
        $response->assertRedirect(route('admin.product-category.index'));
        $response->assertSessionHas('error');

        // Verify category was NOT deleted
        $this->assertDatabaseHas('product_categories', ['id' => $category->id, 'deleted_at' => null]);

        // Now remove product and delete category
        $product->delete();
        $delResponse = $this->actingAs($this->adminUser)->delete(route('admin.product-category.destroy', $category->id));
        $delResponse->assertRedirect(route('admin.product-category.index'));
        $delResponse->assertSessionHas('success');
        $this->assertSoftDeleted('product_categories', ['id' => $category->id]);
    }

    public function test_can_create_product_with_brand_and_category_masters(): void
    {
        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Havells',
            'code' => 'HVL',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Home Appliances',
            'code' => 'HOME-APP',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.product.store'), [
            'company_id' => $this->company->id,
            'name' => 'Havells High-Speed Pedestal Fan',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'model_number' => 'HVL-FAN-01',
            'unit_price' => 3200.00,
            'cost_price' => 2400.00,
            'tax_percentage' => 18.00,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.product.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Havells High-Speed Pedestal Fan',
            'brand_id' => $brand->id,
            'brand' => 'Havells',
            'category_id' => $category->id,
            'category' => 'Home Appliances',
        ]);
    }

    public function test_loan_application_auto_populates_tenure_from_scheme(): void
    {
        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Weekly Micro Enterprise Cash Scheme',
            'code' => 'W-MEC-12',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000,
            'max_amount' => 50000,
            'min_tenure_months' => 12,
            'max_tenure_months' => 12,
            'repayment_frequency' => 'weekly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 18.00,
            'processing_fee_percentage' => 2.00,
            'insurance_fee_percentage' => 1.00,
            'late_fee_percentage' => 2.00,
            'grace_period_days' => 3,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Pooja',
            'last_name' => 'Devi',
            'gender' => 'female',
            'marital_status' => 'married',
            'date_of_birth' => '1990-05-15',
            'mobile_number' => '9876543210',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        // Submit loan application without explicit tenure_months (or automated tenure)
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $customer->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 25000,
            'purpose' => 'Grocery store inventory purchase',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('loan_applications', [
            'customer_id' => $customer->id,
            'loan_scheme_id' => $scheme->id,
            'requested_amount' => 25000,
            'tenure_months' => 12,
            'repayment_frequency' => 'weekly',
        ]);
    }

    public function test_product_loan_application_with_category_and_product_selection(): void
    {
        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Systems',
            'code' => 'SOL',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Tata Solar',
            'code' => 'TATA',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-SOLAR-01',
            'name' => 'Tata 40W Solar Home Lighting Kit',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit_price' => 12000.00,
            'cost_price' => 9500.00,
            'is_active' => true,
        ]);

        // Add inventory stock in branch
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
        ]);

        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Product Loan 15 Days',
            'code' => 'PRD-15D-06',
            'loan_type' => 'product',
            'applicant_type' => 'individual',
            'min_amount' => 2000,
            'max_amount' => 50000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 6,
            'repayment_frequency' => 'bi_weekly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 16.00,
            'processing_fee_percentage' => 2.00,
            'insurance_fee_percentage' => 1.00,
            'late_fee_percentage' => 2.00,
            'grace_period_days' => 3,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-002',
            'first_name' => 'Meera',
            'last_name' => 'Kumari',
            'gender' => 'female',
            'marital_status' => 'married',
            'date_of_birth' => '1992-08-20',
            'mobile_number' => '9812345678',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        // Create product loan application: 1 unit @ 12000, Financed: 10000, Down payment: 2000
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-application.store'), [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $customer->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 10000, // Financed amount
            'purpose' => 'Solar electrification for home workshop',
            'products' => [
                [
                    'category_id' => $category->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 12000.00,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $application = LoanApplication::with('products')->where('customer_id', $customer->id)->first();
        $this->assertNotNull($application);
        $this->assertEquals(10000, (float) $application->requested_amount);
        $this->assertEquals(6, $application->tenure_months);
        $this->assertEquals('bi_weekly', $application->repayment_frequency);
        $this->assertCount(1, $application->products);
        $this->assertEquals($product->id, $application->products->first()->product_id);
    }
}
