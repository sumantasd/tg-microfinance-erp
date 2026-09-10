<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpensePayment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Branch $warehouseBranch;
    protected User $superAdmin;
    protected User $branchManager;
    protected User $loanOfficer;
    protected ExpenseCategory $category;
    protected ChartOfAccount $expenseCoa;
    protected ChartOfAccount $cashCoa;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Base Company & Branches
        $this->company = Company::create([
            'name' => 'Grihalaxmi Microfinance Ltd',
            'code' => 'GML001',
            'email' => 'info@grihalaxmi.com',
            'phone' => '03324567890',
            'address' => 'Kolkata, WB',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Central Branch',
            'code' => 'KOL001',
            'email' => 'kolkata@grihalaxmi.com',
            'phone' => '03324567891',
            'address' => 'Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_warehouse' => false,
            'is_active' => true,
        ]);

        $this->warehouseBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Central Equipment Warehouse',
            'code' => 'CWH001',
            'email' => 'warehouse@grihalaxmi.com',
            'phone' => '03324567892',
            'address' => 'Central Hub',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_warehouse' => true,
            'is_active' => true,
        ]);

        // 2. Create Permissions & Roles
        $permissions = [
            'expense.view',
            'expense.create',
            'expense.edit',
            'expense.delete',
            'expense.submit',
            'expense.approve',
            'expense.reject',
            'expense.pay',
            'expense.cancel',
            'expense.category.manage',
            'expense.report.view',
            'expense.report.export',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->givePermissionTo(Permission::all());

        $branchManagerRole = Role::findOrCreate('Branch Manager', 'web');
        $branchManagerRole->givePermissionTo([
            'expense.view', 'expense.create', 'expense.edit', 'expense.submit',
            'expense.approve', 'expense.reject', 'expense.pay', 'expense.report.view',
        ]);

        $loanOfficerRole = Role::findOrCreate('Loan Officer', 'web');

        // 3. Create Users
        $this->superAdmin = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'superadmin@test.com',
        ]);
        $this->superAdmin->assignRole($superAdminRole);

        $this->branchManager = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'bm@test.com',
        ]);
        $this->branchManager->assignRole($branchManagerRole);

        $this->loanOfficer = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'email' => 'loanofficer@test.com',
        ]);
        $this->loanOfficer->assignRole($loanOfficerRole);

        // 4. Create COAs
        $this->expenseCoa = ChartOfAccount::create([
            'company_id' => $this->company->id,
            'account_code' => '5300',
            'account_name' => 'General Operating Expense',
            'account_type' => 'expense',
            'account_group' => 'Operating Expense',
            'is_active' => true,
        ]);

        $this->cashCoa = ChartOfAccount::create([
            'company_id' => $this->company->id,
            'account_code' => '1000',
            'account_name' => 'Main Cash Vault',
            'account_type' => 'asset',
            'account_group' => 'Current Assets',
            'is_active' => true,
        ]);

        // 5. Create Category
        $this->category = ExpenseCategory::create([
            'company_id' => $this->company->id,
            'category_name' => 'Office Supplies',
            'category_code' => 'CAT-OFFICE',
            'chart_of_account_id' => $this->expenseCoa->id,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access_expense_management(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get(route('admin.expenses.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_expense_index_and_dashboard(): void
    {
        $response = $this->actingAs($this->branchManager)->get(route('admin.expenses.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.expenses.index');

        $dashResponse = $this->actingAs($this->branchManager)->get(route('admin.expenses.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertViewIs('admin.expenses.dashboard');
    }

    public function test_can_create_expense_category(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.expenses.categories.store'), [
            'category_name' => 'Travel & Transport',
            'category_code' => 'CAT-TRAVEL',
            'chart_of_account_id' => $this->expenseCoa->id,
            'description' => 'Local travel allowances',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.expenses.categories.index'));
        $this->assertDatabaseHas('expense_categories', [
            'category_name' => 'Travel & Transport',
            'category_code' => 'CAT-TRAVEL',
        ]);
    }

    public function test_cannot_create_expense_for_warehouse_branch(): void
    {
        $response = $this->actingAs($this->branchManager)->post(route('admin.expenses.store'), [
            'branch_id' => $this->warehouseBranch->id,
            'expense_category_id' => $this->category->id,
            'expense_date' => now()->toDateString(),
            'description' => 'Warehouse maintenance',
            'amount' => 1500.00,
            'tax_amount' => 0.00,
        ]);

        $response->assertSessionHasErrors(['branch_id']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_can_create_draft_expense_and_auto_generate_number(): void
    {
        $response = $this->actingAs($this->branchManager)->post(route('admin.expenses.store'), [
            'branch_id' => $this->branch->id,
            'expense_category_id' => $this->category->id,
            'expense_date' => '2026-09-10',
            'payee_name' => 'XYZ Stationers',
            'description' => 'Paper, pens, and printer ink',
            'amount' => 2000.00,
            'tax_amount' => 360.00,
        ]);

        $expense = Expense::first();
        $this->assertNotNull($expense);
        $this->assertEquals(2360.00, (float)$expense->total_amount);
        $this->assertEquals(2360.00, (float)$expense->outstanding_amount);
        $this->assertEquals('DRAFT', $expense->status);
        $this->assertEquals('UNPAID', $expense->payment_status);
        $this->assertStringStartsWith('EXP-' . date('Y') . '-', $expense->expense_number);

        $response->assertRedirect(route('admin.expenses.show', $expense->id));
    }

    public function test_expense_full_lifecycle_submit_approve_and_pay(): void
    {
        // 1. Create Expense
        $expense = Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-2026-000001',
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $this->category->id,
            'payee_name' => 'ABC Cleaners',
            'description' => 'Branch Deep Cleaning Service',
            'amount' => 5000.00,
            'tax_amount' => 0.00,
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'outstanding_amount' => 5000.00,
            'status' => Expense::STATUS_DRAFT,
            'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
            'requested_by' => $this->branchManager->id,
            'created_by' => $this->branchManager->id,
            'updated_by' => $this->branchManager->id,
        ]);

        // 2. Submit for Approval
        $submitRes = $this->actingAs($this->branchManager)->post(route('admin.expenses.submit', $expense->id));
        $submitRes->assertSessionHasNoErrors();
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $expense->fresh()->status);

        // 3. Approve Expense
        $approveRes = $this->actingAs($this->superAdmin)->post(route('admin.expenses.approve', $expense->id));
        $approveRes->assertSessionHasNoErrors();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->fresh()->status);

        // 4. Record Partial Payment (₹2,000)
        $payRes = $this->actingAs($this->branchManager)->post(route('admin.expenses.pay', $expense->id), [
            'paid_amount' => 2000.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'transaction_reference' => 'CASH-REC-101',
        ]);
        $payRes->assertSessionHasNoErrors();

        $updatedExpense = $expense->fresh();
        $this->assertEquals(2000.00, (float)$updatedExpense->paid_amount);
        $this->assertEquals(3000.00, (float)$updatedExpense->outstanding_amount);
        $this->assertEquals(Expense::STATUS_PARTIALLY_PAID, $updatedExpense->status);
        $this->assertEquals(Expense::PAYMENT_STATUS_PARTIALLY_PAID, $updatedExpense->payment_status);

        // Verify Double-Entry Journal Voucher created
        $this->assertDatabaseHas('vouchers', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'payment',
            'reference_type' => 'expense_payment',
        ]);

        $voucher = Voucher::where('reference_type', 'expense_payment')->first();
        $this->assertNotNull($voucher);
        $this->assertCount(2, $voucher->entries);

        // 5. Pay Remaining Balance (₹3,000)
        $finalPayRes = $this->actingAs($this->branchManager)->post(route('admin.expenses.pay', $expense->id), [
            'paid_amount' => 3000.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $finalPayRes->assertSessionHasNoErrors();

        $finalExpense = $expense->fresh();
        $this->assertEquals(5000.00, (float)$finalExpense->paid_amount);
        $this->assertEquals(0.00, (float)$finalExpense->outstanding_amount);
        $this->assertEquals(Expense::STATUS_PAID, $finalExpense->status);
        $this->assertEquals(Expense::PAYMENT_STATUS_PAID, $finalExpense->payment_status);
    }

    public function test_overpayment_is_prevented(): void
    {
        $expense = Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-2026-000002',
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $this->category->id,
            'description' => 'Test Expense',
            'amount' => 1000.00,
            'tax_amount' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 0.00,
            'outstanding_amount' => 1000.00,
            'status' => Expense::STATUS_APPROVED,
            'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
            'requested_by' => $this->branchManager->id,
            'created_by' => $this->branchManager->id,
            'updated_by' => $this->branchManager->id,
        ]);

        $response = $this->actingAs($this->branchManager)->post(route('admin.expenses.pay', $expense->id), [
            'paid_amount' => 1500.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors(['paid_amount']);
        $this->assertEquals(0.00, (float)$expense->fresh()->paid_amount);
    }

    public function test_expense_cancellation_reverses_accounting_vouchers(): void
    {
        $expense = Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'expense_number' => 'EXP-2026-000003',
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $this->category->id,
            'description' => 'Utility Bill',
            'amount' => 4000.00,
            'tax_amount' => 0.00,
            'total_amount' => 4000.00,
            'paid_amount' => 0.00,
            'outstanding_amount' => 4000.00,
            'status' => Expense::STATUS_APPROVED,
            'payment_status' => Expense::PAYMENT_STATUS_UNPAID,
            'requested_by' => $this->branchManager->id,
            'created_by' => $this->branchManager->id,
            'updated_by' => $this->branchManager->id,
        ]);

        // Record payment
        $this->actingAs($this->branchManager)->post(route('admin.expenses.pay', $expense->id), [
            'paid_amount' => 4000.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $voucher = Voucher::where('reference_type', 'expense_payment')->first();
        $this->assertEquals('posted', $voucher->status);

        // Cancel Expense
        $cancelRes = $this->actingAs($this->superAdmin)->post(route('admin.expenses.cancel', $expense->id), [
            'cancellation_reason' => 'Duplicate payment error',
        ]);

        $cancelRes->assertSessionHasNoErrors();
        $this->assertEquals(Expense::STATUS_CANCELLED, $expense->fresh()->status);
        $this->assertDatabaseHas('vouchers', [
            'reversed_voucher_id' => $voucher->id,
            'is_reversal' => true,
        ]);
    }

    public function test_can_view_expense_reports_and_export_csv(): void
    {
        $response = $this->actingAs($this->branchManager)->get(route('admin.expenses.reports.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.expenses.reports.index');

        $exportRes = $this->actingAs($this->superAdmin)->get(route('admin.expenses.reports.export'));
        $exportRes->assertStatus(200);
        $exportRes->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
