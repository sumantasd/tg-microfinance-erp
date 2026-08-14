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

        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $companyRole = Role::create(['name' => 'Company Admin', 'guard_name' => 'web']);
        $branchRole = Role::create(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $permissions = ['inventory.view', 'inventory.manage', 'inventory.adjust'];
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
}
