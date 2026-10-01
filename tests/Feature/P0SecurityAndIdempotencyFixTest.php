<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class P0SecurityAndIdempotencyFixTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected User $viewOnlyUser;
    protected User $authorizedUser;
    protected LoanScheme $loanScheme;
    protected Customer $customer;
    protected LoanApplication $application;
    protected LoanAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'loan.view',
            'customer.view',
            'loan_application.view',
            'loan_application.create',
            'loan_application.review',
            'loan_application.approve',
            'loan_application.reject',
            'loan_foreclosure.process',
            'loan_settlement.request',
            'loan_settlement.approve',
            'loan_write_off.request',
            'cashbook.view',
            'cashbook.create_entry',
            'expense.view',
            'expense.create',
            'reports.view',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $viewOnlyRole = Role::firstOrCreate(['name' => 'Loan Viewer Role', 'guard_name' => 'web']);
        $viewOnlyRole->syncPermissions(['loan.view', 'customer.view']);

        $authorizedRole = Role::firstOrCreate(['name' => 'Loan Officer Role', 'guard_name' => 'web']);
        $authorizedRole->syncPermissions([
            'loan.view',
            'loan_application.view',
            'loan_application.create',
            'loan_application.review',
            'loan_application.approve',
            'loan_application.reject',
            'loan_foreclosure.process',
            'cashbook.view',
            'cashbook.create_entry',
            'expense.view',
            'expense.create',
            'reports.view',
        ]);

        $this->company = Company::firstOrCreate(['code' => 'P0COMP'], [
            'name' => 'P0 Test Company',
            'email' => 'p0@company.com',
            'phone' => '9800098000',
            'address' => 'P0 Address',
        ]);

        $this->branch = Branch::firstOrCreate(['code' => 'P0BR'], [
            'company_id' => $this->company->id,
            'name' => 'P0 Test Branch',
            'phone' => '033990011',
            'address' => 'Branch Address',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'opening_date' => '2026-01-01',
        ]);

        $this->viewOnlyUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'View Only User',
            'email' => 'viewonly_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $this->viewOnlyUser->assignRole($viewOnlyRole);

        $this->authorizedUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Authorized User',
            'email' => 'auth_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $this->authorizedUser->assignRole($authorizedRole);

        $this->loanScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'P0 Standard Scheme',
            'code' => 'SCH-' . rand(1000, 9999),
            'min_amount' => 1000,
            'max_amount' => 50000,
            'min_tenure_months' => 3,
            'max_tenure_months' => 24,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'Test',
            'last_name' => 'Borrower',
            'customer_code' => 'CUST-' . rand(1000, 9999),
            'mobile_number' => '98000' . rand(10000, 99999),
            'gender' => 'male',
            'kyc_status' => 'verified',
            'registration_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $this->application = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'application_number' => 'APP-' . rand(10000, 99999),
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'requested_amount' => 10000,
            'approved_amount' => 10000,
            'tenure_months' => 6,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'status' => 'submitted',
            'application_date' => '2026-02-01',
            'created_by' => $this->authorizedUser->id,
        ]);

        $this->account = LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_application_id' => $this->application->id,
            'loan_number' => 'ACC-' . rand(10000, 99999),
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'sanctioned_amount' => 10000,
            'disbursed_amount' => 10000,
            'principal_outstanding' => 10000,
            'interest_outstanding' => 1200,
            'total_outstanding' => 11200,
            'tenure_months' => 6,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'flat',
            'repayment_frequency' => 'monthly',
            'status' => 'active',
            'sanction_date' => '2026-02-01',
            'disbursement_date' => '2026-02-01',
        ]);
    }

    public function test_view_only_user_cannot_review_or_approve_loan_application_via_api(): void
    {
        $response = $this->actingAs($this->viewOnlyUser, 'sanctum')
            ->postJson("/api/v1/loans/applications/{$this->application->id}/review", [
                'action' => 'approve',
                'approved_amount' => 10000,
            ]);

        $response->assertStatus(403);
    }

    public function test_view_only_user_cannot_process_loan_settlement_or_foreclosure_via_api(): void
    {
        $response = $this->actingAs($this->viewOnlyUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'foreclosure',
                'execute_now' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_review_loan_application_via_api(): void
    {
        $response = $this->actingAs($this->authorizedUser, 'sanctum')
            ->postJson("/api/v1/loans/applications/{$this->application->id}/review", [
                'action' => 'approve',
                'approved_amount' => 10000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_idempotent_loan_application_submission_replays_response(): void
    {
        $idempotencyKey = 'IDEM-LOAN-KEY-999';

        $payload = [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 15000,
            'tenure_months' => 12,
        ];

        $initialCount = LoanApplication::count();

        // 1st request
        $response1 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $payload);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertEquals($initialCount + 1, LoanApplication::count());
        $createdId = $response1->json('data.id');

        // 2nd retry request with exact same idempotency key
        $response2 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $payload);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $createdId);

        // Total count in database must remain initialCount + 1 (no duplicate record)
        $this->assertEquals($initialCount + 1, LoanApplication::count());
    }

    public function test_idempotent_key_reuse_with_different_payload_is_rejected(): void
    {
        $idempotencyKey = 'IDEM-REUSE-KEY-888';

        $payload1 = [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 5000,
            'tenure_months' => 6,
        ];

        $response1 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $payload1);

        $response1->assertStatus(201);

        // Retry with same idempotency key but modified requested_amount
        $payload2 = array_merge($payload1, ['requested_amount' => 9000]);

        $response2 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $payload2);

        $response2->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Idempotency key reused with different request parameters');
    }

    public function test_report_print_layout_renders_safely_when_rows_key_is_missing(): void
    {
        $view = $this->actingAs($this->authorizedUser)->view('admin.reports.print', [
            'company' => $this->company,
            'branch' => $this->branch,
            'reportData' => [
                'title' => 'Test Safe Report',
                'columns' => ['id' => 'ID', 'name' => 'Name'],
                // 'rows' is intentionally omitted
            ],
            'filters' => [],
        ]);

        $view->assertSee('Test Safe Report');
        $view->assertSee('No records found');
    }

    public function test_expense_idempotency_replays_success_and_rejects_payload_change(): void
    {
        $category = \App\Models\ExpenseCategory::create([
            'company_id' => $this->company->id,
            'category_code' => 'P0-EXP-CAT',
            'category_name' => 'P0 Office Expenses',
            'is_active' => true,
        ]);

        $idempotencyKey = 'IDEM-EXP-KEY-777';
        $payload1 = [
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'expense_date' => '2026-02-16',
            'amount' => 1500.00,
            'tax_amount' => 0.00,
            'payee_name' => 'Stationery Mart',
            'description' => 'Office Files and Pens',
        ];

        $initialCount = \App\Models\Expense::count();

        // 1st request
        $response1 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/expenses', $payload1);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertEquals($initialCount + 1, \App\Models\Expense::count());
        $expenseId = $response1->json('data.id');

        // 2nd retry request with matching payload
        $response2 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/expenses', $payload1);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $expenseId);

        $this->assertEquals($initialCount + 1, \App\Models\Expense::count());

        // Retry with changed payload
        $payload2 = array_merge($payload1, ['amount' => 3000.00]);
        $response3 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/expenses', $payload2);

        $response3->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Idempotency key reused with different request parameters');
    }

    public function test_failed_request_does_not_lock_idempotency_key_allowing_retry(): void
    {
        $idempotencyKey = 'IDEM-FAIL-RETRY-555';

        // Invalid payload missing required customer_id
        $invalidPayload = [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'requested_amount' => 5000,
            'tenure_months' => 6,
        ];

        $response1 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $invalidPayload);

        $response1->assertStatus(422);

        // Corrected payload using same idempotency key succeeds
        $validPayload = array_merge($invalidPayload, ['customer_id' => $this->customer->id]);

        $response2 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $validPayload);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_concurrent_processing_request_returns_409_conflict(): void
    {
        $idempotencyKey = 'IDEM-LOCK-KEY-409';
        $endpoint = 'POST api/v1/loans/applications';

        $payload = [
            'branch_id' => $this->branch->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 7000,
            'tenure_months' => 6,
        ];

        $payloadToHash = json_encode([
            'path' => 'api/v1/loans/applications',
            'query' => [],
            'body' => $payload,
        ]);
        $requestHash = hash('sha256', $payloadToHash);

        // Simulate an in-flight processing record
        \App\Models\ApiIdempotencyKey::create([
            'user_id' => $this->authorizedUser->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'endpoint' => $endpoint,
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/loans/applications', $payload);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A request with this idempotency key is currently processing');
    }

    public function test_settlement_action_specific_permissions_and_branch_isolation(): void
    {
        // 1. OTS User (has loan_settlement.request only)
        $otsRole = Role::firstOrCreate(['name' => 'OTS Requester Role', 'guard_name' => 'web']);
        $otsRole->syncPermissions(['loan_settlement.request']);

        $otsUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'OTS User',
            'email' => 'ots_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $otsUser->assignRole($otsRole);

        // OTS User can request OTS settlement
        $resOts = $this->actingAs($otsUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'settlement_ots',
                'proposed_settlement_amount' => 9000,
                'remarks' => 'OTS request by authorized officer',
            ]);
        $resOts->assertStatus(201)
            ->assertJsonPath('success', true);

        // OTS User CANNOT process foreclosure or write-off
        $resForeclosureDeny = $this->actingAs($otsUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'foreclosure',
                'execute_now' => true,
            ]);
        $resForeclosureDeny->assertStatus(403);

        $resWriteOffDeny = $this->actingAs($otsUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'write_off',
                'remarks' => 'Unauthorized write off attempt',
            ]);
        $resWriteOffDeny->assertStatus(403);

        // 2. Write-Off User (has loan_write_off.request only)
        $writeOffRole = Role::firstOrCreate(['name' => 'WriteOff Requester Role', 'guard_name' => 'web']);
        $writeOffRole->syncPermissions(['loan_write_off.request']);

        $writeOffUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'WriteOff User',
            'email' => 'wo_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $writeOffUser->assignRole($writeOffRole);

        // Write-Off User can request write-off
        $resWriteOff = $this->actingAs($writeOffUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'write_off',
                'remarks' => 'Write off request by authorized credit officer',
            ]);
        $resWriteOff->assertStatus(201)
            ->assertJsonPath('success', true);

        // Write-Off User CANNOT request OTS or foreclosure
        $resOtsDeny = $this->actingAs($writeOffUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'settlement_ots',
                'proposed_settlement_amount' => 8000,
            ]);
        $resOtsDeny->assertStatus(403);

        // 3. Branch Data Isolation Check
        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Other Branch',
            'code' => 'OTH' . rand(100, 999),
            'phone' => '033112233',
            'address' => 'Other Address',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700002',
            'opening_date' => '2026-01-01',
        ]);

        $otherBranchUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch2->id,
            'name' => 'Other Branch Officer',
            'email' => 'other_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $otherBranchUser->assignRole($otsRole);

        // Attempting to settle loan from branch1 using user from branch2 must be blocked
        $resBranchDeny = $this->actingAs($otherBranchUser, 'sanctum')
            ->postJson("/api/v1/loans/accounts/{$this->account->id}/settlements", [
                'request_type' => 'settlement_ots',
                'proposed_settlement_amount' => 9000,
            ]);
        $resBranchDeny->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized access to process settlement for another branch');
    }

    public function test_cash_book_entry_idempotency_replays_response_and_enforces_branch_scoping(): void
    {
        $idempotencyKey = 'IDEM-CASHBOOK-KEY-333';
        $payload1 = [
            'branch_id' => $this->branch->id,
            'entry_type' => 'received',
            'particulars' => 'Direct Branch Cash Collection',
            'cash_amount' => 2500.00,
            'date' => '2026-02-16',
            'remarks' => 'Field agent deposit',
        ];

        $initialEntryCount = \App\Models\CashBookEntry::count();

        // 1st request
        $response1 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/cash-book/entries', $payload1);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true);

        $entryCountAfterFirstRequest = \App\Models\CashBookEntry::count();
        $entryId = $response1->json('data.id');

        // 2nd retry request with matching idempotency key and payload
        $response2 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/cash-book/entries', $payload1);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $entryId);

        // Count must remain exact same (no duplicate cash-book entry created on retry)
        $this->assertEquals($entryCountAfterFirstRequest, \App\Models\CashBookEntry::count());

        // 3rd request with same key but modified payload (different cash_amount)
        $payload2 = array_merge($payload1, ['cash_amount' => 5000.00]);
        $response3 = $this->actingAs($this->authorizedUser, 'sanctum')
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/cash-book/entries', $payload2);

        $response3->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Idempotency key reused with different request parameters');

        // 4. Branch Isolation Check
        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'CashBook Isolation Branch',
            'code' => 'CBISO' . rand(100, 999),
            'phone' => '033445566',
            'address' => 'CBISO Address',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700003',
            'opening_date' => '2026-01-01',
        ]);

        $unauthorizedBranchUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch2->id,
            'name' => 'Foreign Branch Officer',
            'email' => 'foreign_cb_' . uniqid() . '@test.com',
            'password' => bcrypt('Password123'),
            'status' => 'active',
        ]);
        $unauthorizedBranchUser->givePermissionTo('cashbook.create_entry');

        $resBranchDeny = $this->actingAs($unauthorizedBranchUser, 'sanctum')
            ->postJson('/api/v1/cash-book/entries', [
                'branch_id' => $this->branch->id, // Attempting to post to branch1 from branch2 user
                'entry_type' => 'received',
                'particulars' => 'Cross Branch Cash Entry Attempt',
                'cash_amount' => 1000.00,
            ]);

        $resBranchDeny->assertStatus(403)
            ->assertJsonPath('message', 'Unauthorized access to add cash book entry in another branch');
    }
}
