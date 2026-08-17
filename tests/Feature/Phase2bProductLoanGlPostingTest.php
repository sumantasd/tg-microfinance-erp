<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanDisbursement;
use App\Models\LoanDownPayment;
use App\Models\LoanRepayment;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AccountingService;
use App\Services\LoanAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase2bProductLoanGlPostingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected LoanScheme $productScheme;
    protected Customer $customer;
    protected FinancialYear $financialYear;
    protected ProductBrand $brand;
    protected ProductCategory $category;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Grihalaxmi Product Loans Ltd',
            'code' => 'GPL01',
            'registration_number' => 'REG-GPL-01',
            'email' => 'finance@grihalaxmi-products.com',
            'phone' => '9876543210',
            'address' => 'Patna HQ, Bihar',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna City Branch',
            'code' => 'PAT02',
            'email' => 'patna2@grihalaxmi.com',
            'phone' => '9876543212',
            'address' => 'Patna City, Bihar',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800008',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Product Officer',
            'email' => 'officer' . uniqid() . '@grihalaxmi.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($this->user);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'title' => 'FY 2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);

        $this->productScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-PRD-01',
            'name' => 'Product Finance Scheme',
            'loan_type' => 'product',
            'applicant_type' => 'both',
            'min_amount' => 5000,
            'max_amount' => 200000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.00,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-PRD-001',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9876543222',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Usha International',
            'code' => 'USHA',
            'is_active' => true,
        ]);

        $this->category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Sewing & Tailoring Machines',
            'code' => 'SEWING',
            'is_active' => true,
        ]);

        // Product A: Selling Price ₹30,000, Cost Price ₹24,000
        $this->productA = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sku' => 'USHA-SEW-3000',
            'name' => 'Usha Heavy Duty Sewing Machine',
            'unit_price' => 30000.00,
            'cost_price' => 24000.00,
            'is_active' => true,
        ]);

        // Product B: Selling Price ₹20,000, Cost Price ₹16,000
        $this->productB = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $this->brand->id,
            'category_id' => $this->category->id,
            'sku' => 'USHA-MOTOR-200',
            'name' => 'Industrial Tailoring Electric Motor',
            'unit_price' => 20000.00,
            'cost_price' => 16000.00,
            'is_active' => true,
        ]);

        // Seed Branch Stock
        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productA->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->productB->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);
    }

    /**
     * 1. Product Loan with Down Payment: Full Lifecycle Double-Entry Accounting
     */
    public function test_product_loan_with_down_payment_posts_down_payment_receipt_and_sales_issue_journal(): void
    {
        // 1. Create Product Loan Application for Product A (Selling Price: ₹30,000)
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 25000.00,
            'approved_amount' => 25000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 30000.00,
        ]);

        $loanService = app(LoanAccountService::class);

        // 2. Sanction with Down Payment = ₹5,000 (Financed Principal = ₹25,000)
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 5000.00, 0.00, '2026-08-01');

        $this->assertEquals(30000.00, (float) $loanAccount->product_price_amount);
        $this->assertEquals(5000.00, (float) $loanAccount->down_payment_amount);
        $this->assertEquals(25000.00, (float) $loanAccount->sanctioned_amount);
        $this->assertEquals('sanctioned', $loanAccount->status);

        // Verify Down Payment Receipt Voucher
        $downPayment = LoanDownPayment::where('loan_account_id', $loanAccount->id)->first();
        $this->assertNotNull($downPayment);

        $dpVoucher = Voucher::where('reference_type', 'loan_down_payment')
            ->where('reference_id', $downPayment->id)
            ->first();

        $this->assertNotNull($dpVoucher);
        $this->assertEquals('receipt', $dpVoucher->voucher_type);
        $this->assertEquals('posted', $dpVoucher->status);
        $this->assertEquals(5000.00, (float) $dpVoucher->total_debit);
        $this->assertEquals(5000.00, (float) $dpVoucher->total_credit);

        // Check Down Payment Voucher Entries: Dr 1110 (Cash Vault), Cr 2120 (Down Payment Clearing)
        $dpEntries = $dpVoucher->entries()->with('account')->get();
        $dpDebit = $dpEntries->firstWhere('debit', '>', 0);
        $dpCredit = $dpEntries->firstWhere('credit', '>', 0);

        $this->assertEquals('1110', $dpDebit->account->account_code);
        $this->assertEquals(5000.00, (float) $dpDebit->debit);
        $this->assertEquals('2120', $dpCredit->account->account_code);
        $this->assertEquals(5000.00, (float) $dpCredit->credit);

        // 3. Issue Product Loan & Deduct Physical Stock
        $issuedAccount = $loanService->issueProductLoan($loanAccount, 'Delivered sewing machine to borrower');
        $this->assertEquals('active', $issuedAccount->status);

        // Verify Stock Deducted: 10 -> 9
        $stock = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->productA->id)
            ->first();
        $this->assertEquals(9, $stock->current_stock);

        // Verify Product Issue Journal Voucher
        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();
        $this->assertNotNull($disbursement);

        $issueVoucher = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        $this->assertNotNull($issueVoucher);
        $this->assertEquals('journal', $issueVoucher->voucher_type);
        $this->assertEquals('posted', $issueVoucher->status);

        // Total Selling Price = ₹30,000, Total Cost = ₹24,000
        // Debits: 1220 (₹25,000) + 2120 (₹5,000) + 5110 (₹24,000) = ₹54,000
        // Credits: 4310 (₹30,000) + 1310 (₹24,000) = ₹54,000
        $this->assertEquals(54000.00, (float) $issueVoucher->total_debit);
        $this->assertEquals(54000.00, (float) $issueVoucher->total_credit);

        $entries = $issueVoucher->entries()->with('account')->get();

        $drReceivable = $entries->firstWhere('account.account_code', '1220');
        $drClearing = $entries->firstWhere('account.account_code', '2120');
        $drCogs = $entries->firstWhere('account.account_code', '5110');
        $crRevenue = $entries->firstWhere('account.account_code', '4310');
        $crInventory = $entries->firstWhere('account.account_code', '1310');

        $this->assertEquals(25000.00, (float) $drReceivable->debit);
        $this->assertEquals(5000.00, (float) $drClearing->debit);
        $this->assertEquals(24000.00, (float) $drCogs->debit);

        $this->assertEquals(30000.00, (float) $crRevenue->credit);
        $this->assertEquals(24000.00, (float) $crInventory->credit);
    }

    /**
     * 2. Product Loan with Zero Down Payment
     */
    public function test_product_loan_with_zero_down_payment_omits_clearing_account_and_balances_correctly(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 30000.00,
            'approved_amount' => 30000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 30000.00,
        ]);

        $loanService = app(LoanAccountService::class);
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 0.00, 0.00, '2026-08-01');

        // Zero down payment => No down payment voucher
        $this->assertEquals(0, Voucher::where('reference_type', 'loan_down_payment')->count());

        $loanService->issueProductLoan($loanAccount, 'Zero down payment issue');

        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();
        $issueVoucher = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        $this->assertNotNull($issueVoucher);
        // Debits: 1220 (₹30,000) + 5110 (₹24,000) = ₹54,000
        // Credits: 4310 (₹30,000) + 1310 (₹24,000) = ₹54,000
        $this->assertEquals(54000.00, (float) $issueVoucher->total_debit);
        $this->assertEquals(54000.00, (float) $issueVoucher->total_credit);

        $entries = $issueVoucher->entries()->with('account')->get();
        $this->assertNull($entries->firstWhere('account.account_code', '2120')); // 2120 omitted when 0
    }

    /**
     * 3. Product Loan with Multiple Products: Aggregates Revenue and Cost Price
     */
    public function test_product_loan_with_multiple_products_aggregates_total_revenue_and_cogs(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 60000.00, // 30,000 + 2x20,000 = 70,000 - 10,000 = 60,000
            'approved_amount' => 60000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        // 1 x Product A: ₹30,000 selling, ₹24,000 cost
        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 30000.00,
        ]);

        // 2 x Product B: 2 x ₹20,000 = ₹40,000 selling, 2 x ₹16,000 = ₹32,000 cost
        $app->products()->create([
            'product_id' => $this->productB->id,
            'product_sku_snapshot' => $this->productB->sku,
            'product_name_snapshot' => $this->productB->name,
            'quantity' => 2,
            'unit_price_snapshot' => 20000.00,
            'total_value' => 40000.00,
        ]);

        $loanService = app(LoanAccountService::class);
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 10000.00, 0.00, '2026-08-01');

        $this->assertEquals(70000.00, (float) $loanAccount->product_price_amount);
        $this->assertEquals(10000.00, (float) $loanAccount->down_payment_amount);
        $this->assertEquals(60000.00, (float) $loanAccount->sanctioned_amount);

        $loanService->issueProductLoan($loanAccount, 'Multiple products issue');

        $disbursement = LoanDisbursement::where('loan_account_id', $loanAccount->id)->first();
        $voucher = Voucher::where('reference_type', 'loan_disbursement')
            ->where('reference_id', $disbursement->id)
            ->first();

        // Total Cost = 24,000 + 32,000 = 56,000
        // Total Selling = 30,000 + 40,000 = 70,000
        // Total Debits: 60,000 (1220) + 10,000 (2120) + 56,000 (5110) = 126,000
        // Total Credits: 70,000 (4310) + 56,000 (1310) = 126,000
        $this->assertEquals(126000.00, (float) $voucher->total_debit);
        $this->assertEquals(126000.00, (float) $voucher->total_credit);

        $entries = $voucher->entries()->with('account')->get();
        $this->assertEquals(56000.00, (float) $entries->firstWhere('account.account_code', '5110')->debit);
        $this->assertEquals(70000.00, (float) $entries->firstWhere('account.account_code', '4310')->credit);
        $this->assertEquals(56000.00, (float) $entries->firstWhere('account.account_code', '1310')->credit);
    }

    /**
     * 4. Missing or Zero Product Cost Price Blocks Product Issue
     */
    public function test_missing_or_zero_cost_price_strictly_blocks_product_issue(): void
    {
        // Set Product A cost_price = 0
        $this->productA->update(['cost_price' => 0.00]);

        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 30000.00,
            'approved_amount' => 30000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 30000.00,
        ]);

        $loanService = app(LoanAccountService::class);
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 0.00, 0.00, '2026-08-01');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Product cost price is required before this product can be issued under a Product Loan.');

        $loanService->issueProductLoan($loanAccount);
    }

    /**
     * 5. Accounting Failure Rolls Back Product Issue and Restores Stock
     */
    public function test_accounting_failure_rolls_back_product_issue_and_restores_inventory(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 25000.00,
            'approved_amount' => 25000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 2,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 60000.00,
        ]);

        $loanService = app(LoanAccountService::class);
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 10000.00, 0.00, '2026-08-01');

        $initialStock = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->productA->id)
            ->first()->current_stock;
        $this->assertEquals(10, $initialStock);

        // Lock financial year to simulate accounting failure
        $this->financialYear->update(['is_closed' => true]);

        $thrown = false;
        try {
            $loanService->issueProductLoan($loanAccount);
        } catch (\Throwable $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        // Verify rollback: stock remains 10, no disbursements created, loan account remains sanctioned
        $stockAfter = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->productA->id)
            ->first()->current_stock;
        $this->assertEquals(10, $stockAfter);

        $this->assertDatabaseMissing('loan_disbursements', [
            'loan_account_id' => $loanAccount->id,
        ]);
        $this->assertEquals('sanctioned', $loanAccount->fresh()->status);
    }

    /**
     * 6. Product Loan Repayment Correctly Credits 1220 and 4120
     */
    public function test_product_loan_repayment_correctly_credits_product_loans_receivable_and_interest(): void
    {
        $app = LoanApplication::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'application_number' => 'APP-PRD-' . uniqid(),
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customer->id,
            'requested_amount' => 20000.00,
            'approved_amount' => 20000.00,
            'application_date' => '2026-08-01',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->productA->id,
            'product_sku_snapshot' => $this->productA->sku,
            'product_name_snapshot' => $this->productA->name,
            'quantity' => 1,
            'unit_price_snapshot' => 30000.00,
            'total_value' => 30000.00,
        ]);

        $loanService = app(LoanAccountService::class);
        $loanAccount = $loanService->sanctionLoanFromApplication($app, 10000.00, 0.00, '2026-08-01');
        $loanService->issueProductLoan($loanAccount);

        // Record Repayment of ₹3,000 on Product Loan (covers fee ₹200, interest ₹2,400, principal ₹400)
        $loanService->recordRepayment(
            $loanAccount->fresh(),
            3000.00,
            'cash',
            'RCPT-PRD-01',
            'reduce_tenure',
            'Product loan installment',
            '2026-09-01'
        );

        $repayment = LoanRepayment::where('loan_account_id', $loanAccount->id)->first();
        $this->assertNotNull($repayment);
        $this->assertEquals(600.00, (float) $repayment->principal_paid);
        $this->assertEquals(2400.00, (float) $repayment->interest_paid);

        $voucher = Voucher::where('reference_type', 'loan_repayment')
            ->where('reference_id', $repayment->id)
            ->first();

        $this->assertNotNull($voucher);
        $entries = $voucher->entries()->with('account')->get();

        $crPrincipal = $entries->firstWhere('account.account_code', '1220');
        $crInterest = $entries->firstWhere('account.account_code', '4120');

        $this->assertNotNull($crPrincipal, 'Must credit 1220 - Product Loans Receivable');
        $this->assertNotNull($crInterest, 'Must credit 4120 - Interest Income from Product Loans');

        $this->assertEquals(600.00, (float) $crPrincipal->credit);
        $this->assertEquals(2400.00, (float) $crInterest->credit);
    }
}
