<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanScheme;
use App\Models\User;
use App\Services\LoanAccountService;
use App\Services\OverdueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueAndDpdManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $adminUser;
    protected User $branchOfficer;
    protected Customer $customer1;
    protected Customer $customer2;
    protected LoanScheme $loanScheme;
    protected OverdueService $overdueService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RbacSeeder::class);

        $this->overdueService = app(OverdueService::class);

        $this->companyA = Company::create([
            'name' => 'Company A Microfinance',
            'code' => 'COMA',
            'registration_number' => 'REG-COMA-01',
            'email' => 'finance@company-a.com',
            'phone' => '9876543210',
            'address' => 'Patna HQ, Bihar',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B Microfinance',
            'code' => 'COMB',
            'registration_number' => 'REG-COMB-01',
            'email' => 'finance@company-b.com',
            'phone' => '9876543211',
            'address' => 'Gaya HQ, Bihar',
            'is_active' => true,
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Patna Main Branch',
            'code' => 'PAT01',
            'email' => 'patna1@company-a.com',
            'phone' => '9876543212',
            'address' => 'Patna Main, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Danapur Branch',
            'code' => 'DAN01',
            'email' => 'danapur@company-a.com',
            'phone' => '9876543213',
            'address' => 'Danapur, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '801503',
            'is_active' => true,
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Gaya Branch',
            'code' => 'GAY01',
            'email' => 'gaya@company-b.com',
            'phone' => '9876543214',
            'address' => 'Gaya, Bihar',
            'city' => 'Gaya',
            'state' => 'Bihar',
            'pincode' => '823001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Admin User',
            'email' => 'admin@company-a.com',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('Company Admin');

        $this->branchOfficer = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'name' => 'Branch Officer',
            'email' => 'officer@company-a.com',
            'password' => bcrypt('password123'),
        ]);
        $this->branchOfficer->assignRole('Branch Manager');

        $this->customer1 = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Ramesh',
            'last_name' => 'Kumar',
            'mobile_number' => '9876543220',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->customer2 = Customer::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'customer_code' => 'CUST-002',
            'first_name' => 'Anita',
            'last_name' => 'Devi',
            'mobile_number' => '9876543221',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->loanScheme = LoanScheme::create([
            'company_id' => $this->companyA->id,
            'code' => 'SCH-GEN-01',
            'name' => 'General Cash Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'is_active' => true,
        ]);
    }

    protected function createSampleLoan(Customer $customer, Branch $branch, float $principal = 12000.00): LoanAccount
    {
        $app = LoanApplication::create([
            'company_id' => $customer->company_id,
            'branch_id' => $branch->id,
            'application_number' => 'APP-' . uniqid(),
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $customer->id,
            'requested_amount' => $principal,
            'approved_amount' => $principal,
            'application_date' => '2026-01-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $account = LoanAccount::create([
            'loan_number' => 'LN-' . uniqid(),
            'company_id' => $customer->company_id,
            'branch_id' => $branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->loanScheme->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'sanctioned_amount' => $principal,
            'disbursed_amount' => $principal,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => $principal,
            'interest_outstanding' => 1440.00,
            'total_outstanding' => $principal + 1440.00,
            'status' => 'active',
            'sanction_date' => '2026-01-01',
            'disbursement_date' => '2026-01-01',
        ]);

        return $account;
    }

    /**
     * 1 & 2. Future and Due-Today Installments have DPD = 0
     */
    public function test_future_and_due_today_installments_have_zero_dpd(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1);

        $futureInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-20',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);

        $todayInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-08-15',
            'opening_principal' => 11000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 10000,
            'status' => 'pending',
        ]);

        // As of 2026-08-15
        $asOf = '2026-08-15';

        $futureDpd = $this->overdueService->getInstallmentDpd($futureInst, $asOf);
        $this->assertEquals(0, $futureDpd['dpd']);
        $this->assertFalse($futureDpd['is_overdue']);
        $this->assertTrue($futureDpd['is_upcoming']);

        $todayDpd = $this->overdueService->getInstallmentDpd($todayInst, $asOf);
        $this->assertEquals(0, $todayDpd['dpd']);
        $this->assertFalse($todayDpd['is_overdue']);
        $this->assertTrue($todayDpd['is_due_today']);
    }

    /**
     * 3-9. Exact DPD Milestones (1, 30, 31, 60, 61, 90, 91+ Days)
     */
    public function test_exact_dpd_milestones_and_aging_buckets(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1);
        $asOf = '2026-08-15';

        // 1-day overdue: Due 2026-08-14
        $inst1 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-14',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 11000,
            'status' => 'pending',
        ]);
        $dpd1 = $this->overdueService->getInstallmentDpd($inst1, $asOf);
        $this->assertEquals(1, $dpd1['dpd']);
        $this->assertTrue($dpd1['is_overdue']);
        $this->assertEquals('1_30', $this->overdueService->classifyAgingBucket(1)['key']);

        // 30-day overdue: Due 2026-07-16
        $inst30 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-07-16',
            'opening_principal' => 11000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 10000,
            'status' => 'pending',
        ]);
        $dpd30 = $this->overdueService->getInstallmentDpd($inst30, $asOf);
        $this->assertEquals(30, $dpd30['dpd']);
        $this->assertEquals('1_30', $this->overdueService->classifyAgingBucket(30)['key']);

        // 31-day overdue: Due 2026-07-15
        $inst31 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 3,
            'due_date' => '2026-07-15',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);
        $dpd31 = $this->overdueService->getInstallmentDpd($inst31, $asOf);
        $this->assertEquals(31, $dpd31['dpd']);
        $this->assertEquals('31_60', $this->overdueService->classifyAgingBucket(31)['key']);

        // 60-day overdue: Due 2026-06-16
        $inst60 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 4,
            'due_date' => '2026-06-16',
            'opening_principal' => 9000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 8000,
            'status' => 'pending',
        ]);
        $dpd60 = $this->overdueService->getInstallmentDpd($inst60, $asOf);
        $this->assertEquals(60, $dpd60['dpd']);
        $this->assertEquals('31_60', $this->overdueService->classifyAgingBucket(60)['key']);

        // 61-day overdue: Due 2026-06-15
        $inst61 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 5,
            'due_date' => '2026-06-15',
            'opening_principal' => 8000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 7000,
            'status' => 'pending',
        ]);
        $dpd61 = $this->overdueService->getInstallmentDpd($inst61, $asOf);
        $this->assertEquals(61, $dpd61['dpd']);
        $this->assertEquals('61_90', $this->overdueService->classifyAgingBucket(61)['key']);

        // 90-day overdue: Due 2026-05-17
        $inst90 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 6,
            'due_date' => '2026-05-17',
            'opening_principal' => 7000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 6000,
            'status' => 'pending',
        ]);
        $dpd90 = $this->overdueService->getInstallmentDpd($inst90, $asOf);
        $this->assertEquals(90, $dpd90['dpd']);
        $this->assertEquals('61_90', $this->overdueService->classifyAgingBucket(90)['key']);

        // 91-day overdue: Due 2026-05-16
        $inst91 = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 7,
            'due_date' => '2026-05-16',
            'opening_principal' => 6000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'closing_principal' => 5000,
            'status' => 'pending',
        ]);
        $dpd91 = $this->overdueService->getInstallmentDpd($inst91, $asOf);
        $this->assertEquals(91, $dpd91['dpd']);
        $this->assertEquals('90_plus', $this->overdueService->classifyAgingBucket(91)['key']);
    }

    /**
     * 10 & 11. Fully Paid vs Partially Paid Overdue Installment
     */
    public function test_fully_paid_has_zero_dpd_and_partially_paid_overdue_calculates_remaining(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1);
        $asOf = '2026-08-15';

        // Fully paid past due installment
        $paidInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-07-01',
            'opening_principal' => 12000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'principal_paid' => 1000,
            'interest_paid' => 120,
            'total_paid' => 1120,
            'closing_principal' => 11000,
            'status' => 'paid',
        ]);

        $paidDpd = $this->overdueService->getInstallmentDpd($paidInst, $asOf);
        $this->assertEquals(0, $paidDpd['dpd']);
        $this->assertEquals(0.00, $paidDpd['outstanding_amount']);
        $this->assertFalse($paidDpd['is_overdue']);

        // Partially paid past due installment (Due ₹1,120, Paid ₹500, Outstanding ₹620)
        $partialInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-08-01',
            'opening_principal' => 11000,
            'principal_amount' => 1000,
            'interest_amount' => 120,
            'installment_amount' => 1120,
            'interest_paid' => 120,
            'principal_paid' => 380,
            'total_paid' => 500,
            'closing_principal' => 10620,
            'status' => 'partial',
        ]);

        $partialDpd = $this->overdueService->getInstallmentDpd($partialInst, $asOf);
        $this->assertEquals(14, $partialDpd['dpd']); // Aug 1 to Aug 15 = 14 days
        $this->assertEquals(620.00, $partialDpd['outstanding_amount']);
        $this->assertTrue($partialDpd['is_overdue']);
        $this->assertEquals('Partially Paid (Overdue)', $partialDpd['display_status']);
    }

    /**
     * 12-14. Loan Level Overdue: Uses Max/Oldest DPD and Excludes Future Installments
     */
    public function test_loan_level_overdue_uses_max_dpd_and_excludes_future_installments(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1, 24000.00);
        $asOf = '2026-08-15';

        // Inst 1: Overdue by 45 days (Due: 2026-07-01, Amount: 2000, Unpaid)
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-07-01',
            'opening_principal' => 24000,
            'principal_amount' => 2000,
            'interest_amount' => 240,
            'installment_amount' => 2240,
            'closing_principal' => 22000,
            'status' => 'pending',
        ]);

        // Inst 2: Overdue by 14 days (Due: 2026-08-01, Amount: 2000, Partial paid 1000, Rem: 1240)
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-08-01',
            'opening_principal' => 22000,
            'principal_amount' => 2000,
            'interest_amount' => 240,
            'installment_amount' => 2240,
            'principal_paid' => 1000,
            'total_paid' => 1000,
            'closing_principal' => 21000,
            'status' => 'partial',
        ]);

        // Inst 3: Future installment (Due: 2026-09-01, Amount: 2240) - MUST NOT BE IN OVERDUE!
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 3,
            'due_date' => '2026-09-01',
            'opening_principal' => 21000,
            'principal_amount' => 2000,
            'interest_amount' => 240,
            'installment_amount' => 2240,
            'closing_principal' => 19000,
            'status' => 'pending',
        ]);

        $details = $this->overdueService->getLoanOverdueDetails($loan, $asOf);

        // Max DPD = 45 days (from Inst 1)
        $this->assertEquals(45, $details['dpd']);
        $this->assertEquals('31_60', $details['aging_bucket_key']);

        // Overdue amount = 2240 (Inst 1) + 1240 (Inst 2) = 3480. (Future 2240 excluded!)
        $this->assertEquals(3480.00, $details['overdue_amount']);
        $this->assertEquals(2, $details['overdue_installments_count']);
        $this->assertEquals('2026-07-01', $details['oldest_overdue_date']);
        $this->assertEquals('2026-09-01', $details['next_due_date']);
    }

    /**
     * 15. Customer Level Overdue Aggregation Across Multiple Active Loans
     */
    public function test_customer_level_overdue_aggregates_across_multiple_loans(): void
    {
        $asOf = '2026-08-15';

        // Loan 1 for Customer 1 (DPD: 45, Overdue: 3480)
        $loan1 = $this->createSampleLoan($this->customer1, $this->branchA1, 10000.00);
        LoanInstallment::create([
            'loan_account_id' => $loan1->id,
            'installment_number' => 1,
            'due_date' => '2026-07-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Loan 2 for Customer 1 (DPD: 75, Overdue: 1500)
        $loan2 = $this->createSampleLoan($this->customer1, $this->branchA1, 15000.00);
        LoanInstallment::create([
            'loan_account_id' => $loan2->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'opening_principal' => 15000,
            'principal_amount' => 1500,
            'interest_amount' => 150,
            'installment_amount' => 1650,
            'principal_paid' => 150,
            'total_paid' => 150,
            'closing_principal' => 13500,
            'status' => 'partial',
        ]);

        $summary = $this->overdueService->getCustomerOverdueSummary($this->customer1, $asOf);

        $this->assertEquals(2, $summary['active_loans_count']);
        $this->assertEquals(2, $summary['delinquent_loans_count']);
        // Overdue = 1100 (Loan 1) + 1500 (Loan 2) = 2600
        $this->assertEquals(2600.00, $summary['total_overdue_amount']);
        // Max DPD = 75 days (from Loan 2)
        $this->assertEquals(75, $summary['max_dpd']);
        $this->assertEquals('61–90 Days', $summary['aging_bucket']);
        $this->assertEquals('2026-06-01', $summary['oldest_overdue_date']);
    }

    /**
     * 16-20. Branch PAR Metrics (PAR 30, PAR 60, PAR 90) & Zero Division Safety
     */
    public function test_branch_par_metrics_and_zero_division_safety(): void
    {
        $asOf = '2026-08-15';

        // 1. Zero portfolio check: Empty branch B1
        $emptyMetrics = $this->overdueService->getBranchParMetrics($this->companyB->id, $this->branchB1->id, $asOf);
        $this->assertEquals(0.00, $emptyMetrics['total_active_portfolio']);
        $this->assertEquals(0.00, $emptyMetrics['par_30_pct']);
        $this->assertEquals(0.00, $emptyMetrics['par_60_pct']);
        $this->assertEquals(0.00, $emptyMetrics['par_90_pct']);

        // 2. Populate Branch A1:
        // Loan 1: Principal 10,000, DPD = 40 (PAR 30 numerator: +10,000)
        $loan1 = $this->createSampleLoan($this->customer1, $this->branchA1, 10000.00);
        LoanInstallment::create([
            'loan_account_id' => $loan1->id,
            'installment_number' => 1,
            'due_date' => '2026-07-06', // 40 days
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Loan 2: Principal 20,000, DPD = 70 (PAR 30: +20,000, PAR 60: +20,000)
        $loan2 = $this->createSampleLoan($this->customer2, $this->branchA1, 20000.00);
        LoanInstallment::create([
            'loan_account_id' => $loan2->id,
            'installment_number' => 1,
            'due_date' => '2026-06-06', // 70 days
            'opening_principal' => 20000,
            'principal_amount' => 2000,
            'interest_amount' => 200,
            'installment_amount' => 2200,
            'closing_principal' => 18000,
            'status' => 'pending',
        ]);

        // Loan 3: Principal 10,000, DPD = 0 (Current)
        $loan3 = $this->createSampleLoan($this->customer1, $this->branchA1, 10000.00);
        LoanInstallment::create([
            'loan_account_id' => $loan3->id,
            'installment_number' => 1,
            'due_date' => '2026-08-25', // Future
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Total active portfolio in Branch A1 = 10,000 + 20,000 + 10,000 = 40,000
        $metrics = $this->overdueService->getBranchParMetrics($this->companyA->id, $this->branchA1->id, $asOf);

        $this->assertEquals(40000.00, $metrics['total_active_portfolio']);
        // PAR 30 loans: Loan 1 (10,000) + Loan 2 (20,000) = 30,000 -> 30,000 / 40,000 * 100 = 75.00%
        $this->assertEquals(30000.00, $metrics['par_30_amount']);
        $this->assertEquals(75.00, $metrics['par_30_pct']);

        // PAR 60 loans: Loan 2 (20,000) -> 20,000 / 40,000 * 100 = 50.00%
        $this->assertEquals(20000.00, $metrics['par_60_amount']);
        $this->assertEquals(50.00, $metrics['par_60_pct']);

        // PAR 90 loans: 0 -> 0.00%
        $this->assertEquals(0.00, $metrics['par_90_amount']);
        $this->assertEquals(0.00, $metrics['par_90_pct']);
    }

    /**
     * 21-23. Timezone, Month-End, and Leap-Year Calculations
     */
    public function test_timezone_month_end_and_leap_year_calculations(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1);

        // Leap Year: Feb 29, 2024 to Mar 02, 2024 = 2 days
        $leapInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2024-02-29',
            'opening_principal' => 1000,
            'principal_amount' => 100,
            'interest_amount' => 10,
            'installment_amount' => 110,
            'closing_principal' => 900,
            'status' => 'pending',
        ]);
        $leapDpd = $this->overdueService->getInstallmentDpd($leapInst, '2024-03-02');
        $this->assertEquals(2, $leapDpd['dpd']);

        // Month-End: Jan 31 to Mar 01 = 29 days in 2026 (non-leap)
        $monthEndInst = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-01-31',
            'opening_principal' => 900,
            'principal_amount' => 100,
            'interest_amount' => 10,
            'installment_amount' => 110,
            'closing_principal' => 800,
            'status' => 'pending',
        ]);
        $monthEndDpd = $this->overdueService->getInstallmentDpd($monthEndInst, '2026-03-01');
        $this->assertEquals(29, $monthEndDpd['dpd']); // 28 days of Feb + 1 day of Mar
    }

    /**
     * 24-25. Daily Sync Command: `php artisan loans:sync-overdue-status`
     */
    public function test_daily_sync_command_updates_statuses_and_is_idempotent(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1);

        $expiredPending = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 1000,
            'principal_amount' => 100,
            'interest_amount' => 10,
            'installment_amount' => 110,
            'closing_principal' => 900,
            'status' => 'pending',
        ]);

        $futurePending = LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-08-25',
            'opening_principal' => 900,
            'principal_amount' => 100,
            'interest_amount' => 10,
            'installment_amount' => 110,
            'closing_principal' => 800,
            'status' => 'pending',
        ]);

        // Run sync command with as-of date 2026-08-15
        $this->artisan('loans:sync-overdue-status', [
            '--company' => $this->companyA->id,
            '--date' => '2026-08-15',
        ])->assertSuccessful();

        $this->assertEquals('overdue', $expiredPending->fresh()->status);
        $this->assertEquals('pending', $futurePending->fresh()->status);

        // Re-run command to verify idempotency
        $this->artisan('loans:sync-overdue-status', [
            '--company' => $this->companyA->id,
            '--date' => '2026-08-15',
        ])->assertSuccessful();

        $this->assertEquals('overdue', $expiredPending->fresh()->status);
        $this->assertEquals('pending', $futurePending->fresh()->status);
    }

    /**
     * 26-27. Company & Branch Permission Isolation
     */
    public function test_company_and_branch_isolation_in_controller_queries(): void
    {
        // Loan in Company A
        $loanA = $this->createSampleLoan($this->customer1, $this->branchA1, 10000.00);
        LoanInstallment::create([
            'loan_account_id' => $loanA->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 10000,
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'closing_principal' => 9000,
            'status' => 'pending',
        ]);

        // Loan in Company B
        $custB = Customer::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'customer_code' => 'CUST-B01',
            'first_name' => 'Bihari',
            'last_name' => 'Lal',
            'mobile_number' => '9876543233',
            'gender' => 'male',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $schemeB = LoanScheme::create([
            'company_id' => $this->companyB->id,
            'code' => 'SCH-B-01',
            'name' => 'Gaya Scheme',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 5000,
            'max_amount' => 100000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'is_active' => true,
        ]);

        $appB = LoanApplication::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'application_number' => 'APP-B-' . uniqid(),
            'loan_scheme_id' => $schemeB->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $custB->id,
            'requested_amount' => 15000.00,
            'approved_amount' => 15000.00,
            'application_date' => '2026-01-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $loanB = LoanAccount::create([
            'loan_number' => 'LN-B-' . uniqid(),
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'loan_application_id' => $appB->id,
            'customer_id' => $custB->id,
            'loan_scheme_id' => $schemeB->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'sanctioned_amount' => 15000.00,
            'disbursed_amount' => 15000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => 15000.00,
            'total_outstanding' => 16800.00,
            'status' => 'active',
            'sanction_date' => '2026-01-01',
        ]);

        LoanInstallment::create([
            'loan_account_id' => $loanB->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 15000,
            'principal_amount' => 1500,
            'interest_amount' => 150,
            'installment_amount' => 1650,
            'closing_principal' => 13500,
            'status' => 'pending',
        ]);

        // Login as Company A Admin
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.overdue.dashboard', ['as_of_date' => '2026-08-15']));
        $response->assertOk();
        $response->assertSee($loanA->loan_number);
        $response->assertDontSee($loanB->loan_number);

        // Accessing Customer B's profile from Company A should abort with 403
        $crossResponse = $this->get(route('admin.overdue.customer-profile', $custB->id));
        $crossResponse->assertForbidden();
    }

    /**
     * 28. Collection Screen Overdue Metric Uses Exact Past-Due Installments Amount
     */
    public function test_collection_screen_overdue_metric_uses_exact_past_due_amount(): void
    {
        $loan = $this->createSampleLoan($this->customer1, $this->branchA1, 20000.00);

        // Past Due Inst: Due ₹2,000 (Past Due)
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-08-01',
            'opening_principal' => 20000,
            'principal_amount' => 2000,
            'interest_amount' => 200,
            'installment_amount' => 2200,
            'closing_principal' => 18000,
            'status' => 'pending',
        ]);

        // Future Inst: Due ₹2,000 (Future Due)
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => now()->addDays(15)->toDateString(),
            'opening_principal' => 18000,
            'principal_amount' => 2000,
            'interest_amount' => 200,
            'installment_amount' => 2200,
            'closing_principal' => 16000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);
        $response = $this->get(route('admin.emi-collection.index'));
        $response->assertOk();

        // Exact Overdue should be 2200 (NOT the full 22,400 loan balance)
        $exactOverdue = $this->overdueService->calculateTotalOverdueAmount($this->companyA->id, $this->branchA1->id);
        $this->assertEquals(2200.00, $exactOverdue);
    }
}
