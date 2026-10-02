<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\FieldLocationLog;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\SalarySlip;
use App\Models\TravelAllowanceClaim;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\LocationTrackingService;
use App\Services\TravelAllowanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchManagerAndFieldTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branchSource;
    protected Branch $branchDest;
    protected User $superAdmin;
    protected User $bmSource;
    protected User $bmDest;
    protected Product $product;
    protected Employee $empDest;

    protected function setUp(): void
    {
        parent::setUp();

        $superRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $bmRole = Role::firstOrCreate(['name' => 'Branch Manager', 'guard_name' => 'web']);

        $permissions = [
            'inventory.view', 'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.approve',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'field_tracking.view', 'ta_claims.view', 'ta_claims.create', 'ta_claims.approve', 'ta_claims.pay',
            'settings.view',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $superRole->syncPermissions(Permission::all());
        $bmRole->syncPermissions(['inventory.view', 'inventory.transfer.view', 'inventory.transfer.approve', 'payroll.view', 'field_tracking.view', 'ta_claims.view', 'ta_claims.create']);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Ltd',
            'code' => 'GFL-01',
            'email' => 'info@grihalaxmi.test',
            'phone' => '9876543210',
            'address' => 'Kolkata, West Bengal',
            'is_active' => true,
        ]);

        $this->branchSource = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Source Branch HO',
            'code' => 'SRC01',
            'phone' => '03311111111',
            'address' => '10 Main Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->branchDest = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Destination Branch Gaya',
            'code' => 'DST01',
            'phone' => '03322222222',
            'address' => '20 Market Street',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchSource->id,
            'name' => 'Super Admin',
            'email' => 'superadmin@grihalaxmi.test',
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->bmSource = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchSource->id,
            'name' => 'BM Source',
            'email' => 'bmsource@grihalaxmi.test',
        ]);
        $this->bmSource->assignRole($bmRole);

        $this->bmDest = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchDest->id,
            'name' => 'BM Destination',
            'email' => 'bmdest@grihalaxmi.test',
        ]);
        $this->bmDest->assignRole($bmRole);

        $dept = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branchDest->id, 'name' => 'Branch Ops', 'code' => 'OPS', 'is_active' => true]);
        $desg = Designation::create(['company_id' => $this->company->id, 'department_id' => $dept->id, 'title' => 'Branch Manager', 'code' => 'BM', 'is_active' => true]);

        $this->empDest = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchDest->id,
            'department_id' => $dept->id,
            'designation_id' => $desg->id,
            'user_id' => $this->bmDest->id,
            'employee_code' => 'EMP-BM-01',
            'first_name' => 'BM',
            'last_name' => 'Destination',
            'joining_date' => '2026-01-01',
            'basic_salary' => 35000.00,
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-SLR-100',
            'name' => 'Solar Home System',
            'unit_price' => 12000.00,
            'is_active' => true,
        ]);

        // Restock 50 units at source branch
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchSource->id,
            'product_id' => $this->product->id,
            'current_stock' => 50,
            'reserved_stock' => 0,
            'reorder_level' => 5,
        ]);
    }

    /** @test */
    public function stock_transfer_workflow_pending_stock_unchanged_and_bm_approval_increments_destination_stock_once()
    {
        // 1. Admin creates stock transfer to destination branch for 10 units
        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-TEST-0001',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->branchSource->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->branchDest->id,
            'status' => 'draft',
            'total_items' => 1,
            'total_quantity' => 10,
            'total_value' => 120000.00,
        ]);

        $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 12000.00,
            'total_value' => 120000.00,
        ]);

        // 2. Destination stock is 0 while Pending
        $destStock = InventoryStock::where('branch_id', $this->branchDest->id)->where('product_id', $this->product->id)->first();
        $this->assertNull($destStock);

        // 3. Unauthorized source BM attempts to approve -> Rejected (403)
        $responseUnauthorized = $this->actingAs($this->bmSource)->post(route('admin.inventory-transfer.approve', $transfer->id));
        $responseUnauthorized->assertStatus(403);

        // 4. Authorized Destination BM approves transfer
        $responseApprove = $this->actingAs($this->bmDest)->post(route('admin.inventory-transfer.approve', $transfer->id));
        $responseApprove->assertRedirect();

        // 5. Verify status = approved and destination stock increased by 10 (0 -> 10)
        $this->assertEquals('approved', $transfer->fresh()->status);
        $destStockAfter = InventoryStock::where('branch_id', $this->branchDest->id)->where('product_id', $this->product->id)->first();
        $this->assertNotNull($destStockAfter);
        $this->assertEquals(10, $destStockAfter->current_stock);

        // Source stock decreased by 10 (50 -> 40)
        $sourceStockAfter = InventoryStock::where('branch_id', $this->branchSource->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(40, $sourceStockAfter->current_stock);

        // 6. Second approval attempt fails cleanly and does NOT increase stock again
        $responseDuplicate = $this->actingAs($this->bmDest)->post(route('admin.inventory-transfer.approve', $transfer->id));
        $responseDuplicate->assertRedirect();
        $responseDuplicate->assertSessionHas('error');

        $this->assertEquals(10, $destStockAfter->fresh()->current_stock);
    }

    /** @test */
    public function hrm_payroll_list_and_payroll_view_open_without_500_error()
    {
        // Create payroll record
        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchDest->id,
            'month' => 9,
            'year' => 2026,
            'total_employees' => 1,
            'total_gross' => 45000.00,
            'total_deductions' => 4500.00,
            'total_net_payout' => 40500.00,
            'status' => 'draft',
            'processed_by' => $this->superAdmin->id,
        ]);

        $slip = SalarySlip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->empDest->id,
            'basic_salary' => 35000.00,
            'hra' => 7000.00,
            'conveyance_allowance' => 1600.00,
            'special_allowance' => 1400.00,
            'pf_deduction' => 4200.00,
            'tax_deduction' => 300.00,
            'other_deduction' => 0.00,
            'gross_salary' => 45000.00,
            'total_deductions' => 4500.00,
            'net_salary' => 40500.00,
            'payment_status' => 'unpaid',
        ]);

        // 1. Payroll List view opens cleanly
        $responseIndex = $this->actingAs($this->superAdmin)->get(route('admin.hrm.payroll.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Payroll Batch');

        // 2. Payroll Show view opens cleanly without 500 error
        $responseShow = $this->actingAs($this->superAdmin)->get(route('admin.hrm.payroll.show', $payroll->id));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Payroll Batch: September 2026');
        $responseShow->assertSee($this->empDest->full_name);

        // 3. Salary Slip View opens cleanly
        $responseSlip = $this->actingAs($this->superAdmin)->get(route('admin.hrm.payroll.slip', $slip->uuid));
        $responseSlip->assertStatus(200);
        $responseSlip->assertSee('SALARY PAY SLIP');
        $responseSlip->assertSee($this->empDest->full_name);
    }

    /** @test */
    public function branch_manager_api_payslips_restricted_to_own_employee_record()
    {
        $payroll = Payroll::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchDest->id,
            'month' => 9,
            'year' => 2026,
            'total_employees' => 1,
            'total_gross' => 45000.00,
            'total_deductions' => 4500.00,
            'total_net_payout' => 40500.00,
            'status' => 'draft',
        ]);

        $slip = SalarySlip::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->empDest->id,
            'basic_salary' => 35000.00,
            'hra' => 7000.00,
            'conveyance_allowance' => 1600.00,
            'special_allowance' => 1400.00,
            'pf_deduction' => 4200.00,
            'tax_deduction' => 300.00,
            'other_deduction' => 0.00,
            'gross_salary' => 45000.00,
            'total_deductions' => 4500.00,
            'net_salary' => 40500.00,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->bmDest, 'sanctum')->getJson('/api/v1/hrm/payslips');
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertEquals($this->empDest->id, $data[0]['employee_id']);
    }

    /** @test */
    public function ta_settings_update_and_claim_lifecycle_with_cash_book_payment()
    {
        // 1. Admin configures TA rates in settings
        $settingsResp = $this->actingAs($this->superAdmin)->put(route('admin.system.settings.update-ta-settings'), [
            'ta_rate_per_km' => 5.00,
            'ta_rate_per_km_bike' => 5.00,
            'ta_rate_per_km_car' => 10.00,
            'ta_max_daily_limit' => 1000.00,
            'ta_min_distance_km' => 1.00,
        ]);
        $settingsResp->assertRedirect();
        $this->assertDatabaseHas('website_settings', ['ta_rate_per_km_bike' => 5.00]);

        // 2. Submit TA Claim for BM Destination (20 km * 5.00/km = 100.00)
        $taService = app(TravelAllowanceService::class);
        $claim = $taService->submitClaim($this->bmDest, [
            'travel_date' => now()->toDateString(),
            'from_location' => 'Branch Office',
            'to_location' => 'Customer Center 1',
            'transport_mode' => 'bike',
            'distance_km' => 20.0,
            'purpose' => 'Field Inspection',
        ]);

        $this->assertEquals(100.00, $claim->amount);
        $this->assertEquals('pending', $claim->status);

        // 3. Approve Claim
        $approvedClaim = $taService->approveClaim($claim, $this->superAdmin);
        $this->assertEquals('approved', $approvedClaim->status);

        // 4. Mark Claim Paid -> Creates Cash Book payment entry
        $paidClaim = $taService->payClaim($approvedClaim, $this->superAdmin, 'UTR-TA-99881', 'cash');
        $this->assertEquals('paid', $paidClaim->status);
        $this->assertNotNull($paidClaim->cash_book_entry_id);

        $this->assertDatabaseHas('cash_book_entries', [
            'id' => $paidClaim->cash_book_entry_id,
            'entry_type' => 'payment',
            'cash_amount' => 100.00,
        ]);
    }
}
