<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds for local development.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tgmicrofinance.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Super Admin']);
    }
}
