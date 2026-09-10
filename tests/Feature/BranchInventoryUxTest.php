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

class BranchInventoryUxTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchStaffA;
    protected User $branchStaffB;
    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $branchRole = Role::create(['name' => 'Branch Staff', 'guard_name' => 'web']);

        $perm = Permission::create(['name' => 'inventory.view', 'guard_name' => 'web']);
        $superRole->givePermissionTo($perm);
        $branchRole->givePermissionTo($perm);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance HO',
            'code' => 'GLF-HO',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Siliguri, West Bengal',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Rangdhamali Branch',
            'code' => 'GLF-HO-002',
            'phone' => '9876543210',
            'address' => 'Rangdhamali Main Road',
            'city' => 'Siliguri',
            'state' => 'West Bengal',
            'pincode' => '734001',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Nagrakata Branch',
            'code' => 'BR-GLF-003',
            'phone' => '9876543211',
            'address' => 'Nagrakata Station Road',
            'city' => 'Nagrakata',
            'state' => 'West Bengal',
            'pincode' => '735225',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->branchStaffA = User::factory()->create([
            'name' => 'Staff Rangdhamali',
            'email' => 'staffa@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
        ]);
        $this->branchStaffA->assignRole('Branch Staff');

        $this->branchStaffB = User::factory()->create([
            'name' => 'Staff Nagrakata',
            'email' => 'staffb@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
        ]);
        $this->branchStaffB->assignRole('Branch Staff');

        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Steel Bed Deluxe',
            'sku' => 'PRD-BED-001',
            'unit_price' => 15000,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Wooden Dining Table',
            'sku' => 'PRD-TBL-002',
            'unit_price' => 22000,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->productA->id,
            'current_stock' => 10,
            'reserved_stock' => 2,
            'reorder_level' => 3,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'product_id' => $this->productB->id,
            'current_stock' => 5,
            'reserved_stock' => 1,
            'reorder_level' => 2,
        ]);
    }

    public function test_initial_page_shows_branch_list_and_not_stocks_table(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.inventory.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.inventory.index');

        $response->assertSee('Rangdhamali Branch');
        $response->assertSee('GLF-HO-002');
        $response->assertSee('Nagrakata Branch');
        $response->assertSee('BR-GLF-003');

        $response->assertSee('View Inventory');

        $response->assertViewHas('selectedBranch', null);
    }

    public function test_selecting_authorized_branch_shows_only_that_branch_inventory(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.inventory.index', ['branch_id' => $this->branchA->id]));

        $response->assertStatus(200);
        $response->assertViewHas('selectedBranch');
        $this->assertEquals($this->branchA->id, $response->viewData('selectedBranch')->id);

        $stocks = $response->viewData('stocks');
        $this->assertCount(1, $stocks);
        $this->assertEquals($this->productA->id, $stocks->first()->product_id);
        $response->assertSee('Steel Bed Deluxe');
    }

    public function test_searching_within_selected_branch_operates_only_within_that_branch(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.inventory.index', [
                'branch_id' => $this->branchA->id,
                'search' => 'Steel Bed'
            ]));

        $response->assertStatus(200);
        $stocks = $response->viewData('stocks');
        $this->assertCount(1, $stocks);
        $this->assertEquals($this->productA->id, $stocks->first()->product_id);
        $response->assertSee('Steel Bed Deluxe');
    }

    public function test_unauthorized_branch_access_attempt_is_rejected(): void
    {
        $response = $this->actingAs($this->branchStaffA)
            ->get(route('admin.inventory.index', ['branch_id' => $this->branchB->id]));

        $response->assertRedirect(route('admin.inventory.index'));
        $response->assertSessionHas('error');
    }
}
