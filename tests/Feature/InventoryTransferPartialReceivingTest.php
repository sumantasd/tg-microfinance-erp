<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\InventoryTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryTransferPartialReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $branchManagerA;
    protected User $branchManagerB;
    protected Company $company;
    protected Branch $centralWarehouse;
    protected Branch $branchA;
    protected Branch $branchB;
    protected ProductCategory $catSolar;
    protected ProductCategory $catElectronics;
    protected Product $productSolar;
    protected Product $productBattery;
    protected Product $productInverter;
    protected InventoryTransferService $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        // RBAC Permissions Setup
        $roleAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $roleBM = Role::create(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $permissions = [
            'inventory.view', 'inventory.manage', 'inventory.adjust',
            'inventory.transfer.view', 'inventory.transfer.create',
            'inventory.transfer.approve', 'inventory.transfer.dispatch',
            'inventory.transfer.receive', 'inventory.transfer.cancel', 'inventory.transfer.reject'
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $roleAdmin->syncPermissions(Permission::all());
        $roleBM->syncPermissions(['inventory.view', 'inventory.transfer.view', 'inventory.transfer.receive']);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance HO',
            'code' => 'HO001',
            'registration_number' => 'REG-HO-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO, Bihar',
            'is_active' => true,
        ]);

        $this->centralWarehouse = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Central Depot',
            'code' => 'CWH-001',
            'phone' => '8888888888',
            'address' => 'Warehouse Road, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
            'is_warehouse' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna City Retail Branch',
            'code' => 'BR-PAT-001',
            'phone' => '7777777777',
            'address' => 'Patna City',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800008',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Gaya Retail Branch',
            'code' => 'BR-GAY-001',
            'phone' => '6666666666',
            'address' => 'Gaya Town',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->adminUser = User::create([
            'name' => 'HQ Admin User',
            'email' => 'hqadmin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->centralWarehouse->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->branchManagerA = User::create([
            'name' => 'Branch Manager Patna',
            'email' => 'bm.patna@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $this->branchManagerA->assignRole('Branch Manager');

        $this->branchManagerB = User::create([
            'name' => 'Branch Manager Gaya',
            'email' => 'bm.gaya@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'status' => 'active',
        ]);
        $this->branchManagerB->assignRole('Branch Manager');

        $this->catSolar = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Power Systems',
            'code' => 'CAT-SOLAR',
            'is_active' => true,
        ]);

        $this->catElectronics = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Power Inverters & Electronics',
            'code' => 'CAT-ELEC',
            'is_active' => true,
        ]);

        $this->productSolar = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'SKU-SLR-50W',
            'name' => 'Solar Home Lighting 50W',
            'category_id' => $this->catSolar->id,
            'unit_price' => 12000.00,
            'is_active' => true,
        ]);

        $this->productBattery = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'SKU-BAT-150',
            'name' => 'Tubular Solar Battery 150Ah',
            'category_id' => $this->catSolar->id,
            'unit_price' => 18000.00,
            'is_active' => true,
        ]);

        $this->productInverter = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'SKU-INV-900',
            'name' => 'Pure Sine Wave Inverter 900VA',
            'category_id' => $this->catElectronics->id,
            'unit_price' => 8500.00,
            'is_active' => true,
        ]);

        // Stock Central Warehouse: 100 units of each product
        foreach ([$this->productSolar, $this->productBattery, $this->productInverter] as $prod) {
            InventoryStock::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->centralWarehouse->id,
                'product_id' => $prod->id,
                'current_stock' => 100,
                'reserved_stock' => 0,
                'reorder_level' => 10,
            ]);
        }

        $this->transferService = app(InventoryTransferService::class);
    }

    /** 1. Initiation does not change physical stock */
    public function test_initiation_does_not_change_physical_stock(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
            'remarks' => 'Test initiation stock safety',
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
            ['product_id' => $this->productBattery->id, 'quantity' => 5],
        ]);

        $this->assertEquals('draft', $transfer->status);
        $this->assertEquals(15, $transfer->total_quantity);

        // Source stock must remain 100
        $this->assertEquals(100, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productSolar->id)->value('current_stock'));
        $this->assertEquals(100, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productBattery->id)->value('current_stock'));

        // Destination stock must remain 0
        $this->assertNull(InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->first());

        // No movements recorded
        $this->assertDatabaseMissing('inventory_stock_movements', ['reference_type' => 'inventory_transfer']);
    }

    /** 2. Ready to Ship/approval does not change physical stock */
    public function test_approval_does_not_change_physical_stock(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 20],
        ]);

        $this->transferService->approveTransfer($transfer, $this->adminUser->id);

        $this->assertEquals('approved', $transfer->fresh()->status);

        // Stock at source must NOT be deducted at approval
        $this->assertEquals(100, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Stock at destination must NOT be added at approval
        $this->assertNull(InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->first());

        // No movements recorded
        $this->assertDatabaseMissing('inventory_stock_movements', ['reference_type' => 'inventory_transfer']);
    }

    /** 3. Dispatch deducts source stock exactly once */
    public function test_dispatch_deducts_source_stock_exactly_once(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 25],
        ]);

        $this->transferService->approveTransfer($transfer, $this->adminUser->id);
        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        $transfer->refresh();
        $this->assertEquals('in_transit', $transfer->status);

        // Source stock deducted exactly once: 100 - 25 = 75
        $this->assertEquals(75, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Destination stock still 0
        $this->assertNull(InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->first());

        // TRANSFER_OUT movement created
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->centralWarehouse->id,
            'product_id' => $this->productSolar->id,
            'movement_type' => 'transfer_out',
            'quantity' => -25,
            'stock_before' => 100,
            'stock_after' => 75,
        ]);
    }

    /** 4. Full receiving credits destination stock correctly */
    public function test_full_receiving_credits_destination_stock_correctly(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        // Branch Manager A receives full transfer (default empty payload)
        $this->actingAs($this->branchManagerA);
        $this->transferService->receiveTransfer($transfer);

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);

        // Destination stock credited exactly 10
        $this->assertEquals(10, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Line item received quantity = 10
        $item = $transfer->items->first();
        $this->assertEquals(10, $item->received_quantity);
        $this->assertEquals(0, $item->remaining_quantity);

        // Receipt history created
        $this->assertDatabaseHas('inventory_transfer_receipts', [
            'transfer_id' => $transfer->id,
            'total_good_quantity' => 10,
        ]);
    }

    /** 5. Partial receiving credits only the accepted good quantity */
    public function test_partial_receiving_credits_only_accepted_good_quantity(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        // Receive 7 good units out of 10
        $this->actingAs($this->branchManagerA);
        $this->transferService->receiveTransfer($transfer, [
            $transfer->items->first()->id => [
                'good_quantity' => 7,
                'damaged_quantity' => 0,
                'shortage_quantity' => 0,
            ]
        ]);

        $transfer->refresh();
        $this->assertEquals('partially_received', $transfer->status);

        // Destination stock must only be 7, not 10!
        $this->assertEquals(7, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        $item = $transfer->items->first();
        $this->assertEquals(7, $item->received_quantity);
        $this->assertEquals(3, $item->remaining_quantity);

        // Only 7 units in TRANSFER_IN ledger
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productSolar->id,
            'movement_type' => 'transfer_in',
            'quantity' => 7,
        ]);
    }

    /** 6. Receiving selected products across multiple categories */
    public function test_receiving_selected_products_across_multiple_categories(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],   // Solar category
            ['product_id' => $this->productInverter->id, 'quantity' => 5], // Electronics category
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        $solarItem = $transfer->items()->where('product_id', $this->productSolar->id)->first();
        $inverterItem = $transfer->items()->where('product_id', $this->productInverter->id)->first();

        // Branch Manager receives only the Inverter (all 5 units), Solar system not received yet
        $this->actingAs($this->branchManagerA);
        $this->transferService->receiveTransfer($transfer, [
            $inverterItem->id => ['good_quantity' => 5, 'damaged_quantity' => 0, 'shortage_quantity' => 0],
        ]);

        $transfer->refresh();
        $this->assertEquals('partially_received', $transfer->status);

        // Inverter stock credited 5
        $this->assertEquals(5, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productInverter->id)->value('current_stock'));

        // Solar stock not credited (0)
        $this->assertNull(InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->first());

        // Inverter line item completed, Solar line item pending
        $this->assertEquals(0, $inverterItem->fresh()->remaining_quantity);
        $this->assertEquals(10, $solarItem->fresh()->remaining_quantity);
    }

    /** 7. Multiple partial receipts for the same transfer */
    public function test_multiple_partial_receipts_for_same_transfer(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->branchManagerA);

        // Receipt Batch 1: 4 units
        $this->transferService->receiveTransfer($transfer, [
            $itemId => ['good_quantity' => 4],
        ], null, 'First delivery truck arrived');

        $this->assertEquals('partially_received', $transfer->fresh()->status);
        $this->assertEquals(4, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Receipt Batch 2: 3 units
        $this->transferService->receiveTransfer($transfer, [
            $itemId => ['good_quantity' => 3],
        ], null, 'Second van delivery');

        $this->assertEquals('partially_received', $transfer->fresh()->status);
        $this->assertEquals(7, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Receipt Batch 3: 3 units (final batch)
        $this->transferService->receiveTransfer($transfer, [
            $itemId => ['good_quantity' => 3],
        ], null, 'Final package received');

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);
        $this->assertEquals(10, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Exactly 3 receipts recorded
        $this->assertEquals(3, $transfer->receipts()->count());

        // Exactly 3 TRANSFER_IN ledger movements
        $movements = InventoryStockMovement::where('branch_id', $this->branchA->id)
            ->where('product_id', $this->productSolar->id)
            ->where('movement_type', 'transfer_in')
            ->get();
        $this->assertCount(3, $movements);
        $this->assertEquals([4, 3, 3], $movements->pluck('quantity')->toArray());
    }

    /** 8. Damaged and missing quantities do not enter sellable branch stock */
    public function test_damaged_and_missing_quantities_do_not_enter_sellable_stock(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->branchManagerA);
        // Receive 6 good, 2 broken/damaged, 2 missing/short
        $this->transferService->receiveTransfer($transfer, [
            $itemId => [
                'good_quantity' => 6,
                'damaged_quantity' => 2,
                'shortage_quantity' => 2,
                'remarks' => '2 panels cracked, 2 missing in transit',
            ]
        ]);

        $transfer->refresh();
        // Discrepancy status because all 10 accounted for but some damaged/short
        $this->assertEquals('discrepancy', $transfer->status);

        // Sellable stock MUST ONLY BE 6! Not 8, not 10!
        $this->assertEquals(6, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        $item = $transfer->items->first();
        $this->assertEquals(6, $item->received_quantity);
        $this->assertEquals(2, $item->damaged_quantity);
        $this->assertEquals(2, $item->shortage_quantity);
        $this->assertEquals(0, $item->remaining_quantity);
    }

    /** 9. Cannot over-receive beyond dispatched quantity */
    public function test_cannot_over_receive_beyond_dispatched_quantity(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 5],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $itemId = $transfer->items->first()->id;

        $this->actingAs($this->branchManagerA);

        $this->expectException(ValidationException::class);
        $this->transferService->receiveTransfer($transfer, [
            $itemId => ['good_quantity' => 6], // Exceeds 5!
        ]);
    }

    /** 10. Duplicate receipt requests cannot double-credit stock */
    public function test_duplicate_receipt_requests_cannot_double_credit_stock(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 5],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        $this->actingAs($this->branchManagerA);
        // Receive in full
        $this->transferService->receiveTransfer($transfer);

        $this->assertEquals(5, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Attempting to receive again on a completed transfer must fail
        $this->expectException(ValidationException::class);
        $this->transferService->receiveTransfer($transfer);
    }

    /** 11. Cross-branch unauthorized receiving is rejected */
    public function test_cross_branch_unauthorized_receiving_is_rejected(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id, // Destined for Branch A!
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 5],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        // Branch Manager B (Gaya) tries to receive transfer for Branch A (Patna) -> 403 / ValidationException
        $this->actingAs($this->branchManagerB);
        $response = $this->post(route('admin.inventory-transfer.receive', $transfer->id));
        $response->assertStatus(403);

        // Branch stock at Branch B remains 0
        $this->assertNull(InventoryStock::where('branch_id', $this->branchB->id)->where('product_id', $this->productSolar->id)->first());
    }

    /** 12. Approved cancellation cannot corrupt stock */
    public function test_approved_cancellation_cannot_corrupt_stock(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 15],
        ]);

        $this->transferService->approveTransfer($transfer, $this->adminUser->id);
        $this->assertEquals('approved', $transfer->fresh()->status);

        // Cancel approved transfer
        $this->transferService->cancelTransfer($transfer, $this->adminUser->id);
        $this->assertEquals('cancelled', $transfer->fresh()->status);

        // Warehouse stock must still be 100
        $this->assertEquals(100, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // Branch A stock must still be 0
        $this->assertNull(InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->first());
    }

    /** 13. Cannot cancel or reject once dispatched */
    public function test_cannot_cancel_or_reject_once_dispatched(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $this->assertEquals('in_transit', $transfer->fresh()->status);

        // Attempt cancel -> throws error
        $this->expectException(ValidationException::class);
        $this->transferService->cancelTransfer($transfer, $this->adminUser->id);
    }

    /** 14. API endpoint supports partial receiving */
    public function test_api_partial_receiving_support(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $itemId = $transfer->items->first()->id;

        // Post to API with token
        $response = $this->actingAs($this->branchManagerA, 'sanctum')->postJson("/api/v1/inventory/transfers/{$transfer->id}/receive", [
            'notes' => 'Mobile API partial receipt',
            'items' => [
                [
                    'transfer_item_id' => $itemId,
                    'good_quantity' => 8,
                    'damaged_quantity' => 1,
                    'shortage_quantity' => 1,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'discrepancy');

        // Check stock
        $this->assertEquals(8, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));
    }

    /** 15. Authorized user can cancel remaining item quantity */
    public function test_authorized_user_can_cancel_remaining_item_quantity(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $item = $transfer->items->first();

        // Branch receives 6 good units
        $this->transferService->receiveTransfer($transfer, [
            $item->id => ['good_quantity' => 6]
        ]);

        $this->assertEquals(6, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));
        $this->assertEquals(4, $item->fresh()->remaining_quantity);

        // Cancel remaining 4 units
        $updated = $this->transferService->cancelRemainingItems(
            $transfer,
            [$item->id => 4],
            'Remaining 4 units lost in transit and will never arrive',
            $this->adminUser->id
        );

        $itemFresh = $item->fresh();
        $this->assertEquals(0, $itemFresh->remaining_quantity);
        $this->assertEquals(4, $itemFresh->cancelled_quantity);
        $this->assertStringContainsString('lost in transit', $itemFresh->cancellation_reason);
        $this->assertEquals($this->adminUser->id, $itemFresh->cancelled_by);

        // Received stock remains strictly untouched
        $this->assertEquals(6, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock'));
        // Source warehouse remains at 90 (100 - 10 dispatched)
        $this->assertEquals(90, InventoryStock::where('branch_id', $this->centralWarehouse->id)->where('product_id', $this->productSolar->id)->value('current_stock'));

        // All units accounted for (6 good + 4 cancelled = 10), so status is received
        $this->assertEquals('received', $updated->status);

        // Audit cancellation record exists
        $this->assertDatabaseHas('inventory_transfer_cancellations', [
            'transfer_id' => $transfer->id,
            'scope' => 'item',
            'total_cancelled_quantity' => 4,
            'cancelled_by' => $this->adminUser->id,
        ]);
        $this->assertDatabaseHas('inventory_transfer_cancellation_items', [
            'transfer_item_id' => $item->id,
            'product_id' => $this->productSolar->id,
            'cancelled_quantity' => 4,
        ]);
    }

    /** 16. Cancelled quantities cannot be received later in the same transfer */
    public function test_cancelled_quantities_cannot_be_received_later(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $item = $transfer->items->first();

        // Receive 7, cancel 3
        $this->transferService->receiveTransfer($transfer, [$item->id => ['good_quantity' => 7]]);
        $this->transferService->cancelRemainingItems($transfer, [$item->id => 3], 'Damaged beyond repair in transit');

        $this->assertEquals(0, $item->fresh()->remaining_quantity);

        // Attempt to receive 1 unit later in the same transfer -> should throw ValidationException
        $this->expectException(ValidationException::class);
        $this->transferService->receiveTransfer($transfer, [$item->id => ['good_quantity' => 1]]);
    }

    /** 17. Authorized user can cancel remaining category quantities */
    public function test_authorized_user_can_cancel_remaining_category_quantities(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
            ['product_id' => $this->productBattery->id, 'quantity' => 5],
            ['product_id' => $this->productInverter->id, 'quantity' => 8],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        // Cancel remaining for Solar category
        $updated = $this->transferService->cancelCategoryRemaining(
            $transfer,
            $this->catSolar->id,
            'Solar parts discontinued by supplier',
            $this->adminUser->id
        );

        $items = $updated->items()->get();
        $solarItem = $items->firstWhere('product_id', $this->productSolar->id);
        $batteryItem = $items->firstWhere('product_id', $this->productBattery->id);
        $inverterItem = $items->firstWhere('product_id', $this->productInverter->id);

        $this->assertEquals(0, $solarItem->remaining_quantity);
        $this->assertEquals(10, $solarItem->cancelled_quantity);

        $this->assertEquals(0, $batteryItem->remaining_quantity);
        $this->assertEquals(5, $batteryItem->cancelled_quantity);

        // Inverter (Electronics category) is untouched and still pending
        $this->assertEquals(8, $inverterItem->remaining_quantity);
        $this->assertEquals(0, $inverterItem->cancelled_quantity);

        $this->assertEquals('partially_received', $updated->status);
    }

    /** 18. Cancelling all items when zero received marks transfer cancelled */
    public function test_cancelling_all_items_when_zero_received_marks_transfer_cancelled(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);

        $updated = $this->transferService->cancelAllRemaining(
            $transfer,
            'Shipment vehicle accident, total loss',
            $this->adminUser->id
        );

        $this->assertEquals('cancelled', $updated->status);
        $this->assertEquals(0, $updated->items->first()->remaining_quantity);
        $this->assertEquals(10, $updated->items->first()->cancelled_quantity);
        $this->assertEquals(0, InventoryStock::where('branch_id', $this->branchA->id)->where('product_id', $this->productSolar->id)->value('current_stock') ?? 0);
    }

    /** 19. Unauthorized user cannot cancel remaining quantities via web controller */
    public function test_unauthorized_user_cannot_cancel_remaining_quantities(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $item = $transfer->items->first();

        // Branch manager has no inventory.transfer.cancel permission
        $response = $this->actingAs($this->branchManagerA)->post(route('admin.inventory-transfer.cancel-remaining', $transfer->id), [
            'scope' => 'item',
            'item_id' => $item->id,
            'quantity' => 5,
            'cancellation_reason' => 'Branch manager attempting cancel',
        ]);

        $response->assertStatus(403);
    }

    /** 20. API endpoint supports cancel-remaining */
    public function test_api_cancel_remaining_endpoint(): void
    {
        $transfer = $this->transferService->createTransfer([
            'source_branch_id' => $this->centralWarehouse->id,
            'destination_branch_id' => $this->branchA->id,
        ], [
            ['product_id' => $this->productSolar->id, 'quantity' => 10],
        ]);

        $this->transferService->dispatchTransfer($transfer, $this->adminUser->id);
        $item = $transfer->items->first();

        $response = $this->actingAs($this->adminUser, 'sanctum')->postJson("/api/v1/inventory/transfers/{$transfer->id}/cancel-remaining", [
            'scope' => 'item',
            'item_id' => $item->id,
            'quantity' => 10,
            'cancellation_reason' => 'Cancelled via mobile API',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');

        $this->assertEquals(0, $item->fresh()->remaining_quantity);
        $this->assertEquals(10, $item->fresh()->cancelled_quantity);
    }
}
