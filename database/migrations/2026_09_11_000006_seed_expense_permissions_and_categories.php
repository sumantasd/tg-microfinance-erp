<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\ChartOfAccount;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $expensePermissions = [
            'expense.view',
            'expense.create',
            'expense.edit',
            'expense.submit',
            'expense.approve',
            'expense.reject',
            'expense.pay',
            'expense.cancel',
            'expense.delete',
            'expense.category.manage',
            'expense.report.view',
            'expense.report.export',
        ];

        foreach ($expensePermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Assign to Super Admin & Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($expensePermissions);
        }

        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo($expensePermissions);
        }

        $companyAdmin = Role::where('name', 'Company Admin')->first();
        if ($companyAdmin) {
            $companyAdmin->givePermissionTo($expensePermissions);
        }

        $branchManager = Role::where('name', 'Branch Manager')->first();
        if ($branchManager) {
            $branchManager->givePermissionTo([
                'expense.view',
                'expense.create',
                'expense.edit',
                'expense.submit',
                'expense.approve',
                'expense.reject',
                'expense.report.view',
            ]);
        }

        $accountant = Role::where('name', 'Accountant')->first();
        if ($accountant) {
            $accountant->givePermissionTo([
                'expense.view',
                'expense.create',
                'expense.edit',
                'expense.pay',
                'expense.report.view',
                'expense.report.export',
            ]);
        }

        // Seed initial Expense Categories for all companies
        $companies = Company::all();
        foreach ($companies as $comp) {
            $this->seedCategoriesForCompany($comp->id);
        }
    }

    private function seedCategoriesForCompany(int $companyId): void
    {
        $categories = [
            [
                'name' => 'Office Rent',
                'code' => 'EXP-RENT',
                'gl' => '5310',
                'desc' => 'Branch & Head office rental expense',
            ],
            [
                'name' => 'Electricity & Utilities',
                'code' => 'EXP-UTIL',
                'gl' => '5320',
                'desc' => 'Power, electricity, and water bills',
            ],
            [
                'name' => 'Internet & Telephone',
                'code' => 'EXP-TEL',
                'gl' => '5300',
                'desc' => 'Broadband, landline, and mobile recharges',
            ],
            [
                'name' => 'Staff Salary & Allowances',
                'code' => 'EXP-SALARY',
                'gl' => '5210',
                'desc' => 'Staff payroll and allowances',
            ],
            [
                'name' => 'Staff Travel & Fuel',
                'code' => 'EXP-TRAVEL',
                'gl' => '5340',
                'desc' => 'Field collection travel and fuel reimbursement',
            ],
            [
                'name' => 'Office Supplies & Stationery',
                'code' => 'EXP-SUPPLIES',
                'gl' => '5330',
                'desc' => 'Paper, pens, printing, and thermal rolls',
            ],
            [
                'name' => 'Marketing & Advertising',
                'code' => 'EXP-MKT',
                'gl' => '5300',
                'desc' => 'Promotional banners, flyers, and digital ads',
            ],
            [
                'name' => 'Maintenance & Repairs',
                'code' => 'EXP-MAINT',
                'gl' => '5300',
                'desc' => 'Office, furniture, and IT equipment repairs',
            ],
            [
                'name' => 'Bank Charges & Fees',
                'code' => 'EXP-BANK',
                'gl' => '5350',
                'desc' => 'Bank transaction fees and gateway charges',
            ],
            [
                'name' => 'Legal & Professional Fees',
                'code' => 'EXP-LEGAL',
                'gl' => '5300',
                'desc' => 'Consultant fees, CA fees, legal expenses',
            ],
            [
                'name' => 'Miscellaneous',
                'code' => 'EXP-MISC',
                'gl' => '5000',
                'desc' => 'Other general office operational expenses',
            ],
        ];

        foreach ($categories as $cat) {
            $glAccount = ChartOfAccount::where('company_id', $companyId)
                ->where('account_code', $cat['gl'])
                ->first();

            ExpenseCategory::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'category_name' => $cat['name'],
                ],
                [
                    'category_code' => $cat['code'],
                    'chart_of_account_id' => $glAccount?->id,
                    'description' => $cat['desc'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Permissions do not need hard rollback
    }
};
