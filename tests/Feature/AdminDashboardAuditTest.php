<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\InventoryStock;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ProfitLossService;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAuditTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $superAdmin;
    protected User $branchManager1;
    protected User $unassignedManager;
    protected LoanScheme $scheme;
    protected FinancialYear $financialYear;
    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Test Corp',
            'code' => 'GLF01',
            'email' => 'finance@grihalaxmi.com',
            'phone' => '9876543210',
            'address' => 'Corporate Tower, Kolkata',
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Barasat Central Branch',
            'code' => 'BR-BST01',
            'branch_code' => 'BR-BST01',
            'phone' => '9830000001',
            'email' => 'barasat@grihalaxmi.com',
            'address' => 'Barasat Station Road',
            'city' => 'Barasat',
            'state' => 'West Bengal',
            'pincode' => '700124',
            'current_vault_balance' => 25000.00,
            'vault_cash_limit' => 100000.00,
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Madhyamgram Branch',
            'code' => 'BR-MDG02',
            'branch_code' => 'BR-MDG02',
            'phone' => '9830000002',
            'email' => 'madhyamgram@grihalaxmi.com',
            'address' => 'Madhyamgram Chowmatha',
            'city' => 'Madhyamgram',
            'state' => 'West Bengal',
            'pincode' => '700129',
            'current_vault_balance' => 40000.00,
            'vault_cash_limit' => 150000.00,
            'is_active' => true,
        ]);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'title' => 'FY 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Micro Enterprise Loan',
            'code' => 'MEL01',
            'loan_type' => 'cash',
            'applicant_type' => 'individual',
            'min_amount' => 5000.00,
            'max_amount' => 100000.00,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'min_tenure_months' => 1,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'processing_fee_percentage' => 2.00,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->branchManager1 = User::create([
            'name' => 'Branch Manager One',
            'email' => 'bm1@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);
        $this->branchManager1->assignRole('Branch Manager');

        $this->unassignedManager = User::create([
            'name' => 'Unassigned Manager',
            'email' => 'unassigned@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'company_id' => $this->company->id,
            'branch_id' => null,
            'is_active' => true,
        ]);
        $this->unassignedManager->assignRole('Branch Manager');

        $this->dashboardService = app(DashboardService::class);
    }

    /**
     * Helper to create a loan account with valid application
     */
    protected function createLoan(
        Branch $branch,
        Customer $customer,
        string $loanNumber,
        float $sanctionedAmount,
        float $disbursedAmount,
        float $principalOutstanding,
        float $interestOutstanding,
        string $status = 'active',
        ?string $disbursementDate = null
    ): LoanAccount {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'application_number' => 'APP-' . uniqid(),
            'application_date' => now()->toDateString(),
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'requested_amount' => $sanctionedAmount,
            'approved_amount' => $sanctionedAmount,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
            'applied_at' => now(),
            'approved_at' => now(),
        ]);

        return LoanAccount::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_number' => $loanNumber,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'sanctioned_amount' => $sanctionedAmount,
            'disbursed_amount' => $disbursedAmount,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => $principalOutstanding,
            'interest_outstanding' => $interestOutstanding,
            'total_outstanding' => $principalOutstanding + $interestOutstanding,
            'status' => $status,
            'sanction_date' => $disbursementDate ?? now()->toDateString(),
            'disbursement_date' => $disbursementDate ?? now()->toDateString(),
        ]);
    }

    /** 1. Branch isolation: Branch Manager strictly sees assigned branch and cannot access other branches */
    public function test_branch_manager_strictly_sees_assigned_branch_data_and_never_leaks_other_branches(): void
    {
        // Branch 1: 2 customers, 1 loan of 20,000
        $c1 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-B1-01',
            'first_name' => 'Branch1',
            'last_name' => 'Borrower1',
            'mobile_number' => '9800000001',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $c2 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-B1-02',
            'first_name' => 'Branch1',
            'last_name' => 'Borrower2',
            'mobile_number' => '9800000002',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $this->createLoan($this->branch1, $c1, 'LN-B1-001', 20000.00, 20000.00, 15000.00, 1800.00);

        // Branch 2: 1 customer, 1 loan of 50,000
        $c3 = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'customer_code' => 'CUST-B2-01',
            'first_name' => 'Branch2',
            'last_name' => 'Borrower3',
            'mobile_number' => '9800000003',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $this->createLoan($this->branch2, $c3, 'LN-B2-001', 50000.00, 50000.00, 45000.00, 5000.00);

        $this->actingAs($this->branchManager1);

        // Standard dashboard view
        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewHas('activeBranchName', 'Barasat Central Branch');

        $kpis = $response->viewData('kpis');
        $this->assertEquals(2, $kpis['total_customers'], 'Branch Manager must only see Branch 1 customer count');
        $this->assertEquals(15000.00, $kpis['active_portfolio_amount'], 'Branch Manager must only see Branch 1 portfolio');
        $this->assertEquals(25000.00, $kpis['total_vault_cash'], 'Branch Manager must only see Branch 1 vault cash');

        // Attempting to request Branch 2 via query param ?branch_id=...
        $tamperedResponse = $this->get(route('admin.dashboard', ['branch_id' => $this->branch2->id]));
        $tamperedResponse->assertOk();
        $tamperedKpis = $tamperedResponse->viewData('kpis');
        $this->assertEquals(2, $tamperedKpis['total_customers'], 'Branch Manager query tampering to other branch must be ignored');
        $this->assertEquals(15000.00, $tamperedKpis['active_portfolio_amount']);
    }

    /** 2. Unassigned Branch Manager receives zero data and mandatory warning */
    public function test_unassigned_branch_manager_sees_zero_data_and_mandatory_warning(): void
    {
        Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-009',
            'first_name' => 'Some',
            'last_name' => 'Borrower',
            'mobile_number' => '9800000099',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->unassignedManager);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Mandatory Branch Assignment Required');

        $kpis = $response->viewData('kpis');
        $this->assertEquals(0, $kpis['total_customers'], 'Unassigned manager must not see any branch data');
        $this->assertEquals(0.00, $kpis['active_portfolio_amount']);
        $this->assertEquals(0.00, $kpis['total_vault_cash']);
    }

    /** 3. Profit & Loss is completely hidden from Branch Manager */
    public function test_profit_and_loss_is_completely_hidden_from_branch_manager(): void
    {
        $this->actingAs($this->branchManager1);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        // UI must not contain P&L KPI card
        $response->assertDontSee('Net Profit / Loss (YTD)');
        $response->assertDontSee('Audited P&L');

        // View data must not leak profit loss
        $this->assertFalse($response->viewData('canViewProfitLoss'));
        $kpis = $response->viewData('kpis');
        $this->assertNull($kpis['ytd_profit_loss']);
        $this->assertNull($kpis['pending_profit']);
        $this->assertNull($kpis['final_profit']);
    }

    /** 4. Profit & Loss is visible to authorized Head Office Admin */
    public function test_profit_and_loss_is_visible_to_authorized_head_office_admin(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        $response->assertSee('Net Profit / Loss (YTD)');
        $response->assertSee('Audited P&L');

        $this->assertTrue($response->viewData('canViewProfitLoss'));
        $kpis = $response->viewData('kpis');
        $this->assertNotNull($kpis['ytd_profit_loss']);
    }

    /** 5. Genuine calculations for collections, disbursements, and EMI due */
    public function test_genuine_calculations_for_collections_disbursements_and_emi_due(): void
    {
        $today = now(config('app.business_timezone', 'Asia/Kolkata'))->toDateString();

        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-OP-01',
            'first_name' => 'Operation',
            'last_name' => 'Borrower',
            'mobile_number' => '9800000055',
            'registration_date' => $today,
            'status' => 'active',
        ]);

        $loan = $this->createLoan($this->branch1, $cust, 'LN-OP-001', 30000.00, 30000.00, 25000.00, 3000.00, 'active', $today);

        // Repayment recorded today
        LoanRepayment::create([
            'receipt_number' => 'RCPT-OP-001',
            'loan_account_id' => $loan->id,
            'customer_id' => $cust->id,
            'payment_date' => $today,
            'amount' => 3500.00,
            'payment_method' => 'cash',
            'principal_paid' => 3000.00,
            'interest_paid' => 500.00,
        ]);

        // Installment due today
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => $today,
            'opening_principal' => 30000.00,
            'principal_amount' => 2500.00,
            'interest_amount' => 500.00,
            'installment_amount' => 3000.00,
            'principal_paid' => 0.00,
            'interest_paid' => 0.00,
            'total_paid' => 0.00,
            'closing_principal' => 27500.00,
            'status' => 'pending',
        ]);

        $data = $this->dashboardService->getDashboardData($this->company->id, $this->branch1->id);
        $kpis = $data['kpis'];

        $this->assertEquals(3500.00, $kpis['today_collection'], 'Today collection must match repayment recorded today');
        $this->assertEquals(30000.00, $kpis['today_disbursement'], 'Today disbursement must match loan disbursed today');
        $this->assertEquals(3000.00, $kpis['today_pending_emi_amount'], 'Pending EMI due today must match pending installment');
        $this->assertEquals(1, $kpis['today_pending_emi_count']);
    }

    /** 6. Real overdue metrics and PAR 30 mapping from OverdueService */
    public function test_real_overdue_and_par_mapping(): void
    {
        $pastDate = now(config('app.business_timezone', 'Asia/Kolkata'))->subDays(45)->toDateString();

        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-OD-01',
            'first_name' => 'Overdue',
            'last_name' => 'Borrower',
            'mobile_number' => '9800000066',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $loan = $this->createLoan(
            $this->branch1,
            $cust,
            'LN-OD-001',
            20000.00,
            20000.00,
            20000.00,
            2400.00,
            'active',
            now()->subDays(60)->toDateString()
        );

        // Overdue installment 45 days past due (in 31-60 days bucket)
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => $pastDate,
            'opening_principal' => 20000.00,
            'principal_amount' => 2000.00,
            'interest_amount' => 400.00,
            'installment_amount' => 2400.00,
            'principal_paid' => 0.00,
            'interest_paid' => 0.00,
            'total_paid' => 0.00,
            'closing_principal' => 18000.00,
            'status' => 'overdue',
        ]);

        $data = $this->dashboardService->getDashboardData($this->company->id, $this->branch1->id);
        $kpis = $data['kpis'];

        $this->assertEquals(2400.00, $kpis['total_overdue_principal'], 'Overdue amount must map from OverdueService');
        $this->assertGreaterThan(0, $kpis['par_30_rate'], 'PAR 30 rate must be non-zero for 45 DPD loan');

        $charts = $data['charts'];
        $this->assertEquals(2400.00, $charts['overdue_buckets']['31-60 Days'], '31-60 Days bucket must reflect actual overdue amount');
    }

    /** 7. Inventory valuation calculates genuine stock * cost price */
    public function test_inventory_valuation_calculates_genuine_stock_valuation(): void
    {
        $p1 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'LED TV 32 Inch',
            'sku' => 'TV-32',
            'cost_price' => 12000.00,
            'unit_price' => 16000.00,
            'is_active' => true,
        ]);
        $p2 = Product::create([
            'company_id' => $this->company->id,
            'name' => 'Microwave Oven',
            'sku' => 'MW-01',
            'cost_price' => 8000.00,
            'unit_price' => 11000.00,
            'is_active' => true,
        ]);

        // Stock in Branch 1: 5 TVs (60,000) and 2 Ovens (16,000) = 76,000
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $p1->id,
            'current_stock' => 5,
            'reorder_level' => 2,
        ]);
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $p2->id,
            'current_stock' => 2,
            'reorder_level' => 3, // Low stock: current (2) <= reorder (3)
        ]);

        $data = $this->dashboardService->getDashboardData($this->company->id, $this->branch1->id);
        $inv = $data['inventoryOverview'];

        $this->assertEquals(76000.00, $inv['total_stock_value'], 'Total stock value must equal 5*12000 + 2*8000');
        $this->assertEquals(1, $inv['low_stock_count'], 'Low stock items must count items <= reorder level');
    }

    /** 8. Quick Actions strictly respect RBAC permissions in UI */
    public function test_quick_actions_strictly_respect_rbac_permissions(): void
    {
        // Branch Manager has customer.create and loan_application.create, but NOT voucher.create
        $this->actingAs($this->branchManager1);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('New Member');
        $response->assertSee('Loan Application');
        $response->assertDontSee('Voucher Entry'); // Strictly hidden from Branch Manager

        // Super Admin has all permissions including voucher.create
        $this->actingAs($this->superAdmin);
        $adminResponse = $this->get(route('admin.dashboard'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Voucher Entry');
    }

    /** 9. Mobile API dashboard stats endpoint enforces branch isolation and never leaks P&L */
    public function test_mobile_api_dashboard_stats_enforces_branch_isolation(): void
    {
        // Branch 1 has 1 customer
        Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'API-C1',
            'first_name' => 'API',
            'last_name' => 'Cust1',
            'mobile_number' => '9800000077',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        // Branch 2 has 2 customers
        Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'customer_code' => 'API-C2',
            'first_name' => 'API',
            'last_name' => 'Cust2',
            'mobile_number' => '9800000078',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch2->id,
            'customer_code' => 'API-C3',
            'first_name' => 'API',
            'last_name' => 'Cust3',
            'mobile_number' => '9800000079',
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->branchManager1);

        $response = $this->getJson('/api/v1/dashboard/stats');
        $response->assertOk();
        $response->assertJsonPath('data.total_customers', 1);

        // Even if tampered with ?branch_id=...
        $tampered = $this->getJson('/api/v1/dashboard/stats?branch_id=' . $this->branch2->id);
        $tampered->assertOk();
        $tampered->assertJsonPath('data.total_customers', 1);

        // Ensure no Profit & Loss keys exist
        $response->assertJsonMissing(['ytd_profit_loss', 'pending_profit', 'final_profit', 'net_profit']);
    }

    /** 10. Net Profit/Loss matches Admin P&L report for the exact same period */
    public function test_net_profit_loss_matches_admin_profit_loss_report_for_same_period(): void
    {
        $today = now(config('app.business_timezone', 'Asia/Kolkata'))->toDateString();

        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-PNL-01',
            'first_name' => 'ProfitLoss',
            'last_name' => 'Borrower',
            'mobile_number' => '9800000088',
            'registration_date' => $today,
            'status' => 'active',
        ]);

        $loan = $this->createLoan($this->branch1, $cust, 'LN-PNL-001', 25000.00, 25000.00, 20000.00, 2400.00, 'active', $today);

        // Repayment with principal and interest recorded today
        LoanRepayment::create([
            'receipt_number' => 'RCPT-PNL-001',
            'loan_account_id' => $loan->id,
            'customer_id' => $cust->id,
            'payment_date' => $today,
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'principal_paid' => 4000.00,
            'interest_paid' => 1000.00,
        ]);

        $profitLossService = app(ProfitLossService::class);
        $pnlReport = $profitLossService->getComprehensiveProfitLoss($this->company->id, $this->branch1->id, $today, $today);

        $dashboardData = $this->dashboardService->getDashboardData(
            $this->company->id,
            $this->branch1->id,
            true,
            true,
            false,
            null,
            'today'
        );

        // Profit & Loss reconciliation
        $this->assertEquals(
            $pnlReport['net_profit_or_loss'],
            $dashboardData['kpis']['period_profit_loss'],
            'Dashboard period Net Profit/Loss must reconcile with ProfitLossService net_profit_or_loss for the same period'
        );

        // Admin Web View reconciliation
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('admin.dashboard', [
            'date_filter' => 'today',
            'branch_id' => $this->branch1->id,
        ]));
        $response->assertOk();
        $viewKpis = $response->viewData('kpis');
        $this->assertEquals($pnlReport['net_profit_or_loss'], $viewKpis['period_profit_loss']);
    }

    /** 11. Global date filter presets and Asia/Kolkata timezone boundaries */
    public function test_global_date_filter_presets_and_asia_kolkata_timezone_boundaries(): void
    {
        $tz = config('app.business_timezone', 'Asia/Kolkata');
        $now = now($tz);

        // 1. Today preset
        $todayRange = $this->dashboardService->resolveDateRange($this->company->id, 'today');
        $this->assertEquals($now->toDateString(), $todayRange['from_date']);
        $this->assertEquals($now->toDateString(), $todayRange['to_date']);
        $this->assertEquals('Today', $todayRange['label']);

        // 2. Yesterday preset
        $yesterdayRange = $this->dashboardService->resolveDateRange($this->company->id, 'yesterday');
        $this->assertEquals($now->copy()->subDay()->toDateString(), $yesterdayRange['from_date']);
        $this->assertEquals($now->copy()->subDay()->toDateString(), $yesterdayRange['to_date']);
        $this->assertEquals('Yesterday', $yesterdayRange['label']);

        // 3. This Month preset
        $monthRange = $this->dashboardService->resolveDateRange($this->company->id, 'this_month');
        $this->assertEquals($now->copy()->startOfMonth()->toDateString(), $monthRange['from_date']);
        $this->assertEquals($now->toDateString(), $monthRange['to_date']);
        $this->assertEquals('This Month', $monthRange['label']);

        // 4. This Year preset (aligned with configured Financial Year)
        $yearRange = $this->dashboardService->resolveDateRange($this->company->id, 'this_year');
        $this->assertEquals('2026-04-01', $yearRange['from_date'], 'Financial year start must match FY 2026-2027 start');
        $this->assertEquals(min('2027-03-31', $now->toDateString()), $yearRange['to_date']);

        // 5. Valid Custom Date Range
        $customRange = $this->dashboardService->resolveDateRange(
            $this->company->id,
            'custom',
            '2026-05-01',
            '2026-05-15'
        );
        $this->assertEquals('2026-05-01', $customRange['from_date']);
        $this->assertEquals('2026-05-15', $customRange['to_date']);
        $this->assertNull($customRange['validation_error']);
    }

    /** 12. Invalid custom date range is handled safely without SQL exceptions */
    public function test_invalid_custom_date_range_is_handled_safely(): void
    {
        // Start date is AFTER end date
        $range = $this->dashboardService->resolveDateRange(
            $this->company->id,
            'custom',
            '2026-10-25',
            '2026-10-10'
        );

        $this->assertNotNull($range['validation_error']);
        $this->assertStringContainsString('Start date cannot be after end date', $range['validation_error']);
        $this->assertEquals($range['from_date'], $range['to_date'], 'Dates should be safely normalized to single day to prevent invalid SQL range');

        // Verify web request handles it gracefully
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('admin.dashboard', [
            'date_filter' => 'custom',
            'from_date' => '2026-10-25',
            'to_date' => '2026-10-10',
        ]));

        $response->assertOk();
        $response->assertSee('Start date cannot be after end date');
    }

    /** 13. Consistent filtering across widgets while lifetime metrics and current balances remain constant */
    public function test_consistent_filtering_across_widgets_while_lifetime_metrics_remain_constant(): void
    {
        $tz = config('app.business_timezone', 'Asia/Kolkata');
        $today = now($tz)->toDateString();
        $yesterday = now($tz)->subDay()->toDateString();

        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-CONS-01',
            'first_name' => 'Consistent',
            'last_name' => 'Test',
            'mobile_number' => '9800000099',
            'registration_date' => $yesterday,
            'status' => 'active',
        ]);

        $loan = $this->createLoan(
            $this->branch1,
            $cust,
            'LN-CONS-001',
            40000.00,
            40000.00,
            30000.00,
            3600.00,
            'active',
            $yesterday
        );

        // Repayment yesterday: 2,000
        LoanRepayment::create([
            'receipt_number' => 'RCPT-YEST-001',
            'loan_account_id' => $loan->id,
            'customer_id' => $cust->id,
            'payment_date' => $yesterday,
            'amount' => 2000.00,
            'payment_method' => 'cash',
            'principal_paid' => 1500.00,
            'interest_paid' => 500.00,
        ]);

        // Repayment today: 3,000
        LoanRepayment::create([
            'receipt_number' => 'RCPT-TODAY-001',
            'loan_account_id' => $loan->id,
            'customer_id' => $cust->id,
            'payment_date' => $today,
            'amount' => 3000.00,
            'payment_method' => 'cash',
            'principal_paid' => 2500.00,
            'interest_paid' => 500.00,
        ]);

        // Filter 1: Yesterday
        $dataYesterday = $this->dashboardService->getDashboardData(
            $this->company->id,
            $this->branch1->id,
            true,
            true,
            false,
            null,
            'yesterday'
        );
        $kpisYest = $dataYesterday['kpis'];

        // Period-sensitive metrics must reflect yesterday
        $this->assertEquals(2000.00, $kpisYest['period_collection']);
        $this->assertEquals(1, $kpisYest['period_collection_count']);

        // Lifetime and current position metrics must NOT be filtered
        $this->assertEquals(1, $kpisYest['total_customers'], 'Total members must remain lifetime count');
        $this->assertEquals(30000.00, $kpisYest['active_portfolio_amount'], 'Active portfolio must remain current outstanding');
        $this->assertEquals(25000.00, $kpisYest['total_vault_cash'], 'Vault cash must remain current balance');

        // Filter 2: Today
        $dataToday = $this->dashboardService->getDashboardData(
            $this->company->id,
            $this->branch1->id,
            true,
            true,
            false,
            null,
            'today'
        );
        $kpisToday = $dataToday['kpis'];

        // Period-sensitive metrics must reflect today
        $this->assertEquals(3000.00, $kpisToday['period_collection']);
        $this->assertEquals(1, $kpisToday['period_collection_count']);

        // Lifetime metrics must remain identical
        $this->assertEquals(1, $kpisToday['total_customers']);
        $this->assertEquals(30000.00, $kpisToday['active_portfolio_amount']);
        $this->assertEquals(25000.00, $kpisToday['total_vault_cash']);
    }

    /** 14. Empty datasets return genuine zero values without errors */
    public function test_empty_datasets_return_genuine_zero_values(): void
    {
        $emptyBranch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Empty Test Branch',
            'code' => 'BR-EMPTY-01',
            'branch_code' => 'BR-EMPTY-01',
            'phone' => '9800000999',
            'email' => 'empty@grihalaxmi.com',
            'address' => 'Empty Road',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'current_vault_balance' => 0.00,
            'vault_cash_limit' => 50000.00,
            'is_active' => true,
        ]);

        $data = $this->dashboardService->getDashboardData(
            $this->company->id,
            $emptyBranch->id,
            true,
            true,
            false,
            null,
            'today'
        );
        $kpis = $data['kpis'];

        $this->assertEquals(0, $kpis['total_customers']);
        $this->assertEquals(0.00, $kpis['active_portfolio_amount']);
        $this->assertEquals(0.00, $kpis['period_collection']);
        $this->assertEquals(0.00, $kpis['period_disbursement']);
        $this->assertEquals(0.00, $kpis['period_pending_emi_amount']);
        $this->assertEquals(0.00, $kpis['total_overdue_principal']);
        $this->assertEquals(0.00, $kpis['period_profit_loss']);
        $this->assertEquals(0.00, $kpis['ytd_profit_loss']);
        $this->assertEquals(0.00, $kpis['total_vault_cash']);

        // Web interface must render 200 OK with ₹0.00
        $this->actingAs($this->superAdmin);
        $response = $this->get(route('admin.dashboard', ['branch_id' => $emptyBranch->id]));
        $response->assertOk();
        $response->assertSee('₹0.00');
    }

    /** 15. All 10 KPI cards are rendered with valid HTML and responsive grid for authorized Admin */
    public function test_all_10_authorized_kpi_cards_are_present_and_rendered_for_head_office_admin(): void
    {
        $today = now(config('app.business_timezone', 'Asia/Kolkata'))->toDateString();

        $cust = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'customer_code' => 'CUST-10CARDS-01',
            'first_name' => 'Card',
            'last_name' => 'Tester',
            'mobile_number' => '9800000100',
            'registration_date' => $today,
            'status' => 'active',
        ]);

        $loan = $this->createLoan(
            $this->branch1,
            $cust,
            'LN-10CARDS-001',
            50000.00,
            50000.00,
            45000.00,
            5400.00,
            'active',
            $today
        );

        // Repayment recorded today: 4,500
        LoanRepayment::create([
            'receipt_number' => 'RCPT-10C-001',
            'loan_account_id' => $loan->id,
            'customer_id' => $cust->id,
            'payment_date' => $today,
            'amount' => 4500.00,
            'payment_method' => 'cash',
            'principal_paid' => 3800.00,
            'interest_paid' => 700.00,
        ]);

        // Installment due today: 4,000
        LoanInstallment::create([
            'loan_account_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => $today,
            'opening_principal' => 50000.00,
            'principal_amount' => 3500.00,
            'interest_amount' => 500.00,
            'installment_amount' => 4000.00,
            'principal_paid' => 0.00,
            'interest_paid' => 0.00,
            'total_paid' => 0.00,
            'closing_principal' => 46500.00,
            'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin);

        // 1. Test Default / Today View
        $response = $this->get(route('admin.dashboard', ['branch_id' => $this->branch1->id]));
        $response->assertOk();

        // Ensure no uncompiled Blade component tags remain in output
        $response->assertDontSee('<x-ui.kpi-card', false);

        // Verify responsive grid class (3 cols desktop, 2 cols tablet, 1 col mobile)
        $response->assertSee('col-12 col-md-6 col-lg-4');

        // All 10 Cards must be present in Today view:
        $response->assertSee('Total Members');
        $response->assertSee('Active Loans');
        $response->assertSee("Today's Collection");
        $response->assertSee("Today's Disbursement");
        $response->assertSee('Pending EMI Due');
        $response->assertSee('Total Overdue');
        $response->assertSee('Total Outstanding');
        $response->assertSee('Vault Cash');
        $response->assertSee('Bank Balance');
        $response->assertSee('Net Profit / Loss (YTD)');

        // The 3 previously missing cards render with genuine calculated values:
        $response->assertSee('4,500.00'); // Collection
        $response->assertSee('50,000.00'); // Disbursement
        $response->assertSee('4,000.00'); // Pending EMI Due

        // 2. Test Period View (e.g. This Month)
        $monthResponse = $this->get(route('admin.dashboard', [
            'branch_id' => $this->branch1->id,
            'date_filter' => 'this_month',
        ]));
        $monthResponse->assertOk();
        $monthResponse->assertDontSee('<x-ui.kpi-card', false);

        // Period-sensitive labels updated appropriately
        $monthResponse->assertSee('Period Collection');
        $monthResponse->assertSee('Period Disbursement');
        $monthResponse->assertSee('Period EMI Due');

        // Lifetime metrics & current positions remain correct
        $monthResponse->assertSee('Total Members');
        $monthResponse->assertSee('Active Loans');
        $monthResponse->assertSee('Vault Cash');
        $monthResponse->assertSee('Bank Balance');
        $monthResponse->assertSee('Net Profit / Loss (YTD)');
    }
}
