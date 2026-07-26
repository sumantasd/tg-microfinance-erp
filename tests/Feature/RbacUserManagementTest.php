<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);
    }

    public function test_seeded_super_admin_has_super_admin_role_assigned(): void
    {
        $admin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Super Admin'));
    }

    public function test_super_admin_has_unrestricted_access_to_roles_management(): void
    {
        $superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $response = $this->actingAs($superAdmin)->get('/admin/system/roles');

        $response->assertStatus(200);
        $response->assertSee('Role Management');
    }

    public function test_super_admin_has_unrestricted_access_to_all_modules(): void
    {
        $superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $routes = [
            '/admin/system/users',
            '/admin/system/roles',
            '/admin/system/permissions',
            '/admin/system/settings',
            '/admin/cms/homepage',
            '/admin/company',
            '/admin/branch',
            '/admin/customer',
            '/admin/loan',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($superAdmin)->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@tgmicrofinance.test',
            'password' => Hash::make('Admin@123'),
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@tgmicrofinance.test',
            'password' => 'Admin@123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_super_admin_can_create_new_staff_user_with_role(): void
    {
        $superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $response = $this->actingAs($superAdmin)->post('/admin/system/users', [
            'name' => 'Loan Officer Test',
            'email' => 'loanofficer@tgmicrofinance.test',
            'employee_id' => 'EMP-LOAN-001',
            'mobile_number' => '+1555000111',
            'password' => 'SecurePass@123',
            'status' => 'active',
            'role' => 'Loan Officer',
        ]);

        $response->assertRedirect('/admin/system/users');
        $this->assertDatabaseHas('users', [
            'email' => 'loanofficer@tgmicrofinance.test',
            'employee_id' => 'EMP-LOAN-001',
        ]);

        $createdUser = User::where('email', 'loanofficer@tgmicrofinance.test')->first();
        $this->assertTrue($createdUser->hasRole('Loan Officer'));
    }

    public function test_unauthorized_user_without_permission_receives_403(): void
    {
        $userWithoutPerms = User::factory()->create(['status' => 'active']);
        $customRole = Role::create(['name' => 'Restricted Role']);
        $userWithoutPerms->assignRole($customRole);

        $response = $this->actingAs($userWithoutPerms)->get('/admin/system/users');

        $response->assertStatus(403);
        $response->assertSee('403 - Unauthorized Access');
    }

    public function test_super_admin_can_soft_delete_user(): void
    {
        $superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();
        $targetUser = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($superAdmin)->delete('/admin/system/users/' . $targetUser->id);

        $response->assertRedirect('/admin/system/users');
        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    }
}
