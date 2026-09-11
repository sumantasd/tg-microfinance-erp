<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\WebsiteSetting;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintPdfSalesCreateTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $adminUser;
    protected User $branchManagerA;
    protected Customer $customer;
    protected Product $product;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->company = Company::create([
            'name' => 'Acme Finance Head Office',
            'code' => 'ACME01',
            'email' => 'contact@acmefinance.com',
            'phone' => '9876543210',
            'address' => 'Corporate Tower, Financial District',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Metro Branch A',
            'code' => 'BR-A',
            'email' => 'brancha@acmefinance.com',
            'phone' => '9876543211',
            'address' => '123 Main Street',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'pincode' => '700001',
            'gstin' => '19AAAAA0000A1Z1',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Suburban Branch B',
            'code' => 'BR-B',
            'email' => 'branchb@acmefinance.com',
            'phone' => '9876543212',
            'address' => '456 Market Road',
            'city' => 'Siliguri',
            'state' => 'West Bengal',
            'pincode' => '734001',
            'gstin' => '19AAAAA0000B1Z2',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Super Admin User',
            'email' => 'admin@acmefinance.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->branchManagerA = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Manager Branch A',
            'email' => 'managera@acmefinance.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->branchManagerA->assignRole('Branch Manager');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_code' => 'CUST-1001',
            'first_name' => 'Anita',
            'last_name' => 'Sharma',
            'mobile_number' => '9800011122',
            'status' => 'active',
            'registration_date' => now()->toDateString(),
        ]);

        $brand = ProductBrand::create([
            'company_id' => $this->company->id,
            'name' => 'LG',
            'code' => 'LG',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Appliances',
            'code' => 'APP',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Smart Refrigerator',
            'sku' => 'LG-REF-01',
            'unit_price' => 25000.00,
            'cost_price' => 20000.00,
            'is_active' => true,
        ]);

        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-2026-999999',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customer->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'subtotal' => 25000.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'grand_total' => 25000.00,
            'paid_amount' => 25000.00,
            'balance_due' => 0.00,
            'status' => 'paid',
            'payment_method' => 'cash',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_sales_create_page_loads_successfully_with_http_200(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.billing.sales.create'));

        $response->assertStatus(200);
        $response->assertSee('Smart Refrigerator');
        $response->assertSee('Anita');
    }

    public function test_invoice_print_page_loads_successfully_with_http_200_and_dynamic_branding(): void
    {
        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update([
            'company_name' => 'Apex Microfinance Services',
            'legal_name' => 'Apex Microfinance Private Limited',
            'tagline' => 'Financing Futures Together',
            'phone' => '1800-100-2000',
            'email' => 'support@apexmicrofinance.com',
            'gstin' => '19APEXX1234F1Z9',
            'pan' => 'APEXX1234F',
            'cin' => 'U65999WB2026PTC123456',
            'address' => '77 Apex Plaza, Park Street, Kolkata - 700016',
        ]);

        \App\Services\SystemBrandingService::clearCache();

        $response = $this->actingAs($this->adminUser)->get(route('admin.billing.invoices.print', $this->invoice->id));

        $response->assertStatus(200);
        $response->assertSee('Apex Microfinance Private Limited');
        $response->assertSee('19APEXX1234F1Z9');
        $response->assertSee('APEXX1234F');
        $response->assertSee('U65999WB2026PTC123456');
        $response->assertSee('Metro Branch A');
    }

    public function test_invoice_pdf_page_loads_successfully_with_http_200_using_same_branding_contract(): void
    {
        $setting = WebsiteSetting::first() ?? WebsiteSetting::create([]);
        $setting->update([
            'company_name' => 'Apex Microfinance Services',
            'legal_name' => 'Apex Microfinance Private Limited',
        ]);

        \App\Services\SystemBrandingService::clearCache();

        $response = $this->actingAs($this->adminUser)->get(route('admin.billing.invoices.pdf', $this->invoice->id));

        $response->assertStatus(200);
        $response->assertSee('Apex Microfinance Private Limited');
    }

    public function test_branch_manager_cannot_print_or_pdf_invoice_of_another_branch(): void
    {
        $otherBranchInvoice = Invoice::create([
            'invoice_number' => 'INV-2026-888888',
            'invoice_type' => 'direct_sale',
            'company_id' => $this->company->id,
            'branch_id' => $this->branchB->id,
            'customer_id' => $this->customer->id,
            'invoiceable_type' => Company::class,
            'invoiceable_id' => $this->company->id,
            'invoice_date' => now()->toDateString(),
            'grand_total' => 1000.00,
            'created_by' => $this->adminUser->id,
        ]);

        $responsePrint = $this->actingAs($this->branchManagerA)->get(route('admin.billing.invoices.print', $otherBranchInvoice->id));
        $responsePrint->assertStatus(403);

        $responsePdf = $this->actingAs($this->branchManagerA)->get(route('admin.billing.invoices.pdf', $otherBranchInvoice->id));
        $responsePdf->assertStatus(403);
    }
}
