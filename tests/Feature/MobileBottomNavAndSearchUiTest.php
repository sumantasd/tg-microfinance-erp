<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MobileBottomNavAndSearchUiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::firstOrCreate(
            ['code' => 'GF01'],
            ['name' => 'Grihalaxmi Finance', 'email' => 'info@grihalaxmi.com', 'phone' => '9800098000', 'address' => 'Kolkata HQ', 'is_active' => true]
        );

        $branch = Branch::firstOrCreate(
            ['code' => 'BR01'],
            ['company_id' => $company->id, 'name' => 'Head Branch', 'phone' => '0331000001', 'address' => 'Station Road', 'city' => 'Kolkata', 'state' => 'WB', 'pincode' => '700001', 'is_active' => true]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'nav_admin@grihalaxmi.com'],
            ['name' => 'Nav Admin', 'password' => bcrypt('password'), 'company_id' => $company->id, 'branch_id' => $branch->id, 'is_active' => true]
        );

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'collection.view', 'guard_name' => 'web']);
        $loanPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'loan.view', 'guard_name' => 'web']);
        $custPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'customer.view', 'guard_name' => 'web']);
        $role->givePermissionTo([$perm, $loanPerm, $custPerm]);
        $this->adminUser->roles()->syncWithoutDetaching([$role->id]);
        $this->adminUser->givePermissionTo([$perm, $loanPerm, $custPerm]);
    }

    public function test_mobile_bottom_nav_has_exactly_five_items_in_correct_order()
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertStatus(200);

        // Assert the 5 items exist
        $response->assertSee('id="bottomNavHome"', false);
        $response->assertSee('id="bottomNavLoan"', false);
        $response->assertSee('id="bottomNavMembers"', false);
        $response->assertSee('id="bottomNavCollection"', false);
        $response->assertSee('id="bottomNavMore"', false);

        // Assert Duty is NOT a separate item in the mobile bottom navigation
        $content = $response->getContent();
        $navStart = strpos($content, 'id="mobileAppBottomNavigation"');
        $this->assertNotFalse($navStart, 'Mobile bottom nav bar must exist in HTML.');

        $navEnd = strpos($content, '</nav>', $navStart);
        $navHtml = substr($content, $navStart, $navEnd - $navStart);

        $this->assertStringNotContainsString('Duty', $navHtml);
        $this->assertStringNotContainsString('Attendance', $navHtml);

        // Assert strictly the 5 items in the exact order: Home -> Loan -> Members -> Collection -> More
        $homePos = strpos($navHtml, 'id="bottomNavHome"');
        $loanPos = strpos($navHtml, 'id="bottomNavLoan"');
        $membersPos = strpos($navHtml, 'id="bottomNavMembers"');
        $collectionPos = strpos($navHtml, 'id="bottomNavCollection"');
        $morePos = strpos($navHtml, 'id="bottomNavMore"');

        $this->assertTrue($homePos < $loanPos, 'Home must appear before Loan');
        $this->assertTrue($loanPos < $membersPos, 'Loan must appear before Members');
        $this->assertTrue($membersPos < $collectionPos, 'Members must appear before Collection');
        $this->assertTrue($collectionPos < $morePos, 'Collection must appear before More');

        // Verify labels
        $this->assertStringContainsString('<span>Home</span>', $navHtml);
        $this->assertStringContainsString('<span>Loan</span>', $navHtml);
        $this->assertStringContainsString('<span>Members</span>', $navHtml);
        $this->assertStringContainsString('<span>Collection</span>', $navHtml);
        $this->assertStringContainsString('<span>More</span>', $navHtml);

        // Verify More triggers offcanvas drawer
        $this->assertStringContainsString('data-bs-target="#mobileAppDrawer"', $navHtml);
    }

    public function test_collection_page_renders_quick_customer_and_loan_search_component()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index'));

        $response->assertStatus(200);

        // Component elements
        $response->assertSee('collection-search-card');
        $response->assertSee('collection-search-header');
        $response->assertSee('Quick Customer & Loan Search');
        $response->assertSee('btnScanCustomerQr');
        $response->assertSee('Scan QR / Card');
        $response->assertSee('collection-search-container');
        $response->assertSee('collection-search-input-wrap');
        $response->assertSee('id="collectionSearchInput"', false);
        $response->assertSee('placeholder="Enter mobile, customer name, loan or member ID"', false);
        $response->assertSee('collection-search-submit-btn');
        $response->assertSee('Search Customer');

        // Modal for scanning exists
        $response->assertSee('id="scanCustomerQrModal"', false);

        // Collection is active on collection page
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/<a[^>]*id="bottomNavCollection"[^>]*active|<a[^>]*active[^>]*id="bottomNavCollection"/', $content);
    }

    public function test_collection_search_executes_query_successfully()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.emi-collection.index', [
            'search' => '9876543210',
        ]));

        $response->assertStatus(200);
        $response->assertSee('value="9876543210"', false);
    }
}
