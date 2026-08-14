<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchInventoryTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $sourceBranch;
    protected Branch $destBranch;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $permissions = [
            'inventory.view', 'inventory.manage', 'inventory.adjust',
            'inventory.transfer.view', 'inventory.transfer.create',
            'inventory.transfer.approve', 'inventory.transfer.dispatch',
            'inventory.transfer.receive', 'inventory.transfer.cancel'
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

        $this->sourceBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Patna Branch',
            'code' => 'BR001',
            'phone' => '8888888888',
            'address' => 'Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->destBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Gaya Branch',
            'code' => 'BR002',
            'phone' => '7777777777',
            'address' => 'Gaya',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->sourceBranch->id,
            'status' => 'active',
        ]);

        $this->adminUser->assignRole('Super Admin');

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-00100',
            'name' => 'Solar Lighting System 50W',
            'brand' => 'Tata Solar',
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        // Restock 50 units at source branch
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->sourceBranch->id,
            'product_id' => $this->product->id,
            'current_stock' => 50,
            'reserved_stock' => 0,
            'reorder_level' => 5,
        ]);
    }

    public function test_can_create_transfer_request(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.store'), [
            'source_branch_id' => $this->sourceBranch->id,
            'destination_branch_id' => $this->destBranch->id,
            'remarks' => 'Transfer 10 Solar Kits to Gaya Branch',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('inventory_transfers', [
            'source_branch_id' => $this->sourceBranch->id,
            'destination_branch_id' => $this->destBranch->id,
            'status' => 'draft',
            'total_quantity' => 10,
        ]);

        $transfer = InventoryTransfer::where('source_branch_id', $this->sourceBranch->id)->first();
        $this->assertNotNull($transfer);
        $this->assertStringContainsString('TRF-BR001-', $transfer->transfer_number);
    }

    public function test_prevents_transfer_to_same_source_and_destination_branch(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.store'), [
            'source_branch_id' => $this->sourceBranch->id,
            'destination_branch_id' => $this->sourceBranch->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                ]
            ]
        ]);

        $response->assertSessionHasErrors('destination_branch_id');
    }

    public function test_complete_transfer_lifecycle_approval_dispatch_and_receipt(): void
    {
        // 1. Create Draft Transfer
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-BR001-2026-00001',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->sourceBranch->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->destBranch->id,
            'status' => 'draft',
            'total_items' => 1,
            'total_quantity' => 15,
            'total_value' => 225000.00,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 15,
            'unit_price' => 15000.00,
            'total_value' => 225000.00,
        ]);

        // 2. Request & Approve Transfer
        $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.request', $transfer->id));
        $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.approve', $transfer->id));

        $this->assertEquals('approved', $transfer->fresh()->status);

        // 3. Dispatch Transfer -> Deducts 15 units from Source Branch (50 -> 35)
        $dispatchResp = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.dispatch', $transfer->id));
        $dispatchResp->assertRedirect();

        $this->assertEquals('in_transit', $transfer->fresh()->status);

        // Source Branch Stock assertion (50 - 15 = 35)
        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->sourceBranch->id,
            'product_id' => $this->product->id,
            'current_stock' => 35,
        ]);

        // TRANSFER_OUT movement assertion
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->sourceBranch->id,
            'product_id' => $this->product->id,
            'movement_type' => 'transfer_out',
            'quantity' => -15,
            'stock_before' => 50,
            'stock_after' => 35,
        ]);

        // 4. Receive Transfer at Destination Branch -> Adds 15 units to Destination Branch (0 -> 15)
        $receiveResp = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.receive', $transfer->id));
        $receiveResp->assertRedirect();

        $this->assertEquals('received', $transfer->fresh()->status);

        // Destination Branch Stock assertion
        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->destBranch->id,
            'product_id' => $this->product->id,
            'current_stock' => 15,
        ]);

        // TRANSFER_IN movement assertion
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->destBranch->id,
            'product_id' => $this->product->id,
            'movement_type' => 'transfer_in',
            'quantity' => 15,
            'stock_before' => 0,
            'stock_after' => 15,
        ]);
    }

    public function test_prevents_dispatching_more_than_available_stock(): void
    {
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-BR001-2026-00002',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->sourceBranch->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->destBranch->id,
            'status' => 'approved',
            'total_items' => 1,
            'total_quantity' => 100, // Available stock is only 50!
            'total_value' => 1500000.00,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 100,
            'unit_price' => 15000.00,
            'total_value' => 1500000.00,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.dispatch', $transfer->id));
        $response->assertSessionHasErrors('stock');
    }

    public function test_prevents_duplicate_dispatch_or_receive(): void
    {
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-BR001-2026-00003',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->sourceBranch->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->destBranch->id,
            'status' => 'in_transit', // Already dispatched!
            'total_items' => 1,
            'total_quantity' => 5,
            'total_value' => 75000.00,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 15000.00,
            'total_value' => 75000.00,
        ]);

        // Attempting second dispatch
        $response = $this->actingAs($this->adminUser)->post(route('admin.inventory-transfer.dispatch', $transfer->id));
        $response->assertSessionHasErrors('status');
    }
}
