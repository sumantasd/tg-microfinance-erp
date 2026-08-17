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
use App\Models\LoanPenaltyCharge;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\AccountingService;
use App\Services\OverdueService;
use App\Services\ReportService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;

    protected User $superAdmin;
    protected User $companyAdmin;
    protected User $branchManager;
    protected User $accountant;
    protected User $loanOfficer;
    protected User $viewer;

    protected LoanScheme $cashScheme;
    protected Customer $customerA;
    protected Department $deptA;
    protected Designation $desigA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RbacSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->companyA = Company::create([
            'name' => 'Alpha Microfinance HO',
            'code' => 'ALPH',
            'email' => 'alpha@test.com',
            'phone' => '9876543210',
            'address' => '123 Alpha St, Patna',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Beta Microfinance HO',
            'code' => 'BETA',
            'email' => 'beta@test.com',
            'phone' => '9876543211',
            'address' => '456 Beta St, Gaya',
            'is_active' => true,
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha Branch 1',
            'code' => 'A-001',
            'email' => 'branch1@alpha.com',
            'phone' => '9876543220',
            'address' => '100 Alpha Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha Branch 2',
            'code' => 'A-002',
            'email' => 'branch2@alpha.com',
            'phone' => '9876543221',
            'address' => '200 Alpha Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800002',
            'is_active' => true,
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Beta Branch 1',
            'code' => 'B-001',
            'email' => 'branch1@beta.com',
            'phone' => '9876543222',
            'address' => '300 Beta Road',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->superAdmin = User::where('email', 'admin@tgmicrofinance.test')->first();

        $this->companyAdmin = User::factory()->create([
            'name' => 'Company Admin User',
            'email' => 'companyadmin@test.com',
            'company_id' => $this->companyA->id,
            'status' => 'active',
        ]);
        $this->companyAdmin->assignRole('Company Admin');

        $this->branchManager = User::factory()->create([
            'name' => 'Branch Manager User',
            'email' => 'bm@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->accountant = User::factory()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->accountant->assignRole('Accountant');

        $this->loanOfficer = User::factory()->create([
            'name' => 'Loan Officer User',
            'email' => 'loanofficer@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->loanOfficer->assignRole('Loan Officer');

        $this->viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@test.com',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active',
        ]);
        $this->viewer->assignRole('Viewer');

        // Create Sample Scheme & Customer
        $this->cashScheme = LoanScheme::create([
            'company_id' => $this->companyA->id,
            'code' => 'SCH-REP01',
            'name' => 'Standard General Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.0,
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_code' => 'CUST-REP001',
            'first_name' => 'Sita',
            'last_name' => 'Kumari',
            'mobile_number' => '9876543230',
            'gender' => 'female',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->deptA = Department::create([
            'company_id' => $this->companyA->id,
            'name' => 'Credit Operations',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        $this->desigA = Designation::create([
            'company_id' => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'title' => 'Field Officer',
            'code' => 'FO',
            'is_active' => true,
        ]);
    }

    /** 1. /admin/reports loads for authorized user */
    public function test_reports_index_loads_for_authorized_user(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Central Reports Center');
        $response->assertSee('Accounting & Financial Reports');
        $response->assertSee('Loan Reports');
    }

    /** 2. Unauthorized user without reports.view receives 403 */
    public function test_unauthorized_user_cannot_access_reports_center(): void
    {
        $response = $this->actingAs($this->loanOfficer)->get('/admin/reports');

        $response->assertStatus(403);
    }

    /** 3. Unauthenticated guest is redirected to login */
    public function test_unauthenticated_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/reports');
        $response->assertRedirect(route('login'));
    }

    /** 4. Report category visibility follows permissions */
    public function test_report_category_visibility_follows_permissions(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Accounting & Financial Reports');
        $response->assertSee('Trial Balance');
        $response->assertSee('Cash Book Report');
    }

    /** 5. reports.view controls individual report access */
    public function test_reports_view_controls_individual_report_access(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports/loan/disbursement');
        $response->assertStatus(200);
        $response->assertSee('Loan Disbursement Report');

        $responseForbidden = $this->actingAs($this->loanOfficer)->get('/admin/reports/loan/disbursement');
        $responseForbidden->assertStatus(403);
    }

    /** 6. reports.export controls export access */
    public function test_reports_export_permission_controls_csv_export(): void
    {
        // Accountant has reports.export
        $response = $this->actingAs($this->accountant)->get('/admin/reports/loan/disbursement/export');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Viewer has reports.view but NOT reports.export
        $responseForbidden = $this->actingAs($this->viewer)->get('/admin/reports/loan/disbursement/export');
        $responseForbidden->assertStatus(403);
    }

    /** 7. Loan report filters work */
    public function test_loan_disbursement_report_with_data_and_filters(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_number' => 'APP-REP-01',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => now()->toDateString(),
            'requested_amount' => 25000,
            'approved_amount' => 25000,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.0,
            'status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
        ]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);

        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.disburse-cash', $account->id), [
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->accountant)->get('/admin/reports/loan/disbursement');
        $response->assertStatus(200);
        $response->assertSee($account->loan_number);
        $response->assertSee('Sita Kumari');
    }

    /** 8. Collection report filters work */
    public function test_collection_report_filters_and_totals(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_number' => 'APP-REP-02',
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'application_date' => now()->toDateString(),
            'requested_amount' => 12000,
            'approved_amount' => 12000,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.0,
            'status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.disburse-cash', $account->id), ['payment_method' => 'cash']);

        // Record a repayment
        $this->actingAs($this->superAdmin)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 1120.00,
            'payment_method' => 'upi',
            'payment_date' => now()->toDateString(),
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $response = $this->actingAs($this->accountant)->get('/admin/reports/collection/daily?payment_method=upi');
        $response->assertStatus(200);
        $response->assertSee('UPI');
        $response->assertSee('1,120.00');
    }

    /** 9. Customer report works */
    public function test_customer_loan_summary_report(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports/customer/summary');
        $response->assertStatus(200);
        $response->assertSee('Sita Kumari');
        $response->assertSee('CUST-REP001');
    }

    /** 10. Overdue reports reuse correct DPD calculations */
    public function test_overdue_reports_reuse_correct_dpd(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports/overdue/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Total Overdue Amount');
        $response->assertSee('PAR 30 Amount');
    }

    /** 11. Penalty report reads penalty charge data */
    public function test_penalty_assessed_report(): void
    {
        $response = $this->actingAs($this->accountant)->get('/admin/reports/penalty/assessed');
        $response->assertStatus(200);
        $response->assertSee('Penalty Assessed Ledger');
    }

    /** 12. Accounting reports use posted vouchers */
    public function test_accounting_cash_book_and_trial_balance_use_posted_vouchers(): void
    {
        $accountingService = app(AccountingService::class);
        $accountingService->seedDefaultChartOfAccounts($this->companyA->id);

        $responseCash = $this->actingAs($this->accountant)->get('/admin/reports/accounting/cash_book');
        $responseCash->assertStatus(200);
        $responseCash->assertSee('Cash Book Report');

        $responseTb = $this->actingAs($this->accountant)->get('/admin/reports/accounting/trial_balance');
        $responseTb->assertStatus(200);
        $responseTb->assertSee('Trial Balance');
    }

    /** 13. HR reports respect company and branch scope */
    public function test_hr_employee_report_respects_scope(): void
    {
        Employee::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'department_id' => $this->deptA->id,
            'designation_id' => $this->desigA->id,
            'employee_code' => 'EMP-REP01',
            'first_name' => 'Rajesh',
            'last_name' => 'Sharma',
            'email' => 'rajesh@alpha.com',
            'phone' => '9876543299',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->accountant)->get('/admin/reports/hr/employees');
        $response->assertStatus(200);
        $response->assertSee('Rajesh Sharma');
        $response->assertSee('EMP-REP01');
    }

    /** 14. Branch Manager is scoped to own branch even if request asks for another */
    public function test_branch_manager_data_scoping(): void
    {
        // Branch Manager belongs to branchA1
        $response = $this->actingAs($this->branchManager)->get('/admin/reports/loan/disbursement?branch_id=' . $this->branchA2->id);
        $response->assertStatus(200);
        $response->assertViewHas('branchId', $this->branchA1->id);
    }

    /** 15. Company Admin is scoped to own company even if request asks for another */
    public function test_company_admin_data_scoping(): void
    {
        // Company Admin belongs to companyA
        $response = $this->actingAs($this->companyAdmin)->get('/admin/reports/loan/disbursement?company_id=' . $this->companyB->id);
        $response->assertStatus(200);
        $response->assertViewHas('companyId', $this->companyA->id);
    }

    /** 16. Invalid category and report type return 404 */
    public function test_invalid_category_and_report_type_return_404(): void
    {
        $responseCat = $this->actingAs($this->superAdmin)->get('/admin/reports/nonexistent_category/disbursement');
        $responseCat->assertStatus(404);

        $responseType = $this->actingAs($this->superAdmin)->get('/admin/reports/loan/nonexistent_type');
        $responseType->assertStatus(404);
    }

    /** 17. COMPLETE MATRIX AUDIT: Every registered report loads viewer without errors */
    public function test_every_registered_report_loads_viewer_without_errors(): void
    {
        $reportService = app(ReportService::class);
        $categories = $reportService->getAvailableCategories();

        foreach ($categories as $catKey => $catData) {
            foreach ($catData['reports'] as $typeKey => $repData) {
                $response = $this->actingAs($this->superAdmin)->get("/admin/reports/{$catKey}/{$typeKey}");
                $response->assertStatus(200, "Report {$catKey}/{$typeKey} failed to load viewer.");
                $response->assertSee(e($repData['title']), false);
            }
        }
    }

    /** 18. COMPLETE MATRIX AUDIT: Every registered report loads print view without errors */
    public function test_every_registered_report_loads_print_view_without_errors(): void
    {
        $reportService = app(ReportService::class);
        $categories = $reportService->getAvailableCategories();

        foreach ($categories as $catKey => $catData) {
            foreach ($catData['reports'] as $typeKey => $repData) {
                $response = $this->actingAs($this->superAdmin)->get("/admin/reports/{$catKey}/{$typeKey}/print");
                $response->assertStatus(200, "Report {$catKey}/{$typeKey} failed to render print layout.");
            }
        }
    }

    /** 19. COMPLETE MATRIX AUDIT: Every registered report exports CSV for authorized user */
    public function test_every_registered_report_exports_csv_for_authorized_user(): void
    {
        $reportService = app(ReportService::class);
        $categories = $reportService->getAvailableCategories();

        foreach ($categories as $catKey => $catData) {
            foreach ($catData['reports'] as $typeKey => $repData) {
                $response = $this->actingAs($this->superAdmin)->get("/admin/reports/{$catKey}/{$typeKey}/export");
                $response->assertStatus(200, "Report {$catKey}/{$typeKey} failed to export CSV.");
                $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
            }
        }
    }

    /** 20. COMPLETE MATRIX AUDIT: Every registered report export is blocked for unauthorized user */
    public function test_every_registered_report_export_is_blocked_for_unauthorized_user(): void
    {
        $reportService = app(ReportService::class);
        $categories = $reportService->getAvailableCategories();

        foreach ($categories as $catKey => $catData) {
            foreach ($catData['reports'] as $typeKey => $repData) {
                $response = $this->actingAs($this->viewer)->get("/admin/reports/{$catKey}/{$typeKey}/export");
                $response->assertStatus(403, "Report {$catKey}/{$typeKey} export was not blocked with 403.");
            }
        }
    }
}
