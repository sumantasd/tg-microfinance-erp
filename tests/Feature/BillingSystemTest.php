<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Invoice;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\BillingService;
use App\Services\LoanAccountService;
use App\Services\SaleService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $adminUser;
    protected User $branchManagerA;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Grihalaxmi Finance Head Office',
            'code' => 'HO01',
            'email' => 'ho@grihalaxmifinance.com',
            'phone' => '9876543210',
            'address' => 'Head Office Plaza, Kolkata',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Berhampore Branch',
            'code' => 'BR01',
            'email' => 'berhampore@grihalaxmifinance.com',
            'phone' => '9876543211',
            'address' => 'Station Road, Berhampore',
            'city' => 'Berhampore',
            'state' => 'West Bengal',
            'pincode' => '742101',
            'gstin' => '19AAACG1234A1Z1',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Siliguri Branch',
            'code' => 'BR02',
            'email' => 'siliguri@grihalaxmifinance.com',
            'phone' => '9876543212',
            'address' => 'Hill Cart Road, Siliguri',
            'city' => 'Siliguri',
            'state' => 'West Bengal',
            'pincode' => '734001',
            'gstin' => '19AAACG5678B1Z2',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Super Admin',
            'email' => 'admin.billing@grihalaxmifinance.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->branchManagerA = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Manager A',
            'email' => 'manager.a@grihalaxmifinance.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->branchManagerA->assignRole('Branch Manager');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_code' => 'CUST-001',
            'first_name' => 'Rajesh',
            'last_name' => 'Kumar',
            'mobile_number' => '9876500001',
            'registration_date' => now()->toDateString(),
            'phone' => '9876500001',
            'address' => 'Main Market Road',
            'city' => 'Berhampore',
            'state' => 'West Bengal',
            'is_active' => true,
        ]);

        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung',
            'code' => 'SAM',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Electronics',
            'code' => 'ELEC',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Samsung Galaxy Smartphone',
            'sku' => 'SAM-GAL-01',
            'unit_price' => 15000.00,
            'cost_price' => 12000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'current_stock' => 50,
            'reserved_stock' => 0,
            'reorder_level' => 5,
        ]);
    }

    public function test_direct_product_sale_creates_sale_deducts_inventory_and_generates_invoice(): void
    {
        $saleService = app(SaleService::class);

        $saleData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'customer_phone' => $this->customer->phone,
            'customer_address' => $this->customer->address,
            'sale_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'paid_amount' => 30000.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 15000.00,
                    'discount' => 0.00,
                    'tax' => 0.00,
                ],
            ],
        ];

        $sale = $saleService->createDirectSale($saleData);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'total_amount' => 30000.00,
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('inventory_stocks', [
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'current_stock' => 48,
        ]);

        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branchA->id,
            'product_id' => $this->product->id,
            'movement_type' => 'sales_issue',
            'quantity' => -2,
        ]);

        $sale->refresh();
        $this->assertNotNull($sale->invoice);
        $this->assertStringStartsWith('INV-', $sale->invoice->invoice_number);
        $this->assertEquals('direct_sale', $sale->invoice->invoice_type);
        $this->assertEquals(30000.00, $sale->invoice->grand_total);
    }

    public function test_product_loan_issue_automatically_generates_loan_invoice(): void
    {
        $scheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'name' => 'Product Consumer Finance',
            'code' => 'PCF-01',
            'loan_type' => 'product',
            'min_amount' => 1000,
            'max_amount' => 100000,
            'min_tenure_months' => 1,
            'max_tenure_months' => 36,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'reducing_balance',
            'repayment_frequency' => 'monthly',
            'tenure_months' => 12,
            'is_active' => true,
        ]);

        $application = LoanApplication::create([
            'application_number' => 'LA-TEST-001',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'loan_scheme_id' => $scheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'application_date' => now()->toDateString(),
            'requested_amount' => 15000.00,
            'approved_amount' => 15000.00,
            'tenure_months' => 12,
            'interest_rate_per_annum' => 12.00,
            'interest_type' => 'reducing_balance',
            'repayment_frequency' => 'monthly',
            'status' => 'approved',
            'created_by' => $this->adminUser->id,
        ]);

        \App\Models\LoanApplicationProduct::create([
            'loan_application_id' => $application->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_price_snapshot' => 15000.00,
            'total_value' => 15000.00,
            'product_name_snapshot' => $this->product->name,
            'product_sku_snapshot' => $this->product->sku,
        ]);

        $loanAccountService = app(LoanAccountService::class);
        $loanAccount = $loanAccountService->sanctionLoanFromApplication($application, 3000.00);

        // Process upfront payment if due
        if ($loanAccount->upfront_charges_due > 0) {
            $loanAccountService->recordUpfrontPayment($loanAccount, [
                'amount' => $loanAccount->upfront_charges_due,
                'payment_method' => 'cash',
            ]);
        }

        $loanAccount = $loanAccount->fresh();

        // Issue Product Loan
        $issuedLoan = $loanAccountService->issueProductLoan($loanAccount);

        $this->assertNotNull($issuedLoan->invoice);
        $this->assertEquals('product_loan', $issuedLoan->invoice->invoice_type);
        $this->assertEquals(15000.00, $issuedLoan->invoice->grand_total);
        $this->assertEquals(3000.00, $issuedLoan->invoice->paid_amount);
        $this->assertEquals(12000.00, $issuedLoan->invoice->balance_due);
    }

    public function test_invoice_numbers_are_unique_and_sequential(): void
    {
        $billingService = app(BillingService::class);

        $inv1 = $billingService->generateInvoiceNumber();
        Invoice::create([
            'invoice_number' => $inv1,
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 100.00,
            'created_by' => $this->adminUser->id,
        ]);

        $inv2 = $billingService->generateInvoiceNumber();

        $this->assertNotEquals($inv1, $inv2);
        $this->assertStringStartsWith('INV-', $inv1);
        $this->assertStringStartsWith('INV-', $inv2);
    }

    public function test_branch_manager_can_only_access_own_branch_invoices(): void
    {
        $invA = Invoice::create([
            'invoice_number' => 'INV-TEST-A',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 500.00,
            'created_by' => $this->adminUser->id,
        ]);

        $invB = Invoice::create([
            'invoice_number' => 'INV-TEST-B',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 800.00,
            'created_by' => $this->adminUser->id,
        ]);

        // Branch A manager accesses Branch A invoice
        $responseA = $this->actingAs($this->branchManagerA)->get(route('admin.billing.invoices.show', $invA->id));
        $responseA->assertStatus(200);

        // Branch A manager attempts to access Branch B invoice -> 403 Forbidden
        $responseB = $this->actingAs($this->branchManagerA)->get(route('admin.billing.invoices.show', $invB->id));
        $responseB->assertStatus(403);
    }

    public function test_updating_company_profile_in_settings_reflects_on_invoices(): void
    {
        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update([
            'company_name' => 'Grihalaxmi Finance Ltd',
            'legal_name' => 'Grihalaxmi Microfinance Private Limited',
            'gstin' => '19AAAAA9999Z1Z9',
            'address_line_1' => '99 Financial Tower',
            'city' => 'Kolkata',
            'pin_code' => '700001',
        ]);

        \App\Services\SystemBrandingService::clearCache();

        $inv = Invoice::create([
            'invoice_number' => 'INV-TEST-BRAND',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 1000.00,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.billing.invoices.print', $inv->id));
        $response->assertStatus(200);
        $response->assertSee('Grihalaxmi Microfinance Private Limited');
        $response->assertSee('19AAAAA9999Z1Z9');
    }

    public function test_admin_can_cancel_invoice_with_reason(): void
    {
        $inv = Invoice::create([
            'invoice_number' => 'INV-CANCEL-001',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 1000.00,
            'status' => 'issued',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.billing.invoices.cancel', $inv->id), [
            'cancellation_reason' => 'Customer requested order cancellation.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $inv->refresh();
        $this->assertEquals('cancelled', $inv->status);
        $this->assertEquals('Customer requested order cancellation.', $inv->cancellation_reason);
    }
}
