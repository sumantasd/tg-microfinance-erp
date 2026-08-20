<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanScheme;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPurchase;
use App\Models\ProductPurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ErpDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RbacSeeder::class);

        // 1. Company
        $company = Company::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Grihalaxmi Finance Private Limited',
                'code' => 'GLF001',
                'registration_number' => 'REG-GLF-2026',
                'email' => 'info@grihalaxmi.com',
                'phone' => '9876543210',
                'address' => 'Patna Main Road, Patna, Bihar 800001',
                'is_active' => true,
            ]
        );

        // 2. Branch
        $branch = Branch::firstOrCreate(
            ['id' => 1],
            [
                'company_id' => $company->id,
                'name' => 'Head Office / Main Branch',
                'code' => 'BR-GLF-001',
                'phone' => '9876543211',
                'address' => 'Patna Main Road',
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => '800001',
                'is_active' => true,
            ]
        );

        // 3. Super Admin User Context
        $admin = User::where('email', 'admin@tgmicrofinance.test')->first() ?: User::first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@tgmicrofinance.test',
                'password' => Hash::make('Grihalaxmi@2026'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $admin->syncRoles(['Super Admin']);
        }

        $admin->update([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        if (!Hash::check('Grihalaxmi@2026', $admin->password)) {
            $admin->password = Hash::make('Grihalaxmi@2026');
            $admin->save();
        }

        // 4. Loan Schemes
        $cashScheme = LoanScheme::firstOrCreate(
            ['code' => 'SCH-CASH-001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Micro Cash Loan Scheme',
                'loan_type' => 'cash',
                'applicant_type' => 'individual',
                'min_amount' => 10000.00,
                'max_amount' => 200000.00,
                'interest_type' => 'flat',
                'interest_rate_per_annum' => 12.00,
                'min_tenure_months' => 6,
                'max_tenure_months' => 24,
                'repayment_frequency' => 'monthly',
                'processing_fee_percentage' => 1.00,
                'insurance_fee_percentage' => 1.00,
                'is_active' => true,
            ]
        );

        $productScheme = LoanScheme::firstOrCreate(
            ['code' => 'SCH-PROD-001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => 'Consumer Product EMI Loan',
                'loan_type' => 'product',
                'applicant_type' => 'individual',
                'min_amount' => 5000.00,
                'max_amount' => 150000.00,
                'interest_type' => 'flat',
                'interest_rate_per_annum' => 14.00,
                'min_tenure_months' => 3,
                'max_tenure_months' => 18,
                'repayment_frequency' => 'monthly',
                'processing_fee_percentage' => 1.00,
                'insurance_fee_percentage' => 1.00,
                'is_active' => true,
            ]
        );

        // 5. Product Categories & Brands
        $catFurniture = ProductCategory::firstOrCreate(
            ['code' => 'CAT-FUR'],
            [
                'company_id' => $company->id,
                'name' => 'Furniture & Home Steel',
                'is_active' => true,
            ]
        );

        $catElectronics = ProductCategory::firstOrCreate(
            ['code' => 'CAT-ELE'],
            [
                'company_id' => $company->id,
                'name' => 'Consumer Electronics',
                'is_active' => true,
            ]
        );

        $brandTata = ProductBrand::firstOrCreate(
            ['code' => 'BRD-TSF'],
            [
                'company_id' => $company->id,
                'name' => 'TATA STEEL FURNITURE',
                'is_active' => true,
            ]
        );

        $brandSamsung = ProductBrand::firstOrCreate(
            ['code' => 'BRD-SAM'],
            [
                'company_id' => $company->id,
                'name' => 'Samsung Electronics',
                'is_active' => true,
            ]
        );

        // 6. Products
        $p1 = Product::firstOrCreate(
            ['sku' => 'PROD-TSF-001'],
            [
                'company_id' => $company->id,
                'category_id' => $catFurniture->id,
                'brand_id' => $brandTata->id,
                'name' => 'Tata Steel Almirah 2-Door Premium',
                'description' => 'Heavy duty 2-door steel wardrobe',
                'cost_price' => 25000.00,
                'unit_price' => 32000.00,
                'is_active' => true,
            ]
        );

        $p2 = Product::firstOrCreate(
            ['sku' => 'PROD-TSF-002'],
            [
                'company_id' => $company->id,
                'category_id' => $catFurniture->id,
                'brand_id' => $brandTata->id,
                'name' => 'Tata Steel Executive Desk Table',
                'description' => 'Steel office table with drawers',
                'cost_price' => 15000.00,
                'unit_price' => 20000.00,
                'is_active' => true,
            ]
        );

        // 7. Inventory Stock
        InventoryStock::firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $p1->id],
            [
                'company_id' => $company->id,
                'current_stock' => 15,
                'reserved_stock' => 0,
                'reorder_level' => 3,
            ]
        );

        InventoryStock::firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $p2->id],
            [
                'company_id' => $company->id,
                'current_stock' => 10,
                'reserved_stock' => 0,
                'reorder_level' => 2,
            ]
        );

        // 8. Supplier (TATA STEEL FURNITURE)
        $supplier = Supplier::firstOrCreate(
            ['supplier_code' => 'SUP-2026-00001'],
            [
                'company_id' => $company->id,
                'supplier_type' => 'company',
                'supplier_name' => 'TATA STEEL FURNITURE',
                'company_name' => 'Tata Steel Furniture Private Limited',
                'contact_person' => 'Rajesh Sharma',
                'mobile' => '9835012345',
                'email' => 'sales@tatasteelfurniture.com',
                'gstin' => '10AAAAA0000A1Z5',
                'pan' => 'AAAAA0000A',
                'address' => 'Industrial Area, Phase 1',
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => '800013',
                'opening_balance' => 0.00,
                'opening_balance_type' => 'payable',
                'credit_limit' => 500000.00,
                'status' => 'active',
            ]
        );

        // 9. Product Purchases
        $purchase = ProductPurchase::firstOrCreate(
            ['purchase_number' => 'PUR-BR-GLF-001-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_reference' => 'REF-TSF-9901',
                'supplier_invoice_number' => 'INV-TSF-2026-88',
                'purchase_date' => date('Y-m-d'),
                'subtotal' => 147500.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'other_charges' => 0.00,
                'grand_total' => 147500.00,
                'paid_amount' => 0.00,
                'due_amount' => 147500.00,
                'payment_status' => 'unpaid',
                'payment_method' => 'bank_transfer',
                'purchase_status' => 'confirmed',
                'created_by' => $admin->id,
            ]
        );

        ProductPurchaseItem::firstOrCreate(
            ['purchase_id' => $purchase->id, 'product_id' => $p1->id],
            [
                'product_sku_snapshot' => $p1->sku,
                'product_name_snapshot' => $p1->name,
                'quantity' => 5,
                'unit_purchase_cost' => 25000.00,
                'mrp_snapshot' => 35000.00,
                'line_subtotal' => 125000.00,
                'line_total' => 125000.00,
            ]
        );

        ProductPurchaseItem::firstOrCreate(
            ['purchase_id' => $purchase->id, 'product_id' => $p2->id],
            [
                'product_sku_snapshot' => $p2->sku,
                'product_name_snapshot' => $p2->name,
                'quantity' => 1,
                'unit_purchase_cost' => 22500.00,
                'mrp_snapshot' => 22500.00,
                'line_subtotal' => 22500.00,
                'line_total' => 22500.00,
            ]
        );

        // 10. Supplier Payments
        SupplierPayment::firstOrCreate(
            ['payment_number' => 'PAY-SUP-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'amount' => 50000.00,
                'payment_date' => date('Y-m-d', strtotime('-5 days')),
                'payment_method' => 'bank',
                'reference_number' => 'NEFT1234567',
                'notes' => 'Advance Supplier Payment',
                'created_by' => $admin->id,
            ]
        );

        SupplierPayment::firstOrCreate(
            ['payment_number' => 'PAY-SUP-2026-00002'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'amount' => 20000.00,
                'payment_date' => date('Y-m-d', strtotime('-3 days')),
                'payment_method' => 'upi',
                'reference_number' => 'UPI99887766',
                'notes' => 'Supplier Payment Part 2',
                'created_by' => $admin->id,
            ]
        );

        SupplierPayment::firstOrCreate(
            ['payment_number' => 'PAY-SUP-2026-00004'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'amount' => 147500.00,
                'payment_date' => date('Y-m-d', strtotime('-1 day')),
                'payment_method' => 'bank',
                'reference_number' => 'IMPS77665544',
                'notes' => 'Full Purchase Payment Advance',
                'created_by' => $admin->id,
            ]
        );

        // 11. Customers
        $c1 = Customer::firstOrCreate(
            ['customer_code' => 'CUST-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Ramesh',
                'last_name' => 'Kumar',
                'mobile_number' => '9876543210',
                'email' => 'ramesh.kumar@example.com',
                'gender' => 'male',
                'registration_date' => date('Y-m-d'),
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $c2 = Customer::firstOrCreate(
            ['customer_code' => 'CUST-2026-00002'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => 'Priya',
                'last_name' => 'Singh',
                'mobile_number' => '9876543211',
                'email' => 'priya.singh@example.com',
                'gender' => 'female',
                'registration_date' => date('Y-m-d'),
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // 12. Loan Applications & Accounts
        $app1 = LoanApplication::firstOrCreate(
            ['application_number' => 'APP-CASH-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $c1->id,
                'loan_scheme_id' => $cashScheme->id,
                'loan_type' => 'cash',
                'borrower_type' => 'individual',
                'application_date' => date('Y-m-d'),
                'requested_amount' => 100000.00,
                'approved_amount' => 100000.00,
                'tenure_months' => 12,
                'repayment_frequency' => 'monthly',
                'interest_rate_per_annum' => 12.00,
                'interest_type' => 'flat',
                'processing_fee_percentage' => 1.00,
                'processing_fee_amount' => 1000.00,
                'insurance_fee_percentage' => 1.00,
                'insurance_fee_amount' => 1000.00,
                'status' => 'approved',
                'created_by' => $admin->id,
            ]
        );

        LoanAccount::firstOrCreate(
            ['loan_number' => 'LN-CASH-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $c1->id,
                'loan_application_id' => $app1->id,
                'loan_scheme_id' => $cashScheme->id,
                'loan_type' => 'cash',
                'borrower_type' => 'individual',
                'sanctioned_amount' => 100000.00,
                'principal_outstanding' => 100000.00,
                'total_outstanding' => 112000.00,
                'tenure_months' => 12,
                'repayment_frequency' => 'monthly',
                'interest_type' => 'flat',
                'interest_rate_per_annum' => 12.00,
                'processing_fee_percentage' => 1.00,
                'processing_fee_amount' => 1000.00,
                'insurance_fee_percentage' => 1.00,
                'insurance_fee_amount' => 1000.00,
                'upfront_charges_paid' => 2000.00,
                'upfront_payment_status' => 'paid',
                'status' => 'active',
                'sanction_date' => date('Y-m-d', strtotime('-10 days')),
                'disbursement_date' => date('Y-m-d', strtotime('-10 days')),
                'created_by' => $admin->id,
            ]
        );

        $app2 = LoanApplication::firstOrCreate(
            ['application_number' => 'APP-PROD-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $c2->id,
                'loan_scheme_id' => $productScheme->id,
                'loan_type' => 'product',
                'borrower_type' => 'individual',
                'application_date' => date('Y-m-d'),
                'requested_amount' => 32000.00,
                'approved_amount' => 32000.00,
                'tenure_months' => 12,
                'repayment_frequency' => 'monthly',
                'interest_rate_per_annum' => 14.00,
                'interest_type' => 'flat',
                'processing_fee_percentage' => 1.00,
                'processing_fee_amount' => 320.00,
                'insurance_fee_percentage' => 1.00,
                'insurance_fee_amount' => 320.00,
                'status' => 'approved',
                'created_by' => $admin->id,
            ]
        );

        LoanAccount::firstOrCreate(
            ['loan_number' => 'LN-PROD-2026-00001'],
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $c2->id,
                'loan_application_id' => $app2->id,
                'loan_scheme_id' => $productScheme->id,
                'loan_type' => 'product',
                'borrower_type' => 'individual',
                'product_price_amount' => 32000.00,
                'down_payment_amount' => 3200.00,
                'sanctioned_amount' => 28800.00,
                'principal_outstanding' => 28800.00,
                'total_outstanding' => 32832.00,
                'tenure_months' => 12,
                'repayment_frequency' => 'monthly',
                'interest_type' => 'flat',
                'interest_rate_per_annum' => 14.00,
                'processing_fee_percentage' => 1.00,
                'processing_fee_amount' => 320.00,
                'insurance_fee_percentage' => 1.00,
                'insurance_fee_amount' => 320.00,
                'upfront_charges_paid' => 640.00,
                'upfront_payment_status' => 'paid',
                'status' => 'active',
                'sanction_date' => date('Y-m-d', strtotime('-5 days')),
                'disbursement_date' => date('Y-m-d', strtotime('-5 days')),
                'created_by' => $admin->id,
            ]
        );

        // Seed product line item for app2 (Tata Steel Almirah 2-Door Premium x 1 = ₹32,000)
        \App\Models\LoanApplicationProduct::firstOrCreate(
            ['loan_application_id' => $app2->id, 'product_id' => $p1->id],
            [
                'product_sku_snapshot' => $p1->sku,
                'product_name_snapshot' => $p1->name,
                'quantity' => 1,
                'unit_price_snapshot' => 32000.00,
                'total_value' => 32000.00,
                'remarks' => 'Tata Steel Almirah 2-Door Premium',
            ]
        );
    }
}
