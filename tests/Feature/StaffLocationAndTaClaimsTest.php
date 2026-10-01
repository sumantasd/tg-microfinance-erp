<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\FieldLocationLog;
use App\Models\TravelAllowanceClaim;
use App\Models\User;
use App\Services\LocationTrackingService;
use App\Services\TravelAllowanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffLocationAndTaClaimsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $adminUser;
    protected User $staffUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Ltd',
            'code' => 'GFL',
            'email' => 'info@grihalaxmifinance.test',
            'phone' => '9876543210',
            'address' => 'Kolkata, West Bengal',
            'is_active' => true,
        ]);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Main Branch',
            'code' => 'KOL01',
            'phone' => '03312345678',
            'address' => '12 Park Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700016',
            'is_active' => true,
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $fieldStaffRole = Role::firstOrCreate(['name' => 'Field Officer', 'guard_name' => 'web']);

        $permissions = [
            'field_tracking.view',
            'ta_claims.view',
            'ta_claims.create',
            'ta_claims.approve',
            'ta_claims.pay',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $superAdminRole->givePermissionTo($permissions);
        $fieldStaffRole->givePermissionTo(['field_tracking.view', 'ta_claims.view', 'ta_claims.create']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.test',
        ]);
        $this->adminUser->assignRole($superAdminRole);

        $this->staffUser = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Field Staff User',
            'email' => 'staff@grihalaxmi.test',
        ]);
        $this->staffUser->assignRole($fieldStaffRole);

        $department = Department::create(['company_id' => $this->company->id, 'branch_id' => $this->branch->id, 'name' => 'Field Operations', 'code' => 'OPS', 'is_active' => true]);
        $designation = Designation::create(['company_id' => $this->company->id, 'department_id' => $department->id, 'title' => 'Field Officer', 'code' => 'FO', 'is_active' => true]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'user_id' => $this->staffUser->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Field',
            'last_name' => 'Staff',
            'joining_date' => '2026-01-01',
            'employment_status' => 'active',
        ]);
    }

    public function test_staff_duty_ping_in_and_ping_out_via_api()
    {
        $responseIn = $this->actingAs($this->staffUser, 'sanctum')->postJson('/api/v1/location/ping-in', [
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'accuracy' => 4.5,
            'notes' => 'Duty start at branch office',
            'offline_sync_id' => 'PING-IN-001',
        ]);

        $responseIn->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event_type', 'ping_in');

        $this->assertDatabaseHas('field_location_logs', [
            'user_id' => $this->staffUser->id,
            'event_type' => 'ping_in',
            'offline_sync_id' => 'PING-IN-001',
        ]);

        $responseOut = $this->actingAs($this->staffUser, 'sanctum')->postJson('/api/v1/location/ping-out', [
            'latitude' => 22.5800,
            'longitude' => 88.3700,
            'accuracy' => 5.0,
            'notes' => 'Duty completed for the day',
            'offline_sync_id' => 'PING-OUT-001',
        ]);

        $responseOut->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event_type', 'ping_out');

        $this->assertDatabaseHas('field_location_logs', [
            'user_id' => $this->staffUser->id,
            'event_type' => 'ping_out',
            'offline_sync_id' => 'PING-OUT-001',
        ]);
    }

    public function test_batch_offline_location_sync_with_deduplication()
    {
        $payload = [
            'pings' => [
                [
                    'latitude' => 22.5726,
                    'longitude' => 88.3639,
                    'offline_sync_id' => 'BATCH-SYNC-001',
                    'battery_level' => 95,
                    'recorded_at' => now()->subMinutes(30)->toDateTimeString(),
                ],
                [
                    'latitude' => 22.5800,
                    'longitude' => 88.3700,
                    'offline_sync_id' => 'BATCH-SYNC-002',
                    'battery_level' => 90,
                    'recorded_at' => now()->subMinutes(15)->toDateTimeString(),
                ],
                [
                    'latitude' => 22.5800,
                    'longitude' => 88.3700,
                    'offline_sync_id' => 'BATCH-SYNC-001', // Duplicate ID
                    'battery_level' => 90,
                    'recorded_at' => now()->subMinutes(15)->toDateTimeString(),
                ],
            ]
        ];

        $response = $this->actingAs($this->staffUser, 'sanctum')->postJson('/api/v1/location/sync-pings', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.synced_count', 2)
            ->assertJsonPath('data.ignored_count', 1);

        $this->assertEquals(2, FieldLocationLog::where('user_id', $this->staffUser->id)->count());
    }

    public function test_realtime_location_status_calculation()
    {
        $locationService = app(LocationTrackingService::class);

        // Scenario 1: Ping in 5 minutes ago -> Online
        $locationService->logPing($this->staffUser, 22.5726, 88.3639, 'location_ping', recordedAt: now()->subMinutes(5)->toDateTimeString());
        $status = $locationService->getStaffStatus($this->staffUser);
        $this->assertEquals('online', $status['status']);

        // Scenario 2: Latest ping 30 minutes ago -> Stale
        FieldLocationLog::query()->delete();
        $locationService->logPing($this->staffUser, 22.5726, 88.3639, 'location_ping', recordedAt: now()->subMinutes(30)->toDateTimeString());
        $statusStale = $locationService->getStaffStatus($this->staffUser);
        $this->assertEquals('stale', $statusStale['status']);

        // Scenario 3: Latest ping 120 minutes ago -> Offline
        FieldLocationLog::query()->delete();
        $locationService->logPing($this->staffUser, 22.5726, 88.3639, 'location_ping', recordedAt: now()->subMinutes(120)->toDateTimeString());
        $statusOffline = $locationService->getStaffStatus($this->staffUser);
        $this->assertEquals('offline', $statusOffline['status']);
    }

    public function test_haversine_verified_distance_calculation()
    {
        $locationService = app(LocationTrackingService::class);
        $today = now()->toDateString();

        // Point A: Kolkata Park Street (22.5522, 88.3527)
        $locationService->logPing($this->staffUser, 22.5522, 88.3527, 'ping_in', recordedAt: now()->subHours(4)->toDateTimeString());

        // Point B: Kolkata Salt Lake (~7 km away: 22.5867, 88.4171)
        $locationService->logPing($this->staffUser, 22.5867, 88.4171, 'location_ping', recordedAt: now()->subHours(2)->toDateTimeString());

        $verifiedKm = $locationService->calculateVerifiedDistanceKm($this->staffUser->id, $today);
        $this->assertGreaterThan(6.0, $verifiedKm);
        $this->assertLessThan(10.0, $verifiedKm);
    }

    public function test_ta_claim_submission_and_duplicate_prevention()
    {
        $taService = app(TravelAllowanceService::class);

        $claim1 = $taService->submitClaim($this->staffUser, [
            'travel_date' => now()->toDateString(),
            'from_location' => 'Branch Office',
            'to_location' => 'Customer Village A',
            'transport_mode' => 'bike',
            'distance_km' => 15.0,
            'purpose' => 'Field collections',
        ]);

        $this->assertEquals('pending', $claim1->status);
        $this->assertEquals(60.00, $claim1->amount); // 15 km * 4.00/km

        // Submitting duplicate claim for same user on same date must throw exception
        $this->expectException(\InvalidArgumentException::class);
        $taService->submitClaim($this->staffUser, [
            'travel_date' => now()->toDateString(),
            'from_location' => 'Branch Office',
            'to_location' => 'Customer Village B',
            'transport_mode' => 'bike',
            'distance_km' => 10.0,
            'purpose' => 'Second trip',
        ]);
    }

    public function test_ta_claim_approval_and_payment_with_cash_book_entry()
    {
        $taService = app(TravelAllowanceService::class);

        $claim = $taService->submitClaim($this->staffUser, [
            'travel_date' => now()->toDateString(),
            'from_location' => 'Branch Office',
            'to_location' => 'Site Visit',
            'transport_mode' => 'car',
            'distance_km' => 20.0,
            'purpose' => 'Audit & Inspection',
        ]);

        $this->assertEquals(160.00, $claim->amount); // 20 km * 8.00/km

        // Approve claim
        $approvedClaim = $taService->approveClaim($claim, $this->adminUser);
        $this->assertEquals('approved', $approvedClaim->status);
        $this->assertEquals($this->adminUser->id, $approvedClaim->approved_by);

        // Mark paid via cash
        $paidClaim = $taService->payClaim($approvedClaim, $this->adminUser, 'CASH-TA-98124', 'cash');
        $this->assertEquals('paid', $paidClaim->status);
        $this->assertNotNull($paidClaim->paid_at);
        $this->assertNotNull($paidClaim->cash_book_entry_id);

        // Verify Cash Book entry created
        $this->assertDatabaseHas('cash_book_entries', [
            'id' => $paidClaim->cash_book_entry_id,
            'entry_type' => 'payment',
            'cash_amount' => 160.00,
            'reference_id' => $claim->id,
        ]);
    }

    public function test_admin_field_tracking_dashboard_and_route_history_endpoint()
    {
        $locationService = app(LocationTrackingService::class);
        $today = now()->toDateString();

        $locationService->logPing($this->staffUser, 22.5726, 88.3639, 'ping_in', recordedAt: now()->subMinutes(10)->toDateTimeString());

        // Admin Dashboard request
        $response = $this->actingAs($this->adminUser)->get('/admin/field-tracking');
        $response->assertStatus(200);
        $response->assertSee('Staff Real-Time Status');

        // Route History JSON endpoint request
        $routeResponse = $this->actingAs($this->adminUser)->getJson("/admin/field-tracking/route-history/{$this->staffUser->id}?date={$today}");
        $routeResponse->assertStatus(200)
            ->assertJsonPath('user_id', $this->staffUser->id)
            ->assertJsonPath('total_points', 1);
    }
}
