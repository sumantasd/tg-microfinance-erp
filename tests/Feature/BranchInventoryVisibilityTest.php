<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchInventoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $companyAdmin;
    protected User $branchManagerA;
    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $companyRole = Role::create(['name' => 'Company Admin', 'guard_name' => 'web']);
        $branchRole = Role::create(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $permissions = [
            'inventory.view', 'inventory.manage', 'inventory.adjust', 'inventory.restock',
            'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.approve',
            'inventory.transfer.dispatch', 'inventory.transfer.receive', 'inventory.transfer.reject', 'inventory.transfer.cancel'
        ];
        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }

        $superRole->syncPermissions(Permission::all());
        $companyRole->syncPermissions(Permission::all());
        $branchRole->syncPermissions(['inventory.view']);

        $this->company = Company::create([
            'name' => 'Grihalaxmi HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO, Bihar',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Jalpaiguri Branch',
            'code' => 'JAL01',
            'phone' => '8888888888',
            'address' => 'Jalpaiguri',
            'city' => 'Jalpaiguri',
            'state' => 'West Bengal',
            'pincode' => '735101',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Ranaghat Branch',
            'code' => 'RNG01',
            'phone' => '7777777777',
            'address' => 'Ranaghat',
            'city' => 'Ranaghat',
            'state' => 'West Bengal',
            'pincode' => '741201',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->companyAdmin = User::create([
            'name' => 'Company Admin User',
            'email' => 'companyadmin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $this->companyAdmin->assignRole('Company Admin');

        $this->branchManagerA = User::create([
            'name' => 'Branch Manager Jalpaiguri',
            'email' => 'bm_jalpaiguri@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'status' => 'active',
        ]);
        $this->branchManagerA->assignRole('Branch Manager');

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-TV01',
            'name' => 'Smart TV 32 Inch',
            'unit_price' => 12500.00,
            'is_active' => true,
        ]);
    }

    public function test_same_product_inventory_is_visible_across_multiple_branches(): void
    {
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'current_stock' => 5,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);

        // 1. Company Admin sees BOTH branches (10 units & 5 units)
        $response = $this->actingAs($this->companyAdmin)->get(route('admin.inventory.index'));
        $response->assertOk();
        $stocks = $response->viewData('stocks');
        $this->assertEquals(2, $stocks->count());

        // 2. Branch A Filter by Company Admin
        $responseBranchA = $this->actingAs($this->companyAdmin)->get(route('admin.inventory.index', ['branch_id' => $this->branchA->id]));
        $responseBranchA->assertOk();
        $stocksA = $responseBranchA->viewData('stocks');
        $this->assertEquals(1, $stocksA->count());
        $this->assertEquals($this->branchA->id, $stocksA->first()->branch_id);

        // 3. Branch B Filter by Company Admin
        $responseBranchB = $this->actingAs($this->companyAdmin)->get(route('admin.inventory.index', ['branch_id' => $this->branchB->id]));
        $responseBranchB->assertOk();
        $stocksB = $responseBranchB->viewData('stocks');
        $this->assertEquals(1, $stocksB->count());
        $this->assertEquals($this->branchB->id, $stocksB->first()->branch_id);

        // 4. Branch Manager A is locked to Branch A
        $responseBM = $this->actingAs($this->branchManagerA)->get(route('admin.inventory.index'));
        $responseBM->assertOk();
        $stocksBM = $responseBM->viewData('stocks');
        $this->assertEquals(1, $stocksBM->count());
        $this->assertEquals($this->branchA->id, $stocksBM->first()->branch_id);
    }

    public function test_restock_creates_inventory_stock_record_for_new_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.inventory.restock'), [
            'branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'remarks' => 'Restock new branch',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branchB->id,
            'product_id' => $this->product->id,
            'current_stock' => 15,
        ]);
    }

    public function test_quick_restock_three_level_dependent_ajax_and_end_to_end_restock(): void
    {
        $category = \App\Models\ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Home Appliances',
            'code' => 'CAT-HA01',
            'is_active' => true,
        ]);

        $brand = \App\Models\ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'LG Electronics',
            'code' => 'BRD-LG',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-LG-REF01',
            'name' => 'LG Refrigerator 260L',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_price' => 24500.00,
            'is_active' => true,
        ]);

        // 1. AJAX Brands by Category
        $brandsResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.inventory.ajax.brands-by-category', ['category_id' => $category->id]));
        $brandsResponse->assertOk();
        $brandsData = $brandsResponse->json();
        $this->assertNotEmpty($brandsData);
        $this->assertEquals($brand->id, $brandsData[0]['id']);

        // 2. AJAX Products by Brand & Category
        $productsResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.inventory.ajax.products-by-brand', ['category_id' => $category->id, 'brand_id' => $brand->id]));
        $productsResponse->assertOk();
        $productsData = $productsResponse->json();
        $this->assertNotEmpty($productsData);
        $this->assertEquals($product->id, $productsData[0]['id']);

        // 3. Submit Quick Restock
        $restockResponse = $this->actingAs($this->superAdmin)->post(route('admin.inventory.restock'), [
            'branch_id' => $this->branchA->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'product_id' => $product->id,
            'quantity' => 25,
            'unit_price' => 22000.00,
            'remarks' => 'Bulk Restock LG Refrigerators',
        ]);

        $restockResponse->assertRedirect();
        $restockResponse->assertSessionHas('success');

        // 4. Verify Stock created & updated
        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branchA->id,
            'product_id' => $product->id,
            'current_stock' => 25,
        ]);
    }

    public function test_stock_transfer_three_level_dependent_ajax_and_end_to_end_transfer_workflow(): void
    {
        $category = \App\Models\ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Solar Power',
            'code' => 'CAT-SLR',
            'is_active' => true,
        ]);

        $brand = \App\Models\ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Luminous',
            'code' => 'BRD-LUM',
            'is_active' => true,
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-LUM-INV01',
            'name' => 'Luminous Inverter 1100VA',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        // Restock 50 units at Branch A first
        $this->actingAs($this->superAdmin)->post(route('admin.inventory.restock'), [
            'branch_id' => $this->branchA->id,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        // Initiate Transfer from Branch A to Branch B for 20 units
        $transferResponse = $this->actingAs($this->superAdmin)->post(route('admin.inventory-transfer.store'), [
            'source_branch_id' => $this->branchA->id,
            'destination_branch_id' => $this->branchB->id,
            'remarks' => 'Inter-branch stock rebalancing',
            'items' => [
                [
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'product_id' => $product->id,
                    'quantity' => 20,
                ],
            ],
        ]);

        $transferResponse->assertSessionHasNoErrors();
        $transferResponse->assertRedirect();

        $location = $transferResponse->headers->get('Location');
        $transferId = (int) last(explode('/', parse_url($location, PHP_URL_PATH)));
        $transfer = \App\Models\InventoryTransfer::findOrFail($transferId);

        $this->assertNotNull($transfer);
        $this->assertEquals('draft', $transfer->status);

        $transferService = app(\App\Services\InventoryTransferService::class);

        // Request Transfer
        $transfer = $transferService->requestTransfer($transfer);
        $this->assertEquals('requested', $transfer->status);

        // Approve Transfer
        $transfer = $transferService->approveTransfer($transfer);
        $this->assertEquals('approved', $transfer->status);

        // Dispatch Transfer (deduct stock from Branch A)
        $transfer = $transferService->dispatchTransfer($transfer);
        $this->assertEquals('in_transit', $transfer->status);

        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branchA->id,
            'product_id' => $product->id,
            'current_stock' => 30, // 50 - 20 = 30
        ]);

        // Receive Transfer (add stock to Branch B)
        $transfer = $transferService->receiveTransfer($transfer);
        $this->assertEquals('received', $transfer->status);

        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branchB->id,
            'product_id' => $product->id,
            'current_stock' => 20, // 0 + 20 = 20
        ]);
    }
}
