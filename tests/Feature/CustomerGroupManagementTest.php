<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupMember;
use App\Models\CustomerKycDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        
        $permissions = [
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete',
            'customer.restore', 'customer.verify_kyc', 'customer.manage_guarantor',
            'customer.manage_nominee', 'customer.change_status',
            'group.view', 'group.create', 'group.edit', 'group.delete',
            'group.change_status', 'group.manage_members'
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

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'BR001',
            'phone' => '8888888888',
            'address' => 'Main Road, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->adminUser->assignRole('Super Admin');
    }

    public function test_can_create_customer_group_with_auto_code(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('admin.customer-group.store'), [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Rampur JLG 1',
            'meeting_day' => 'Monday',
            'meeting_time' => '10:00 AM',
            'meeting_location' => 'Rampur Center',
            'formation_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_groups', [
            'name' => 'Rampur JLG 1',
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $group = CustomerGroup::where('name', 'Rampur JLG 1')->first();
        $this->assertNotNull($group);
        $this->assertStringContainsString('GRP-BR001-', $group->group_code);
    }

    public function test_can_view_group_listing_and_profile(): void
    {
        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-BR001-2026-0001',
            'name' => 'Kankarbagh SHG',
            'formation_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $listResponse = $this->actingAs($this->adminUser)->get(route('admin.customer-group.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Kankarbagh SHG');

        $profileResponse = $this->actingAs($this->adminUser)->get(route('admin.customer-group.show', $group->id));
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Kankarbagh SHG');
    }

    public function test_can_add_existing_customer_to_group(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00101',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9800000001',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-BR001-2026-0002',
            'name' => 'Phulwari JLG',
            'formation_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer-group.member.store', $group->id), [
            'customer_id' => $customer->id,
            'role' => 'member',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_group_members', [
            'group_id' => $group->id,
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);
    }

    public function test_prevents_duplicate_membership_in_same_group(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00102',
            'first_name' => 'Meena',
            'last_name' => 'Kumari',
            'mobile_number' => '9800000002',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-BR001-2026-0003',
            'name' => 'Danapur JLG',
            'formation_date' => '2026-08-14',
            'status' => 'active',
        ]);

        CustomerGroupMember::create([
            'group_id' => $group->id,
            'customer_id' => $customer->id,
            'role' => 'member',
            'joined_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer-group.member.store', $group->id), [
            'customer_id' => $customer->id,
            'role' => 'member',
        ]);

        $response->assertSessionHasErrors('customer_id');
    }

    public function test_can_assign_group_leader_and_remove_member(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00103',
            'first_name' => 'Rekha',
            'last_name' => 'Singh',
            'mobile_number' => '9800000003',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-BR001-2026-0004',
            'name' => 'Boring Road JLG',
            'formation_date' => '2026-08-14',
            'status' => 'active',
        ]);

        CustomerGroupMember::create([
            'group_id' => $group->id,
            'customer_id' => $customer->id,
            'role' => 'member',
            'joined_at' => now(),
            'status' => 'active',
        ]);

        // Assign Leader
        $leaderResponse = $this->actingAs($this->adminUser)->post(route('admin.customer-group.assign-leader', $group->id), [
            'leader_customer_id' => $customer->id,
        ]);
        $leaderResponse->assertRedirect();
        $this->assertDatabaseHas('customer_groups', [
            'id' => $group->id,
            'leader_customer_id' => $customer->id,
        ]);

        // Remove Member
        $removeResponse = $this->actingAs($this->adminUser)->delete(route('admin.customer-group.member.destroy', [$group->id, $customer->id]));
        $removeResponse->assertRedirect();
        $this->assertSoftDeleted('customer_group_members', [
            'group_id' => $group->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_multiple_kyc_documents_displayed_and_managed(): void
    {
        Storage::fake('private');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00104',
            'first_name' => 'Aarti',
            'last_name' => 'Sharma',
            'mobile_number' => '9800000004',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        // Upload Aadhaar
        $doc1 = UploadedFile::fake()->create('aadhaar.pdf', 300, 'application/pdf');
        $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.store', $customer->id), [
            'kyc_document_type' => 'aadhaar',
            'document_number' => '1111-2222-3333',
            'file' => $doc1,
        ]);

        // Upload PAN
        $doc2 = UploadedFile::fake()->create('pan.jpg', 200, 'image/jpeg');
        $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.store', $customer->id), [
            'kyc_document_type' => 'pan',
            'document_number' => 'ABCDE5678G',
            'file' => $doc2,
        ]);

        $this->assertEquals(2, $customer->kycDocuments()->count());

        $showResponse = $this->actingAs($this->adminUser)->get(route('admin.customer.show', $customer->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('aadhaar');
        $showResponse->assertSee('pan');
    }
}
