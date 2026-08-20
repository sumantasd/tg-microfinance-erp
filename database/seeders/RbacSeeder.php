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
            // 1. User & Role Administration
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.toggle_status',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign',
            'permissions.view', 'permissions.assign',
            'settings.view', 'settings.edit', 'settings.manage',

            // 2. Organization (Company & Branch)
            'company.view', 'company.create', 'company.edit', 'company.delete', 'company.restore', 'company.toggle_status',
            'branch.view', 'branch.create', 'branch.edit', 'branch.delete', 'branch.restore', 'branch.toggle_status',

            // 3. Customer & Member Management
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete', 'customer.restore', 'customer.change_status', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee',
            'group.view', 'group.create', 'group.edit', 'group.delete', 'group.change_status', 'group.manage_members', 'group.assign_leader',

            // 4. Loan Schemes
            'loan_scheme.view', 'loan_scheme.create', 'loan_scheme.edit', 'loan_scheme.delete',

            // 5. Loan Applications & Approvals
            'loan_application.view', 'loan_application.create', 'loan_application.edit', 'loan_application.delete', 'loan_application.submit', 'loan_application.review', 'loan_application.approve', 'loan_application.reject', 'loan_application.cancel',

            // 6. Loan Accounts & Operations
            'loan.view', 'loan.create', 'loan.edit', 'loan.delete', 'loan.sanction', 'loan.disburse', 'loan.issue_product', 'loan.view_schedule', 'loan.record_down_payment', 'loan.record_repayment',
            'loan_closure.view', 'loan_closure.calculate', 'loan_foreclosure.process', 'loan_settlement.request', 'loan_settlement.approve', 'loan_write_off.request', 'loan_write_off.approve', 'loan_closure.certificate',

            // 7. EMI Collection
            'collection.view', 'loan.collection.view', 'loan.collection.create', 'loan.collection.receipt', 'loan.collection.history',

            // 8. Overdue, DPD & PAR
            'overdue.view', 'dpd.view', 'overdue.branch_report',

            // 9. Penalty Management & Waivers
            'penalty.view', 'penalty.waive', 'loans.waive_penalty', 'loan.waive_penalty',

            // 10. Products & Catalog
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'product_brand.view', 'product_brand.create', 'product_brand.edit', 'product_brand.delete',
            'product_category.view', 'product_category.create', 'product_category.edit', 'product_category.delete',

            // 11. Branch Inventory & Transfers
            'inventory.view', 'inventory.manage', 'inventory.adjust', 'inventory.restock',
            'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.approve', 'inventory.transfer.reject', 'inventory.transfer.dispatch', 'inventory.transfer.receive', 'inventory.transfer.cancel',

            // 12. Product Purchase & Supplier Procurement
            'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.confirm', 'purchase.receive', 'purchase.cancel',
            'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete', 'supplier.payments', 'supplier.ledger', 'supplier.reports', 'supplier.export',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.payments', 'suppliers.ledger', 'suppliers.reports', 'suppliers.export',

            // 13. General Ledger & Double-Entry Accounting
            'accounting.view', 'accounting.dashboard',
            'chart_of_accounts.view', 'chart_of_accounts.create', 'chart_of_accounts.edit',
            'bank_accounts.view', 'bank_accounts.create', 'bank_accounts.edit',
            'voucher.view', 'voucher.create', 'voucher.post', 'voucher.reverse',

            // 14. Enterprise HRM & Payroll
            'department.view', 'department.create', 'department.edit', 'department.delete', 'department.restore', 'department.toggle_status',
            'designation.view', 'designation.create', 'designation.edit', 'designation.delete', 'designation.restore', 'designation.toggle_status',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.restore', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject', 'leave.delete',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'hr_letter.view', 'hr_letter.generate',
            'hr_reports.view',

            // 15. Reports & Analytics
            'reports.view', 'reports.export',

            // 16. Website CMS & Savings
            'website.manage', 'savings.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 10 Key Roles
        $roles = [
            'Super Admin',
            'Admin',
            'Company Admin',
            'Branch Manager',
            'Loan Officer',
            'Field Officer',
            'Collection Officer',
            'Cashier',
            'Accountant',
            'Auditor',
            'Inventory Manager',
            'HR Manager',
            'Viewer',
            'Report Viewer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 1. Super Admin: Unrestricted Full System Access
        Role::findByName('Super Admin', 'web')->syncPermissions(Permission::all());

        // 2. Admin (HQ System Admin): Full ERP Operational & Configuration Access
        Role::findByName('Admin', 'web')->syncPermissions(Permission::all());

        // 3. Company Admin: Company-level Administrator (Multi-Branch Management within Company)
        Role::findByName('Company Admin', 'web')->syncPermissions([
            'company.view', 'company.create', 'company.edit', 'company.toggle_status',
            'branch.view', 'branch.create', 'branch.edit', 'branch.toggle_status',
            'users.view', 'users.create', 'users.edit', 'users.toggle_status',
            'customer.view', 'customer.create', 'customer.edit', 'customer.delete', 'customer.restore', 'customer.change_status', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee',
            'group.view', 'group.create', 'group.edit', 'group.delete', 'group.change_status', 'group.manage_members', 'group.assign_leader',
            'loan_scheme.view', 'loan_scheme.create', 'loan_scheme.edit',
            'loan_application.view', 'loan_application.create', 'loan_application.edit', 'loan_application.delete', 'loan_application.submit', 'loan_application.review', 'loan_application.approve', 'loan_application.reject', 'loan_application.cancel',
            'loan.view', 'loan.create', 'loan.edit', 'loan.sanction', 'loan.disburse', 'loan.issue_product', 'loan.view_schedule', 'loan.record_down_payment', 'loan.record_repayment',
            'loan_closure.view', 'loan_closure.calculate', 'loan_foreclosure.process', 'loan_settlement.request', 'loan_settlement.approve', 'loan_write_off.request', 'loan_closure.certificate',
            'collection.view', 'loan.collection.view', 'loan.collection.create', 'loan.collection.receipt', 'loan.collection.history',
            'overdue.view', 'dpd.view', 'overdue.branch_report',
            'penalty.view', 'penalty.waive', 'loans.waive_penalty', 'loan.waive_penalty',
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'product_brand.view', 'product_brand.create', 'product_brand.edit',
            'product_category.view', 'product_category.create', 'product_category.edit',
            'inventory.view', 'inventory.manage', 'inventory.adjust', 'inventory.restock',
            'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.approve', 'inventory.transfer.reject', 'inventory.transfer.dispatch', 'inventory.transfer.receive', 'inventory.transfer.cancel',
            'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.confirm', 'purchase.receive', 'purchase.cancel',
            'accounting.view', 'accounting.dashboard', 'chart_of_accounts.view', 'chart_of_accounts.create', 'chart_of_accounts.edit', 'bank_accounts.view', 'bank_accounts.create', 'bank_accounts.edit', 'voucher.view', 'voucher.create', 'voucher.post', 'voucher.reverse',
            'department.view', 'department.create', 'department.edit', 'department.toggle_status',
            'designation.view', 'designation.create', 'designation.edit', 'designation.toggle_status',
            'employee.view', 'employee.create', 'employee.edit', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject', 'leave.delete',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'hr_letter.view', 'hr_letter.generate',
            'hr_reports.view',
            'reports.view', 'reports.export',
            'settings.view', 'settings.manage', 'savings.view',
        ]);

        // 4. Branch Manager: Operational Branch Authority (Locked to Branch)
        Role::findByName('Branch Manager', 'web')->syncPermissions([
            'company.view', 'branch.view',
            'customer.view', 'customer.create', 'customer.edit', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee', 'customer.change_status',
            'group.view', 'group.create', 'group.edit', 'group.change_status', 'group.manage_members', 'group.assign_leader',
            'loan_scheme.view',
            'loan_application.view', 'loan_application.create', 'loan_application.edit', 'loan_application.submit', 'loan_application.review', 'loan_application.approve', 'loan_application.reject',
            'loan.view', 'loan.create', 'loan.sanction', 'loan.disburse', 'loan.issue_product', 'loan.view_schedule', 'loan.record_down_payment', 'loan.record_repayment',
            'loan_closure.view', 'loan_closure.calculate', 'loan_foreclosure.process', 'loan_settlement.request', 'loan_settlement.approve', 'loan_closure.certificate',
            'collection.view', 'loan.collection.view', 'loan.collection.create', 'loan.collection.receipt', 'loan.collection.history',
            'overdue.view', 'dpd.view', 'overdue.branch_report',
            'penalty.view', 'penalty.waive', 'loans.waive_penalty', 'loan.waive_penalty',
            'product.view', 'product_brand.view', 'product_category.view',
            'inventory.view', 'inventory.manage', 'inventory.adjust',
            'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.receive', 'inventory.transfer.dispatch',
            'purchase.view', 'purchase.receive',
            'department.view', 'designation.view', 'employee.view', 'employee.create', 'employee.edit', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject',
            'payroll.view', 'hr_letter.view', 'hr_letter.generate', 'hr_reports.view',
            'reports.view', 'reports.export', 'savings.view',
        ]);

        // 5. Loan Officer & Field Officer: Field Sourcing, Applications, EMI Collection
        $loanOfficerPermissions = [
            'company.view', 'branch.view',
            'customer.view', 'customer.create', 'customer.edit', 'customer.verify_kyc', 'customer.manage_guarantor', 'customer.manage_nominee',
            'group.view', 'group.create', 'group.edit', 'group.manage_members',
            'loan_scheme.view',
            'loan_application.view', 'loan_application.create', 'loan_application.edit', 'loan_application.submit',
            'loan.view', 'loan.view_schedule',
            'loan_closure.view', 'loan_closure.calculate', 'loan_settlement.request', 'loan_closure.certificate',
            'collection.view', 'loan.collection.view', 'loan.collection.create', 'loan.collection.receipt', 'loan.collection.history', 'loan.record_repayment',
            'overdue.view', 'dpd.view',
            'savings.view',
        ];
        Role::findByName('Loan Officer', 'web')->syncPermissions($loanOfficerPermissions);
        Role::findByName('Field Officer', 'web')->syncPermissions($loanOfficerPermissions);

        // 6. Collection Officer & Cashier: Cash Receipts, EMI Collection & DPD/Overdue Lookup
        $collectionOfficerPermissions = [
            'company.view', 'branch.view',
            'customer.view',
            'loan.view', 'loan.view_schedule',
            'collection.view', 'loan.collection.view', 'loan.collection.create', 'loan.collection.receipt', 'loan.collection.history', 'loan.record_repayment',
            'overdue.view', 'dpd.view',
            'penalty.view',
            'savings.view',
        ];
        Role::findByName('Collection Officer', 'web')->syncPermissions($collectionOfficerPermissions);
        Role::findByName('Cashier', 'web')->syncPermissions($collectionOfficerPermissions);

        // 7. Accountant & Auditor: Accounting, General Ledger, Vouchers & Financial Statements
        $accountantPermissions = [
            'company.view', 'branch.view',
            'customer.view',
            'loan.view', 'loan.view_schedule',
            'loan_closure.view', 'loan_closure.calculate', 'loan_foreclosure.process', 'loan_closure.certificate',
            'collection.view', 'loan.collection.view', 'loan.collection.history',
            'overdue.view', 'dpd.view',
            'penalty.view',
            'accounting.view', 'accounting.dashboard',
            'chart_of_accounts.view', 'chart_of_accounts.create', 'chart_of_accounts.edit',
            'bank_accounts.view', 'bank_accounts.create', 'bank_accounts.edit',
            'voucher.view', 'voucher.create', 'voucher.post', 'voucher.reverse',
            'reports.view', 'reports.export',
        ];
        Role::findByName('Accountant', 'web')->syncPermissions($accountantPermissions);
        Role::findByName('Auditor', 'web')->syncPermissions([
            'company.view', 'branch.view', 'customer.view', 'group.view', 'loan_scheme.view', 'loan_application.view', 'loan.view', 'loan.view_schedule', 'loan_closure.view', 'loan_closure.certificate', 'collection.view', 'loan.collection.view', 'loan.collection.history', 'overdue.view', 'dpd.view', 'penalty.view', 'product.view', 'inventory.view', 'purchase.view', 'accounting.view', 'accounting.dashboard', 'chart_of_accounts.view', 'bank_accounts.view', 'voucher.view', 'employee.view', 'attendance.view', 'leave.view', 'payroll.view', 'reports.view', 'reports.export',
        ]);

        // 8. Inventory Manager: Products, Catalog, Branch Stock, Transfers & Purchases
        Role::findByName('Inventory Manager', 'web')->syncPermissions([
            'company.view', 'branch.view',
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'product_brand.view', 'product_brand.create', 'product_brand.edit', 'product_brand.delete',
            'product_category.view', 'product_category.create', 'product_category.edit', 'product_category.delete',
            'inventory.view', 'inventory.manage', 'inventory.adjust', 'inventory.restock',
            'inventory.transfer.view', 'inventory.transfer.create', 'inventory.transfer.approve', 'inventory.transfer.reject', 'inventory.transfer.dispatch', 'inventory.transfer.receive', 'inventory.transfer.cancel',
            'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.confirm', 'purchase.receive', 'purchase.cancel',
            'loan.view', 'loan.issue_product',
            'reports.view', 'reports.export',
        ]);

        // 9. HR Manager: Employees, Departments, Designations, Attendance, Leave, Payroll
        Role::findByName('HR Manager', 'web')->syncPermissions([
            'company.view', 'branch.view',
            'department.view', 'department.create', 'department.edit', 'department.delete', 'department.restore', 'department.toggle_status',
            'designation.view', 'designation.create', 'designation.edit', 'designation.delete', 'designation.restore', 'designation.toggle_status',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.restore', 'employee.toggle_status',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'leave.view', 'leave.create', 'leave.approve', 'leave.reject', 'leave.delete',
            'payroll.view', 'payroll.process', 'payroll.disburse',
            'hr_letter.view', 'hr_letter.generate',
            'hr_reports.view',
            'reports.view', 'reports.export',
        ]);

        // 10. Viewer / Report Viewer: Read-only Dashboard & Reports
        $viewerPermissions = [
            'company.view', 'branch.view',
            'customer.view',
            'group.view',
            'loan_scheme.view',
            'loan_application.view',
            'loan.view', 'loan.view_schedule',
            'collection.view', 'loan.collection.view', 'loan.collection.history',
            'overdue.view', 'dpd.view',
            'product.view',
            'inventory.view',
            'reports.view',
        ];
        Role::findByName('Viewer', 'web')->syncPermissions($viewerPermissions);
        Role::findByName('Report Viewer', 'web')->syncPermissions(array_merge($viewerPermissions, ['reports.export', 'hr_reports.view']));
    }
}
