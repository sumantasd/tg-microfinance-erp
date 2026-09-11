<?php

namespace Tests\Feature;

use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoanAccount;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\BankDepositService;
use App\Services\CashBookService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyCashBookTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $superAdmin;
    protected User $branchManager;
    protected CashBookService $cashBookService;
    protected BankDepositService $bankDepositService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->cashBookService = app(CashBookService::class);
        $this->bankDepositService = app(BankDepositService::class);

        $this->company = Company::create([
            'name' => 'Titan Microfinance Corp',
            'code' => 'HO01',
            'email' => 'contact@titanmicrofinance.com',
            'phone' => '9876543210',
            'address' => 'Corporate Plaza',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Main Branch',
            'code' => 'BR001',
            'email' => 'kolkata@titanmicrofinance.com',
            'phone' => '9876543211',
            'address' => 'Main Road Kolkata',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Howrah Branch',
            'code' => 'BR002',
            'email' => 'howrah@titanmicrofinance.com',
            'phone' => '9876543212',
            'address' => 'Station Road Howrah',
            'city' => 'Howrah',
            'state' => 'West Bengal',
            'pincode' => '711101',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => null,
            'name' => 'System Super Admin',
            'email' => 'admin.cashbook@titanmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Branch Manager User',
            'email' => 'bm.cashbook@titanmicrofinance.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');
    }

    /**
     * Test 1: Full User Test Scenario - Day 1 & Day 2 Cash Book roll-over after Bank Deposit Approval
     */
    public function test_full_user_scenario_day1_and_day2_opening_cash_and_approved_bank_deposits(): void
    {
        $day1 = '2026-09-10';
        $day2 = '2026-09-11';

        // DAY 1: Initial cashbook setup with Opening Cash = ₹5,000
        $cbDay1 = CashBook::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'date' => $day1,
            'opening_balance' => 5000.00,
            'status' => 'open',
            'created_by' => $this->branchManager->id,
        ]);

        // Seed 8 default receipt entries
        CashBookEntry::create([
            'cash_book_id' => $cbDay1->id,
            'entry_type' => 'received',
            'category_code' => 'cash_opening_balance',
            'particulars' => 'CASH OPENING BALANCE',
            'entry_date' => $day1,
            'cash_amount' => 5000.00,
        ]);

        // Day 1 Cash Receipts = ₹10,000 total across categories
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'weekly_collection', 'particulars' => 'WEEKLY COLLECTION', 'entry_date' => $day1, 'cash_amount' => 5000.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'processing_fee', 'particulars' => 'PROCESSING FEE', 'entry_date' => $day1, 'cash_amount' => 500.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'insurance_fee', 'particulars' => 'INSURANCE FEE', 'entry_date' => $day1, 'cash_amount' => 200.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'new_loan_advance', 'particulars' => 'NEW LOAN ADVANCE', 'entry_date' => $day1, 'cash_amount' => 1000.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'pre_payment', 'particulars' => 'PRE PAYMENT', 'entry_date' => $day1, 'cash_amount' => 500.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'cash_selling', 'particulars' => 'CASH SELLING', 'entry_date' => $day1, 'cash_amount' => 2000.00]);
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'received', 'category_code' => 'od_collection', 'particulars' => 'OD COLLECTION', 'entry_date' => $day1, 'cash_amount' => 800.00]);

        // Day 1 Cash Payments = ₹5,000
        CashBookEntry::create(['cash_book_id' => $cbDay1->id, 'entry_type' => 'payment', 'category_code' => 'management_expense', 'particulars' => 'MANAGEMENT EXPENSE', 'entry_date' => $day1, 'cash_amount' => 5000.00]);

        $this->cashBookService->recalculateTotals($cbDay1);
        $cbDay1->refresh();

        // Physical Cash before Bank Deposit: 5,000 (Opening) + 10,000 (Receipts) - 5,000 (Payments) = ₹10,000
        $this->assertEquals(10000.00, $cbDay1->closing_cash);

        // Branch Manager submits bank deposit of ₹7,000 (Status: PENDING)
        $deposit = $this->bankDepositService->submitDeposit([
            'branch_id' => $this->branch1->id,
            'deposit_date' => $day1,
            'amount' => 7000.00,
            'bank_name' => 'State Bank of India',
            'reference_number' => 'SLIP-DAY1-7000',
        ], $this->branchManager);

        $this->assertEquals('pending', $deposit->status);

        // Verify Pending deposit does NOT reduce Day 1 physical cash or Day 2 opening balance
        $day2OpeningBeforeApproval = $this->cashBookService->getOpeningBalanceForDate($this->branch1->id, $day2);
        $this->assertEquals(10000.00, $day2OpeningBeforeApproval);

        // Admin approves ₹7,000 bank deposit
        $this->bankDepositService->approveDeposit($deposit, $this->superAdmin, 'Verified bank slip');

        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);

        // After approval, Day 1 final physical cash = ₹10,000 - ₹7,000 = ₹3,000
        $day2OpeningAfterApproval = $this->cashBookService->getOpeningBalanceForDate($this->branch1->id, $day2);
        $this->assertEquals(3000.00, $day2OpeningAfterApproval);

        // DAY 2: Create Cash Book for Day 2
        $cbDay2 = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $day2, $this->branchManager->id);

        $this->assertEquals(3000.00, $cbDay2->opening_balance);

        // Add Day 2 Cash Receipts: Weekly Collection = ₹5,000, Processing = ₹500, Insurance = ₹200, Prepayment = ₹1,000, Selling = ₹2,000, OD = ₹800 (Total = ₹9,500)
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'weekly_collection')->update(['cash_amount' => 5000.00]);
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'processing_fee')->update(['cash_amount' => 500.00]);
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'insurance_fee')->update(['cash_amount' => 200.00]);
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'pre_payment')->update(['cash_amount' => 1000.00]);
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'cash_selling')->update(['cash_amount' => 2000.00]);
        CashBookEntry::where('cash_book_id', $cbDay2->id)->where('category_code', 'od_collection')->update(['cash_amount' => 800.00]);

        $this->cashBookService->recalculateTotals($cbDay2);
        $cbDay2->refresh();

        // Day 2 Cash before deposit = ₹3,000 (Opening) + ₹9,500 (Receipts) = ₹12,500
        $this->assertEquals(12500.00, $cbDay2->closing_cash);

        // Day 2 Bank Deposit of ₹5,000 approved
        $depositDay2 = $this->bankDepositService->submitDeposit([
            'branch_id' => $this->branch1->id,
            'deposit_date' => $day2,
            'amount' => 5000.00,
            'bank_name' => 'HDFC Bank',
            'reference_number' => 'SLIP-DAY2-5000',
        ], $this->branchManager);

        $this->bankDepositService->approveDeposit($depositDay2, $this->superAdmin);

        // Day 2 final physical cash = ₹12,500 - ₹5,000 = ₹7,500
        $day3Opening = $this->cashBookService->getOpeningBalanceForDate($this->branch1->id, '2026-09-12');
        $this->assertEquals(7500.00, $day3Opening);
    }

    /**
     * Test 2: Rejected bank deposit does NOT reduce physical cash
     */
    public function test_rejected_bank_deposit_does_not_reduce_physical_cash(): void
    {
        $date = '2026-09-10';

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->branchManager->id);
        $cashBook->update(['opening_balance' => 5000.00]);
        $this->cashBookService->recalculateTotals($cashBook);

        $deposit = $this->bankDepositService->submitDeposit([
            'branch_id' => $this->branch1->id,
            'deposit_date' => $date,
            'amount' => 2000.00,
            'bank_name' => 'ICICI Bank',
            'reference_number' => 'SLIP-REJECT-01',
        ], $this->branchManager);

        $this->bankDepositService->rejectDeposit($deposit, $this->superAdmin, 'Invalid reference slip image');

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);

        $nextDayOpening = $this->cashBookService->getOpeningBalanceForDate($this->branch1->id, '2026-09-11');
        $this->assertEquals(5000.00, $nextDayOpening);
    }

    /**
     * Test 3: Branch Manager cannot approve their own bank deposit
     */
    public function test_branch_manager_cannot_approve_own_bank_deposit(): void
    {
        $deposit = $this->bankDepositService->submitDeposit([
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-10',
            'amount' => 1500.00,
            'bank_name' => 'Axis Bank',
            'reference_number' => 'SLIP-SELF-01',
        ], $this->branchManager);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot approve your own submitted bank deposit.');

        $this->bankDepositService->approveDeposit($deposit, $this->branchManager);
    }

    /**
     * Test 4: Cash vs Bank Split - Bank payment method does NOT increase physical cash in hand
     */
    public function test_cash_vs_bank_payment_method_split(): void
    {
        $date = '2026-09-10';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->branchManager->id);

        // Add a Weekly Collection entry with Cash ₹3,000 and Bank ₹2,000
        CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'weekly_collection')
            ->update([
                'cash_amount' => 3000.00,
                'bank_amount' => 2000.00,
            ]);

        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        // Cash Received = ₹3,000 (excluding 0 opening), Bank Received = ₹2,000
        $this->assertEquals(3000.00, $cashBook->total_cash_received);
        $this->assertEquals(2000.00, $cashBook->total_bank_received);

        // System Closing Cash (Physical Cash In Hand) must be ₹3,000, NOT ₹5,000
        $this->assertEquals(3000.00, $cashBook->closing_cash);
    }

    /**
     * Test 5: Branch Manager is isolated to their assigned branch in controller requests
     */
    public function test_branch_manager_branch_isolation(): void
    {
        // Branch Manager attempts to submit deposit for another branch (Branch 2)
        $response = $this->from('/admin/bank-deposits')
            ->actingAs($this->branchManager)
            ->post('/admin/bank-deposits', [
                'branch_id' => $this->branch2->id,
                'deposit_date' => '2026-09-10',
                'amount' => 1000.00,
                'bank_name' => 'PNB',
                'reference_number' => 'UNAUTH-01',
            ]);

        $response->assertRedirect('/admin/bank-deposits');
        $response->assertSessionHas('error', 'You can only submit bank deposits for your assigned branch.');
    }
}
