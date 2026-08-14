<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGuarantor;
use App\Models\CustomerKycDocument;
use App\Models\CustomerNominee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin role and permissions
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        
        $permissions = [
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete',
            'customer.restore', 'customer.verify_kyc', 'customer.manage_guarantor',
            'customer.manage_nominee', 'customer.change_status'
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

    public function test_authenticated_user_can_view_customer_list(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.customer.index'));
        $response->assertStatus(200);
        $response->assertSee('Customer & Member Management');
    }

    public function test_can_create_customer_with_addresses_and_auto_generated_code(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.store'), [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_type' => 'individual',
            'first_name' => 'Sunil',
            'last_name' => 'Kumar',
            'mobile_number' => '9876543210',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'addresses' => [
                'present' => [
                    'address_line' => '123 Main Road',
                    'district' => 'Patna',
                    'state' => 'Bihar',
                    'pin_code' => '800001',
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'first_name' => 'Sunil',
            'last_name' => 'Kumar',
            'mobile_number' => '9876543210',
        ]);

        $customer = Customer::where('mobile_number', '9876543210')->first();
        $this->assertNotNull($customer);
        $this->assertStringContainsString('CUST-BR001-', $customer->customer_code);
        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'district' => 'Patna',
            'pin_code' => '800001',
        ]);
    }

    public function test_can_view_customer_profile_page(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00001',
            'first_name' => 'Anita',
            'last_name' => 'Devi',
            'mobile_number' => '9876543211',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.customer.show', $customer->id));
        $response->assertStatus(200);
        $response->assertSee('Anita Devi');
        $response->assertSee('CUST-BR001-2026-00001');
    }

    public function test_can_update_customer_details(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00002',
            'first_name' => 'Vikram',
            'last_name' => 'Singh',
            'mobile_number' => '9876543212',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('admin.customer.update', $customer->id), [
            'customer_code' => $customer->customer_code,
            'customer_type' => 'individual',
            'status' => 'active',
            'first_name' => 'Vikram',
            'last_name' => 'Choudhary',
            'mobile_number' => '9876543212',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'addresses' => [
                'present' => [
                    'address_line' => '456 Station Road',
                    'district' => 'Gaya',
                    'state' => 'Bihar',
                    'pin_code' => '823001',
                ]
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'last_name' => 'Choudhary',
        ]);
    }

    public function test_can_upload_and_verify_kyc_document(): void
    {
        Storage::fake('private');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00003',
            'first_name' => 'Pooja',
            'last_name' => 'Kumari',
            'mobile_number' => '9876543213',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('aadhaar.pdf', 500, 'application/pdf');

        $uploadResponse = $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.store', $customer->id), [
            'kyc_document_type' => 'aadhaar',
            'document_number' => '1234-5678-9012',
            'file' => $file,
        ]);

        $uploadResponse->assertRedirect();
        $this->assertDatabaseHas('customer_kyc_documents', [
            'customer_id' => $customer->id,
            'kyc_document_type' => 'aadhaar',
            'verification_status' => 'pending',
        ]);

        $kyc = CustomerKycDocument::where('customer_id', $customer->id)->first();

        // Verify Document
        $verifyResponse = $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.verify', $kyc->id), [
            'verification_status' => 'verified',
            'remarks' => 'Verified with original Aadhaar card.',
        ]);

        $verifyResponse->assertRedirect();
        $this->assertDatabaseHas('customer_kyc_documents', [
            'id' => $kyc->id,
            'verification_status' => 'verified',
            'verified_by' => $this->adminUser->id,
        ]);
    }

    public function test_can_add_and_remove_guarantor(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00004',
            'first_name' => 'Rajesh',
            'last_name' => 'Verma',
            'mobile_number' => '9876543214',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.guarantor.store', $customer->id), [
            'full_name' => 'Amit Verma',
            'relationship' => 'Brother',
            'mobile' => '9123456789',
            'address' => 'Patna City, Bihar',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_guarantors', [
            'customer_id' => $customer->id,
            'full_name' => 'Amit Verma',
            'relationship' => 'Brother',
        ]);

        $guarantor = CustomerGuarantor::where('customer_id', $customer->id)->first();

        // Delete Guarantor
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('admin.customer.guarantor.destroy', $guarantor->id));
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('customer_guarantors', ['id' => $guarantor->id]);
    }

    public function test_can_edit_existing_guarantor(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00006',
            'first_name' => 'Kiran',
            'last_name' => 'Prakash',
            'mobile_number' => '9876543216',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $guarantor = CustomerGuarantor::create([
            'customer_id' => $customer->id,
            'full_name' => 'Original Guarantor',
            'relationship' => 'Friend',
            'mobile' => '9000000001',
            'address' => 'Old Address, Patna',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.guarantor.store', $customer->id), [
            'id' => $guarantor->id,
            'full_name' => 'Updated Guarantor Name',
            'relationship' => 'Uncle',
            'mobile' => '9000000002',
            'address' => 'New Address, Gaya',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_guarantors', [
            'id' => $guarantor->id,
            'full_name' => 'Updated Guarantor Name',
            'relationship' => 'Uncle',
            'mobile' => '9000000002',
        ]);
    }

    public function test_can_edit_existing_nominee(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00007',
            'first_name' => 'Deepak',
            'last_name' => 'Mishra',
            'mobile_number' => '9876543217',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $nominee = CustomerNominee::create([
            'customer_id' => $customer->id,
            'nominee_name' => 'Original Nominee',
            'relationship' => 'Son',
            'share_percentage' => 50.00,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.nominee.store', $customer->id), [
            'id' => $nominee->id,
            'nominee_name' => 'Updated Nominee Name',
            'relationship' => 'Son',
            'share_percentage' => 100.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_nominees', [
            'id' => $nominee->id,
            'nominee_name' => 'Updated Nominee Name',
            'share_percentage' => 100.00,
        ]);
    }

    public function test_can_reject_kyc_document_with_reason(): void
    {
        Storage::fake('private');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00008',
            'first_name' => 'Manish',
            'last_name' => 'Kashyap',
            'mobile_number' => '9876543218',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $kyc = CustomerKycDocument::create([
            'customer_id' => $customer->id,
            'kyc_document_type' => 'pan',
            'document_number' => 'ABCDE1234F',
            'file_path' => 'kyc/documents/pan.pdf',
            'file_name' => 'pan.pdf',
            'verification_status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.verify', $kyc->id), [
            'verification_status' => 'rejected',
            'rejection_reason' => 'Blurry copy, identity text not legible.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customer_kyc_documents', [
            'id' => $kyc->id,
            'verification_status' => 'rejected',
            'rejection_reason' => 'Blurry copy, identity text not legible.',
        ]);
    }

    public function test_customer_creation_resolves_company_id_from_branch_id_when_omitted_in_payload(): void
    {
        // Testing exact user scenario: branch_id provided in POST payload without company_id
        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.store'), [
            'branch_id' => $this->branch->id,
            'customer_type' => 'group_member',
            'first_name' => 'Ramu',
            'last_name' => 'Das',
            'mobile_number' => '9888877777',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'addresses' => [
                'present' => [
                    'address_line' => 'Rampur Main Road',
                    'district' => 'Patna',
                    'state' => 'Bihar',
                    'pin_code' => '800001',
                ]
            ]
        ]);

        $response->assertRedirect();
        
        $customer = Customer::where('mobile_number', '9888877777')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->company->id, $customer->company_id);
        $this->assertEquals($this->branch->id, $customer->branch_id);
        $this->assertStringContainsString('CUST-BR001-', $customer->customer_code);
    }

    public function test_prevents_cross_company_branch_assignment_for_scoped_users(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Finance Ltd',
            'code' => 'COMP02',
            'registration_number' => 'REG-1002',
            'email' => 'other@finance.com',
            'phone' => '8888888888',
            'address' => 'Gaya, Bihar',
            'is_active' => true,
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Gaya Branch',
            'code' => 'BR002',
            'phone' => '7777777777',
            'address' => 'Station Road, Gaya',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $companyAdmin = User::create([
            'name' => 'Company Admin',
            'email' => 'cadmin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $cRole = Role::firstOrCreate(['name' => 'Company Admin', 'guard_name' => 'web']);
        $companyAdmin->assignRole('Company Admin');

        // Company Admin trying to create customer in a branch of Other Company
        $response = $this->actingAs($companyAdmin)->post(route('admin.customer.store'), [
            'branch_id' => $otherBranch->id,
            'customer_type' => 'individual',
            'first_name' => 'Invalid',
            'last_name' => 'BranchUser',
            'mobile_number' => '9991112223',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'addresses' => [
                'present' => [
                    'address_line' => 'Test',
                    'district' => 'Patna',
                    'state' => 'Bihar',
                    'pin_code' => '800001',
                ]
            ]
        ]);

        $response->assertSessionHasErrors('branch_id');
    }

    public function test_unauthorized_user_cannot_access_or_download_kyc_document(): void
    {
        Storage::fake('private');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00999',
            'first_name' => 'Secure',
            'last_name' => 'User',
            'mobile_number' => '9990001112',
            'gender' => 'male',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('secret_id.pdf', 300, 'application/pdf');
        $this->actingAs($this->adminUser)->post(route('admin.customer.kyc.store', $customer->id), [
            'kyc_document_type' => 'voter_id',
            'document_number' => 'VOTER12345',
            'file' => $file,
        ]);

        $kyc = CustomerKycDocument::where('customer_id', $customer->id)->first();

        // Unauthenticated access attempt
        \Illuminate\Support\Facades\Auth::logout();
        $guestResponse = $this->get(route('admin.customer.kyc.download', $kyc->id));
        $guestResponse->assertRedirect(route('login'));
    }

    public function test_guarantor_kyc_upload_and_download_stream(): void
    {
        Storage::fake('private');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-BR001-2026-00998',
            'first_name' => 'Guarantor',
            'last_name' => 'Owner',
            'mobile_number' => '9990001113',
            'gender' => 'female',
            'registration_date' => '2026-08-14',
            'status' => 'active',
        ]);

        $kycFile = UploadedFile::fake()->create('guarantor_pan.pdf', 400, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('admin.customer.guarantor.store', $customer->id), [
            'full_name' => 'Guarantor Full Name',
            'relationship' => 'Uncle',
            'mobile' => '9888877771',
            'address' => 'Patna Address',
            'kyc_type' => 'pan',
            'kyc_number' => 'GUA123456P',
            'kyc_file' => $kycFile,
        ]);

        $response->assertRedirect();
        $guarantor = CustomerGuarantor::where('customer_id', $customer->id)->first();
        $this->assertNotNull($guarantor);
        $this->assertNotNull($guarantor->kyc_document_path);

        Storage::disk('private')->assertExists($guarantor->kyc_document_path);

        $downloadResponse = $this->actingAs($this->adminUser)->get(route('admin.customer.guarantor.download-kyc', $guarantor->id));
        $downloadResponse->assertStatus(200);
    }
}
