<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BranchManagerRoleSeeder extends Seeder
{
    /**
     * Exact permissions intended for Branch Manager role as specified in project RBAC requirements.
     */
    public static function getPermissions(): array
    {
        return [
            // 1. Branch Details (View assigned branch only)
            'branch.view',

            // 2. Customer & Member Management
            'customer.view', 'customer.create', 'customer.edit', 'customer.verify_kyc',
            'customer.manage_guarantor', 'customer.manage_nominee', 'customer.change_status',

            // 3. Customer Groups
            'group.view', 'group.create', 'group.edit', 'group.change_status',
            'group.manage_members', 'group.assign_leader',

            // 4. Loan Applications
            'loan_application.view', 'loan_application.create', 'loan_application.edit',
            'loan_application.submit', 'loan_application.review', 'loan_application.approve',
            'loan_application.reject',

            // 5. Loan Accounts & Operations
            'loan.view', 'loan.create', 'loan.sanction', 'loan.disburse',
            'loan.issue_product', 'loan.view_schedule', 'loan.record_down_payment',
            'loan.record_repayment',

            // 6. Loan Closures & Settlements
            'loan_closure.view', 'loan_closure.calculate', 'loan_foreclosure.process',
            'loan_settlement.request', 'loan_settlement.approve', 'loan_closure.certificate',

            // 7. EMI Collection
            'collection.view', 'loan.collection.view', 'loan.collection.create',
            'loan.collection.receipt', 'loan.collection.history',

            // 8. Overdue, DPD & PAR
            'overdue.view', 'dpd.view', 'overdue.branch_report',

            // 9. Penalty Management & Waivers
            'penalty.view', 'penalty.waive', 'loans.waive_penalty', 'loan.waive_penalty',

            // 10. Branch Inventory & Stock Transfers (Local Branch Only)
            'inventory.view',
            'inventory.transfer.view', 'inventory.transfer.receive',

            // 11. Enterprise HRM (Branch Staff Supervision Only)
            'employee.view',
            'attendance.view',
            'leave.view', 'leave.create',
            'payroll.view', 'hr_letter.view', 'hr_letter.generate',

            // 12. Savings & Products
            'savings.view',

            // 13. Cash Book Register
            'cashbook.view', 'cashbook.create', 'cashbook.edit',
            'cashbook.close', 'cashbook.print', 'cashbook.export',

            // 14. Bank Deposits
            'bank_deposit.view', 'bank_deposit.create',

            // 15. Billing & Invoices
            'billing.view', 'billing.create', 'billing.print', 'billing.pdf',

            // 16. Branch Expenses
            'expense.view', 'expense.create', 'expense.edit',
        ];
    }

    public function run(): void
    {
        // 1. Forget cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = static::getPermissions();

        // 2. Ensure each permission exists in database
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 3. Find or create the Branch Manager role
        $role = Role::firstOrCreate([
            'name' => 'Branch Manager',
            'guard_name' => 'web',
        ]);

        // 4. Sync ONLY Branch Manager role permissions (removes obsolete company.view, product.view, etc.)
        $role->syncPermissions($permissions);

        // 5. Clear permission cache again
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if (isset($this->command)) {
            $this->command->info("Branch Manager role permissions successfully synced (" . count($permissions) . " permissions).");
        }
    }
}
