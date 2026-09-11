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
use App\Models\Invoice;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\CashBookService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticCashBookTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected CashBookService $cashBookService;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Customer $customerC;
    protected CustomerGroup $group;
    protected LoanScheme $loanScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->cashBookService = app(CashBookService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Microfinance Ltd',
            'code' => 'GLM01',
            'email' => 'contact@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Corporate Hub Patna',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Central Branch',
            'code' => 'PAT01',
            'phone' => '9876543211',
            'address' => 'Boring Road Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->admin = User::where('email', 'admin@grihalaxmifinance.com')->first() ?: User::first();
        if ($this->admin) {
            $this->admin->update([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'status' => 'active',
            ]);
            $this->admin->syncRoles(['Super Admin']);
        }

        $this->group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-001',
            'name' => 'Sabitri Group',
            'formation_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-A',
            'first_name' => 'Customer',
            'last_name' => 'A',
            'name' => 'Customer A',
            'mobile_number' => '9800000001',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-B',
            'first_name' => 'Customer',
            'last_name' => 'B',
            'name' => 'Customer B',
            'mobile_number' => '9800000002',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->customerC = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-C',
            'first_name' => 'Customer',
            'last_name' => 'C',
            'name' => 'Customer C',
            'mobile_number' => '9800000003',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->loanScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-01',
            'name' => 'Weekly Micro Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 1000.00,
            'max_amount' => 100000.00,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 3,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'weekly',
            'is_active' => true,
        ]);

        $this->loanApp = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'application_number' => 'APP-AUTO-001',
            'requested_amount' => 10000.00,
            'approved_amount' => 10000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'weekly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
            'application_date' => now()->toDateString(),
        ]);
    }

    private function createTestLoan(array $attributes = []): LoanAccount
    {
        $amount = $attributes['disbursed_amount'] ?? $attributes['sanctioned_amount'] ?? $attributes['amount'] ?? 10000.00;

        $defaults = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_application_id' => $this->loanApp->id,
            'customer_id' => $this->customerA->id,
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
            'sanction_date' => '2026-09-11',
            'status' => 'active',
        ];

        return LoanAccount::create(array_merge($defaults, $attributes));
    }

    /**
     * Requirement 1: Verification that Physical Cash Denomination section is NOT required.
     */
    public function test_physical_cash_denomination_section_is_not_required(): void
    {
        $date = '2026-09-11';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch->id, $date, $this->admin->id);

        $this->assertNotNull($cashBook);
        $this->assertEquals('open', $cashBook->status);
        $this->assertEquals(0.00, (float)$cashBook->cash_difference);
        $this->assertEquals('balanced', $cashBook->reconciled_status);
        $this->assertEquals((float)$cashBook->closing_cash, (float)$cashBook->physical_cash);

        // Can close register without any physical denomination counting
        $this->cashBookService->closeCashBook($cashBook, $this->admin->id, 'Closed cleanly without denominations');
        $cashBook->refresh();

        $this->assertTrue($cashBook->isClosed());
    }

    /**
     * Requirements 2, 3, 4: Verification that manual Add buttons are absent from UI.
     */
    public function test_no_manual_add_buttons_or_denomination_table_in_cash_book_ui(): void
    {
        $date = '2026-09-11';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch->id, $date, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('admin.cash-book.show', $cashBook->id));

        $response->assertStatus(200);
        $response->assertDontSee('+ Add Receipt');
        $response->assertDontSee('+ Add Payment');
        $response->assertDontSee('+ Add Entry');
        $response->assertDontSee('CASH DENOMINATION DETAILS (PHYSICAL COUNT)');
        $response->assertSee('ONLINE COLLECTION DETAILS');
        $response->assertSee('AUTOMATIC ERP');
    }

    /**
     * Requirement 2 & 3: Received and Payment transactions populate automatically from ERP data.
     */
    public function test_received_and_payment_data_populate_automatically_from_erp_transactions(): void
    {
        $date = '2026-09-11';

        // Create a loan account disbursed today
        $loan = $this->createTestLoan([
            'customer_id' => $this->customerA->id,
            'loan_number' => 'LN-1001',
            'account_number' => 'LN-1001',
            'sanctioned_amount' => 10000.00,
            'disbursed_amount' => 10000.00,
            'amount' => 10000.00,
            'disbursement_date' => $date,
            'status' => 'active',
        ]);

        // Create cash loan repayment collected today (Weekly Collection)
        LoanRepayment::create([
            'loan_account_id' => $loan->id,
            'payment_date' => $date,
            'amount' => 2500.00,
            'principal_component' => 2000.00,
            'interest_component' => 500.00,
            'payment_method' => 'cash',
            'receipt_number' => 'RCP-CASH-001',
        ]);

        // Create direct cash sale today
        Invoice::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customerA->id,
            'invoiceable_type' => Customer::class,
            'invoiceable_id' => $this->customerA->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => $date,
            'invoice_type' => 'direct_sale',
            'total_amount' => 1200.00,
            'paid_amount' => 1200.00,
            'payment_method' => 'cash',
            'status' => 'paid',
            'created_by' => $this->admin->id,
        ]);

        // Create approved bank deposit today
        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'deposit_date' => $date,
            'amount' => 1500.00,
            'bank_name' => 'State Bank of India',
            'reference_number' => 'DEP-REF-001',
            'status' => 'approved',
            'submitted_by' => $this->admin->id,
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch->id, $date, $this->admin->id);

        $weeklyCollection = CashBookEntry::where('cash_book_id', $cashBook->id)->where('category_code', 'weekly_collection')->first();
        $cashSelling = CashBookEntry::where('cash_book_id', $cashBook->id)->where('category_code', 'cash_selling')->first();
        $loanDisbursed = CashBookEntry::where('cash_book_id', $cashBook->id)->where('category_code', 'loan_disbursed')->first();
        $depositToBank = CashBookEntry::where('cash_book_id', $cashBook->id)->where('category_code', 'deposit_to_bank')->first();

        $this->assertEquals(2500.00, (float)$weeklyCollection->cash_amount);
        $this->assertEquals(1200.00, (float)$cashSelling->cash_amount);
        $this->assertEquals(10000.00, (float)$loanDisbursed->cash_amount);
        $this->assertEquals(1500.00, (float)$depositToBank->cash_amount);
    }

    /**
     * Requirement 4, 5, 6: Multiple online collections on SAME DATE appear as separate individual rows and do NOT aggregate.
     */
    public function test_multiple_online_collections_on_same_day_appear_as_separate_individual_records(): void
    {
        $date = '2026-09-11';

        $loan1 = $this->createTestLoan([
            'customer_id' => $this->customerA->id,
            'group_id' => $this->group->id,
            'loan_number' => 'LN-2001',
            'account_number' => 'LN-2001',
            'sanctioned_amount' => 20000.00,
            'disbursed_amount' => 20000.00,
            'amount' => 20000.00,
            'status' => 'active',
        ]);

        $loan2 = $this->createTestLoan([
            'customer_id' => $this->customerB->id,
            'group_id' => $this->group->id,
            'loan_number' => 'LN-2002',
            'account_number' => 'LN-2002',
            'sanctioned_amount' => 35000.00,
            'disbursed_amount' => 35000.00,
            'amount' => 35000.00,
            'status' => 'active',
        ]);

        $loan3 = $this->createTestLoan([
            'customer_id' => $this->customerC->id,
            'group_id' => $this->group->id,
            'loan_number' => 'LN-2003',
            'account_number' => 'LN-2003',
            'sanctioned_amount' => 15000.00,
            'disbursed_amount' => 15000.00,
            'amount' => 15000.00,
            'status' => 'active',
        ]);

        // Three online payments on 11/09 (SAME DATE)
        $rep1 = LoanRepayment::create([
            'loan_account_id' => $loan1->id,
            'payment_date' => $date,
            'amount' => 2000.00,
            'payment_method' => 'upi',
            'reference_number' => 'UPI-REF-001',
            'receipt_number' => 'RCP-UPI-001',
        ]);

        $rep2 = LoanRepayment::create([
            'loan_account_id' => $loan2->id,
            'payment_date' => $date,
            'amount' => 3500.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'BANK-REF-002',
            'receipt_number' => 'RCP-BANK-002',
        ]);

        $rep3 = LoanRepayment::create([
            'loan_account_id' => $loan3->id,
            'payment_date' => $date,
            'amount' => 1500.00,
            'payment_method' => 'upi',
            'reference_number' => 'UPI-REF-003',
            'receipt_number' => 'RCP-UPI-003',
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch->id, $date, $this->admin->id);

        $onlineCollections = CashBookOnlineCollection::where('cash_book_id', $cashBook->id)->get();

        // Must have 3 separate records, NOT 1 merged record
        $this->assertCount(3, $onlineCollections);

        $amounts = $onlineCollections->pluck('amount')->map(fn($v) => (float)$v)->toArray();
        $this->assertContains(2000.00, $amounts);
        $this->assertContains(3500.00, $amounts);
        $this->assertContains(1500.00, $amounts);

        $customerNames = $onlineCollections->pluck('customer_name')->toArray();
        $this->assertContains('Customer A', $customerNames);
        $this->assertContains('Customer B', $customerNames);
        $this->assertContains('Customer C', $customerNames);

        // Render view check: all three records appear separately in the HTML
        $response = $this->actingAs($this->admin)->get(route('admin.cash-book.show', $cashBook->id));
        $response->assertStatus(200);
        $response->assertSee('Customer A');
        $response->assertSee('Customer B');
        $response->assertSee('Customer C');
        $response->assertSee('UPI-REF-001');
        $response->assertSee('BANK-REF-002');
        $response->assertSee('UPI-REF-003');
    }
}
