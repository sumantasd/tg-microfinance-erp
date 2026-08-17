<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\WebsiteSetting;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemHealthAndRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $staffUser;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Microfinance Ltd',
            'code' => 'GML-HQ',
            'registration_number' => 'REG-GML-1001',
            'email' => 'contact@grihalaxmi.com',
            'phone' => '03322114455',
            'address' => 'Salt Lake, Kolkata, WB 700091',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Main Branch',
            'code' => 'BR-KOL-01',
            'email' => 'kolkata@grihalaxmi.com',
            'phone' => '9830098300',
            'address' => 'Kolkata Central, WB 700001',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Super Administrator',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->staffUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Staff Member',
            'email' => 'staff@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
    }

    /**
     * Phase 1 & 4: Website CMS Settings Page Health Check
     */
    public function test_cms_settings_page_loads_successfully_and_can_be_updated(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.cms.settings.edit'));
        $response->assertStatus(200);
        $response->assertSee('Website & Calculator Settings', false);
        $response->assertSee('Save Website Settings');

        // Test Update
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('admin.cms.settings.update'), [
            'company_name' => 'Grihalaxmi Finance Private Limited',
            'phone' => '+91 98300 12345',
            'email' => 'info@grihalaxmi.com',
            'address' => 'Salt Lake Sector V, Kolkata, WB 700091',
            'calc_enabled' => '1',
            'calc_title' => 'Micro-Loan Estimator',
            'calc_subtitle' => 'Calculate your monthly EMI quickly',
            'calc_default_amount' => '25000',
            'calc_min_amount' => '5000',
            'calc_max_amount' => '100000',
            'calc_interest_rate' => '18.0% P.A.',
            'calc_type' => 'reducing_balance',
            'calc_rounding_type' => 'nearest_integer',
            'calc_cta_text' => 'Apply Online Now',
            'footer_text' => '© 2026 Grihalaxmi Finance. All rights reserved.',
        ]);

        $updateResponse->assertRedirect(route('admin.cms.settings.edit'));
        $updateResponse->assertSessionHas('success');

        $setting = WebsiteSetting::first();
        $this->assertEquals('Grihalaxmi Finance Private Limited', $setting->company_name);
        $this->assertEquals('+91 98300 12345', $setting->phone);
    }

    /**
     * Phase 4: All 17 Website CMS Pages Health Check
     */
    public function test_all_cms_admin_pages_load_successfully(): void
    {
        $cmsRoutes = [
            '/admin/cms/settings',
            '/admin/cms/homepage',
            '/admin/cms/banners',
            '/admin/cms/pages',
            '/admin/cms/loan-products',
            '/admin/cms/savings-products',
            '/admin/cms/interest-rates',
            '/admin/cms/services',
            '/admin/cms/news',
            '/admin/cms/gallery',
            '/admin/cms/downloads',
            '/admin/cms/faq',
            '/admin/cms/footer',
            '/admin/cms/seo',
            '/admin/cms/contact',
            '/admin/cms/careers',
            '/admin/cms/team',
        ];

        foreach ($cmsRoutes as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $this->assertEquals(200, $response->status(), "CMS Route [{$url}] failed with status " . $response->status());
        }
    }

    /**
     * Phase 2 & 18: Complete Admin Core Modules Smoke Test Matrix
     */
    public function test_all_core_admin_index_and_dashboard_routes_smoke_test(): void
    {
        $adminRoutes = [
            // Dashboard & Search
            '/admin',
            '/admin/dashboard',
            '/admin/search?q=test',
            '/admin/profile',

            // Organization
            '/admin/company',
            '/admin/branch',
            '/admin/customer',
            '/admin/customer-group',

            // Loans & Operations
            '/admin/loan-scheme',
            '/admin/loan-application',
            '/admin/loan-account',
            '/admin/emi-collection',
            '/admin/overdue',
            '/admin/penalties/ledger',
            '/admin/loan-settlement',

            // Products & Inventory
            '/admin/product',
            '/admin/product-brand',
            '/admin/product-category',
            '/admin/inventory',
            '/admin/inventory/transfers',
            '/admin/inventory/purchases',

            // Accounting & Finance
            '/admin/accounting/dashboard',
            '/admin/accounting/chart-of-accounts',
            '/admin/accounting/bank-accounts',
            '/admin/accounting/vouchers',
            '/admin/reports',

            // Enterprise HRM
            '/admin/employee',
            '/admin/department',
            '/admin/designation',
            '/admin/hrm/attendance',
            '/admin/hrm/leave',
            '/admin/hrm/payroll',
            '/admin/hrm/letters',
            '/admin/hrm/reports',

            // System & Settings
            '/admin/system/users',
            '/admin/system/roles',
            '/admin/system/permissions',
            '/admin/system/settings',
            '/admin/system/audit-logs',
            '/admin/system/backup',
        ];

        foreach ($adminRoutes as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $this->assertEquals(200, $response->status(), "Admin Route [{$url}] failed with status " . $response->status());
        }
    }

    /**
     * Phase 7 & 18: Unauthenticated and Unauthorized Route Protection
     */
    public function test_unauthenticated_and_unauthorized_route_protection(): void
    {
        // Unauthenticated redirect to login
        $resGuest = $this->get('/admin/cms/settings');
        $resGuest->assertRedirect(route('login'));

        // Staff user without website.manage gets 403 Forbidden
        $resForbidden = $this->actingAs($this->staffUser)->get('/admin/cms/settings');
        $resForbidden->assertStatus(403);
    }

    /**
     * Phase 14: Scheduled Console Commands Health Check
     */
    public function test_scheduled_console_commands_run_without_errors(): void
    {
        $exitCodeSync = Artisan::call('loans:sync-overdue-status');
        $this->assertEquals(0, $exitCodeSync);

        $exitCodePenalty = Artisan::call('loans:apply-penalties');
        $this->assertEquals(0, $exitCodePenalty);
    }

    /**
     * Favicon Branding Across All Layouts
     */
    public function test_favicon_branding_is_rendered_across_admin_auth_and_public_layouts(): void
    {
        // 1. Admin Layout
        $resAdmin = $this->actingAs($this->superAdmin)->get('/admin');
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('favicon.ico');
        $resAdmin->assertSee('favicon.svg');

        // 2. Auth / Login Layout
        $resAuth = $this->get('/login');
        $resAuth->assertStatus(200);
        $resAuth->assertSee('favicon.ico');
        $resAuth->assertSee('favicon.svg');

        // 3. Public Homepage Layout
        $resPublic = $this->get('/');
        $resPublic->assertStatus(200);
        $resPublic->assertSee('favicon.ico');
        $resPublic->assertSee('favicon.svg');
    }
}
