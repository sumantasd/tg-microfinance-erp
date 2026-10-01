<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\MediaFile;
use App\Models\SystemNotification;
use App\Models\TravelAllowanceClaim;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminEnterpriseModulesTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected User $superAdmin;
    protected User $branchManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Test Company HO',
            'code' => 'TCOMP',
            'email' => 'tcomp@test.com',
            'phone' => '9876543210',
            'address' => '123 Main St',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MB-001',
            'email' => 'mb@test.com',
            'phone' => '9876543211',
            'address' => '456 Branch Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@grihalaxmifinance.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->branchManager = User::factory()->create([
            'name' => 'Branch Manager User',
            'email' => 'bm@test.com',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');
    }

    /** 1. Media Library Upload and Download */
    public function test_media_library_file_upload_and_download(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->superAdmin)->post('/admin/media/upload', [
            'file' => $file,
            'title' => 'Test KYC PDF',
            'collection' => 'kyc',
        ]);

        $response->assertRedirect('/admin/media');
        $this->assertDatabaseHas('media_files', [
            'title' => 'Test KYC PDF',
            'collection' => 'kyc',
            'file_type' => 'pdf',
        ]);

        $mediaFile = MediaFile::first();
        $downloadResponse = $this->actingAs($this->superAdmin)->get("/admin/media/{$mediaFile->id}/download");
        $downloadResponse->assertStatus(200);
    }

    /** 2. System Notifications Broadcast and Mark as Read */
    public function test_system_notifications_broadcast_and_read_status(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/notifications/send', [
            'title' => 'Important Circular',
            'message' => 'Please complete day closing on time.',
            'type' => 'urgent',
            'target_branch_id' => $this->branch->id,
        ]);

        $response->assertRedirect('/admin/notifications/manage');
        $this->assertDatabaseHas('system_notifications', [
            'title' => 'Important Circular',
            'type' => 'urgent',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->branchManager->id,
            'is_read' => false,
        ]);

        $userNotif = UserNotification::where('user_id', $this->branchManager->id)->first();
        $markReadResponse = $this->actingAs($this->branchManager)->post("/admin/notifications/{$userNotif->id}/read");
        $markReadResponse->assertRedirect();
        $this->assertTrue($userNotif->fresh()->is_read);
    }

    /** 3. System Audit Logs Viewer */
    public function test_system_audit_logs_viewer(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/activity-logs');
        $response->assertStatus(200);
        $response->assertSee('System Audit & Activity Logs', false);
    }

    /** 4. Field Staff Tracking Overview */
    public function test_field_staff_tracking_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/field-tracking');
        $response->assertStatus(200);
        $response->assertSee('Field Staff Tracking');
    }

    /** 5. Travel Allowance (TA) Claim Lifecycle */
    public function test_travel_allowance_claim_submission_and_approval(): void
    {
        $response = $this->actingAs($this->branchManager)->post('/admin/ta-claims', [
            'travel_date' => date('Y-m-d'),
            'from_location' => 'Patna Branch',
            'to_location' => 'Gaya Village',
            'transport_mode' => 'bike',
            'distance_km' => 50,
            'rate_per_km' => 4.0,
            'amount' => 200.0,
            'purpose' => 'Field verification and loan collection',
        ]);

        $response->assertRedirect('/admin/ta-claims');
        $this->assertDatabaseHas('travel_allowance_claims', [
            'from_location' => 'Patna Branch',
            'status' => 'pending',
            'amount' => 200.0,
        ]);

        $claim = TravelAllowanceClaim::first();
        $approveResponse = $this->actingAs($this->superAdmin)->post("/admin/ta-claims/{$claim->id}/approve");
        $approveResponse->assertRedirect();
        $this->assertEquals('approved', $claim->fresh()->status);

        $payResponse = $this->actingAs($this->superAdmin)->post("/admin/ta-claims/{$claim->id}/pay", [
            'payment_reference' => 'CASH-REF-99',
        ]);
        $payResponse->assertRedirect();
        $this->assertEquals('paid', $claim->fresh()->status);
    }
}
