<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanApplicationProduct;
use App\Models\LoanInstallment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductLoanMargin;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\AccountingService;
use App\Services\LoanAccountService;
use App\Services\ProfitLossService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProfitLossTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $superAdmin;
    protected User $companyAdmin;
    protected User $branchManager;
    protected User $loanOfficer;
    protected LoanScheme $scheme;
    protected Customer $customer;
    protected Product $product;
    protected FinancialYear $financialYear;
    protected ProfitLossService $profitLossService;
    protected LoanAccountService $loanAccountService;
    protected AccountingService $accountingService;

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

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kolkata Central Branch',
            'code' => 'KC01',
            'branch_code' => 'KC01',
            'phone' => '9876543211',
            'email' => 'kolkata@grihalaxmi.com',
            'address' => 'Central Avenue',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'is_active' => true,
        ]);

        // Users
        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Super Administrator',
            'email' => 'superadmin@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->superAdmin->assignRole('Super Admin');

        $this->companyAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Company Admin User',
            'email' => 'co_admin@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->companyAdmin->assignRole('Company Admin');

        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Branch Manager User',
            'email' => 'bm@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->branchManager->assignRole('Branch Manager');

        $this->loanOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Loan Officer User',
            'email' => 'officer@grihalaxmi.com',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);
        $this->loanOfficer->assignRole('Loan Officer');

        // Customer
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Amina',
            'last_name' => 'Begum',
            'mobile_number' => '9876500001',
            'registration_date' => now()->toDateString(),
            'gender' => 'female',
            'status' => 'active',
        ]);

        // Loan Scheme
        $this->scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Product Loan Standard',
            'code' => 'SCH-PL-01',
            'loan_type' => 'product',
            'applicant_type' => 'individual',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 0.00,
            'min_amount' => 5000,
            'max_amount' => 50000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 24,
            'repayment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // Category & Brand
        $cat = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Appliances',
            'code' => 'CAT-APP-01',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Prestige',
            'code' => 'BRD-PRS-01',
            'is_active' => true,
        ]);

        // Product: Cost ₹15,000, Unit Selling Price ₹20,000
        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'name' => 'Smart Sewing Machine',
            'sku' => 'PRD-SEW-01',
            'cost_price' => 15000.00,
            'unit_price' => 20000.00,
            'status' => 'active',
        ]);

        // Financial Year
        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'title' => 'FY 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->accountingService = app(AccountingService::class);
        $this->profitLossService = app(ProfitLossService::class);
        $this->loanAccountService = app(LoanAccountService::class);

        // Seed Chart of Accounts
        $this->accountingService->seedDefaultChartOfAccounts($this->company->id);
    }

    /**
     * Helper to create a product loan with application.
     */
    protected function createProductLoanAccount(float $sanctionedAmount = 20000.00, float $costPrice = 15000.00): LoanAccount
    {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'application_number' => 'APP-PL-' . uniqid(),
            'application_date' => now()->toDateString(),
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'requested_amount' => $sanctionedAmount,
            'approved_amount' => $sanctionedAmount,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 0.00,
            'status' => 'approved',
            'applied_at' => now(),
            'approved_at' => now(),
        ]);

        $product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->product->category_id,
            'brand_id' => $this->product->brand_id,
            'name' => 'Loan Product ' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'cost_price' => $costPrice,
            'unit_price' => $sanctionedAmount,
            'status' => 'active',
        ]);

        LoanApplicationProduct::create([
            'loan_application_id' => $app->id,
            'product_id' => $product->id,
            'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'quantity' => 1,
            'unit_price_snapshot' => $sanctionedAmount,
            'total_value' => $sanctionedAmount,
        ]);

        $loan = LoanAccount::create([
            'loan_number' => 'LN-PL-' . uniqid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $this->scheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'product_price_amount' => $sanctionedAmount,
            'sanctioned_amount' => $sanctionedAmount,
            'tenure_months' => 6,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 0.00,
            'principal_outstanding' => $sanctionedAmount,
            'total_outstanding' => $sanctionedAmount,
            'status' => 'active',
            'sanction_date' => now()->toDateString(),
        ]);

        // Generate installments
        $rem = $sanctionedAmount;
        $instPrincipal = round($sanctionedAmount / 6, 2);
        for ($i = 1; $i <= 6; $i++) {
            $closing = max(0, round($rem - $instPrincipal, 2));
            LoanInstallment::create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'loan_account_id' => $loan->id,
                'installment_number' => $i,
                'due_date' => now()->addMonths($i)->toDateString(),
                'opening_principal' => $rem,
                'principal_amount' => $instPrincipal,
                'interest_amount' => 0.00,
                'installment_amount' => $instPrincipal,
                'closing_principal' => $closing,
                'status' => 'pending',
            ]);
            $rem = $closing;
        }

        return $loan;
    }

    /**
     * Requirement 1 Test: Product Loan generates Pending Margin on sanction.
     * Cost ₹15,000, Sanction ₹20,000 => ₹5,000 Pending Profit, 0 Final Profit.
     */
    public function test_product_loan_creates_pending_profit_margin_on_sanction(): void
    {
        $loan = $this->createProductLoanAccount(20000.00, 15000.00);

        // Record pending margin
        $margin = $this->profitLossService->recordPendingMargin($loan);

        $this->assertNotNull($margin);
        $this->assertEquals('pending', $margin->status);
        $this->assertEquals(15000.00, (float) $margin->cost_price_total);
        $this->assertEquals(20000.00, (float) $margin->selling_price_total);
        $this->assertEquals(5000.00, (float) $margin->margin_amount);

        // Query P&L summary
        $summary = $this->profitLossService->getProductLoanMarginsSummary($this->company->id);

        $this->assertEquals(5000.00, $summary['pending_profit']);
        $this->assertEquals(0.00, $summary['final_profit']);
        $this->assertEquals(5000.00, $summary['total_generated_margin']);
        $this->assertEquals(1, $summary['pending_count']);
        $this->assertEquals(0, $summary['finalized_count']);
    }

    /**
     * Requirement 1 Test: Partial EMI repayments do NOT finalize profit.
     */
    public function test_partial_repayments_do_not_finalize_product_loan_profit(): void
    {
        $this->actingAs($this->superAdmin);

        $loan = $this->createProductLoanAccount(20000.00, 15000.00);
        $this->profitLossService->recordPendingMargin($loan);

        // Pay partial installment (e.g. 1st EMI of ₹3,333.33)
        $this->loanAccountService->recordRepayment(
            $loan,
            3333.33,
            'cash',
            null,
            'reduce_tenure',
            'Partial EMI payment',
            now()->toDateString()
        );

        $loan->refresh();
        $this->assertEquals('active', $loan->status);
        $this->assertGreaterThan(0, $loan->total_outstanding);

        $margin = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
        $this->assertEquals('pending', $margin->status);
        $this->assertNull($margin->finalized_at);

        // P&L shows pending profit, zero final profit
        $summary = $this->profitLossService->getProductLoanMarginsSummary($this->company->id);
        $this->assertEquals(5000.00, $summary['pending_profit']);
        $this->assertEquals(0.00, $summary['final_profit']);
    }

    /**
     * Requirement 1 Test: Full legitimate loan closure finalizes profit exactly once.
     */
    public function test_full_loan_closure_finalizes_profit_exactly_once(): void
    {
        $this->actingAs($this->superAdmin);

        $loan = $this->createProductLoanAccount(20000.00, 15000.00);
        $this->profitLossService->recordPendingMargin($loan);

        // Repay entire remaining balance of ₹20,000
        $this->loanAccountService->recordRepayment(
            $loan,
            20000.00,
            'cash',
            null,
            'reduce_tenure',
            'Full loan payoff',
            now()->toDateString()
        );

        $loan->refresh();
        $this->assertEquals('closed', $loan->status);

        $margin = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
        $this->assertEquals('finalized', $margin->status);
        $this->assertNotNull($margin->finalized_at);
        $this->assertEquals(5000.00, (float) $margin->margin_amount);

        // P&L summary: pending drops to 0, final becomes ₹5,000
        $summary = $this->profitLossService->getProductLoanMarginsSummary($this->company->id);
        $this->assertEquals(0.00, $summary['pending_profit']);
        $this->assertEquals(5000.00, $summary['final_profit']);
        $this->assertEquals(5000.00, $summary['total_generated_margin']);
    }

    /**
     * Requirement 6 Test: Repeated finalization requests are idempotent and prevent double-counting.
     */
    public function test_duplicate_finalization_is_idempotent_and_prevents_double_counting(): void
    {
        $this->actingAs($this->superAdmin);

        $loan = $this->createProductLoanAccount(20000.00, 15000.00);
        $this->profitLossService->recordPendingMargin($loan);

        $loan->update(['status' => 'closed', 'closure_type' => 'normal', 'closed_at' => now()]);

        // First finalization
        $margin1 = $this->profitLossService->finalizeProductLoanMargin($loan, $this->superAdmin->id);
        $this->assertEquals('finalized', $margin1->status);

        // Second finalization attempt (duplicate call)
        $margin2 = $this->profitLossService->finalizeProductLoanMargin($loan, $this->superAdmin->id);

        $this->assertEquals($margin1->id, $margin2->id);
        $this->assertEquals(1, ProductLoanMargin::where('loan_account_id', $loan->id)->count());

        $summary = $this->profitLossService->getProductLoanMarginsSummary($this->company->id);
        $this->assertEquals(5000.00, $summary['final_profit']);
        $this->assertEquals(5000.00, $summary['total_generated_margin']);
    }

    /**
     * Requirement 1 & 6 Test: Loan written off or cancelled must NEVER finalize profit.
     */
    public function test_written_off_or_cancelled_loan_does_not_finalize_profit(): void
    {
        $loan = $this->createProductLoanAccount(20000.00, 15000.00);
        $this->profitLossService->recordPendingMargin($loan);

        // Mark write-off
        $loan->update(['status' => 'closed', 'closure_type' => 'write_off', 'closed_at' => now()]);

        $finalized = $this->profitLossService->finalizeProductLoanMargin($loan);
        $this->assertNull($finalized); // Must reject write-off from finalizing

        // Reverse margin
        $this->profitLossService->reverseProductLoanMargin($loan, $this->superAdmin->id, 'Bad Debt Write Off');

        $margin = ProductLoanMargin::where('loan_account_id', $loan->id)->first();
        $this->assertEquals('cancelled', $margin->status);

        $summary = $this->profitLossService->getProductLoanMarginsSummary($this->company->id);
        $this->assertEquals(0.00, $summary['pending_profit']);
        $this->assertEquals(0.00, $summary['final_profit']);
    }

    /**
     * Requirement 2 Test: Direct Product Sales Gross Profit using revenue minus COGS.
     */
    public function test_direct_product_sales_gross_profit_calculation(): void
    {
        // Sale: 2 units of sewing machine. Cost = 2 * 15,000 = 30,000.
        // Subtotal = 2 * 20,000 = 40,000. Discount = 2,000 => Net Revenue = 38,000.
        // Gross Profit = 38,000 - 30,000 = 8,000.
        $sale = Sale::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_name' => 'Amina Begum',
            'sale_number' => 'INV-TEST-001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 40000.00,
            'discount_amount' => 2000.00,
            'tax_amount' => 0.00,
            'total_amount' => 38000.00,
            'paid_amount' => 38000.00,
            'balance_amount' => 0.00,
            'payment_status' => 'paid',
            'created_by' => $this->superAdmin->id,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'quantity' => 2,
            'unit_price' => 20000.00,
            'discount' => 2000.00,
            'total_amount' => 38000.00,
        ]);

        $salesProfit = $this->profitLossService->getDirectSalesProfit($this->company->id);

        $this->assertEquals(38000.00, $salesProfit['total_sales_revenue']);
        $this->assertEquals(30000.00, $salesProfit['total_cogs']);
        $this->assertEquals(8000.00, $salesProfit['total_gross_profit']);
        $this->assertEquals(1, $salesProfit['sales_count']);
    }

    /**
     * Requirement 3 Test: Principal disbursements and repayments are EXCLUDED from P&L;
     * Interest/fee income and business expenses from posted GL are counted.
     */
    public function test_cash_loan_principal_excluded_and_interest_expenses_included(): void
    {
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $principalAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1210')->first();
        $interestAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4110')->first();
        $procFeeAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4210')->first();
        $salaryAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '5210')->first();
        $rentAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '5310')->first();

        // 1. Voucher 1: Principal Disbursement of ₹50,000 (Dr 1210, Cr 1110) - Balance sheet asset entry
        $vDisb = Voucher::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'V-DISB-01',
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 50000.00,
            'total_credit' => 50000.00,
        ]);
        VoucherEntry::create(['voucher_id' => $vDisb->id, 'account_id' => $principalAcc->id, 'debit' => 50000.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vDisb->id, 'account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 50000.00]);

        // 2. Voucher 2: Principal Repayment of ₹10,000 + Interest ₹2,500 + Fee ₹500 (Dr Cash 13,000, Cr 1210 10,000, Cr 4110 2,500, Cr 4210 500)
        $vRepay = Voucher::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'V-REPAY-01',
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 13000.00,
            'total_credit' => 13000.00,
        ]);
        VoucherEntry::create(['voucher_id' => $vRepay->id, 'account_id' => $cashAcc->id, 'debit' => 13000.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vRepay->id, 'account_id' => $principalAcc->id, 'debit' => 0.00, 'credit' => 10000.00]);
        VoucherEntry::create(['voucher_id' => $vRepay->id, 'account_id' => $interestAcc->id, 'debit' => 0.00, 'credit' => 2500.00]);
        VoucherEntry::create(['voucher_id' => $vRepay->id, 'account_id' => $procFeeAcc->id, 'debit' => 0.00, 'credit' => 500.00]);

        // 3. Voucher 3: Operating Expenses: Salaries ₹1,200 + Rent ₹800 (Dr 5210 1,200, Dr 5310 800, Cr Cash 2,000)
        $vExp = Voucher::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'V-EXP-01',
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 2000.00,
            'total_credit' => 2000.00,
        ]);
        VoucherEntry::create(['voucher_id' => $vExp->id, 'account_id' => $salaryAcc->id, 'debit' => 1200.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vExp->id, 'account_id' => $rentAcc->id, 'debit' => 800.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vExp->id, 'account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 2000.00]);

        $accounting = $this->profitLossService->getAccountingRevenueAndExpenses($this->company->id);

        // Verification:
        // Revenue should contain interest ₹2,500 and processing fee ₹500 = ₹3,000.
        // Repaid principal of ₹10,000 MUST NOT be in revenue.
        $this->assertEquals(2500.00, $accounting['interest_cash_loan']);
        $this->assertEquals(500.00, $accounting['processing_fees']);
        $this->assertEquals(3000.00, $accounting['total_cash_loan_revenue']);

        // Expenses should contain Salaries ₹1,200 and Rent ₹800 = ₹2,000.
        // Disbursed principal of ₹50,000 MUST NOT be in expenses.
        $this->assertEquals(1200.00, $accounting['personnel_expense']);
        $this->assertEquals(800.00, $accounting['rent_expense']);
        $this->assertEquals(2000.00, $accounting['total_operating_expenses']);
    }

    /**
     * Requirement 4 Test: Comprehensive Net Profit / Loss calculation across all streams.
     */
    public function test_comprehensive_net_profit_calculation(): void
    {
        // 1. Finalized Product Profit: ₹5,000
        $loan = $this->createProductLoanAccount(20000.00, 15000.00);
        $loan->update(['status' => 'closed', 'closure_type' => 'normal', 'closed_at' => now()]);
        $this->profitLossService->finalizeProductLoanMargin($loan, $this->superAdmin->id);

        // 2. Pending Product Profit: ₹3,000 (Cost 12,000, Sanction 15,000)
        $pendingLoan = $this->createProductLoanAccount(15000.00, 12000.00);
        $this->profitLossService->recordPendingMargin($pendingLoan);

        // 3. Direct Sales Profit: ₹2,000
        $sale = Sale::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_name' => 'Amina Begum',
            'sale_number' => 'INV-002',
            'sale_date' => now()->toDateString(),
            'subtotal' => 10000.00,
            'discount_amount' => 0.00,
            'total_amount' => 10000.00,
            'paid_amount' => 10000.00,
            'payment_status' => 'paid',
            'created_by' => $this->superAdmin->id,
        ]);
        $directSaleProduct = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->product->category_id,
            'brand_id' => $this->product->brand_id,
            'name' => 'Direct Sale Item',
            'sku' => 'SKU-DS-01',
            'cost_price' => 8000.00,
            'unit_price' => 10000.00,
            'status' => 'active',
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $directSaleProduct->id,
            'product_name' => $directSaleProduct->name,
            'product_sku' => $directSaleProduct->sku,
            'quantity' => 1,
            'unit_price' => 10000.00,
            'discount' => 0.00,
            'total_amount' => 10000.00,
        ]);

        // 4. Accounting entries: Interest income ₹1,000, Rent expense ₹1,500
        $cashAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '1110')->first();
        $interestAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '4110')->first();
        $rentAcc = ChartOfAccount::where('company_id', $this->company->id)->where('account_code', '5310')->first();

        $vRev = Voucher::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'V-REV-99',
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 1000.00,
            'total_credit' => 1000.00,
        ]);
        VoucherEntry::create(['voucher_id' => $vRev->id, 'account_id' => $cashAcc->id, 'debit' => 1000.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vRev->id, 'account_id' => $interestAcc->id, 'debit' => 0.00, 'credit' => 1000.00]);

        $vExp = Voucher::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'V-EXP-99',
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 1500.00,
            'total_credit' => 1500.00,
        ]);
        VoucherEntry::create(['voucher_id' => $vExp->id, 'account_id' => $rentAcc->id, 'debit' => 1500.00, 'credit' => 0.00]);
        VoucherEntry::create(['voucher_id' => $vExp->id, 'account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 1500.00]);

        $pnl = $this->profitLossService->getComprehensiveProfitLoss($this->company->id);

        // Verification:
        // Final Product Profit: 5,000
        // Direct Sales Profit: 2,000
        // Cash Loan Revenue: 1,000
        // Total Recognized Operating Income = 5,000 + 2,000 + 1,000 = 8,000
        // Total Operating Expenses = 1,500
        // Net Profit = 8,000 - 1,500 = 6,500
        // Pending Profit = 3,000 (kept unrealized)
        $this->assertEquals(5000.00, $pnl['final_product_profit']);
        $this->assertEquals(2000.00, $pnl['direct_sales_gross_profit']);
        $this->assertEquals(1000.00, $pnl['cash_loan_revenue']);
        $this->assertEquals(8000.00, $pnl['total_recognized_income']);
        $this->assertEquals(1500.00, $pnl['total_operating_expenses']);
        $this->assertEquals(6500.00, $pnl['net_profit_or_loss']);
        $this->assertEquals(3000.00, $pnl['pending_product_profit']);
    }

    /**
     * Requirement 5 Test: Authorized Admin roles can access Profit & Loss and Export.
     */
    public function test_authorized_admins_can_access_profit_loss_page_and_export(): void
    {
        // 1. Super Admin Access
        $response = $this->actingAs($this->superAdmin)->get(route('admin.accounting.profit-loss.index'));
        $response->assertStatus(200);
        $response->assertSee('Enterprise Profit &amp; Loss Statement', false);

        // JSON API
        $jsonResponse = $this->actingAs($this->superAdmin)->json('GET', route('admin.accounting.profit-loss.index'));
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'pending_product_profit',
            'final_product_profit',
            'total_generated_margin',
            'direct_sales_gross_profit',
            'cash_loan_revenue',
            'total_recognized_income',
            'total_operating_expenses',
            'net_profit_or_loss',
        ]);

        // CSV Export
        $exportResponse = $this->actingAs($this->superAdmin)->get(route('admin.accounting.profit-loss.export'));
        $exportResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportResponse->headers->get('Content-Type'));

        // 2. Company Admin Access
        $caResponse = $this->actingAs($this->companyAdmin)->get(route('admin.accounting.profit-loss.index'));
        $caResponse->assertStatus(200);
    }

    /**
     * Requirement 5 Test: Branch Managers and operational staff are strictly DENIED access (HTTP 403).
     */
    public function test_branch_manager_and_operational_staff_are_strictly_denied(): void
    {
        // 1. Branch Manager must get HTTP 403 on View
        $bmResponse = $this->actingAs($this->branchManager)->get(route('admin.accounting.profit-loss.index'));
        $bmResponse->assertStatus(403);

        // 2. Branch Manager must get HTTP 403 on Export
        $bmExport = $this->actingAs($this->branchManager)->get(route('admin.accounting.profit-loss.export'));
        $bmExport->assertStatus(403);

        // 3. Branch Manager must get HTTP 403 on JSON API
        $bmJson = $this->actingAs($this->branchManager)->json('GET', route('admin.accounting.profit-loss.index'));
        $bmJson->assertStatus(403);

        // 4. Loan Officer must get HTTP 403
        $officerResponse = $this->actingAs($this->loanOfficer)->get(route('admin.accounting.profit-loss.index'));
        $officerResponse->assertStatus(403);

        // 5. Unauthenticated guest redirected to login (HTTP 302)
        auth()->logout();
        $guestResponse = $this->get(route('admin.accounting.profit-loss.index'));
        $guestResponse->assertStatus(302);
        $guestResponse->assertRedirect('/admin/login');
    }
}
