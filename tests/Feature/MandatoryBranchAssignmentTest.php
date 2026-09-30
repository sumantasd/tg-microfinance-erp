<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MandatoryBranchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $activeBranch1;
    protected Branch $activeBranch2;
    protected Branch $inactiveBranch;
    protected User $admin;

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

        $this->activeBranch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Branch',
            'code' => 'KOL-01',
            'email' => 'kolkata@grihalaxmi.com',
            'phone' => '9876543211',
            'address' => 'Park Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'is_active' => true,
        ]);

        $this->activeBranch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Siliguri Branch',
            'code' => 'SLG-01',
            'email' => 'siliguri@grihalaxmi.com',
            'phone' => '9876543212',
            'address' => 'Hill Cart Road',
            'city' => 'Siliguri',
            'state' => 'West Bengal',
            'pincode' => '734001',
            'is_active' => true,
        ]);

        $this->inactiveBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Closed Branch',
            'code' => 'CLD-01',
            'email' => 'closed@grihalaxmi.com',
            'phone' => '9876543213',
            'address' => 'Old Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => false,
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
    }

    /** 1. Admin cannot create a Branch Manager without selecting a branch */
    public function test_admin_cannot_create_branch_manager_without_branch(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.system.users.store'), [
            'name' => 'Test Branch Manager',
            'email' => 'bm_nobranch@grihalaxmi.com',
            'password' => 'Password123!',
            'role' => 'Branch Manager',
            'status' => 'active',
            'branch_id' => '',
        ]);

        $response->assertSessionHasErrors(['branch_id']);
        $errors = session('errors')->get('branch_id');
        $this->assertContains('Please select a branch for this Branch Manager.', $errors);
    }

    /** 2. Admin can create a Branch Manager with a valid branch */
    public function test_admin_can_create_branch_manager_with_valid_branch(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.system.users.store'), [
            'name' => 'Valid Branch Manager',
            'email' => 'bm_valid@grihalaxmi.com',
            'password' => 'Password123!',
            'role' => 'Branch Manager',
            'status' => 'active',
            'branch_id' => $this->activeBranch1->id,
        ]);

        $response->assertRedirect(route('admin.system.users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'bm_valid@grihalaxmi.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->activeBranch1->id, $user->branch_id);
        $this->assertEquals($this->company->id, $user->company_id);
        $this->assertTrue($user->hasRole('Branch Manager'));
    }

    /** 3. Invalid or inactive branch assignments are rejected */
    public function test_invalid_or_inactive_branch_assignments_are_rejected(): void
    {
        // Non-existent branch ID
        $responseNonExistent = $this->actingAs($this->admin)->post(route('admin.system.users.store'), [
            'name' => 'Invalid Branch Manager',
            'email' => 'bm_invalid@grihalaxmi.com',
            'password' => 'Password123!',
            'role' => 'Branch Manager',
            'status' => 'active',
            'branch_id' => 99999,
        ]);
        $responseNonExistent->assertSessionHasErrors(['branch_id']);

        // Inactive branch ID
        $responseInactive = $this->actingAs($this->admin)->post(route('admin.system.users.store'), [
            'name' => 'Inactive Branch Manager',
            'email' => 'bm_inactive@grihalaxmi.com',
            'password' => 'Password123!',
            'role' => 'Branch Manager',
            'status' => 'active',
            'branch_id' => $this->inactiveBranch->id,
        ]);
        $responseInactive->assertSessionHasErrors(['branch_id']);
    }

    /** 4. Editing a Branch Manager displays the current assignment */
    public function test_editing_branch_manager_displays_current_assignment(): void
    {
        $bmUser = User::factory()->create([
            'name' => 'Existing Manager',
            'email' => 'existing_bm@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->activeBranch1->id,
            'status' => 'active',
        ]);
        $bmUser->assignRole('Branch Manager');

        $response = $this->actingAs($this->admin)->get(route('admin.system.users.edit', $bmUser->id));

        $response->assertStatus(200);
        $response->assertSee($this->activeBranch1->name);
        $response->assertSee('value="' . $this->activeBranch1->id . '" selected', false);
    }

    /** 5. Authorized reassignment updates the manager's branch scope */
    public function test_authorized_reassignment_updates_manager_branch_scope(): void
    {
        $bmUser = User::factory()->create([
            'name' => 'Reassigned Manager',
            'email' => 'reassigned_bm@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->activeBranch1->id,
            'status' => 'active',
        ]);
        $bmUser->assignRole('Branch Manager');

        $response = $this->actingAs($this->admin)->put(route('admin.system.users.update', $bmUser->id), [
            'name' => 'Reassigned Manager Updated',
            'email' => 'reassigned_bm@grihalaxmi.com',
            'role' => 'Branch Manager',
            'status' => 'active',
            'branch_id' => $this->activeBranch2->id,
        ]);

        $response->assertRedirect(route('admin.system.users.index'));
        $bmUser->refresh();

        $this->assertEquals($this->activeBranch2->id, $bmUser->branch_id);

        // Verify newly assigned branch scope in cashbook access
        $responseNewBranch = $this->actingAs($bmUser)->get('/admin/cash-book/branch/' . $this->activeBranch2->id);
        $responseNewBranch->assertStatus(200);

        // Verify previous branch access is now denied -> 403
        $responseOldBranch = $this->actingAs($bmUser)->get('/admin/cash-book/branch/' . $this->activeBranch1->id);
        $responseOldBranch->assertStatus(403);
    }

    /** 6. A Branch Manager without an assignment cannot access branch-specific operations */
    public function test_branch_manager_without_assignment_cannot_access_branch_operations(): void
    {
        $unassignedBm = User::factory()->create([
            'name' => 'Unassigned Manager',
            'email' => 'unassigned_bm@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => null,
            'status' => 'active',
        ]);
        $unassignedBm->assignRole('Branch Manager');

        // Cashbook access denied
        $responseCashbook = $this->actingAs($unassignedBm)->get('/admin/cash-book/branch/' . $this->activeBranch1->id);
        $responseCashbook->assertStatus(403);

        // My Branch Details redirects or notifies
        $responseMyBranch = $this->actingAs($unassignedBm)->get('/admin/my-branch');
        $responseMyBranch->assertRedirect(route('admin.dashboard'));

        // Dashboard displays mandatory assignment warning
        $responseDashboard = $this->actingAs($unassignedBm)->get('/admin/dashboard');
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Mandatory Branch Assignment Required');
    }

    /** 7. A Branch Manager cannot access another branch's data by manipulating IDs or URLs */
    public function test_branch_manager_cannot_access_another_branch_data_via_id_manipulation(): void
    {
        $bmUser = User::factory()->create([
            'name' => 'Kolkata Manager',
            'email' => 'kolkata_bm@grihalaxmi.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->activeBranch1->id,
            'status' => 'active',
        ]);
        $bmUser->assignRole('Branch Manager');

        $responseOtherCashbook = $this->actingAs($bmUser)->get('/admin/cash-book/branch/' . $this->activeBranch2->id);
        $responseOtherCashbook->assertStatus(403);
    }

    /** 8. Existing non-Branch-Manager user creation workflows continue working */
    public function test_existing_non_branch_manager_user_creation_continues_working(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.system.users.store'), [
            'name' => 'HQ Accountant',
            'email' => 'hq_accountant@grihalaxmi.com',
            'password' => 'Password123!',
            'role' => 'Accountant',
            'status' => 'active',
            'branch_id' => '',
        ]);

        $response->assertRedirect(route('admin.system.users.index'));
        $user = User::where('email', 'hq_accountant@grihalaxmi.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Accountant'));
    }
}
