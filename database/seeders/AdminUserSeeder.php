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
            ['email' => 'admin@grihalaxmifinance.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@Grihalaxmi2026'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated === false) {
            $admin->update([
                'password' => Hash::make('Admin@Grihalaxmi2026'),
                'status' => 'active',
            ]);
        }

        $admin->syncRoles(['Super Admin']);
    }
}
