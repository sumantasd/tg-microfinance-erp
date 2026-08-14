<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'permissions.view', 'permissions.assign',

            'company.view', 'company.create', 'company.edit', 'company.delete', 'company.restore', 'company.toggle_status',
            'branch.view', 'branch.create', 'branch.edit', 'branch.delete', 'branch.restore', 'branch.toggle_status',

            'department.view', 'department.create', 'department.edit', 'department.delete', 'department.restore', 'department.toggle_status',
            'designation.view', 'designation.create', 'designation.edit', 'designation.delete', 'designation.restore', 'designation.toggle_status',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.restore', 'employee.toggle_status',

            // Essential HRM Permissions
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.delete',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'hr_letter.view', 'hr_letter.generate',

            'customer.view', 'customer.create', 'customer.edit', 'customer.delete', 'customer.restore', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee', 'customer.change_status',
            'group.view', 'group.create', 'group.edit', 'group.delete', 'group.change_status', 'group.manage_members',
            'loan.view', 'loan.create', 'loan.edit', 'loan.approve', 'loan.delete',
            'savings.view', 'collection.view', 'accounting.view', 'reports.view',
            'website.manage', 'settings.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $roles = [
            'Super Admin', 'Admin', 'Company Admin', 'Branch Manager',
            'Accountant', 'Loan Officer', 'Field Officer', 'Cashier', 'Auditor',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        Role::findByName('Super Admin', 'web')->syncPermissions(Permission::all());
        Role::findByName('Admin', 'web')->syncPermissions(Permission::all());

        Role::findByName('Company Admin', 'web')->syncPermissions([
            'company.view', 'company.create', 'company.edit', 'company.toggle_status',
            'branch.view', 'branch.create', 'branch.edit', 'branch.toggle_status',
            'department.view', 'department.create', 'department.edit', 'department.toggle_status',
            'designation.view', 'designation.create', 'designation.edit', 'designation.toggle_status',
            'employee.view', 'employee.create', 'employee.edit', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.delete',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'hr_letter.view', 'hr_letter.generate',
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete', 'customer.restore', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee', 'customer.change_status',
            'group.view', 'group.create', 'group.edit', 'group.delete', 'group.change_status', 'group.manage_members',
            'loan.view', 'loan.create', 'loan.edit', 'loan.approve',
            'savings.view', 'collection.view', 'accounting.view', 'reports.view', 'settings.manage',
        ]);

        Role::findByName('Branch Manager', 'web')->syncPermissions([
            'company.view', 'branch.view', 'department.view', 'designation.view',
            'employee.view', 'employee.create', 'employee.edit', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve',
            'payroll.view', 'hr_letter.view', 'hr_letter.generate',
            'customer.view', 'customer.create', 'customer.edit', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee', 'customer.change_status',
            'group.view', 'group.create', 'group.edit', 'group.change_status', 'group.manage_members',
            'loan.view', 'loan.create', 'loan.approve',
            'savings.view', 'collection.view', 'reports.view',
        ]);
    }
}
