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

            // Company Management
            'company.view',
            'company.create',
            'company.edit',
            'company.delete',
            'company.restore',
            'company.toggle_status',

            // Branch Management
            'branch.view',
            'branch.create',
            'branch.edit',
            'branch.delete',
            'branch.restore',
            'branch.toggle_status',

            // ERP Modules
            'customer.view',
            'customer.create',
            'customer.edit',
            'customer.delete',
            'loan.view',
            'loan.create',
            'loan.edit',
            'loan.approve',
            'loan.delete',
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

        // 9 Default Roles
        $roles = [
            'Super Admin',
            'Admin',
            'Company Admin',
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

        // Company Admin permissions (View, Create, Edit, Toggle Status; No permanent delete or restore)
        $companyAdminRole = Role::findByName('Company Admin', 'web');
        $companyAdminRole->syncPermissions([
            'company.view',
            'company.create',
            'company.edit',
            'company.toggle_status',
            'branch.view',
            'branch.create',
            'branch.edit',
            'branch.toggle_status',
            'customer.view',
            'customer.create',
            'customer.edit',
            'loan.view',
            'loan.create',
            'loan.edit',
            'loan.approve',
            'savings.view',
            'collection.view',
            'accounting.view',
            'reports.view',
            'settings.manage',
        ]);

        // Branch Manager permissions (Strict View Only for assigned branch; No create, edit, delete, restore, status toggle)
        $branchManagerRole = Role::findByName('Branch Manager', 'web');
        $branchManagerRole->syncPermissions([
            'company.view',
            'branch.view',
            'customer.view',
            'customer.create',
            'customer.edit',
            'loan.view',
            'loan.create',
            'loan.approve',
            'savings.view',
            'collection.view',
            'reports.view',
        ]);
    }
}
