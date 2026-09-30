<?php

namespace Tests\Feature;

use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Services\CashBookService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhysicalCashBookRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $superAdmin;
    protected CashBookService $cashBookService;
    protected \App\Models\LoanScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->cashBookService = app(CashBookService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Bihar',
            'code' => 'GLF-BIHAR',
            'email' => 'bihar@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Patna',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'BR-PATNA',
            'phone' => '9876543211',
            'address' => 'Patna Main Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Gaya Branch',
            'code' => 'BR-GAYA',
            'phone' => '9876543212',
            'address' => 'Gaya Station Road',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->scheme = \App\Models\LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCHEME-001',
            'name' => 'General Cash Loan Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 1000.00,
            'max_amount' => 100000.00,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 1,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 1.00,
            'insurance_fee_percentage' => 1.00,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'name' => 'Super Admin User',
            'email' => 'admin.cb@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');
    }

    /**
     * Requirement 1: FUND TRANSFER TO is removed/disabled from the register display.
     */
    public function test_fund_transfer_to_is_not_displayed_in_payment_register(): void
    {
        $today = date('Y-m-d');
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));

        $response->assertStatus(200);
        $response->assertDontSee('FUND TRANSFER TO');
    }

    /**
     * Requirement 2: Total Payment Amount includes ONLY Deposit to Bank + Management Expense + Borrower Death.
     */
    public function test_total_payment_includes_only_three_eligible_categories(): void
    {
        $today = date('Y-m-d');
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->superAdmin->id);

        // 1. Loan Disbursed = ₹50,000 (MUST BE EXCLUDED FROM TOTAL PAYMENT)
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'loan_disbursed')
            ->update(['cash_amount' => 50000.00]);

        // 2. Member No = 5 (MUST BE EXCLUDED FROM TOTAL PAYMENT)
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'member_no')
            ->update(['cash_amount' => 5.00]);

        // 3. Online EMI Collection = ₹15,000 (MUST BE EXCLUDED FROM TOTAL PAYMENT)
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'gl_steel_furniture')
            ->update(['cash_amount' => 0.00, 'bank_amount' => 15000.00]);

        // 4. Deposit to Bank = ₹10,000 (ELIGIBLE PAYMENT)
        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'bank_name' => 'State Bank of India',
            'reference_number' => 'DEP-REF-100',
            'deposit_date' => $today,
            'amount' => 10000.00,
            'status' => 'approved',
            'created_by' => $this->superAdmin->id,
            'submitted_by' => $this->superAdmin->id,
        ]);

        // 5. Management Expense = ₹1,500 (ELIGIBLE PAYMENT)
        $expCat = \App\Models\ExpenseCategory::create([
            'company_id' => $this->company->id,
            'category_name' => 'Office Expense',
            'category_code' => 'OFFICE',
            'is_active' => true,
        ]);
        \App\Models\Expense::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'expense_number' => 'EXP-100',
            'expense_date' => $today,
            'expense_category_id' => $expCat->id,
            'payee_name' => 'Vendor',
            'description' => 'Office Stationeries',
            'amount' => 1500.00,
            'total_amount' => 1500.00,
            'paid_amount' => 1500.00,
            'status' => 'APPROVED',
            'payment_status' => 'PAID',
            'payment_method' => 'cash',
            'requested_by' => $this->superAdmin->id,
        ]);

        // 6. Borrower Death = ₹500 (ELIGIBLE PAYMENT)
        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'first_name' => 'Death',
            'last_name' => 'Borrower',
            'customer_code' => 'CUST-DEATH',
            'mobile_number' => '9876543299',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);
        $app = \App\Models\LoanApplication::create([
            'application_number' => 'APP-DEATH',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $cust->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => date('Y-m-d'),
            'requested_amount' => 500.00,
            'approved_amount' => 500.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);
        $loan = LoanAccount::create([
            'loan_application_id' => $app->id,
            'loan_scheme_id' => $this->scheme->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $cust->id,
            'loan_application_number' => 'APP-DEATH',
            'loan_account_number' => 'LOAN-DEATH',
            'loan_number' => 'LN-DEATH',
            'sanctioned_amount' => 500.00,
            'disbursed_amount' => 500.00,
            'principal_outstanding' => 500.00,
            'total_outstanding' => 500.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'sanction_date' => date('Y-m-d'),
            'disbursement_date' => '2026-08-01',
            'status' => 'active',
        ]);

        \App\Models\LoanSettlementRequest::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'loan_account_id' => $loan->id,
            'request_type' => 'borrower_death',
            'status' => 'approved',
            'as_of_date' => $today,
            'valid_until_date' => $today,
            'principal_outstanding' => 500.00,
            'discount_concession_amount' => 500.00,
            'final_settlement_amount' => 0.00,
            'requested_by' => $this->superAdmin->id,
        ]);

        $this->cashBookService->syncErpTransactions($cashBook);
        $cashBook->refresh();

        // Total Payment = 10000 + 1500 + 500 = 12,000.00
        $this->assertEquals(12000.00, (float)$cashBook->total_cash_payment);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));
        $response->assertStatus(200);
        $response->assertSee('₹12,000.00');
    }

    /**
     * Requirement 3: Cash In Hand = Total Received - Total Payment & Next-Day Opening Balance carry forward.
     */
    public function test_cash_in_hand_calculation_and_next_day_opening_balance(): void
    {
        $day1 = '2026-09-01';
        $day2 = '2026-09-02';

        // Day 1: Opening ₹6,080, Receipts ₹2,000, Payments ₹500 -> Cash In Hand ₹7,580
        $cashBookDay1 = CashBook::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'date' => $day1,
            'opening_balance' => 6080.00,
            'status' => 'closed',
            'created_by' => $this->superAdmin->id,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $cashBookDay1->id,
            'entry_type' => 'received',
            'category_code' => 'cash_opening_balance',
            'particulars' => 'CASH OPENING BALANCE',
            'entry_date' => $day1,
            'cash_amount' => 6080.00,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $cashBookDay1->id,
            'entry_type' => 'received',
            'category_code' => 'weekly_collection',
            'particulars' => 'WEEKLY COLLECTION',
            'entry_date' => $day1,
            'cash_amount' => 2000.00,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $cashBookDay1->id,
            'entry_type' => 'payment',
            'category_code' => 'management_expense',
            'particulars' => 'MANAGEMENT EXPENSE',
            'entry_date' => $day1,
            'cash_amount' => 500.00,
        ]);

        $this->cashBookService->recalculateTotals($cashBookDay1);
        $cashBookDay1->refresh();

        // Total Received = 6080 + 2000 = 8080. Total Payment = 500. Cash In Hand = 7580
        $this->assertEquals(8080.00, (float)$cashBookDay1->total_cash_received);
        $this->assertEquals(500.00, (float)$cashBookDay1->total_cash_payment);
        $this->assertEquals(7580.00, (float)$cashBookDay1->closing_cash);

        // Day 2: Opening balance must automatically be ₹7,580
        $openingDay2 = $this->cashBookService->getOpeningBalanceForDate($this->branch1->id, $day2);
        $this->assertEquals(7580.00, $openingDay2);

        $cashBookDay2 = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $day2, $this->superAdmin->id);
        $this->assertEquals(7580.00, (float)$cashBookDay2->opening_balance);
    }

    /**
     * Requirement 3: Handles negative Cash In Hand accurately and flags for reconciliation.
     */
    public function test_negative_cash_in_hand_handled_accurately_and_flagged(): void
    {
        $today = date('Y-m-d');
        $cashBook = CashBook::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'date' => $today,
            'opening_balance' => 1000.00,
            'status' => 'open',
            'created_by' => $this->superAdmin->id,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $cashBook->id,
            'entry_type' => 'received',
            'category_code' => 'cash_opening_balance',
            'particulars' => 'CASH OPENING BALANCE',
            'entry_date' => $today,
            'cash_amount' => 1000.00,
        ]);

        CashBookEntry::create([
            'cash_book_id' => $cashBook->id,
            'entry_type' => 'payment',
            'category_code' => 'management_expense',
            'particulars' => 'MANAGEMENT EXPENSE',
            'entry_date' => $today,
            'cash_amount' => 2500.00,
        ]);

        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        // Received = 1000, Payment = 2500 -> Cash In Hand = -1500
        $this->assertEquals(-1500.00, (float)$cashBook->closing_cash);
        $this->assertEquals('cash_short', $cashBook->reconciled_status);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));
        $response->assertStatus(200);
        $response->assertSee('CASH SHORT');
    }

    /**
     * Requirement 4: Online / UPI EMI Collection displays in GL Steel Furniture row as informational.
     */
    public function test_online_upi_emi_collections_displayed_in_gl_steel_furniture_row(): void
    {
        $today = date('Y-m-d');
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->superAdmin->id);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'customer_code' => 'CUST-001',
            'mobile_number' => '9876543210',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $app1 = \App\Models\LoanApplication::create([
            'application_number' => 'APP-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => date('Y-m-d'),
            'requested_amount' => 20000.00,
            'approved_amount' => 20000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $loan = LoanAccount::create([
            'loan_application_id' => $app1->id,
            'loan_scheme_id' => $this->scheme->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_number' => 'APP-001',
            'loan_account_number' => 'LOAN-001',
            'loan_number' => 'LN-001',
            'sanctioned_amount' => 20000.00,
            'disbursed_amount' => 20000.00,
            'principal_outstanding' => 20000.00,
            'total_outstanding' => 20000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'sanction_date' => date('Y-m-d'),
            'disbursement_date' => '2026-08-01',
            'status' => 'active',
        ]);

        // Online UPI repayment of ₹2,500
        LoanRepayment::create([
            'loan_account_id' => $loan->id,
            'receipt_number' => 'REC-UPI-001',
            'payment_date' => $today,
            'amount' => 2500.00,
            'payment_method' => 'upi',
            'reference_number' => 'UPI-REF-999',
        ]);

        $this->cashBookService->syncErpTransactions($cashBook);
        $cashBook->refresh();

        $glRow = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'gl_steel_furniture')
            ->first();

        $this->assertNotNull($glRow);
        $this->assertEquals('ONLINE / UPI EMI COLLECTION', $glRow->particulars);
        $this->assertEquals(2500.00, (float)$glRow->bank_amount);

        // Informational presentation: Must NOT affect Total Payment or Cash In Hand
        $this->assertEquals(0.00, (float)$cashBook->total_cash_payment);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));
        $response->assertStatus(200);
        $response->assertSee('ONLINE / UPI EMI COLLECTION');
        $response->assertSee('2,500.00');
    }

    /**
     * Requirement 5: LOAN DISBURSED AMOUNT displays total disbursements but is excluded from Total Payment.
     */
    public function test_loan_disbursement_row_displays_total_and_is_excluded_from_total_payment(): void
    {
        $today = date('Y-m-d');

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'first_name' => 'Suresh',
            'last_name' => 'Singh',
            'customer_code' => 'CUST-002',
            'mobile_number' => '9876543211',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $app2 = \App\Models\LoanApplication::create([
            'application_number' => 'APP-002',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => date('Y-m-d'),
            'requested_amount' => 45000.00,
            'approved_amount' => 45000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        LoanAccount::create([
            'loan_application_id' => $app2->id,
            'loan_scheme_id' => $this->scheme->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $customer->id,
            'loan_application_number' => 'APP-002',
            'loan_account_number' => 'LOAN-002',
            'loan_number' => 'LN-002',
            'sanctioned_amount' => 45000.00,
            'disbursed_amount' => 45000.00,
            'principal_outstanding' => 45000.00,
            'total_outstanding' => 45000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'sanction_date' => date('Y-m-d'),
            'disbursement_date' => $today,
            'disbursement_payment_method' => 'cash',
            'status' => 'active',
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->superAdmin->id);

        $disbursedEntry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'loan_disbursed')
            ->first();

        $this->assertNotNull($disbursedEntry);
        $this->assertEquals(45000.00, (float)$disbursedEntry->cash_amount);

        // Excluded from Total Payment formula!
        $this->assertEquals(0.00, (float)$cashBook->total_cash_payment);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.cash-book.show', $cashBook->id));
        $response->assertStatus(200);
        $response->assertSee('LOAN DISBURSED AMOUNT');
        $response->assertSee('45,000.00');
    }

    /**
     * Requirement 6: Branch & Date isolation prevents cross-branch data leaking.
     */
    public function test_branch_isolation_keeps_cashbooks_completely_separated(): void
    {
        $today = date('Y-m-d');

        $cbBranch1 = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $today, $this->superAdmin->id);
        $cbBranch2 = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch2->id, $today, $this->superAdmin->id);

        CashBookEntry::where('cash_book_id', $cbBranch1->id)
            ->where('category_code', 'management_expense')
            ->update(['cash_amount' => 5000.00]);

        $this->cashBookService->recalculateTotals($cbBranch1);
        $this->cashBookService->recalculateTotals($cbBranch2);

        $cbBranch1->refresh();
        $cbBranch2->refresh();

        $this->assertEquals(5000.00, (float)$cbBranch1->total_cash_payment);
        $this->assertEquals(0.00, (float)$cbBranch2->total_cash_payment);
    }
}
