<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerKycDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KycDocumentPreviewTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $adminUser;
    protected User $loanOfficer;
    protected User $unauthorizedUser;
    protected Customer $customer;
    protected CustomerKycDocument $kycDoc;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('local');

        $permissions = ['customer.view', 'customer.create', 'customer.edit', 'customer.verify_kyc', 'customer.kyc_upload', 'customer.kyc_view'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $loRole = Role::firstOrCreate(['name' => 'Loan Officer', 'guard_name' => 'web']);
        $loRole->givePermissionTo($permissions);

        $this->company = Company::create([
            'name' => 'Preview Test Finance',
            'code' => 'PTF',
            'email' => 'preview@finance.com',
            'phone' => '9988776655',
            'address' => 'Preview Street',
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Alpha',
            'code' => 'ALPHA01',
            'phone' => '03311112222',
            'address' => 'Alpha Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'opening_date' => '2026-01-01',
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Branch Beta',
            'code' => 'BETA02',
            'phone' => '03311113333',
            'address' => 'Beta Street',
            'city' => 'Howrah',
            'state' => 'West Bengal',
            'pincode' => '711101',
            'opening_date' => '2026-01-01',
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Admin User',
            'email' => 'admin.preview@example.com',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->loanOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Loan Officer',
            'email' => 'lo.preview@example.com',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $this->loanOfficer->assignRole($loRole);

        $this->unauthorizedUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'name' => 'Branch 2 Officer',
            'email' => 'unauth.preview@example.com',
            'password' => Hash::make('Secret123'),
            'status' => 'active',
        ]);
        $this->unauthorizedUser->assignRole($loRole);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-PREV-001',
            'first_name' => 'Aarav',
            'last_name' => 'Mukherjee',
            'name' => 'Aarav Mukherjee',
            'mobile_number' => '9800098000',
            'gender' => 'male',
            'registration_date' => '2026-01-01',
        ]);

        $file = UploadedFile::fake()->image('aadhaar_card.jpg', 800, 600);
        $storedPath = $file->store('kyc/documents', 'private');

        $this->kycDoc = CustomerKycDocument::create([
            'customer_id' => $this->customer->id,
            'kyc_document_type' => 'aadhaar_front',
            'document_number' => '9999-8888-7777',
            'file_path' => $storedPath,
            'file_name' => 'aadhaar_card.jpg',
            'file_size_kb' => 150,
            'verification_status' => 'verified',
            'created_by' => $this->loanOfficer->id,
        ]);
    }

    public function test_authenticated_api_user_can_preview_kyc_image_inline(): void
    {
        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Preview route 1: /api/v1/kyc/{id}/preview
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/kyc/' . $this->kycDoc->id . '/preview');

        $response1->assertStatus(200);
        $this->assertStringContainsString('image/jpeg', $response1->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response1->headers->get('Content-Disposition'));

        // Preview route 2: /api/v1/kyc/documents/{id}/preview
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/kyc/documents/' . $this->kycDoc->id . '/preview');

        $response2->assertStatus(200);
        $this->assertStringContainsString('image/jpeg', $response2->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response2->headers->get('Content-Disposition'));
    }

    public function test_authenticated_api_user_can_download_kyc_file_as_attachment(): void
    {
        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        // Download endpoint: /api/v1/kyc/documents/{id}
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/kyc/documents/' . $this->kycDoc->id);

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_user_can_preview_and_download_kyc_document(): void
    {
        // Admin preview route
        $previewResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.customer.kyc.preview', $this->kycDoc->id));

        $previewResponse->assertStatus(200);
        $this->assertStringContainsString('inline', $previewResponse->headers->get('Content-Disposition'));

        // Admin download route
        $downloadResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.customer.kyc.download', $this->kycDoc->id));

        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_unauthorized_user_cannot_preview_kyc_document(): void
    {
        $token = $this->unauthorizedUser->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/kyc/' . $this->kycDoc->id . '/preview');

        $response->assertStatus(403);
    }

    public function test_missing_kyc_document_returns_404(): void
    {
        $token = $this->loanOfficer->createToken('TestDevice')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/kyc/999999/preview');

        $response->assertStatus(404);
    }
}
