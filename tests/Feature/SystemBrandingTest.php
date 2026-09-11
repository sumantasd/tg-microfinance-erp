<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\SystemBrandingService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SystemBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $company = Company::create([
            'name' => 'Grihalaxmi Finance Head Office',
            'code' => 'HO01',
            'email' => 'ho@grihalaxmifinance.com',
            'phone' => '9876543210',
            'address' => 'Head Office Plaza, Kolkata',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $company->id,
            'name' => 'System Admin',
            'email' => 'admin.branding@grihalaxmifinance.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->adminUser->assignRole('Super Admin');
    }

    public function test_default_system_branding_fallback_loads_correctly(): void
    {
        SystemBrandingService::clearCache();

        $branding = SystemBrandingService::getBranding();

        $this->assertNotEmpty($branding->system_name);
        $this->assertStringContainsString('logo', $branding->logo_url);
        $this->assertStringContainsString('logo-icon', $branding->logo_icon_url);
    }

    public function test_admin_can_update_system_name_and_invalidate_cache(): void
    {
        Storage::fake('public');
        SystemBrandingService::clearCache();

        $response = $this->actingAs($this->adminUser)->put(route('admin.system.settings.update-branding'), [
            'company_name' => 'Apex Microfinance ERP',
        ]);

        $response->assertRedirect(route('admin.system.settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('website_settings', [
            'company_name' => 'Apex Microfinance ERP',
        ]);

        $branding = SystemBrandingService::getBranding();
        $this->assertEquals('Apex Microfinance ERP', $branding->system_name);
    }

    public function test_updated_branding_reflects_dynamically_in_admin_dashboard_and_login(): void
    {
        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update(['company_name' => 'Titan Microfinance Group']);
        SystemBrandingService::clearCache();

        // Admin Dashboard Page Title check
        $dashResponse = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Titan Microfinance Group');

        // Login Page check
        auth()->logout();
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Titan Microfinance Group');
    }

    public function test_uploading_custom_logo_icon_and_favicon_stores_files(): void
    {
        Storage::fake('public');
        SystemBrandingService::clearCache();

        $logoFile = UploadedFile::fake()->create('custom_logo.png', 10, 'image/png');
        $iconFile = UploadedFile::fake()->create('custom_icon.png', 10, 'image/png');

        $response = $this->actingAs($this->adminUser)->put(route('admin.system.settings.update-branding'), [
            'company_name' => 'Global Finance Corp',
            'logo' => $logoFile,
            'logo_icon' => $iconFile,
        ]);

        $response->assertRedirect(route('admin.system.settings.index'));

        $setting = WebsiteSetting::first();
        $this->assertNotNull($setting->logo);
        $this->assertNotNull($setting->logo_icon);

        Storage::disk('public')->assertExists($setting->logo);
        Storage::disk('public')->assertExists($setting->logo_icon);

        $branding = SystemBrandingService::getBranding();
        $this->assertStringContainsString('storage/', $branding->logo_url);
        $this->assertStringContainsString('storage/', $branding->logo_icon_url);
    }

    public function test_validation_rejects_non_image_file_uploads(): void
    {
        Storage::fake('public');

        $fakeScript = UploadedFile::fake()->create('malicious.php', 10, 'application/x-php');

        $response = $this->actingAs($this->adminUser)->put(route('admin.system.settings.update-branding'), [
            'company_name' => 'Safe Finance',
            'logo' => $fakeScript,
        ]);

        $response->assertSessionHasErrors(['logo']);
    }

    public function test_removing_uploaded_branding_assets_resets_to_defaults(): void
    {
        Storage::fake('public');

        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update([
            'company_name' => 'Temporary Finance',
            'logo' => 'cms/settings/test_logo.png',
        ]);
        Storage::disk('public')->put('cms/settings/test_logo.png', 'fake image content');
        SystemBrandingService::clearCache();

        $response = $this->actingAs($this->adminUser)->put(route('admin.system.settings.update-branding'), [
            'company_name' => 'Temporary Finance',
            'remove_logo' => '1',
        ]);

        $response->assertRedirect(route('admin.system.settings.index'));

        $setting->refresh();
        $this->assertNull($setting->logo);
        Storage::disk('public')->assertMissing('cms/settings/test_logo.png');

        $branding = SystemBrandingService::getBranding();
        $this->assertStringContainsString('images/logo.png', $branding->logo_url);
    }
}
