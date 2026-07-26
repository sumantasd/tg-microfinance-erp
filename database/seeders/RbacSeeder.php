<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Standardized Dot-Notation Permissions List
        $permissions = [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Role Management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Permission Management
            'permissions.view',
            'permissions.assign',

            // ERP Modules
            'company.view',
            'branch.view',
            'customer.view',
            'loan.view',
            'loan.approve',
            'savings.view',
            'collection.view',
            'accounting.view',
            'reports.view',

            // Website CMS & Settings
            'website.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 8 Default Roles
        $roles = [
            'Super Admin',
            'Admin',
            'Branch Manager',
            'Accountant',
            'Loan Officer',
            'Field Officer',
            'Cashier',
            'Auditor',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Assign All Permissions to Super Admin and Admin
        $superAdminRole = Role::findByName('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::findByName('Admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        // Assign Specific Permissions to Branch Manager
        $branchManagerRole = Role::findByName('Branch Manager', 'web');
        $branchManagerRole->syncPermissions([
            'company.view', 'branch.view', 'customer.view', 'loan.view', 'loan.approve',
            'savings.view', 'collection.view', 'reports.view'
        ]);
    }
}
