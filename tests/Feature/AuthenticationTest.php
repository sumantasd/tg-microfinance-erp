<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirected_from_admin_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@tgmicrofinance.test',
            'password' => Hash::make('Admin@123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@tgmicrofinance.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_user_can_authenticate_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@tgmicrofinance.test',
            'password' => Hash::make('Admin@123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@tgmicrofinance.test',
            'password' => 'Admin@123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin');
    }

    public function test_authenticated_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'admin@tgmicrofinance.test',
            'password' => Hash::make('Admin@123'),
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }
}
