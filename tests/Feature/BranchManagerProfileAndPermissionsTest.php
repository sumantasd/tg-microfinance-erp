<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagerProfileAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;

    protected User $admin;
    protected User $branchManager1;
    protected User $branchManager2;
    protected Employee $employee1;
    protected Employee $employee2;

    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Ltd',
            'code' => 'GFL',
            'email' => 'info@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'HQ Address, Kolkata',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Salt Lake Branch',
            'code' => 'SL-01',
            'email' => 'saltlake@grihalaxmi.com',
            'phone' => '9876543211',
            'address' => 'Sector 5, Salt Lake',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700091',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Howrah Branch',
            'code' => 'HW-01',
            'email' => 'howrah@grihalaxmi.com',
            'phone' => '9876543212',
            'address' => 'Howrah Station Road',
            'city' => 'Howrah',
            'state' => 'West Bengal',
            'pincode' => '711101',
            'is_active' => true,
            'is_warehouse' => false,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Operations',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        $this->designation = Designation::create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'title' => 'Branch Manager',
            'code' => 'BM',
            'is_active' => true,
        ]);

        $this->admin = User::where('email', 'admin@grihalaxmifinance.com')->first();
        if (!$this->admin) {
            $this->admin = User::factory()->create([
                'name' => 'HQ System Admin',
                'email' => 'admin@grihalaxmifinance.com',
                'company_id' => $this->company->id,
                'status' => 'active',
            ]);
            $this->admin->assignRole('Admin');
        }

        $this->branchManager1 = User::factory()->create([
            'name' => 'Salt Lake Manager',
            'email' => 'bm1@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'status' => 'active',
        ]);
        $this->branchManager1->assignRole('Branch Manager');

        $this->employee1 = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'user_id' => $this->branchManager1->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-BM-01',
            'first_name' => 'Salt Lake',
            'last_name' => 'Manager',
            'email' => 'bm1@grihalaxmi.com',
            'phone' => '9876543211',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->branchManager2 = User::factory()->create([
            'name' => 'Howrah Manager',
            'email' => 'bm2@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'status' => 'active',
        ]);
        $this->branchManager2->assignRole('Branch Manager');

        $this->employee2 = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'user_id' => $this->branchManager2->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_code' => 'EMP-BM-02',
            'first_name' => 'OtherStaffFirstname',
            'last_name' => 'OtherStaffLastname',
            'email' => 'bm2@grihalaxmi.com',
            'phone' => '9876543212',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    /** 1. Product Catalog, Product Brand & Category are hidden and direct access is denied (403) */
    public function test_product_catalog_brand_and_category_are_hidden_and_inaccessible_to_branch_manager(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Catalog Item',
            'code' => 'PROD-CAT-01',
            'sku' => 'SKU-CAT-01',
            'unit' => 'pcs',
            'unit_price' => 5000.00,
            'is_active' => true,
        ]);

        // Direct GET requests for Catalog, Brand, Category return 403 Forbidden
        $this->actingAs($this->branchManager1)->get('/admin/product')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/product/create')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get("/admin/product/{$product->id}")->assertStatus(403);
        $this->actingAs($this->branchManager1)->get("/admin/product/{$product->id}/edit")->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/product-brand')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/product-category')->assertStatus(403);

        // Admin access remains unchanged
        $this->actingAs($this->admin)->get("/admin/product/{$product->id}")->assertStatus(200);
    }

    /** 2. Branch Inventory opens successfully and displays assigned branch stock */
    public function test_branch_inventory_opens_successfully_and_displays_only_assigned_branch_stock(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Inventory Item',
            'code' => 'INV-001',
            'sku' => 'SKU-INV-001',
            'unit' => 'pcs',
            'unit_price' => 2000.00,
            'is_active' => true,
        ]);

        $stockBranch1 = InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $product->id,
            'current_stock' => 50,
            'reserved_stock' => 0,
        ]);

        $stockBranch2 = InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'product_id' => $product->id,
            'current_stock' => 100,
            'reserved_stock' => 0,
        ]);

        // Branch Manager 1 opens Branch Inventory cleanly -> 200 OK
        $responseIndex = $this->actingAs($this->branchManager1)->get('/admin/inventory');
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Salt Lake Branch');
        $responseIndex->assertSee('50 Units');
        $responseIndex->assertSee('Branch Inventory Item');
        $responseIndex->assertSee('SKU-INV-001');
        $responseIndex->assertDontSee(route('admin.product.show', $product->id));

        // Accessing another branch via URL parameter is denied (403)
        $this->actingAs($this->branchManager1)->get('/admin/inventory?branch_id=' . $this->branch2->id)->assertStatus(403);
    }

    /** 3. Branch Manager cannot modify inventory */
    public function test_branch_manager_cannot_modify_inventory(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Modify Block Product',
            'code' => 'INV-002',
            'sku' => 'SKU-INV-002',
            'unit' => 'pcs',
            'unit_price' => 3000.00,
            'is_active' => true,
        ]);

        $stock = InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $product->id,
            'current_stock' => 20,
            'reserved_stock' => 0,
        ]);

        $this->actingAs($this->branchManager1)->post('/admin/inventory/restock', [
            'branch_id' => $this->branch1->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ])->assertStatus(403);

        $this->actingAs($this->branchManager1)->post('/admin/inventory/adjust', [
            'branch_id' => $this->branch1->id,
            'product_id' => $product->id,
            'new_stock_level' => 5,
            'remarks' => 'Unauthorized adjustment attempt',
        ])->assertStatus(403);
    }

    /** 4 & 5. Create New Transfer hidden & denied; eligible incoming transfer received exactly once */
    public function test_create_new_transfer_hidden_and_eligible_transfer_received_exactly_once(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Transfer Phone',
            'code' => 'TRF-PROD-01',
            'sku' => 'SKU-TRF-01',
            'unit' => 'pcs',
            'unit_price' => 15000.00,
            'is_active' => true,
        ]);

        // Branch Manager cannot access create page or submit transfer creation -> 403
        $this->actingAs($this->branchManager1)->get('/admin/inventory/transfers/create')->assertStatus(403);
        $this->actingAs($this->branchManager1)->post('/admin/inventory/transfers', [
            'source_branch_id' => $this->branch2->id,
            'destination_branch_id' => $this->branch1->id,
        ])->assertStatus(403);

        // Create an in_transit transfer to Branch 1
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-TEST-001',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->branch2->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->branch1->id,
            'status' => 'in_transit',
            'created_by' => $this->admin->id,
        ]);

        InventoryTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_price' => 15000.00,
            'total_value' => 150000.00,
        ]);

        // Branch Manager 2 (Howrah) cannot receive transfer meant for Salt Lake (Branch 1) -> 403
        $this->actingAs($this->branchManager2)->post("/admin/inventory/transfers/{$transfer->id}/receive")->assertStatus(403);

        // Branch Manager 1 (Salt Lake) can receive transfer -> 302 redirect with success
        $responseReceive = $this->actingAs($this->branchManager1)->post("/admin/inventory/transfers/{$transfer->id}/receive");
        $responseReceive->assertStatus(302);

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);

        // Stock added to Branch 1
        $stock = InventoryStock::where('branch_id', $this->branch1->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(10, $stock->current_stock);

        // Cannot duplicate receipt -> 403 Forbidden
        $responseDuplicate = $this->actingAs($this->branchManager1)->post("/admin/inventory/transfers/{$transfer->id}/receive");
        $responseDuplicate->assertStatus(403);
        
        // Stock remains 10 (not double counted)
        $stock->refresh();
        $this->assertEquals(10, $stock->current_stock);
    }

    /** 6. Employee, Department and Designation are hidden and inaccessible (403) */
    public function test_employee_department_and_designation_are_hidden_and_inaccessible(): void
    {
        $this->actingAs($this->branchManager1)->get('/admin/employee')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/employee/create')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/department')->assertStatus(403);
        $this->actingAs($this->branchManager1)->get('/admin/designation')->assertStatus(403);
    }

    /** 7. Attendance, Leave, Payroll, HR Letter & ID Cards expose only own records */
    public function test_hrm_modules_expose_only_logged_in_user_own_records(): void
    {
        // Attendance records
        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'employee_id' => $this->employee1->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);
        Attendance::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'employee_id' => $this->employee2->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $resAttendance = $this->actingAs($this->branchManager1)->get('/admin/hrm/attendance');
        $resAttendance->assertStatus(200);
        $resAttendance->assertSee($this->employee1->first_name);
        $resAttendance->assertDontSee($this->employee2->first_name);

        // Leave records & approve action
        $leaveType = LeaveType::create([
            'company_id' => $this->company->id,
            'name' => 'Casual Leave',
            'code' => 'CL',
            'days_per_year' => 12,
            'is_active' => true,
        ]);

        $leave2 = Leave::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'employee_id' => $this->employee2->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'total_days' => 1,
            'reason' => 'Sick',
            'status' => 'pending',
        ]);

        $resLeave = $this->actingAs($this->branchManager1)->get('/admin/hrm/leave');
        $resLeave->assertStatus(200);

        // BM1 cannot approve another employee leave request -> 403
        $this->actingAs($this->branchManager1)->post("/admin/hrm/leave/{$leave2->id}/approve")->assertStatus(403);

        // Payroll records & process/disburse actions
        $resPayroll = $this->actingAs($this->branchManager1)->get('/admin/hrm/payroll');
        $resPayroll->assertStatus(200);

        // BM1 cannot process or disburse payroll -> 403
        $this->actingAs($this->branchManager1)->post('/admin/hrm/payroll', [
            'month' => now()->month,
            'year' => now()->year,
        ])->assertStatus(403);

        // HR Letters & ID cards
        $resLetters = $this->actingAs($this->branchManager1)->get('/admin/hrm/letters');
        $resLetters->assertStatus(200);

        // ID Card own access vs other employee access
        $this->actingAs($this->branchManager1)->get('/admin/hrm/letters/' . $this->employee1->id . '/id-card')->assertStatus(200);
        $this->actingAs($this->branchManager1)->get('/admin/hrm/letters/' . $this->employee2->id . '/id-card')->assertStatus(403);
    }

    /** 8. Admin permissions and existing workflows continue to work */
    public function test_admin_permissions_and_existing_workflows_continue_to_work(): void
    {
        $this->actingAs($this->admin)->get('/admin/product')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/product-brand')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/product-category')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/department')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/designation')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/employee')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/inventory')->assertStatus(200);
    }
}
