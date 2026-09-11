<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\SystemBrandingService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $branchManagerUser;
    protected User $regularStaffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $company = Company::create([
            'name' => 'Apex Microfinance Corp',
            'code' => 'HO01',
            'email' => 'contact@apexmicrofinance.com',
            'phone' => '9876543210',
            'address' => 'Corporate Plaza',
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'code' => 'BR001',
            'phone' => '9876543211',
            'email' => 'main@apexmicrofinance.com',
            'address' => 'Main Street 101',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        // Create Super Admin User
        $this->adminUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Super Admin User',
            'email' => 'admin@apexmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        // Create Branch Manager User
        $this->branchManagerUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Branch Manager User',
            'email' => 'bm@apexmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManagerUser->assignRole('Branch Manager');

        // Create Regular Staff User without administrative permissions
        $this->regularStaffUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Regular Staff User',
            'email' => 'staff@apexmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
    }

    /**
     * Matrix Test 1: Guest GET /admin -> redirect to /admin/login
     */
    public function test_guest_accessing_admin_redirects_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    /**
     * Matrix Test 2: Guest GET /admin/login -> HTTP 200 Admin Login Page
     */
    public function test_guest_accessing_admin_login_returns_200(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Admin Login');
    }

    /**
     * Matrix Test 3: Authenticated GET /admin -> authorized Admin Dashboard
     */
    public function test_authenticated_user_accessing_admin_returns_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
    }

    /**
     * Matrix Test 4: Authenticated GET /admin/login -> redirect to /admin (dashboard)
     */
    public function test_authenticated_user_accessing_admin_login_redirects_to_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/login');

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }

    /**
     * Matrix Test 5: Valid login -> correct dashboard
     */
    public function test_valid_credentials_log_in_user_and_redirect_to_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@apexmicrofinance.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->adminUser);
    }

    /**
     * Matrix Test 6: Invalid login -> proper authentication error, no 500
     */
    public function test_invalid_credentials_returns_error_and_no_500(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@apexmicrofinance.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Matrix Test 7: Logout -> /admin requires login again
     */
    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
        $this->assertGuest();

        // Ensure /admin now requires login again
        $adminResponse = $this->get('/admin');
        $adminResponse->assertStatus(302);
        $adminResponse->assertRedirect('/admin/login');
    }

    /**
     * Matrix Test 8: Unauthenticated user cannot access protected admin pages
     */
    public function test_unauthenticated_user_cannot_access_protected_admin_pages(): void
    {
        $routesToTest = [
            '/admin/system/users',
            '/admin/billing/sales/create',
            '/admin/profile',
        ];

        foreach ($routesToTest as $route) {
            $response = $this->get($route);
            $response->assertStatus(302);
            $response->assertRedirect('/admin/login');
        }
    }

    /**
     * Matrix Test 9: Dynamic branding appears on login
     */
    public function test_dynamic_branding_renders_on_login_page(): void
    {
        SystemBrandingService::clearCache();
        $branding = SystemBrandingService::getBranding();

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee($branding->system_name, false);
    }

    /**
     * Matrix Test 10: Existing RBAC remains intact
     */
    public function test_existing_rbac_remains_intact(): void
    {
        // Super Admin can access system users
        $adminResp = $this->actingAs($this->adminUser)->get('/admin/system/users');
        $adminResp->assertStatus(200);

        // Staff without permission gets 403 on system users
        $staffResp = $this->actingAs($this->regularStaffUser)->get('/admin/system/users');
        $staffResp->assertStatus(403);
    }

    /**
     * Matrix Test 11: Branch Manager permissions remain intact
     */
    public function test_branch_manager_permissions_remain_intact(): void
    {
        // Branch Manager has access to dashboard
        $bmResp = $this->actingAs($this->branchManagerUser)->get('/admin');
        $bmResp->assertStatus(200);

        // Branch Manager cannot access system users (role management restricted to super admin)
        $bmSystemResp = $this->actingAs($this->branchManagerUser)->get('/admin/system/users');
        $bmSystemResp->assertStatus(403);
    }
}
