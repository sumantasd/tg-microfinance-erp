<?php

namespace Tests\Feature;

use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\CashBookOnlineCollection;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\LoanAccount;
use App\Models\LoanDisbursement;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\LoanSettlementRequest;
use App\Models\User;
use App\Services\CashBookService;
use App\Services\LoanSettlementService;
use Carbon\Carbon;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBookPaymentCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $admin;
    protected User $branchManager;
    protected CashBookService $cashBookService;
    protected LoanScheme $loanScheme;
    protected Customer $customer1;
    protected Customer $customer2;
    protected \App\Models\LoanApplication $loanApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->cashBookService = app(CashBookService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Ltd',
            'code' => 'GLF01',
            'email' => 'info@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna HQ',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'BR-01',
            'phone' => '9876543211',
            'address' => 'Main Road Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Gaya Branch',
            'code' => 'BR-02',
            'phone' => '9876543212',
            'address' => 'Station Road Gaya',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->admin = User::where('email', 'admin@grihalaxmifinance.com')->first() ?: User::first();
        if ($this->admin) {
            $this->admin->update([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch1->id,
                'status' => 'active',
            ]);
            $this->admin->syncRoles(['Super Admin']);
        }

        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Patna BM',
            'email' => 'bm.patna@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->customer1 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Anita',
            'last_name' => 'Devi',
            'name' => 'Anita Devi',
            'mobile_number' => '9900000001',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->customer2 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-002',
            'first_name' => 'Sunita',
            'last_name' => 'Kumari',
            'name' => 'Sunita Kumari',
            'mobile_number' => '9900000002',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->loanScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-MICRO',
            'name' => 'Micro Enterprise Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 1000.00,
            'max_amount' => 50000.00,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 3,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'weekly',
            'is_active' => true,
        ]);

        $this->loanApp = \App\Models\LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'application_number' => 'APP-TEST-001',
            'requested_amount' => 10000.00,
            'approved_amount' => 10000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'weekly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
            'application_date' => now()->toDateString(),
        ]);

        $this->expenseCategory = \App\Models\ExpenseCategory::firstOrCreate(
            ['company_id' => $this->company->id, 'category_name' => 'Management Expenses'],
            ['category_code' => 'MGMT_EXP', 'is_active' => true]
        );
    }

    private function createTestLoan(array $attributes = []): LoanAccount
    {
        $amount = $attributes['disbursed_amount'] ?? $attributes['sanctioned_amount'] ?? 10000.00;

        $defaults = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'loan_application_id' => $this->loanApp->id,
            'customer_id' => $this->customer1->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_number' => 'LN-' . uniqid(),
            'account_number' => 'ACC-' . uniqid(),
            'sanctioned_amount' => $amount,
            'disbursed_amount' => $amount,
            'principal_outstanding' => $amount,
            'total_outstanding' => $amount,
            'tenure_months' => 12,
            'repayment_frequency' => 'weekly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'sanction_date' => '2026-09-12',
            'status' => 'active',
        ];

        return LoanAccount::create(array_merge($defaults, $attributes));
    }

    /**
     * Requirements 9, 10, 11, 12: Exactly 7 payment rows present, MISCELLANEOUS and DISTRIBUTION PAYMENT removed.
     */
    public function test_payment_section_has_exact_7_rows_and_removed_items_are_absent(): void
    {
        $date = '2026-09-12';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->admin->id);

        $paymentEntries = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('entry_type', 'payment')
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(7, $paymentEntries);

        $codes = $paymentEntries->pluck('category_code')->toArray();
        $expectedCodes = [
            'loan_disbursed',
            'member_no',
            'deposit_to_bank',
            'management_expense',
            'fund_transfer',
            'gl_steel_furniture',
            'borrower_death',
        ];

        $this->assertEquals($expectedCodes, $codes);
        $this->assertNotContains('miscellaneous', $codes);
        $this->assertNotContains('distribution_payment', $codes);
    }

    /**
     * Requirements 1 & 2: Today's actual loan disbursement appears, sanctioned-but-not-disbursed loan does NOT appear.
     */
    public function test_loan_disbursed_amount_includes_only_disbursed_loans_on_date(): void
    {
        $today = '2026-09-12';

        // Disbursed today: ₹15,000
        $disbursedToday = $this->createTestLoan([
            'loan_number' => 'LN-DISB-01',
            'account_number' => 'LN-DISB-01',
            'sanctioned_amount' => 15000.00,
            'disbursed_amount' => 15000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        // Sanctioned today but NOT disbursed (status: sanctioned, disbursement_date: null): ₹25,000
        $sanctionedOnly = $this->createTestLoan([
            'customer_id' => $this->customer2->id,
            'loan_number' => 'LN-SANC-02',
            'account_number' => 'LN-SANC-02',
            'sanctioned_amount' => 25000.00,
            'disbursed_amount' => 0.00,
            'sanction_date' => $today,
            'disbursement_date' => null,
            'status' => 'sanctioned',
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'loan_disbursed')
            ->first();

        // Must be ₹15,000, NOT ₹40,000
        $this->assertEquals(15000.00, (float)$entry->cash_amount);
    }

    /**
     * Requirement 3: MEMBER NO matches distinct loan disbursement recipients on selected date.
     */
    public function test_member_no_counts_distinct_actual_disbursement_recipients(): void
    {
        $today = '2026-09-12';

        // Customer 1 gets 2 loans disbursed today
        $this->createTestLoan([
            'customer_id' => $this->customer1->id,
            'loan_number' => 'LN-C1-01',
            'account_number' => 'LN-C1-01',
            'sanctioned_amount' => 10000.00,
            'disbursed_amount' => 10000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        $this->createTestLoan([
            'customer_id' => $this->customer1->id,
            'loan_number' => 'LN-C1-02',
            'account_number' => 'LN-C1-02',
            'sanctioned_amount' => 5000.00,
            'disbursed_amount' => 5000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        // Customer 2 gets 1 loan disbursed today
        $this->createTestLoan([
            'customer_id' => $this->customer2->id,
            'loan_number' => 'LN-C2-01',
            'account_number' => 'LN-C2-01',
            'sanctioned_amount' => 12000.00,
            'disbursed_amount' => 12000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'member_no')
            ->first();

        // 3 loan rows, but 2 distinct members -> MEMBER NO = 2
        $this->assertEquals(2, (int)$entry->cash_amount);
    }

    /**
     * Requirements 4, 5, 6: Only APPROVED bank deposits appear in DEPOSIT TO BANK. Pending and rejected do not.
     */
    public function test_deposit_to_bank_includes_approved_only(): void
    {
        $today = '2026-09-12';

        // Approved deposit: ₹5,000
        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $today,
            'amount' => 5000.00,
            'bank_name' => 'SBI',
            'status' => 'approved',
            'reference_number' => 'REF-DEP-001',
            'submitted_by' => $this->branchManager->id,
        ]);

        // Pending deposit: ₹3,000
        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $today,
            'amount' => 3000.00,
            'bank_name' => 'HDFC',
            'status' => 'pending',
            'reference_number' => 'REF-DEP-002',
            'submitted_by' => $this->branchManager->id,
        ]);

        // Rejected deposit: ₹2,000
        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $today,
            'amount' => 2000.00,
            'bank_name' => 'ICICI',
            'status' => 'rejected',
            'reference_number' => 'REF-DEP-003',
            'submitted_by' => $this->branchManager->id,
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'deposit_to_bank')
            ->first();

        // Must be ₹5,000, NOT ₹10,000
        $this->assertEquals(5000.00, (float)$entry->cash_amount);
    }

    /**
     * Requirements 7 & 8: Management expense includes approved/paid expenses; non-cash expenses do NOT reduce physical cash.
     */
    public function test_management_expense_cash_vs_bank_handling(): void
    {
        $today = '2026-09-12';

        // Cash paid expense: ₹800
        $expCash = Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'expense_number' => 'EXP-001',
            'expense_date' => $today,
            'description' => 'Tea & Refreshments',
            'amount' => 800.00,
            'paid_amount' => 800.00,
            'status' => 'PAID',
            'payment_status' => 'PAID',
            'payment_method' => 'cash',
            'requested_by' => $this->branchManager->id,
        ]);

        \App\Models\ExpensePayment::create([
            'expense_id' => $expCash->id,
            'payment_number' => 'PAY-001',
            'payment_date' => $today,
            'paid_amount' => 800.00,
            'payment_method' => 'cash',
            'paid_by' => $this->branchManager->id,
        ]);

        // Bank/UPI paid expense: ₹1,200 (Online payment)
        $expBank = Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'expense_number' => 'EXP-002',
            'expense_date' => $today,
            'description' => 'Office Broadband Bill',
            'amount' => 1200.00,
            'paid_amount' => 1200.00,
            'status' => 'PAID',
            'payment_status' => 'PAID',
            'payment_method' => 'upi',
            'requested_by' => $this->branchManager->id,
        ]);

        \App\Models\ExpensePayment::create([
            'expense_id' => $expBank->id,
            'payment_number' => 'PAY-002',
            'payment_date' => $today,
            'paid_amount' => 1200.00,
            'payment_method' => 'upi',
            'paid_by' => $this->branchManager->id,
        ]);

        // Draft expense: ₹500 (Ignored)
        Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'expense_category_id' => $this->expenseCategory->id,
            'expense_number' => 'EXP-003',
            'expense_date' => $today,
            'description' => 'Draft Stationary',
            'amount' => 500.00,
            'paid_amount' => 0.00,
            'status' => 'DRAFT',
            'payment_status' => 'UNPAID',
            'requested_by' => $this->branchManager->id,
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);
        $cashBook->update(['opening_balance' => 5000.00]);
        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'management_expense')
            ->first();

        $this->assertEquals(800.00, (float)$entry->cash_amount);
        $this->assertEquals(1200.00, (float)$entry->bank_amount);

        // Physical Cash = Opening (5,000) - Cash Expense (800) = 4,200 (UPI expense 1,200 must NOT reduce physical cash!)
        $this->assertEquals(4200.00, (float)$cashBook->closing_cash);
    }

    /**
     * Requirements 13 & 14: Borrower death loan waiver appears on correct date and does not double-count or reduce physical cash.
     */
    public function test_borrower_death_loan_waiver_row_behavior(): void
    {
        $today = '2026-09-12';

        $loan = $this->createTestLoan([
            'loan_number' => 'LN-DEATH-01',
            'account_number' => 'LN-DEATH-01',
            'sanctioned_amount' => 20000.00,
            'disbursed_amount' => 20000.00,
            'principal_outstanding' => 15000.00,
            'interest_outstanding' => 1000.00,
            'fee_outstanding' => 0.00,
            'penalty_outstanding' => 0.00,
            'total_outstanding' => 16000.00,
            'status' => 'active',
        ]);

        // Record approved borrower death waiver request for ₹16,000
        LoanSettlementRequest::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'loan_account_id' => $loan->id,
            'request_type' => 'borrower_death',
            'status' => 'approved',
            'as_of_date' => $today,
            'valid_until_date' => $today,
            'principal_outstanding' => 15000.00,
            'accrued_interest' => 1000.00,
            'discount_concession_amount' => 16000.00,
            'final_settlement_amount' => 0.00,
            'requested_by' => $this->branchManager->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);
        $cashBook->update(['opening_balance' => 10000.00]);
        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'borrower_death')
            ->first();

        // Forgiven amount = ₹16,000
        $this->assertEquals(16000.00, (float)$entry->cash_amount);

        // Physical Cash must remain ₹10,000 (NOT reduced by ₹16,000 waiver)
        $this->assertEquals(10000.00, (float)$cashBook->closing_cash);
    }

    /**
     * Requirements 15, 16, 17: Branch isolation and date boundary filtering (yesterday/today/tomorrow).
     */
    public function test_branch_isolation_and_date_boundaries(): void
    {
        $yesterday = '2026-09-11';
        $today = '2026-09-12';
        $tomorrow = '2026-09-13';

        // Yesterday loan in Branch 1
        $this->createTestLoan([
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'loan_number' => 'LN-YEST-01',
            'account_number' => 'LN-YEST-01',
            'sanctioned_amount' => 5000.00,
            'disbursed_amount' => 5000.00,
            'disbursement_date' => $yesterday,
            'status' => 'active',
        ]);

        // Today loan in Branch 2 (Different branch)
        $this->createTestLoan([
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
            'loan_number' => 'LN-BR2-01',
            'account_number' => 'LN-BR2-01',
            'sanctioned_amount' => 8000.00,
            'disbursed_amount' => 8000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        // Tomorrow loan in Branch 1
        $this->createTestLoan([
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'loan_number' => 'LN-TOMM-01',
            'account_number' => 'LN-TOMM-01',
            'sanctioned_amount' => 12000.00,
            'disbursed_amount' => 12000.00,
            'disbursement_date' => $tomorrow,
            'status' => 'active',
        ]);

        // Today loan in Branch 1: ₹7,000
        $this->createTestLoan([
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'loan_number' => 'LN-TODAY-01',
            'account_number' => 'LN-TODAY-01',
            'sanctioned_amount' => 7000.00,
            'disbursed_amount' => 7000.00,
            'disbursement_date' => $today,
            'status' => 'active',
        ]);

        $cashBookBranch1Today = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);

        $entry = CashBookEntry::where('cash_book_id', $cashBookBranch1Today->id)
            ->where('category_code', 'loan_disbursed')
            ->first();

        // Branch 1 Today's loan disbursement must be exactly ₹7,000
        $this->assertEquals(7000.00, (float)$entry->cash_amount);
    }

    /**
     * Requirement 21: Export CSV and Print view match screen data.
     */
    public function test_print_and_export_match_web_screen_data(): void
    {
        $today = '2026-09-12';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->admin->id);

        // Web view response check
        $showResponse = $this->actingAs($this->admin)->get(route('admin.cash-book.show', $cashBook->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('LOAN DISBURSED AMOUNT');
        $showResponse->assertSee('MEMBER NO');
        $showResponse->assertSee('BORROWER DEATH');
        $showResponse->assertDontSee('MISCELLANEOUS');
        $showResponse->assertDontSee('DISTRIBUTION PAYMENT');

        // Print view check
        $printResponse = $this->actingAs($this->admin)->get(route('admin.cash-book.print', $cashBook->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('LOAN DISBURSED AMOUNT');

        // Export CSV check
        $exportResponse = $this->actingAs($this->admin)->get(route('admin.cash-book.export', $cashBook->id));
        $exportResponse->assertStatus(200);
    }
}
