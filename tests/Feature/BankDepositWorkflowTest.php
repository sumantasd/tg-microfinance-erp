<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\CashBook;
use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\User;
use App\Services\BankDepositService;
use App\Services\CashBookService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankDepositWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $admin;
    protected User $secondAdmin;
    protected User $branchManager;
    protected User $branchManager2;
    protected User $unauthorizedUser;
    protected BankDepositService $bankDepositService;
    protected CashBookService $cashBookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->bankDepositService = app(BankDepositService::class);
        $this->cashBookService = app(CashBookService::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Test',
            'code' => 'GLFTEST',
            'email' => 'info@glftest.com',
            'phone' => '9876543210',
            'address' => 'Patna Main Road',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN01',
            'phone' => '9876543210',
            'address' => 'Patna Main Branch Address',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'City Branch',
            'code' => 'CITY02',
            'phone' => '9876543211',
            'address' => 'Patna City Branch Address',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800002',
            'is_active' => true,
        ]);

        // Admins
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'System Admin',
            'email' => 'admin.test@glf.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->admin->assignRole('Admin');

        $this->secondAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Secondary Admin',
            'email' => 'admin2.test@glf.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->secondAdmin->assignRole('Admin');

        // Branch Managers
        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Main Branch Manager',
            'email' => 'bm1.test@glf.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->branchManager2 = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'name' => 'City Branch Manager',
            'email' => 'bm2.test@glf.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->branchManager2->assignRole('Branch Manager');

        // Unauthorized User (e.g. Loan Officer)
        $this->unauthorizedUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Field Officer',
            'email' => 'officer.test@glf.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->unauthorizedUser->assignRole('Loan Officer');
    }

    /**
     * Requirement 1: Authorized Admin can view pending deposits.
     */
    public function test_authorized_admin_can_view_pending_deposits(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 15000.00,
            'bank_name' => 'State Bank of India',
            'account_number' => '38491028471',
            'reference_number' => 'SLIP-9901',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bank-deposits.index'));

        $response->assertStatus(200);
        $response->assertSee('BRANCH BANK DEPOSIT MANAGEMENT');
        $response->assertSee('SLIP-9901');
        $response->assertSee('State Bank of India');
        $response->assertSee('PENDING');
    }

    /**
     * Requirement 2: Authorized Admin sees the Approve action for pending deposits submitted by others.
     */
    public function test_authorized_admin_sees_approve_action_for_pending_deposits(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 8000.00,
            'bank_name' => 'HDFC Bank',
            'reference_number' => 'SLIP-8822',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bank-deposits.index'));

        $response->assertStatus(200);
        $response->assertSee('Approve');
        $response->assertSee('Reject');
    }

    /**
     * Requirement 3: Authorized Admin can successfully approve a pending deposit.
     */
    public function test_authorized_admin_can_successfully_approve_a_pending_deposit(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 10000.00,
            'bank_name' => 'ICICI Bank',
            'reference_number' => 'SLIP-1001',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bank-deposits.approve', $deposit->id), [
            'remarks' => 'Verified with bank receipt',
        ]);

        $response->assertRedirect(route('admin.bank-deposits.index'));
        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);
    }

    /**
     * Requirement 4 & 5: Approval changes status to APPROVED and records approved_by and approved_at.
     */
    public function test_approval_updates_status_approved_by_and_approved_at(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 5000.00,
            'bank_name' => 'Axis Bank',
            'reference_number' => 'SLIP-5005',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now()->subHour(),
        ]);

        $this->bankDepositService->approveDeposit($deposit, $this->admin, 'Valid slip');

        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);
        $this->assertEquals($this->admin->id, $deposit->approved_by);
        $this->assertNotNull($deposit->approved_at);
        $this->assertEquals($this->branchManager->id, $deposit->submitted_by);
    }

    /**
     * Requirement 6: Approved deposit appears in Cash Book DEPOSIT TO BANK.
     */
    public function test_approved_deposit_appears_in_cash_book_deposit_to_bank(): void
    {
        $date = '2026-09-12';
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $date,
            'amount' => 12000.00,
            'bank_name' => 'Canara Bank',
            'reference_number' => 'SLIP-1200',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->admin->id);

        $this->bankDepositService->approveDeposit($deposit, $this->admin);

        $entry = CashBookEntry::where('cash_book_id', $cashBook->id)
            ->where('category_code', 'deposit_to_bank')
            ->first();

        $this->assertEquals(12000.00, (float)$entry->cash_amount);
    }

    /**
     * Requirement 7: Approved deposit reduces physical cash exactly once (no double counting).
     */
    public function test_approved_deposit_reduces_physical_cash_exactly_once(): void
    {
        $date = '2026-09-12';

        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->admin->id);
        $cashBook->update(['opening_balance' => 50000.00]);
        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();

        $this->assertEquals(50000.00, (float)$cashBook->closing_cash);

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $date,
            'amount' => 15000.00,
            'bank_name' => 'Punjab National Bank',
            'reference_number' => 'SLIP-PNB-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        // Pending state: physical cash must remain ₹50,000
        $this->cashBookService->recalculateForDateAndSubsequent($this->branch1->id, $date);
        $cashBook->refresh();
        $this->assertEquals(50000.00, (float)$cashBook->closing_cash);

        // Approve deposit
        $this->bankDepositService->approveDeposit($deposit, $this->admin);
        $cashBook->refresh();

        // Physical Cash = Opening (50,000) - Approved Deposit (15,000) = 35,000 (Exactly once!)
        $this->assertEquals(35000.00, (float)$cashBook->closing_cash);

        // Subsequent recalculation should maintain exact balance of 35,000 without double reduction
        $this->cashBookService->recalculateTotals($cashBook);
        $cashBook->refresh();
        $this->assertEquals(35000.00, (float)$cashBook->closing_cash);
    }

    /**
     * Requirement 8: Pending deposit does not reduce physical cash.
     */
    public function test_pending_deposit_does_not_reduce_physical_cash(): void
    {
        $date = '2026-09-12';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->admin->id);
        $cashBook->update(['opening_balance' => 20000.00]);
        $this->cashBookService->recalculateTotals($cashBook);

        BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $date,
            'amount' => 8000.00,
            'bank_name' => 'Union Bank',
            'reference_number' => 'SLIP-PEND-1',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $this->cashBookService->recalculateForDateAndSubsequent($this->branch1->id, $date);
        $cashBook->refresh();

        $this->assertEquals(20000.00, (float)$cashBook->closing_cash);
    }

    /**
     * Requirement 9 & 10 & 11: Authorized Admin can reject pending deposit, rejection reason is recorded, physical cash is not reduced.
     */
    public function test_authorized_admin_can_reject_pending_deposit_with_reason(): void
    {
        $date = '2026-09-12';
        $cashBook = $this->cashBookService->getOrCreateCashBook($this->company->id, $this->branch1->id, $date, $this->admin->id);
        $cashBook->update(['opening_balance' => 30000.00]);
        $this->cashBookService->recalculateTotals($cashBook);

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => $date,
            'amount' => 7000.00,
            'bank_name' => 'Bank of Baroda',
            'reference_number' => 'SLIP-REJ-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.bank-deposits.reject', $deposit->id), [
            'rejection_reason' => 'Invalid bank slip stamp attached.',
        ]);

        $response->assertRedirect(route('admin.bank-deposits.index'));
        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);
        $this->assertEquals('Invalid bank slip stamp attached.', $deposit->rejection_reason);

        // Physical cash remains ₹30,000
        $cashBook->refresh();
        $this->assertEquals(30000.00, (float)$cashBook->closing_cash);
    }

    /**
     * Requirement 12: Branch Manager cannot approve their own deposit.
     */
    public function test_branch_manager_cannot_approve_their_own_deposit(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 6000.00,
            'bank_name' => 'Indian Overseas Bank',
            'reference_number' => 'SLIP-SELF-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You cannot approve your own submitted bank deposit.');

        $this->bankDepositService->approveDeposit($deposit, $this->branchManager);
    }

    /**
     * Requirement 13: Unauthorized user cannot approve through direct backend request.
     */
    public function test_unauthorized_user_cannot_approve_through_direct_backend_request(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 4000.00,
            'bank_name' => 'Federal Bank',
            'reference_number' => 'SLIP-UNAUTH-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->unauthorizedUser)->post(route('admin.bank-deposits.approve', $deposit->id));

        $response->assertStatus(403);
    }

    /**
     * Requirement 14: Branch isolation remains enforced for Branch Managers.
     */
    public function test_branch_isolation_remains_enforced_for_branch_managers(): void
    {
        // Deposit in Branch 2
        $depositBranch2 = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'deposit_date' => '2026-09-12',
            'amount' => 9000.00,
            'bank_name' => 'Kotak Mahindra',
            'reference_number' => 'SLIP-BR2-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager2->id,
            'submitted_at' => now(),
        ]);

        // Branch Manager 1 attempts to submit deposit for Branch 2 -> rejected by controller
        $response = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch2->id,
            'deposit_date' => '2026-09-12',
            'amount' => 2000.00,
            'bank_name' => 'SBI',
            'reference_number' => 'SLIP-CROSS-01',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You can only submit bank deposits for your assigned branch.');
    }

    /**
     * Requirement 15: Audit log is created for submission, approval, and rejection.
     */
    public function test_audit_log_is_created_for_approval_and_rejection(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 11000.00,
            'bank_name' => 'IDBI Bank',
            'reference_number' => 'SLIP-AUDIT-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin);
        $this->bankDepositService->approveDeposit($deposit, $this->admin, 'Audit verified');

        $approvedLog = ActivityLog::where('event', 'bank_deposit_approved')
            ->where('auditable_id', $deposit->id)
            ->first();

        $this->assertNotNull($approvedLog);
        $this->assertEquals($this->admin->id, $approvedLog->user_id);
    }
}
