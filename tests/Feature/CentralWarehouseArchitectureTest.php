<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPurchase;
use App\Models\ProductPurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentralWarehouseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;
    protected Branch $centralWarehouse;
    protected Branch $retailBranch;
    protected Product $product;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissions = [
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer.view',
            'inventory.transfer.create',
            'inventory.transfer.approve',
            'inventory.transfer.dispatch',
            'inventory.transfer.receive',
            'purchase.view',
            'purchase.create',
            'purchase.confirm',
            'purchase.receive',
        ];

        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        $role->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Head Office',
            'code' => 'GLF-HO',
            'registration_number' => 'REG-99001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Siliguri, West Bengal',
            'is_active' => true,
        ]);

        $this->centralWarehouse = Branch::getCentralWarehouse($this->company->id);

        $this->retailBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Rangdhamali Retail Branch',
            'code' => 'BR-RNG-001',
            'phone' => '9876543211',
            'address' => 'Rangdhamali Main Road',
            'city' => 'Siliguri',
            'state' => 'West Bengal',
            'pincode' => '734001',
            'is_warehouse' => false,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Electronics',
            'code' => 'CAT-ELEC',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'LG Electronics',
            'code' => 'BRD-LG',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Smart LED TV 43 Inch',
            'sku' => 'PRD-TV-43',
            'cost_price' => 20000.00,
            'unit_price' => 25000.00,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'supplier_code' => 'SUP-001',
            'supplier_name' => 'Electronics Distro Pvt Ltd',
            'contact_person' => 'Rajesh Sharma',
            'mobile' => '9832001122',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function central_warehouse_is_properly_initialized_and_flagged()
    {
        $this->assertTrue($this->centralWarehouse->is_warehouse);
        $this->assertEquals('CWH-001', $this->centralWarehouse->code);
        $this->assertEquals('Central Warehouse', $this->centralWarehouse->name);
    }

    /** @test */
    public function warehouse_index_renders_stock_and_stats()
    {
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
            'product_id' => $this->product->id,
            'current_stock' => 50,
            'reserved_stock' => 0,
            'available_stock' => 50,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.warehouse.index'));

        $response->assertStatus(200);
        $response->assertSee('Central Warehouse');
        $response->assertSee('Central receiving depot');
        $response->assertSee('Smart LED TV 43 Inch');
        $response->assertSee('50');
    }

    /** @test */
    public function warehouse_stock_can_be_manually_adjusted()
    {
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
            'product_id' => $this->product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'available_stock' => 10,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.warehouse.adjust'), [
                'product_id' => $this->product->id,
                'new_stock_level' => 25,
                'remarks' => 'Physical stock count correction',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $stock = InventoryStock::where('branch_id', $this->centralWarehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(25, $stock->current_stock);
        $this->assertEquals(25, $stock->available_stock);
    }

    /** @test */
    public function product_purchase_creation_forces_destination_to_central_warehouse()
    {
        $purchaseData = [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->supplier_name,
            'branch_id' => $this->retailBranch->id, // Attempting to select retail branch
            'purchase_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Bulk restock request',
            'items' => [
                [
                    'category_id' => $this->product->category_id,
                    'brand_id' => $this->product->brand_id,
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_purchase_cost' => 20000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.product-purchase.store'), $purchaseData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $purchase = ProductPurchase::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($purchase);
        // branch_id should be overwritten to central warehouse ID!
        $this->assertEquals($this->centralWarehouse->id, $purchase->branch_id);
    }

    /** @test */
    public function receiving_product_purchase_credits_central_warehouse_stock()
    {
        $purchase = ProductPurchase::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->supplier_name,
            'purchase_number' => 'PUR-2026-0002',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 200000.00,
            'grand_total' => 200000.00,
            'paid_amount' => 0,
            'due_amount' => 200000.00,
            'purchase_status' => 'confirmed',
            'payment_status' => 'unpaid',
            'created_by' => $this->superAdmin->id,
        ]);

        ProductPurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $this->product->id,
            'product_sku_snapshot' => $this->product->sku,
            'product_name_snapshot' => $this->product->name,
            'quantity' => 10,
            'unit_purchase_cost' => 20000.00,
            'line_subtotal' => 200000.00,
            'line_total' => 200000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.product-purchase.receive', $purchase->id));

        $response->assertRedirect();

        // Check stock in Central Warehouse
        $warehouseStock = InventoryStock::where('branch_id', $this->centralWarehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($warehouseStock);
        $this->assertEquals(10, $warehouseStock->current_stock);

        // Retail branch stock should remain 0
        $retailStock = InventoryStock::where('branch_id', $this->retailBranch->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNull($retailStock);
    }

    /** @test */
    public function branch_inventory_step1_excludes_central_warehouse()
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.inventory.index'));

        $response->assertStatus(200);
        $response->assertSee('Rangdhamali Retail Branch');
        $response->assertDontSee('Central Warehouse (CWH-001)');
    }

    /** @test */
    public function warehouse_to_branch_stock_transfer_moves_stock_atomically()
    {
        // Add initial 30 units to Central Warehouse
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
            'product_id' => $this->product->id,
            'current_stock' => 30,
            'reserved_stock' => 0,
            'available_stock' => 30,
        ]);

        // Create transfer request from Central Warehouse to Retail Branch
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-2026-0001',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->retailBranch->id,
            'status' => 'draft',
            'requested_by' => $this->superAdmin->id,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 20000.00,
            'total_value' => 200000.00,
        ]);

        $service = app(InventoryTransferService::class);

        // Submit transfer request
        $service->requestTransfer($transfer);
        // Approve transfer
        $service->approveTransfer($transfer, $this->superAdmin->id);
        // Dispatch transfer (deducts warehouse stock)
        $service->dispatchTransfer($transfer, $this->superAdmin->id);

        $warehouseStockAfterDispatch = InventoryStock::where('branch_id', $this->centralWarehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(20, $warehouseStockAfterDispatch->current_stock);
        $this->assertEquals(20, $warehouseStockAfterDispatch->available_stock);

        // Receive transfer at retail branch (credits branch stock)
        $service->receiveTransfer($transfer, $this->superAdmin->id, [
            $transfer->items->first()->id => 10,
        ]);

        $retailStockAfterReceive = InventoryStock::where('branch_id', $this->retailBranch->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($retailStockAfterReceive);
        $this->assertEquals(10, $retailStockAfterReceive->current_stock);
        $this->assertEquals(10, $retailStockAfterReceive->available_stock);
    }
}
