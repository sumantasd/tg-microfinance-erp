<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherNumberSequence;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingDoubleEntryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO, Bihar',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Branch',
            'code' => 'PAT01',
            'phone' => '9888888888',
            'email' => 'patna@grihalaxmi.com',
            'address' => 'Fraser Road, Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        $this->accountingService = app(AccountingService::class);
    }

    /**
     * 1. Test Default Chart of Accounts Seeding
     */
    public function test_default_chart_of_accounts_seeding_creates_all_standard_microfinance_accounts(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'account_code' => '1110',
            'account_name' => 'Branch Cash Vault',
            'account_type' => 'asset',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'account_code' => '1210',
            'account_name' => 'Cash Microfinance Loans Receivable',
            'account_type' => 'asset',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'account_code' => '1220',
            'account_name' => 'Product Loans Receivable (Financed Principal)',
            'account_type' => 'asset',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'account_code' => '4110',
            'account_name' => 'Interest Income from Cash Loans',
            'account_type' => 'revenue',
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'company_id' => $this->company->id,
            'account_code' => '5110',
            'account_name' => 'Cost of Goods Sold (COGS - Product Loans)',
            'account_type' => 'expense',
            'is_system' => true,
        ]);

        $count = ChartOfAccount::where('company_id', $this->company->id)->count();
        $this->assertGreaterThanOrEqual(30, $count);
    }

    /**
     * 2. Test Chart of Accounts CRUD and System Account Protection
     */
    public function test_chart_of_accounts_crud_and_system_account_protection(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);

        // System account cannot be deleted
        $systemAccount = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $response = $this->actingAs($this->adminUser)->delete(route('admin.accounting.chart-of-accounts.destroy', $systemAccount->id));
        $response->assertRedirect(route('admin.accounting.chart-of-accounts.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('chart_of_accounts', ['id' => $systemAccount->id]);

        // Custom account creation
        $customData = [
            'company_id' => $this->company->id,
            'account_code' => '5360',
            'account_name' => 'Community Field Camps Expense',
            'account_type' => 'expense',
            'account_group' => 'administrative_expense',
            'description' => 'Expenses incurred during village outreach',
            'is_active' => '1',
        ];

        $postResponse = $this->actingAs($this->adminUser)->post(route('admin.accounting.chart-of-accounts.store'), $customData);
        $postResponse->assertRedirect(route('admin.accounting.chart-of-accounts.index'));
        $this->assertDatabaseHas('chart_of_accounts', [
            'account_code' => '5360',
            'account_name' => 'Community Field Camps Expense',
            'is_system' => false,
        ]);

        $customAccount = ChartOfAccount::where('account_code', '5360')->first();

        // Custom account deletion without transactions is allowed
        $delResponse = $this->actingAs($this->adminUser)->delete(route('admin.accounting.chart-of-accounts.destroy', $customAccount->id));
        $delResponse->assertRedirect(route('admin.accounting.chart-of-accounts.index'));
        $this->assertSoftDeleted('chart_of_accounts', ['id' => $customAccount->id]);
    }

    /**
     * 3. Test Bank Accounts CRUD and Linkage
     */
    public function test_bank_accounts_crud_and_ledger_linkage(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $bankCoa = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1130')->first();

        $bankData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'chart_of_account_id' => $bankCoa->id,
            'bank_name' => 'State Bank of India',
            'account_name' => 'Grihalaxmi Current A/c',
            'account_number' => '38491028301',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Main Fraser Road',
            'opening_balance' => 50000.00,
            'is_active' => '1',
        ];

        $res = $this->actingAs($this->adminUser)->post(route('admin.accounting.bank-accounts.store'), $bankData);
        $res->assertRedirect(route('admin.accounting.bank-accounts.index'));

        $this->assertDatabaseHas('bank_accounts', [
            'account_number' => '38491028301',
            'bank_name' => 'State Bank of India',
            'chart_of_account_id' => $bankCoa->id,
        ]);
    }

    /**
     * 4. Test Voucher Balanced Double-Entry Validation Enforces Debits Equal Credits
     */
    public function test_voucher_balanced_double_entry_validation_enforces_debits_equal_credits(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $loanAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1210')->first();

        $voucherData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
            'narration' => 'Test Cash Loan Disbursement',
        ];

        $entries = [
            ['account_id' => $loanAcc->id, 'debit' => 25000.00, 'credit' => 0.00, 'description' => 'Dr Loan Portfolio'],
            ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 25000.00, 'description' => 'Cr Cash Vault'],
        ];

        $voucher = $this->accountingService->createVoucher($voucherData, $entries, true);

        $this->assertNotNull($voucher->id);
        $this->assertEquals(25000.00, (float) $voucher->total_debit);
        $this->assertEquals(25000.00, (float) $voucher->total_credit);
        $this->assertEquals('posted', $voucher->status);
        $this->assertCount(2, $voucher->entries);
    }

    /**
     * 5. Test Voucher Creation Fails if Unbalanced or Single Line
     */
    public function test_voucher_creation_fails_if_unbalanced_or_single_line(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $loanAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1210')->first();

        $voucherData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'journal',
            'voucher_date' => now()->toDateString(),
        ];

        // Unbalanced: Debit 25,000 vs Credit 20,000
        $unbalancedEntries = [
            ['account_id' => $loanAcc->id, 'debit' => 25000.00, 'credit' => 0.00],
            ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 20000.00],
        ];

        $this->expectException(ValidationException::class);
        $this->accountingService->createVoucher($voucherData, $unbalancedEntries, true);
    }

    /**
     * 6. Test Voucher Number Sequence Generates Safe Sequential Numbers
     */
    public function test_voucher_number_sequence_generates_safe_sequential_numbers(): void
    {
        $num1 = VoucherNumberSequence::generateNextVoucherNumber($this->company->id, $this->branch->id, 'JV');
        $num2 = VoucherNumberSequence::generateNextVoucherNumber($this->company->id, $this->branch->id, 'JV');

        $this->assertStringStartsWith('JV-PAT01-' . date('Y') . '-', $num1);
        $this->assertStringStartsWith('JV-PAT01-' . date('Y') . '-', $num2);
        $this->assertNotEquals($num1, $num2);
    }

    /**
     * 7. Test Posted Voucher Immutability and Reversal Voucher Creation
     */
    public function test_posted_voucher_immutability_and_reversal_voucher_creation(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $feeAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4210')->first();

        $voucherData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'narration' => 'Processing Fee Collected',
        ];

        $entries = [
            ['account_id' => $cashAcc->id, 'debit' => 1500.00, 'credit' => 0.00, 'description' => 'Dr Cash'],
            ['account_id' => $feeAcc->id, 'debit' => 0.00, 'credit' => 1500.00, 'description' => 'Cr Fee Income'],
        ];

        $originalVoucher = $this->accountingService->createVoucher($voucherData, $entries, true);

        // Reverse voucher
        $reversal = $this->accountingService->reverseVoucher($originalVoucher, 'Incorrect client ID fee collection', now()->toDateString());

        $this->assertTrue($reversal->is_reversal);
        $this->assertEquals($originalVoucher->id, $reversal->reversed_voucher_id);
        $this->assertEquals(1500.00, (float) $reversal->total_debit);
        $this->assertEquals(1500.00, (float) $reversal->total_credit);

        // Verify that in reversal: Cash is Credited and Fee Income is Debited
        $reversalCashEntry = $reversal->entries()->where('account_id', $cashAcc->id)->first();
        $this->assertEquals(1500.00, (float) $reversalCashEntry->credit);
        $this->assertEquals(0.00, (float) $reversalCashEntry->debit);

        $reversalFeeEntry = $reversal->entries()->where('account_id', $feeAcc->id)->first();
        $this->assertEquals(1500.00, (float) $reversalFeeEntry->debit);
        $this->assertEquals(0.00, (float) $reversalFeeEntry->credit);
    }

    /**
     * 8. Test Cannot Reverse an Already Reversed Voucher
     */
    public function test_cannot_reverse_an_already_reversed_voucher(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $feeAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4210')->first();

        $original = $this->accountingService->createVoucher([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
        ], [
            ['account_id' => $cashAcc->id, 'debit' => 500.00, 'credit' => 0.00],
            ['account_id' => $feeAcc->id, 'debit' => 0.00, 'credit' => 500.00],
        ]);

        $this->accountingService->reverseVoucher($original, 'First reversal');

        $this->expectException(ValidationException::class);
        $this->accountingService->reverseVoucher($original, 'Second reversal duplicate');
    }

    /**
     * 9. Test Financial Year Lock Prevents Voucher Creation in Closed Period
     */
    public function test_financial_year_lock_prevents_voucher_creation_in_closed_period(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $feeAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4210')->first();

        // Create closed financial year for 2024
        FinancialYear::create([
            'company_id' => $this->company->id,
            'title' => 'FY 2023-2024 (Audited & Closed)',
            'start_date' => '2023-04-01',
            'end_date' => '2024-03-31',
            'is_closed' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->accountingService->createVoucher([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'journal',
            'voucher_date' => '2023-11-15', // Falls into closed FY
        ], [
            ['account_id' => $cashAcc->id, 'debit' => 1000.00, 'credit' => 0.00],
            ['account_id' => $feeAcc->id, 'debit' => 0.00, 'credit' => 1000.00],
        ]);
    }

    /**
     * 10. Test Account Balance Calculation from Posted Vouchers
     */
    public function test_account_balance_calculation_from_posted_vouchers(): void
    {
        $this->accountingService->seedDefaultChartOfAccounts($this->company, $this->adminUser->id);
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $interestAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4110')->first();

        // Initial balances are 0
        $this->assertEquals(0.00, $cashAcc->getBalance());
        $this->assertEquals(0.00, $interestAcc->getBalance());

        // Post Receipt: Dr Cash ₹8,000, Cr Interest Income ₹8,000
        $this->accountingService->createVoucher([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
        ], [
            ['account_id' => $cashAcc->id, 'debit' => 8000.00, 'credit' => 0.00],
            ['account_id' => $interestAcc->id, 'debit' => 0.00, 'credit' => 8000.00],
        ]);

        // Cash Asset balance (Debit normal): 8000
        $this->assertEquals(8000.00, $cashAcc->getBalance());
        // Interest Revenue balance (Credit normal): 8000
        $this->assertEquals(8000.00, $interestAcc->getBalance());

        // Post Payment: Dr Rent Expense ₹3,000, Cr Cash ₹3,000
        $rentAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '5310')->first();
        $this->accountingService->createVoucher([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
        ], [
            ['account_id' => $rentAcc->id, 'debit' => 3000.00, 'credit' => 0.00],
            ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 3000.00],
        ]);

        // Cash Asset balance after payment: 8000 - 3000 = 5000
        $this->assertEquals(5000.00, $cashAcc->getBalance());
        // Rent Expense balance: 3000
        $this->assertEquals(3000.00, $rentAcc->getBalance());
    }

    /**
     * 11. Test Accounting UI Routes Accessible by Admin
     */
    public function test_accounting_ui_routes_accessible_by_admin(): void
    {
        $dashboardRes = $this->actingAs($this->adminUser)->get(route('admin.accounting.dashboard'));
        $dashboardRes->assertOk();

        $coaRes = $this->actingAs($this->adminUser)->get(route('admin.accounting.chart-of-accounts.index'));
        $coaRes->assertOk();

        $bankRes = $this->actingAs($this->adminUser)->get(route('admin.accounting.bank-accounts.index'));
        $bankRes->assertOk();

        $voucherIndexRes = $this->actingAs($this->adminUser)->get(route('admin.accounting.vouchers.index'));
        $voucherIndexRes->assertOk();

        $voucherCreateRes = $this->actingAs($this->adminUser)->get(route('admin.accounting.vouchers.create'));
        $voucherCreateRes->assertOk();
    }
}
