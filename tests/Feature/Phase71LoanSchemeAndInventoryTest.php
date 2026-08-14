<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase71LoanSchemeAndInventoryTest extends TestCase
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
            'inventory.view', 'inventory.manage', 'inventory.adjust'
        ];

        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
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
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->adminUser->assignRole('Super Admin');
    }

    public function test_can_create_loan_scheme_with_auto_generated_code(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-scheme.store'), [
            'company_id' => $this->company->id,
            'name' => 'Micro Enterprise Loan Scheme',
            'loan_type' => 'both',
            'applicant_type' => 'individual',
            'min_amount' => 10000.00,
            'max_amount' => 100000.00,
            'interest_type' => 'reducing_balance',
            'interest_rate_per_annum' => 16.50,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 2.00,
            'insurance_fee_percentage' => 1.00,
            'late_fee_percentage' => 1.50,
            'grace_period_days' => 5,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.loan-scheme.index'));
        $this->assertDatabaseHas('loan_schemes', [
            'name' => 'Micro Enterprise Loan Scheme',
            'interest_rate_per_annum' => 16.50,
        ]);

        $scheme = LoanScheme::where('name', 'Micro Enterprise Loan Scheme')->first();
        $this->assertNotNull($scheme);
        $this->assertEquals('SCH-0001', $scheme->code);
    }

    public function test_can_create_product_with_auto_generated_sku(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.product.store'), [
            'company_id' => $this->company->id,
            'name' => 'Solar Home Light 20W',
            'brand' => 'Tata Solar',
            'model_number' => 'TS-20W',
            'category' => 'Solar Energy',
            'unit_price' => 8500.00,
            'cost_price' => 6800.00,
            'tax_percentage' => 18.00,
            'description' => 'Solar home light with 2 bulbs and mobile charger.',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.product.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Solar Home Light 20W',
            'unit_price' => 8500.00,
        ]);

        $product = Product::where('name', 'Solar Home Light 20W')->first();
        $this->assertNotNull($product);
        $this->assertEquals('PRD-00001', $product->sku);
    }

    public function test_can_restock_branch_inventory_and_records_generic_movement_ledger(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00002',
            'name' => 'Sewing Machine Heavy Duty',
            'brand' => 'Usha',
            'unit_price' => 12000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory.restock'), [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'quantity' => 25,
            'unit_price' => 11500.00,
            'remarks' => 'Initial Batch Restock',
        ]);

        $response->assertRedirect();
        
        // Assert inventory stock level
        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'current_stock' => 25,
        ]);

        // Assert stock movement log
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase_in',
            'quantity' => 25,
            'stock_before' => 0,
            'stock_after' => 25,
        ]);

        $movement = InventoryStockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertStringContainsString('STK-BR001-', $movement->movement_code);
    }

    public function test_can_adjust_branch_stock_level(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00003',
            'name' => 'E-Rickshaw Battery 120Ah',
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 3,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory.adjust'), [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'new_stock_level' => 8,
            'remarks' => 'Physical stock count audit correction (-2 damaged)',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'current_stock' => 8,
        ]);

        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment',
            'quantity' => -2,
            'stock_before' => 10,
            'stock_after' => 8,
        ]);
    }
}
