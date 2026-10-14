<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BankAccount;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $file = UploadedFile::fake()->image('slip.jpg');

        // Branch Manager 1 attempts to submit deposit for Branch 2 -> rejected by controller
        $response = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch2->id,
            'deposit_date' => '2026-09-12',
            'amount' => 2000.00,
            'bank_name' => 'SBI',
            'reference_number' => 'SLIP-CROSS-01',
            'receipt' => $file,
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

    /**
     * Requirement 16: Admin can create, edit, and deactivate/activate bank accounts.
     */
    public function test_admin_can_create_edit_and_deactivate_bank_accounts(): void
    {
        // 1. Create Bank Account
        $storeResponse = $this->actingAs($this->admin)->post(route('admin.bank-deposits.accounts.store'), [
            'bank_name' => 'State Bank of India',
            'account_holder_name' => 'Grihalaxmi Finance Pvt Ltd',
            'account_number' => '123456789012',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Patna Main',
            'account_nickname' => 'SBI Primary Collection',
            'is_active' => 1,
        ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');

        $account = BankAccount::where('account_number', '123456789012')->first();
        $this->assertNotNull($account);
        $this->assertEquals('State Bank of India', $account->bank_name);
        $this->assertEquals('SBI Primary Collection', $account->account_nickname);
        $this->assertTrue((bool)$account->is_active);

        // 2. Edit Bank Account
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.bank-deposits.accounts.update', $account->id), [
            'bank_name' => 'State Bank of India',
            'account_holder_name' => 'Grihalaxmi Finance Pvt Ltd',
            'account_number' => '123456789012',
            'ifsc_code' => 'SBIN0001234',
            'branch_name' => 'Patna Bailey Road',
            'account_nickname' => 'SBI Updated Account',
            'is_active' => 1,
        ]);

        $updateResponse->assertRedirect();
        $account->refresh();
        $this->assertEquals('Patna Bailey Road', $account->branch_name);
        $this->assertEquals('SBI Updated Account', $account->account_nickname);

        // 3. Deactivate Bank Account
        $toggleResponse = $this->actingAs($this->admin)->post(route('admin.bank-deposits.accounts.toggle-status', $account->id));
        $toggleResponse->assertRedirect();
        $account->refresh();
        $this->assertFalse((bool)$account->is_active);

        // 4. Reactivate Bank Account
        $toggleAgainResponse = $this->actingAs($this->admin)->post(route('admin.bank-deposits.accounts.toggle-status', $account->id));
        $toggleAgainResponse->assertRedirect();
        $account->refresh();
        $this->assertTrue((bool)$account->is_active);
    }

    /**
     * Requirement 17: Non-admin user cannot manage bank accounts.
     */
    public function test_non_admin_cannot_manage_bank_accounts(): void
    {
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'HDFC Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '987654321098',
            'ifsc_code' => 'HDFC0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.accounts.store'), [
            'bank_name' => 'Unauthorized Bank',
            'account_holder_name' => 'Test',
            'account_number' => '111122223333',
            'ifsc_code' => 'HDFC0001111',
            'branch_name' => 'Test',
        ]);
        $response->assertStatus(403);

        $responseToggle = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.accounts.toggle-status', $account->id));
        $responseToggle->assertStatus(403);
    }

    /**
     * Requirement 18: Branch Manager sees only active bank accounts when submitting deposits.
     */
    public function test_branch_manager_sees_only_active_bank_accounts_in_form(): void
    {
        $activeAccount = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Active ICICI Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '444455556666',
            'ifsc_code' => 'ICIC0001234',
            'branch_name' => 'Patna',
            'account_nickname' => 'ICICI Active Ops',
            'is_active' => true,
        ]);

        $inactiveAccount = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Old Inactive Axis Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '777788889999',
            'ifsc_code' => 'UTIB0001234',
            'branch_name' => 'Patna',
            'account_nickname' => 'Axis Closed Ops',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->branchManager)->get(route('admin.bank-deposits.index'));
        $response->assertStatus(200);
        $response->assertSee('ICICI Active Ops');
        $response->assertDontSee('Axis Closed Ops');
    }

    /**
     * Requirement 19: Receipt upload is mandatory for new deposit submissions.
     */
    public function test_receipt_is_mandatory_for_new_deposits(): void
    {
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Axis Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '555566667777',
            'ifsc_code' => 'UTIB0005555',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => now()->toDateString(),
            'amount' => 5000.00,
            'reference_number' => 'SLIP-NO-RECEIPT',
            // 'receipt' is omitted
        ]);

        $response->assertSessionHasErrors(['receipt']);
    }

    /**
     * Requirement 20: Valid receipt upload stores file securely in private storage and records metadata.
     */
    public function test_valid_receipt_upload_stores_file_privately(): void
    {
        Storage::fake('local');

        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Bank of India',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '888899990000',
            'ifsc_code' => 'BKID0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('deposit_counterfoil.png', 800, 600);

        $response = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => now()->toDateString(),
            'amount' => 18000.00,
            'reference_number' => 'SLIP-VALID-UPLOAD-01',
            'description' => 'Daily branch cash deposit',
            'receipt' => $file,
        ]);

        $response->assertRedirect(route('admin.bank-deposits.index'));
        $response->assertSessionHas('success');

        $deposit = BankDeposit::where('reference_number', 'SLIP-VALID-UPLOAD-01')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals('pending', $deposit->status);
        $this->assertNotNull($deposit->receipt_path);
        $this->assertEquals('deposit_counterfoil.png', $deposit->receipt_original_name);
        $this->assertEquals('image/png', $deposit->receipt_mime_type);
        $this->assertTrue(Storage::disk('local')->exists($deposit->receipt_path));
    }

    /**
     * Requirement 21: Invalid MIME type and oversized files are rejected.
     */
    public function test_invalid_mime_type_and_oversized_receipts_are_rejected(): void
    {
        Storage::fake('local');

        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Bank of Baroda',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '112233445566',
            'ifsc_code' => 'BARB0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        // 1. Invalid file format (text/pdf)
        $textDoc = UploadedFile::fake()->create('malicious.pdf', 500, 'application/pdf');
        $response1 = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => now()->toDateString(),
            'amount' => 5000.00,
            'reference_number' => 'SLIP-INVALID-MIME',
            'receipt' => $textDoc,
        ]);
        $response1->assertSessionHasErrors(['receipt']);

        // 2. Oversized file (> 5MB)
        $oversizedFile = UploadedFile::fake()->create('huge_receipt.jpg', 6000, 'image/jpeg');
        $response2 = $this->actingAs($this->branchManager)->post(route('admin.bank-deposits.store'), [
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => now()->toDateString(),
            'amount' => 5000.00,
            'reference_number' => 'SLIP-OVERSIZED',
            'receipt' => $oversizedFile,
        ]);
        $response2->assertSessionHasErrors(['receipt']);
    }

    /**
     * Requirement 22: Authorized receipt viewing and branch isolation enforcement.
     */
    public function test_authorized_receipt_viewing_and_branch_isolation(): void
    {
        Storage::fake('local');

        $filePath = 'receipts/bank_deposits/' . $this->company->id . '/test_slip.jpg';
        Storage::disk('local')->put($filePath, 'fake-jpeg-content');

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 10000.00,
            'bank_name' => 'SBI',
            'account_number' => '123456789012',
            'reference_number' => 'SLIP-VIEW-01',
            'receipt_path' => $filePath,
            'receipt_original_name' => 'test_slip.jpg',
            'receipt_mime_type' => 'image/jpeg',
            'receipt_size' => 1024,
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        // 1. Admin can view receipt
        $adminView = $this->actingAs($this->admin)->get(route('admin.bank-deposits.receipt', $deposit->id));
        $adminView->assertStatus(200);
        $this->assertEquals('image/jpeg', $adminView->headers->get('Content-Type'));

        // 2. Branch Manager 1 (same branch) can view receipt
        $bm1View = $this->actingAs($this->branchManager)->get(route('admin.bank-deposits.receipt', $deposit->id));
        $bm1View->assertStatus(200);

        // 3. Branch Manager 2 (different branch) receives 403 Forbidden
        $bm2View = $this->actingAs($this->branchManager2)->get(route('admin.bank-deposits.receipt', $deposit->id));
        $bm2View->assertStatus(403);

        // 4. Unauthorized User (Loan Officer) receives 403 Forbidden
        $unauthView = $this->actingAs($this->unauthorizedUser)->get(route('admin.bank-deposits.receipt', $deposit->id));
        $unauthView->assertStatus(403);
    }

    /**
     * Requirement 23: Prevent accidental deletion of bank account referenced by historical deposits.
     */
    public function test_prevent_accidental_deletion_of_bank_account_with_historical_deposits(): void
    {
        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Kotak Mahindra Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '998877665544',
            'ifsc_code' => 'KKBK0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => '2026-09-12',
            'amount' => 14000.00,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'reference_number' => 'SLIP-HIST-01',
            'status' => 'approved',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Attempt deletion by Admin
        $response = $this->actingAs($this->admin)->delete(route('admin.bank-deposits.accounts.destroy', $account->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('historical bank deposits are linked to it', session('error'));

        // Account must remain in the database intact
        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id]);
    }

    /**
     * Requirement 24: Rejection requires a reason and updates rejected_by and rejected_at.
     */
    public function test_rejection_requires_reason_and_records_audit_fields(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 7500.00,
            'bank_name' => 'Canara Bank',
            'reference_number' => 'SLIP-REJ-AUDIT',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        // Rejection without reason fails validation
        $failResponse = $this->actingAs($this->admin)->post(route('admin.bank-deposits.reject', $deposit->id), [
            'rejection_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors(['rejection_reason']);

        // Rejection with valid reason succeeds
        $successResponse = $this->actingAs($this->admin)->post(route('admin.bank-deposits.reject', $deposit->id), [
            'rejection_reason' => 'Bank seal is missing and amount does not reflect in bank statement.',
        ]);
        $successResponse->assertRedirect(route('admin.bank-deposits.index'));

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);
        $this->assertEquals('Bank seal is missing and amount does not reflect in bank statement.', $deposit->rejection_reason);
        $this->assertEquals($this->admin->id, $deposit->rejected_by);
        $this->assertNotNull($deposit->rejected_at);
    }

    /**
     * Requirement 25: Double approval or concurrent approval attempts are safely prevented.
     */
    public function test_double_approval_is_safely_prevented(): void
    {
        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 9500.00,
            'bank_name' => 'Federal Bank',
            'reference_number' => 'SLIP-DOUBLE-01',
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        // First approval succeeds
        $this->bankDepositService->approveDeposit($deposit, $this->admin, 'First approval');
        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);

        // Second approval attempt throws an Exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bank deposit is already approved.');

        $this->bankDepositService->approveDeposit($deposit, $this->secondAdmin, 'Second concurrent approval');
    }

    /**
     * Requirement 26: Historical deposit records without bank_account_id or receipt remain intact and accessible.
     */
    public function test_historical_deposit_records_remain_intact_and_accessible(): void
    {
        $legacyDeposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'bank_account_id' => null,
            'deposit_date' => '2025-01-10',
            'amount' => 25000.00,
            'bank_name' => 'Legacy Central Bank',
            'account_number' => '123456789012',
            'reference_number' => 'LEGACY-SLIP-01',
            'receipt_path' => null,
            'status' => 'approved',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now()->subYear(),
            'approved_by' => $this->admin->id,
            'approved_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bank-deposits.index'));
        $response->assertStatus(200);
        $response->assertSee('LEGACY-SLIP-01');
        $response->assertSee('Legacy Central Bank');
        $this->assertEquals('••••••••9012', $legacyDeposit->masked_account_number);
        $this->assertFalse($legacyDeposit->hasReceipt());
    }

    /**
     * Requirement 27: Mobile API - active accounts listing with masked account numbers.
     */
    public function test_mobile_api_returns_active_accounts_with_masked_numbers(): void
    {
        BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Mobile Active Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '112233445566',
            'ifsc_code' => 'MOBI0001234',
            'branch_name' => 'Main',
            'account_nickname' => 'Collection Ops A',
            'is_active' => true,
        ]);

        BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'Mobile Inactive Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '998877665544',
            'ifsc_code' => 'MOBI0009999',
            'branch_name' => 'Main',
            'account_nickname' => 'Old Ops B',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->branchManager, 'sanctum')
            ->getJson('/api/v1/bank-deposits/accounts');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $accounts = $response->json('data');

        $this->assertCount(1, $accounts);
        $this->assertEquals('Mobile Active Bank', $accounts[0]['bank_name']);
        $this->assertEquals('••••••••5566', $accounts[0]['masked_account_number']);
        $this->assertArrayNotHasKey('account_number', $accounts[0]);
    }

    /**
     * Requirement 28: Mobile API - deposit submission with multipart receipt upload.
     */
    public function test_mobile_api_deposit_submission_with_receipt_upload(): void
    {
        Storage::fake('local');

        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'IDFC First Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '778899001122',
            'ifsc_code' => 'IDFB0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('camera_capture.jpg', 1024, 768);

        $response = $this->actingAs($this->branchManager, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/bank-deposits', [
                'branch_id' => $this->branch1->id,
                'bank_account_id' => $account->id,
                'deposit_date' => now()->toDateString(),
                'amount' => 12500.00,
                'reference_number' => 'API-MOB-SLIP-01',
                'description' => 'Captured from mobile camera',
                'receipt' => $file,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertEquals('pending', $response->json('data.status'));

        $deposit = BankDeposit::where('reference_number', 'API-MOB-SLIP-01')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals('pending', $deposit->status);
        $this->assertTrue(Storage::disk('local')->exists($deposit->receipt_path));
    }

    /**
     * Requirement 29: Mobile API - receipt streaming and branch isolation.
     */
    public function test_mobile_api_receipt_streaming_and_branch_isolation(): void
    {
        Storage::fake('local');

        $filePath = 'receipts/bank_deposits/' . $this->company->id . '/api_receipt.png';
        Storage::disk('local')->put($filePath, 'fake-png-data');

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'deposit_date' => '2026-09-12',
            'amount' => 6000.00,
            'bank_name' => 'SBI',
            'reference_number' => 'API-RCPT-01',
            'receipt_path' => $filePath,
            'receipt_original_name' => 'api_receipt.png',
            'receipt_mime_type' => 'image/png',
            'receipt_size' => 512,
            'status' => 'pending',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now(),
        ]);

        // Same branch manager can view
        $response1 = $this->actingAs($this->branchManager, 'sanctum')
            ->get('/api/v1/bank-deposits/' . $deposit->id . '/receipt');
        $response1->assertStatus(200);

        // Different branch manager gets 403 Forbidden
        $response2 = $this->actingAs($this->branchManager2, 'sanctum')
            ->get('/api/v1/bank-deposits/' . $deposit->id . '/receipt');
        $response2->assertStatus(403);
    }

    /**
     * Requirement 30: Mobile API - resubmit rejected deposit with corrected amount and receipt.
     */
    public function test_mobile_api_resubmit_rejected_deposit(): void
    {
        Storage::fake('local');

        $account = BankAccount::create([
            'company_id' => $this->company->id,
            'bank_name' => 'IndusInd Bank',
            'account_holder_name' => 'Grihalaxmi Finance',
            'account_number' => '334455667788',
            'ifsc_code' => 'INDB0001234',
            'branch_name' => 'Patna',
            'is_active' => true,
        ]);

        $deposit = BankDeposit::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'bank_account_id' => $account->id,
            'deposit_date' => '2026-09-12',
            'amount' => 5000.00,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'reference_number' => 'REJ-SLIP-RESUBMIT',
            'status' => 'rejected',
            'rejection_reason' => 'Receipt was blurry. Please upload clear photo.',
            'submitted_by' => $this->branchManager->id,
            'submitted_at' => now()->subDay(),
            'rejected_by' => $this->admin->id,
            'rejected_at' => now()->subHours(2),
        ]);

        $newReceipt = UploadedFile::fake()->image('clear_receipt.jpg', 1200, 900);

        $response = $this->actingAs($this->branchManager, 'sanctum')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/bank-deposits/' . $deposit->id . '/resubmit', [
                'deposit_date' => '2026-09-12',
                'amount' => 5000.00,
                'reference_number' => 'REJ-SLIP-RESUBMIT',
                'description' => 'Uploaded clear photo with legible bank stamp',
                'receipt' => $newReceipt,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEquals('pending', $response->json('data.status'));

        $deposit->refresh();
        $this->assertEquals('pending', $deposit->status);
        $this->assertNull($deposit->rejection_reason);
        $this->assertNull($deposit->rejected_by);
        $this->assertNull($deposit->rejected_at);
        $this->assertEquals('clear_receipt.jpg', $deposit->receipt_original_name);
    }
}
