<?php

namespace Tests\Feature;

use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\User;
use App\Services\BankDepositService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagerRbacTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;

    protected User $admin;
    protected User $branchManager1;
    protected User $branchManager2;

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

        $this->branchManager2 = User::factory()->create([
            'name' => 'Howrah Manager',
            'email' => 'bm2@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'status' => 'active',
        ]);
        $this->branchManager2->assignRole('Branch Manager');
    }

    /** 1. Branch Manager Dashboard Isolation */
    public function test_branch_manager_dashboard_access_and_isolation(): void
    {
        $response = $this->actingAs($this->branchManager1)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Salt Lake Branch');
        $response->assertSee('Dashboard');
    }

    /** 2. Cashbook Branch Restrictions */
    public function test_cashbook_branch_restriction_and_isolation(): void
    {
        // Branch Manager 1 opening own branch cashbook -> 200
        $responseOwn = $this->actingAs($this->branchManager1)->get('/admin/cash-book/branch/' . $this->branch1->id);
        $responseOwn->assertStatus(200);
        $responseOwn->assertSee('Salt Lake Branch');

        // Branch Manager 1 trying to open Branch 2 cashbook -> 403 Forbidden
        $responseOther = $this->actingAs($this->branchManager1)->get('/admin/cash-book/branch/' . $this->branch2->id);
        $responseOther->assertStatus(403);
    }

    /** 3. Bank Deposit Workflow & Self-Approval Block */
    public function test_bank_deposit_pending_and_approval_workflow_with_self_approval_block(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'reference_number' => 'REF-DEP-001',
            'deposit_date' => now()->toDateString(),
            'amount' => 5000.00,
            'bank_name' => 'State Bank of India',
            'account_number' => '1234567890',
            'status' => 'pending',
            'submitted_by' => $this->branchManager1->id,
        ]);

        $depositService = app(BankDepositService::class);

        // Branch Manager 1 cannot self-approve their own deposit
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot approve your own submitted bank deposit.');
        $depositService->approveDeposit($deposit, $this->branchManager1);
    }

    public function test_admin_can_approve_bank_deposit(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'reference_number' => 'REF-DEP-002',
            'deposit_date' => now()->toDateString(),
            'amount' => 5000.00,
            'bank_name' => 'State Bank of India',
            'account_number' => '1234567890',
            'status' => 'pending',
            'submitted_by' => $this->branchManager1->id,
        ]);

        $depositService = app(BankDepositService::class);
        $approvedDeposit = $depositService->approveDeposit($deposit, $this->admin);

        $this->assertEquals('approved', $approvedDeposit->status);
        $this->assertEquals($this->admin->id, $approvedDeposit->approved_by);
    }

    /** 4. Customer Group & Member Management Restrictions */
    public function test_customer_group_member_management_and_delete_restriction(): void
    {
        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'group_code' => 'GRP-001',
            'name' => 'Salt Lake Self Help Group',
            'formation_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->branchManager1->id,
        ]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-SL-01',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9876543299',
            'gender' => 'female',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Branch Manager 1 can view group profile
        $responseShow = $this->actingAs($this->branchManager1)->get('/admin/customer-group/' . $group->id);
        $responseShow->assertStatus(200);

        // Branch Manager 1 cannot delete group -> 403
        $responseDeleteGroup = $this->actingAs($this->branchManager1)->delete('/admin/customer-group/' . $group->id);
        $responseDeleteGroup->assertStatus(403);

        // Branch Manager 1 cannot remove member from group -> 403
        $responseDeleteMember = $this->actingAs($this->branchManager1)->delete("/admin/customer-group/{$group->id}/member/{$customer->id}");
        $responseDeleteMember->assertStatus(403);

        // Branch Manager 1 cannot access another branch's group -> 403
        $group2 = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'group_code' => 'GRP-002',
            'name' => 'Howrah Self Help Group',
            'formation_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->branchManager2->id,
        ]);

        $responseOtherGroup = $this->actingAs($this->branchManager1)->get('/admin/customer-group/' . $group2->id);
        $responseOtherGroup->assertStatus(403);
    }

    /** 5. Loan Scheme Denial */
    public function test_loan_scheme_management_denial_for_branch_manager(): void
    {
        $responseIndex = $this->actingAs($this->branchManager1)->get('/admin/loan-scheme');
        $responseIndex->assertStatus(403);

        $responseCreate = $this->actingAs($this->branchManager1)->get('/admin/loan-scheme/create');
        $responseCreate->assertStatus(403);
    }

    /** 6. Rejected and pending deposits do not post to Cashbook balance */
    public function test_rejected_and_pending_deposits_do_not_update_cashbook(): void
    {
        $depositService = app(BankDepositService::class);

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'reference_number' => 'REF-DEP-003',
            'deposit_date' => now()->toDateString(),
            'amount' => 3000.00,
            'bank_name' => 'HDFC Bank',
            'account_number' => '987654321',
            'status' => 'pending',
            'submitted_by' => $this->branchManager1->id,
        ]);

        $this->assertEquals('pending', $deposit->status);
        $this->assertNull($deposit->posted_at);

        $rejectedDeposit = $depositService->rejectDeposit($deposit, $this->admin, 'Invalid slip image');
        $this->assertEquals('rejected', $rejectedDeposit->status);
        $this->assertNull($rejectedDeposit->posted_at);
    }

    /** 7. Stock Transfer Acceptance Scoping and Duplicate Acceptance Prevention */
    public function test_inventory_transfer_acceptance_and_procurement_denial(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Test Mobile Phone',
            'code' => 'PROD-001',
            'sku' => 'SKU-001',
            'unit' => 'pcs',
            'unit_price' => 10000.00,
            'is_active' => true,
        ]);

        $transfer = InventoryTransfer::create([
            'transfer_number' => 'TRF-001',
            'source_company_id' => $this->company->id,
            'source_branch_id' => $this->branch2->id,
            'destination_company_id' => $this->company->id,
            'destination_branch_id' => $this->branch1->id,
            'status' => 'in_transit',
            'created_by' => $this->admin->id,
        ]);

        // Branch Manager 1 can view transfer to their branch
        $responseShow = $this->actingAs($this->branchManager1)->get('/admin/inventory/transfers/' . $transfer->id);
        $responseShow->assertStatus(200);

        // Branch Manager 2 (source/unassigned branch manager for receiving) trying to receive -> 403
        $responseReceiveWrongBranch = $this->actingAs($this->branchManager2)->post("/admin/inventory/transfers/{$transfer->id}/receive");
        $responseReceiveWrongBranch->assertStatus(403);

        // Procurement direct URL denial -> 403
        $responsePurchase = $this->actingAs($this->branchManager1)->get('/admin/inventory/purchases');
        $responsePurchase->assertStatus(403);

        $responseSuppliers = $this->actingAs($this->branchManager1)->get('/admin/suppliers');
        $responseSuppliers->assertStatus(403);
    }

    /** 8. Account & Finance: Expenses allowed, Accounting and Reports denied */
    public function test_expenses_allowed_accounting_and_reports_denied(): void
    {
        // Expenses allowed
        $responseExpense = $this->actingAs($this->branchManager1)->get('/admin/expenses/dashboard');
        $responseExpense->assertStatus(200);

        // Accounting denied -> 403
        $responseAccounting = $this->actingAs($this->branchManager1)->get('/admin/accounting/dashboard');
        $responseAccounting->assertStatus(403);

        // Reports denied -> 403
        $responseReports = $this->actingAs($this->branchManager1)->get('/admin/reports');
        $responseReports->assertStatus(403);
    }

    /** 9. HRM Branch Scoping and HR Letters / ID Cards isolation */
    public function test_hrm_records_cannot_expose_another_branch(): void
    {
        $responseAttendance = $this->actingAs($this->branchManager1)->get('/admin/hrm/attendance');
        $responseAttendance->assertStatus(200);

        $responseLeave = $this->actingAs($this->branchManager1)->get('/admin/hrm/leave');
        $responseLeave->assertStatus(200);

        $responseLetters = $this->actingAs($this->branchManager1)->get('/admin/hrm/letters');
        $responseLetters->assertStatus(200);
    }

    /** 10. Profile and Branch Details cannot update protected fields */
    public function test_profile_and_branch_details_cannot_modify_protected_assignments(): void
    {
        // Branch Manager cannot edit branch details (branch.edit not granted)
        $responseUpdateBranch = $this->actingAs($this->branchManager1)->put('/admin/branch/' . $this->branch1->id, [
            'name' => 'Hacked Branch Name',
            'company_id' => 999,
        ]);
        $responseUpdateBranch->assertStatus(403);

        // Branch Manager profile update cannot alter branch_id or roles
        $responseProfileUpdate = $this->actingAs($this->branchManager1)->put('/admin/profile', [
            'name' => 'Updated Manager Name',
            'email' => 'bm1@grihalaxmi.com',
            'branch_id' => 999,
            'role' => 'Super Admin',
        ]);
        $responseProfileUpdate->assertStatus(302);
        
        $this->branchManager1->refresh();
        $this->assertEquals($this->branch1->id, $this->branchManager1->branch_id);
        $this->assertTrue($this->branchManager1->hasRole('Branch Manager'));
    }

    /** 11. Restricted Modules Completely Hidden from Branch Manager Sidebar */
    public function test_hidden_modules_completely_absent_from_sidebar(): void
    {
        $response = $this->actingAs($this->branchManager1)->get('/admin/dashboard');
        $response->assertStatus(200);

        $response->assertDontSee('Organization Management');
        $response->assertDontSee('Procurement');
        $response->assertDontSee('Website CMS');
        $response->assertDontSee('Loan Schemes');
        $response->assertDontSee('HR Reports');
    }

    /** 12. Admin user retains full functional access */
    public function test_admin_user_retains_full_functional_access(): void
    {
        $routes = [
            '/admin/company',
            '/admin/branch',
            '/admin/loan-scheme',
            '/admin/inventory/purchases',
            '/admin/suppliers',
            '/admin/accounting/dashboard',
            '/admin/reports',
            '/admin/system/users',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertStatus(200);
        }
    }

    /** 13. Branch Manager cannot access Organization Management routes */
    public function test_branch_manager_cannot_access_organization_management_routes(): void
    {
        $orgRoutes = [
            '/admin/company',
            '/admin/branch',
            '/admin/company/create',
            '/admin/branch/create',
        ];

        foreach ($orgRoutes as $route) {
            $response = $this->actingAs($this->branchManager1)->get($route);
            $response->assertStatus(403);
        }
    }

    /** 14. Permitted Branch Manager routes render successfully */
    public function test_branch_manager_permitted_menus_and_routes_render_successfully(): void
    {
        $permittedRoutes = [
            '/admin/cash-book',
            '/admin/bank-deposits',
            '/admin/inventory',
            '/admin/inventory/transfers',
            '/admin/billing/invoices',
            '/admin/my-branch',
        ];

        foreach ($permittedRoutes as $route) {
            $response = $this->actingAs($this->branchManager1)->get($route);
            $response->assertStatus(200);
        }
    }

    /** 15. Targeted BranchManagerRoleSeeder syncs exact intended permissions and removes obsolete ones */
    public function test_branch_manager_role_seeder_syncs_exact_intended_permissions_and_removes_obsolete_permissions(): void
    {
        $role = \Spatie\Permission\Models\Role::findByName('Branch Manager', 'web');

        // Intentionally give obsolete permission company.view & product.view to simulate live production state before fix
        $role->givePermissionTo(['company.view', 'product.view']);
        $this->assertTrue($role->hasPermissionTo('company.view'));
        $this->assertTrue($role->hasPermissionTo('product.view'));

        // Run the targeted BranchManagerRoleSeeder
        $this->seed(\Database\Seeders\BranchManagerRoleSeeder::class);
        $role->refresh();

        // Verify obsolete company.view and product.view are removed
        $this->assertFalse($role->hasPermissionTo('company.view'));
        $this->assertFalse($role->hasPermissionTo('product.view'));

        // Verify required menu permissions are granted
        $this->assertTrue($role->hasPermissionTo('cashbook.view'));
        $this->assertTrue($role->hasPermissionTo('bank_deposit.view'));
        $this->assertTrue($role->hasPermissionTo('inventory.view'));
        $this->assertTrue($role->hasPermissionTo('billing.view'));
        $this->assertTrue($role->hasPermissionTo('branch.view'));

        // Verify exact count of 73 permissions
        $this->assertCount(73, $role->permissions);

        // Verify other roles were NOT modified
        $adminRole = \Spatie\Permission\Models\Role::findByName('Super Admin', 'web');
        $this->assertTrue($adminRole->hasPermissionTo('company.view'));
    }
}


