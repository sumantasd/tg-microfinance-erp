<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissions = [
            'purchase.view', 'purchase.create', 'purchase.edit',
            'purchase.receive', 'purchase.cancel',
            'inventory.view', 'inventory.manage'
        ];
        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        $role->syncPermissions(Permission::all());

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
            'name' => 'Main Branch',
            'code' => 'BR001',
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

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00010',
            'name' => 'Sewing Machine Standard',
            'unit_price' => 10000.00,
            'cost_price' => 8000.00,
            'tax_percentage' => 18.00,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00020',
            'name' => 'Solar Home Light 50W',
            'unit_price' => 18000.00,
            'cost_price' => 15000.00,
            'tax_percentage' => 18.00,
            'is_active' => true,
        ]);
    }

    public function test_can_create_draft_purchase_order(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.product-purchase.store'), [
            'branch_id' => $this->branch->id,
            'supplier_name' => 'Usha International',
            'supplier_invoice_number' => 'USHA-INV-9901',
            'purchase_date' => date('Y-m-d'),
            'paid_amount' => 50000.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10,
                    'unit_purchase_cost' => 8000.00,
                    'tax_rate' => 18.00,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 5,
                    'unit_purchase_cost' => 15000.00,
                    'tax_rate' => 18.00,
                ],
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_purchases', [
            'supplier_name' => 'Usha International',
            'purchase_status' => 'draft',
            'paid_amount' => 50000.00,
        ]);

        $purchase = ProductPurchase::where('supplier_name', 'Usha International')->first();
        $this->assertNotNull($purchase);
        $this->assertStringContainsString('PUR-BR001-', $purchase->purchase_number);
        $this->assertCount(2, $purchase->items);

        // Physical inventory stock MUST NOT change during draft purchase creation!
        $this->assertDatabaseMissing('inventory_stocks', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->productA->id,
        ]);
    }

    public function test_receiving_purchase_updates_physical_inventory_and_records_purchase_in_movement(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.product-purchase.store'), [
            'branch_id' => $this->branch->id,
            'supplier_name' => 'Tata Solar Ltd',
            'supplier_invoice_number' => 'TATA-9988',
            'purchase_date' => date('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 20,
                    'unit_purchase_cost' => 14500.00,
                    'tax_rate' => 18.00,
                ]
            ]
        ]);

        $purchase = ProductPurchase::where('supplier_name', 'Tata Solar Ltd')->first();
        $this->assertNotNull($purchase);

        // Receive Purchase
        $receiveResp = $this->actingAs($this->adminUser)->post(route('admin.product-purchase.receive', $purchase->id));
        $receiveResp->assertRedirect();

        $this->assertEquals('received', $purchase->fresh()->purchase_status);

        // Verify inventory stock increased to 20 units
        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'current_stock' => 20,
        ]);

        // Verify purchase_in movement logged with purchase reference
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'movement_type' => 'purchase_in',
            'quantity' => 20,
            'stock_before' => 0,
            'stock_after' => 20,
            'reference_type' => 'product_purchase',
            'reference_id' => $purchase->id,
        ]);
    }

    public function test_blocks_duplicate_receiving_of_same_purchase(): void
    {
        $purchase = ProductPurchase::create([
            'purchase_number' => 'PUR-BR001-2026-00099',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'supplier_name' => 'Vendor Test',
            'purchase_date' => date('Y-m-d'),
            'subtotal' => 10000.00,
            'grand_total' => 11800.00,
            'purchase_status' => 'received', // Already received!
        ]);

        $purchase->items()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 5,
            'unit_purchase_cost' => 8000.00,
            'line_subtotal' => 40000.00,
            'line_total' => 47200.00,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.product-purchase.receive', $purchase->id));
        $response->assertSessionHasErrors('purchase_status');
    }

    public function test_historical_purchase_cost_remains_unchanged_when_product_catalog_price_changes(): void
    {
        $purchase = ProductPurchase::create([
            'purchase_number' => 'PUR-BR001-2026-00100',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'supplier_name' => 'Historical Vendor',
            'purchase_date' => date('Y-m-d'),
            'subtotal' => 80000.00,
            'grand_total' => 94400.00,
            'purchase_status' => 'draft',
        ]);

        $item = $purchase->items()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 10,
            'unit_purchase_cost' => 8000.00,
            'mrp_snapshot' => 10000.00,
            'line_subtotal' => 80000.00,
            'line_total' => 94400.00,
        ]);

        // Update Product Catalog price
        $this->productA->update(['cost_price' => 12000.00, 'unit_price' => 15000.00]);

        // Assert purchase item historical unit_purchase_cost is still 8000.00
        $this->assertEquals(8000.00, $item->fresh()->unit_purchase_cost);
    }
}
