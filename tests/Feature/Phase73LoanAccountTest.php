<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\InventoryStock;
use App\Models\InventoryStockMovement;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase73LoanAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Customer $customerA;
    protected Customer $customerB;
    protected CustomerGroup $group;
    protected LoanScheme $cashScheme;
    protected LoanScheme $productScheme;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $permissions = [
            'loan_application.view', 'loan_application.create', 'loan_application.edit',
            'loan_application.submit', 'loan_application.review', 'loan_application.approve',
            'loan.view', 'loan.sanction', 'loan.disburse', 'loan.issue_product',
            'loan.view_schedule', 'loan.record_down_payment',
            'inventory.view', 'inventory.manage'
        ];
        foreach ($permissions as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::all());

        $this->company = Company::create([
            'name' => 'Grihalaxmi HO',
            'code' => 'HO001',
            'registration_number' => 'REG-1001',
            'email' => 'ho@grihalaxmi.com',
            'phone' => '9999999999',
            'address' => 'Patna HO',
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Patna Main Branch',
            'code' => 'PAT01',
            'phone' => '8888888888',
            'address' => 'Patna',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@grihalaxmi.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Super Admin');

        $this->customerA = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-00001',
            'first_name' => 'Sunita',
            'last_name' => 'Devi',
            'mobile_number' => '9800000001',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->customerB = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_code' => 'CUST-00002',
            'first_name' => 'Anita',
            'last_name' => 'Kumari',
            'mobile_number' => '9800000002',
            'gender' => 'female',
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->group = CustomerGroup::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'group_code' => 'GRP-00001',
            'name' => 'Maa Durga SHG',
            'formation_date' => date('Y-m-d'),
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->group->members()->create([
            'customer_id' => $this->customerA->id,
            'role' => 'leader',
            'joined_at' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->group->members()->create([
            'customer_id' => $this->customerB->id,
            'role' => 'member',
            'joined_at' => date('Y-m-d'),
            'status' => 'active',
        ]);

        $this->cashScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-CASH01',
            'name' => 'Micro Enterprise Cash Loan',
            'loan_type' => 'cash',
            'applicant_type' => 'both',
            'min_amount' => 10000.00,
            'max_amount' => 100000.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 24,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.50,
            'insurance_fee_percentage' => 0.50,
            'is_active' => true,
        ]);

        $this->productScheme = LoanScheme::create([
            'company_id' => $this->company->id,
            'code' => 'SCH-PRD01',
            'name' => 'Goods & Machinery Product Loan',
            'loan_type' => 'product',
            'applicant_type' => 'both',
            'min_amount' => 5000.00,
            'max_amount' => 200000.00,
            'min_tenure_months' => 6,
            'max_tenure_months' => 36,
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 2.00,
            'insurance_fee_percentage' => 1.00,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'PRD-SEW01',
            'name' => 'Singer Heavy Duty Sewing Machine',
            'unit_price' => 18500.00,
            'cost_price' => 15000.00,
            'is_active' => true,
        ]);

        InventoryStock::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'current_stock' => 10,
            'reserved_stock' => 0,
            'reorder_level' => 2,
        ]);
    }

    /**
     * MANDATORY EXPLICIT REGRESSION TEST:
     * Product Price = ₹18,500
     * Customer Down Payment = ₹3,500
     * Sanctioned Amount = ₹15,000
     * Assert: Loan principal = 15000 and EMI calculation NEVER uses 18500 as principal!
     */
    public function test_product_loan_sanction_calculates_principal_and_emi_on_financed_amount_only(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000999',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 18500.00,
            'approved_amount' => 18500.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->product->id,
            'product_sku_snapshot' => $this->product->sku,
            'product_name_snapshot' => $this->product->name,
            'quantity' => 1,
            'unit_price_snapshot' => 18500.00,
            'total_value' => 18500.00,
        ]);

        // Sanction Loan with Down Payment = 3500
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3500.00,
        ]);

        $response->assertRedirect();

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);

        // Assert Product Price is 18500, Down Payment is 3500, Sanctioned Principal is 15000!
        $this->assertEquals(18500.00, $account->product_price_amount);
        $this->assertEquals(3500.00, $account->down_payment_amount);
        $this->assertEquals(15000.00, $account->sanctioned_amount);
        $this->assertEquals(15000.00, $account->principal_outstanding);

        // Assert total sum of installment principal components equals 15000 (NOT 18500!)
        $totalInstallmentPrincipal = $account->installments->sum('principal_amount');
        $this->assertEquals(15000.00, round($totalInstallmentPrincipal, 2));

        // Flat interest on 15000 @ 12% p.a. for 1 year = 1800. Total repayment = 16800.
        $this->assertEquals(1800.00, $account->total_interest_amount);
        $this->assertEquals(16800.00, $account->total_repayment_amount);
    }

    public function test_can_sanction_cash_loan_from_approved_application(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000888',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'approved_amount' => 50000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
        ]);

        $response->assertRedirect();
        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(50000.00, $account->sanctioned_amount);
        $this->assertEquals(12, $account->installments->count());
    }

    public function test_issuing_product_loan_deducts_physical_inventory_stock_and_logs_product_loan_issue_movement(): void
    {
        // Stock before issue = 10
        $stockBefore = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first()->current_stock;
        $this->assertEquals(10, $stockBefore);

        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000777',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 37000.00, // 2 x 18500
            'approved_amount' => 37000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->product->id,
            'product_sku_snapshot' => $this->product->sku,
            'product_name_snapshot' => $this->product->name,
            'quantity' => 2,
            'unit_price_snapshot' => 18500.00,
            'total_value' => 37000.00,
        ]);

        $account = LoanAccount::create([
            'loan_number' => 'LN-PAT01-2026-000777',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'product_price_amount' => 37000.00,
            'down_payment_amount' => 7000.00,
            'sanctioned_amount' => 30000.00,
            'disbursed_amount' => 0.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => 30000.00,
            'total_outstanding' => 33600.00,
            'status' => 'sanctioned',
            'sanction_date' => date('Y-m-d'),
        ]);

        // Fulfill & Issue Product
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.issue-product', $account->id));
        $response->assertRedirect();

        // Physical inventory stock MUST deduct by 2 (10 - 2 = 8)!
        $stockAfter = InventoryStock::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first()->current_stock;
        $this->assertEquals(8, $stockAfter);

        // Verify product_loan_issue movement logged
        $this->assertDatabaseHas('inventory_stock_movements', [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'movement_type' => 'product_loan_issue',
            'quantity' => -2,
            'stock_before' => 10,
            'stock_after' => 8,
            'reference_type' => 'loan_account',
            'reference_id' => $account->id,
        ]);
    }

    public function test_prevents_duplicate_product_issue(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000666',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 30000.00,
            'approved_amount' => 30000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $account = LoanAccount::create([
            'loan_number' => 'LN-PAT01-2026-000666',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_application_id' => $app->id,
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'sanctioned_amount' => 30000.00,
            'disbursed_amount' => 30000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'principal_outstanding' => 30000.00,
            'total_outstanding' => 33600.00,
            'status' => 'active', // Already active/fulfilled!
            'sanction_date' => date('Y-m-d'),
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.issue-product', $account->id));
        $response->assertSessionHasErrors('status');
    }

    public function test_full_and_partial_repayment_waterfall_allocation_and_schedule_recalculation(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000555',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 12000.00,
            'approved_amount' => 12000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00, // 1440 interest total, 1120 EMI / mo
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
        ]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals(12000.00, $account->principal_outstanding);
        $this->assertEquals(1440.00, $account->interest_outstanding);
        $this->assertEquals(13440.00, $account->total_outstanding);

        // 1. Partial Payment of ₹500
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 500.00,
            'payment_method' => 'cash',
            'reference_number' => 'REF-PARTIAL-001',
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $response->assertRedirect();
        $freshAcc = $account->fresh();

        // 500 goes first to interest (1440 - 500 = 940)
        $this->assertEquals(940.00, $freshAcc->interest_outstanding);
        $this->assertEquals(12000.00, $freshAcc->principal_outstanding);
        $this->assertEquals(12940.00, $freshAcc->total_outstanding);
        $this->assertEquals('partial', $freshAcc->installments->first()->status);

        // 2. Extra Prepayment of ₹5,000 with reduce_tenure
        $response2 = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 5000.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-EXTRA-002',
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $response2->assertRedirect();
        $freshAcc2 = $account->fresh();

        // Remaining 940 interest is cleared, rest (4060) reduces principal (12000 - 4060 = 7940)
        $this->assertEquals(0.00, $freshAcc2->interest_outstanding);
        $this->assertEquals(7940.00, $freshAcc2->principal_outstanding);
        $this->assertEquals(7940.00, $freshAcc2->total_outstanding);

        // Assert receipt record created
        $this->assertDatabaseHas('loan_repayments', [
            'loan_account_id' => $account->id,
            'amount' => 5000.00,
            'reference_number' => 'REF-EXTRA-002',
            'adjustment_mode' => 'reduce_tenure',
        ]);
    }

    public function test_product_loan_repayment_after_down_payment(): void
    {
        // Product = 18500, Down Payment = 3500, Financed = 15000
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000444',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'product',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->productScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 18500.00,
            'approved_amount' => 18500.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'status' => 'approved',
        ]);

        $app->products()->create([
            'product_id' => $this->product->id,
            'product_sku_snapshot' => $this->product->sku,
            'product_name_snapshot' => $this->product->name,
            'quantity' => 1,
            'unit_price_snapshot' => 18500.00,
            'total_value' => 18500.00,
        ]);

        // Sanction Loan with Down Payment = 3500
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), [
            'loan_application_id' => $app->id,
            'down_payment_amount' => 3500.00,
        ]);

        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertEquals(15000.00, $account->sanctioned_amount);

        // Record post-disbursement repayment of ₹2,000
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 2000.00,
            'payment_method' => 'upi',
            'reference_number' => 'UPI-PAY-8899',
            'adjustment_mode' => 'reduce_tenure',
        ]);

        $response->assertRedirect();

        // 2000 goes first to interest (1800), remaining 200 reduces principal (15000 - 200 = 14800)
        $fresh = $account->fresh();
        $this->assertEquals(14800.00, $fresh->principal_outstanding);
        $this->assertEquals(0.00, $fresh->interest_outstanding);
        $this->assertEquals(14800.00, $fresh->total_outstanding);

        // Down payment MUST STILL BE 3500 (NOT altered by post-disbursement repayment!)
        $this->assertEquals(3500.00, $fresh->down_payment_amount);
    }

    public function test_overpayment_and_duplicate_reference_protection(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000333',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 10000.00,
            'approved_amount' => 10000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00, // Total 11200
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();

        // Overpayment (15000 exceeds 11200) should fail
        $response = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 15000.00,
            'payment_method' => 'cash',
            'adjustment_mode' => 'reduce_tenure',
        ]);
        $response->assertSessionHasErrors('amount');

        // Valid Payment
        $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'reference_number' => 'DUP-REF-100',
            'adjustment_mode' => 'reduce_tenure',
        ])->assertRedirect();

        // Duplicate Reference number should fail
        $response2 = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-repayment', $account->id), [
            'amount' => 1000.00,
            'payment_method' => 'cash',
            'reference_number' => 'DUP-REF-100',
            'adjustment_mode' => 'reduce_tenure',
        ]);
        $response2->assertSessionHasErrors('reference_number');
    }

    public function test_upfront_charge_payment_lifecycle_and_disbursement_lock(): void
    {
        $app = LoanApplication::create([
            'application_number' => 'LN-APP-PAT01-2026-000222',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'loan_type' => 'cash',
            'borrower_type' => 'individual',
            'customer_id' => $this->customerA->id,
            'loan_scheme_id' => $this->cashScheme->id,
            'application_date' => date('Y-m-d'),
            'requested_amount' => 50000.00,
            'approved_amount' => 50000.00,
            'tenure_months' => 12,
            'repayment_frequency' => 'monthly',
            'interest_type' => 'flat',
            'interest_rate_per_annum' => 12.00,
            'processing_fee_percentage' => 1.50,
            'processing_fee_amount' => 750.00,
            'insurance_fee_percentage' => 0.50,
            'insurance_fee_amount' => 250.00,
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser)->post(route('admin.loan-account.sanction'), ['loan_application_id' => $app->id]);
        $account = LoanAccount::where('loan_application_id', $app->id)->first();
        $this->assertNotNull($account);

        // Initial State: Total 1000, Paid 0, Due 1000, Status pending, Disbursement Locked!
        $this->assertEquals(1000.00, $account->upfront_charges_total);
        $this->assertEquals(0.00, $account->upfront_charges_paid);
        $this->assertEquals(1000.00, $account->upfront_charges_due);
        $this->assertEquals('pending', $account->upfront_payment_status);
        $this->assertFalse($account->is_upfront_charges_paid);

        // Overpayment Validation Test (> 1000)
        $invalidRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-upfront-payment', $account->id), [
            'amount' => 1500.00,
            'payment_method' => 'cash',
        ]);
        $invalidRes->assertSessionHasErrors('amount');

        // Zero Amount Validation Test
        $zeroRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-upfront-payment', $account->id), [
            'amount' => 0.00,
            'payment_method' => 'cash',
        ]);
        $zeroRes->assertSessionHasErrors('amount');

        // 1. Record Partial Payment of ₹400
        $partialRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-upfront-payment', $account->id), [
            'amount' => 400.00,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'NEFT-UPF-001',
            'remarks' => 'Partial upfront fee payment',
        ]);
        $partialRes->assertRedirect();

        $freshAcc1 = $account->fresh();
        $this->assertEquals(400.00, $freshAcc1->upfront_charges_paid);
        $this->assertEquals(600.00, $freshAcc1->upfront_charges_due);
        $this->assertEquals('partial', $freshAcc1->upfront_payment_status);
        $this->assertFalse($freshAcc1->is_upfront_charges_paid);
        $this->assertEquals('sanctioned', $freshAcc1->status); // Still locked!

        // 2. Record Final Payment of ₹600
        $finalRes = $this->actingAs($this->adminUser)->post(route('admin.loan-account.record-upfront-payment', $account->id), [
            'amount' => 600.00,
            'payment_method' => 'cash',
            'remarks' => 'Final upfront fee payment',
        ]);
        $finalRes->assertRedirect();

        $freshAcc2 = $account->fresh();
        $this->assertEquals(1000.00, $freshAcc2->upfront_charges_paid);
        $this->assertEquals(0.00, $freshAcc2->upfront_charges_due);
        $this->assertEquals('paid', $freshAcc2->upfront_payment_status);
        $this->assertTrue($freshAcc2->is_upfront_charges_paid);
        $this->assertEquals('ready_for_disbursement', $freshAcc2->status); // UNLOCKED!
    }
}
