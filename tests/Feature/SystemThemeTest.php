<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\SystemThemeService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemThemeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $adminUser;
    protected User $branchManagerUser;
    protected User $regularStaffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Titan Microfinance Corp',
            'code' => 'HO01',
            'email' => 'contact@titanmicrofinance.com',
            'phone' => '9876543210',
            'address' => 'Corporate Plaza',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Branch',
            'code' => 'BR001',
            'email' => 'kolkata@titanmicrofinance.com',
            'phone' => '9876543211',
            'address' => 'Main Road Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        // Super Admin with full settings permission
        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Super Admin User',
            'email' => 'admin.theme@titanmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        // Branch Manager with standard branch manager role
        $this->branchManagerUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Branch Manager User',
            'email' => 'bm.theme@titanmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManagerUser->assignRole('Branch Manager');

        // Staff user without settings permission
        $this->regularStaffUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Regular Staff User',
            'email' => 'staff.theme@titanmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Default theme loads with safe fallback values
     */
    public function test_default_theme_loads_with_safe_defaults(): void
    {
        SystemThemeService::clearCache();

        $theme = SystemThemeService::getTheme();

        $this->assertEquals('#0C2340', $theme->primary_color);
        $this->assertEquals('#C5A059', $theme->secondary_color);
        $this->assertEquals('navy_gold', $theme->theme_preset);
        $this->assertEquals('light', $theme->theme_mode);
    }

    /**
     * Test 2: Guest cannot access theme settings
     */
    public function test_guest_cannot_access_theme_settings(): void
    {
        $response = $this->get('/admin/system/settings');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        $updateResponse = $this->put('/admin/system/settings/theme', [
            'primary_color' => '#1E40AF',
        ]);
        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect('/admin/login');
    }

    /**
     * Test 3: Unauthorized user without settings permission gets 403
     */
    public function test_unauthorized_user_cannot_update_theme(): void
    {
        $response = $this->actingAs($this->regularStaffUser)->put('/admin/system/settings/theme', [
            'theme_preset' => 'corporate_blue',
            'primary_color' => '#1E40AF',
            'secondary_color' => '#3B82F6',
            'accent_color' => '#60A5FA',
            'sidebar_color' => '#1E3A8A',
            'sidebar_text_color' => '#F8FAFC',
            'sidebar_active_color' => '#60A5FA',
            'header_color' => '#FFFFFF',
            'header_text_color' => '#1E40AF',
            'body_background_color' => '#F8FAFC',
            'card_background_color' => '#FFFFFF',
            'border_color' => '#E2E8F0',
            'button_radius' => '0.375rem',
            'theme_mode' => 'light',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 4: Authorized admin can update theme with valid HEX colors
     */
    public function test_authorized_admin_can_update_theme(): void
    {
        SystemThemeService::clearCache();

        $response = $this->actingAs($this->adminUser)->put('/admin/system/settings/theme', [
            'theme_preset' => 'corporate_blue',
            'primary_color' => '#1E40AF',
            'secondary_color' => '#3B82F6',
            'accent_color' => '#60A5FA',
            'sidebar_color' => '#1E3A8A',
            'sidebar_text_color' => '#F8FAFC',
            'sidebar_active_color' => '#60A5FA',
            'header_color' => '#FFFFFF',
            'header_text_color' => '#1E40AF',
            'body_background_color' => '#F8FAFC',
            'card_background_color' => '#FFFFFF',
            'border_color' => '#E2E8F0',
            'button_radius' => '0.375rem',
            'theme_mode' => 'light',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.system.settings.index'));

        $theme = SystemThemeService::getTheme();
        $this->assertEquals('#1E40AF', $theme->primary_color);
        $this->assertEquals('corporate_blue', $theme->theme_preset);
    }

    /**
     * Test 5: Invalid HEX colors are rejected by validation
     */
    public function test_invalid_hex_colors_are_rejected(): void
    {
        $response = $this->actingAs($this->adminUser)->put('/admin/system/settings/theme', [
            'primary_color' => 'INVALID_HEX',
            'secondary_color' => '#3B82F6',
            'accent_color' => '#60A5FA',
            'sidebar_color' => '#1E3A8A',
            'sidebar_text_color' => '#F8FAFC',
            'sidebar_active_color' => '#60A5FA',
            'header_color' => '#FFFFFF',
            'header_text_color' => '#1E40AF',
            'body_background_color' => '#F8FAFC',
            'card_background_color' => '#FFFFFF',
            'border_color' => '#E2E8F0',
            'button_radius' => '0.375rem',
            'theme_mode' => 'light',
        ]);

        $response->assertSessionHasErrors('primary_color');
    }

    /**
     * Test 6: Reset to default theme works properly
     */
    public function test_reset_to_default_theme_restores_defaults(): void
    {
        // First update to non-default theme
        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update([
            'primary_color' => '#065F46',
            'secondary_color' => '#10B981',
            'theme_preset' => 'finance_green',
        ]);
        SystemThemeService::clearCache();

        $resetResponse = $this->actingAs($this->adminUser)->post('/admin/system/settings/theme/reset');

        $resetResponse->assertStatus(302);
        $resetResponse->assertRedirect(route('admin.system.settings.index'));

        $theme = SystemThemeService::getTheme();
        $this->assertEquals('#0C2340', $theme->primary_color);
        $this->assertEquals('#C5A059', $theme->secondary_color);
        $this->assertEquals('navy_gold', $theme->theme_preset);
    }

    /**
     * Test 7: Theme CSS variables are injected into Admin layout
     */
    public function test_theme_css_variables_rendered_in_admin_layout(): void
    {
        SystemThemeService::clearCache();

        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('system-dynamic-theme-css', false);
        $response->assertSee('--theme-primary', false);
    }

    /**
     * Test 8: Branch Manager permissions are not expanded
     */
    public function test_branch_manager_permissions_not_expanded(): void
    {
        $response = $this->actingAs($this->branchManagerUser)->put('/admin/system/settings/theme', [
            'primary_color' => '#1E40AF',
        ]);

        $response->assertStatus(403);
    }
}
