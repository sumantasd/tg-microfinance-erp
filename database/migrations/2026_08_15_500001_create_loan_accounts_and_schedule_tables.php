<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 50)->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignId('loan_scheme_id')->constrained('loan_schemes')->cascadeOnDelete();
            
            $table->enum('loan_type', ['cash', 'product'])->default('cash');
            $table->enum('borrower_type', ['individual', 'group'])->default('individual');
            
            // Financial Breakdown
            $table->decimal('product_price_amount', 12, 2)->default(0.00);
            $table->decimal('down_payment_amount', 12, 2)->default(0.00);
            $table->decimal('sanctioned_amount', 12, 2); // Financed principal (Product Price - Down Payment for Product Loan)
            $table->decimal('disbursed_amount', 12, 2)->default(0.00);
            
            $table->integer('tenure_months');
            $table->string('repayment_frequency', 50);
            $table->string('interest_type', 50);
            $table->decimal('interest_rate_per_annum', 5, 2);
            
            $table->decimal('processing_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('processing_fee_amount', 12, 2)->default(0.00);
            $table->decimal('insurance_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('insurance_fee_amount', 12, 2)->default(0.00);
            $table->decimal('other_charges_amount', 12, 2)->default(0.00);
            
            $table->decimal('total_interest_amount', 12, 2)->default(0.00);
            $table->decimal('total_repayment_amount', 12, 2)->default(0.00);
            
            // Outstanding Balances
            $table->decimal('principal_outstanding', 12, 2);
            $table->decimal('interest_outstanding', 12, 2)->default(0.00);
            $table->decimal('fee_outstanding', 12, 2)->default(0.00);
            $table->decimal('penalty_outstanding', 12, 2)->default(0.00);
            $table->decimal('total_outstanding', 12, 2);
            
            $table->enum('status', [
                'sanctioned',
                'ready_for_disbursement',
                'active',
                'closed',
                'defaulted',
                'cancelled'
            ])->default('sanctioned');
            
            $table->date('sanction_date');
            $table->date('disbursement_date')->nullable();
            $table->date('maturity_date')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loan_account_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('sanctioned_amount', 12, 2);
            $table->decimal('down_payment_amount', 12, 2)->default(0.00);
            $table->decimal('principal_outstanding', 12, 2);
            $table->decimal('interest_outstanding', 12, 2)->default(0.00);
            $table->decimal('total_outstanding', 12, 2);
            $table->timestamps();
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            
            $table->decimal('opening_principal', 12, 2);
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('fee_amount', 12, 2)->default(0.00);
            $table->decimal('penalty_amount', 12, 2)->default(0.00);
            $table->decimal('installment_amount', 12, 2);
            
            $table->decimal('principal_paid', 12, 2)->default(0.00);
            $table->decimal('interest_paid', 12, 2)->default(0.00);
            $table->decimal('fee_paid', 12, 2)->default(0.00);
            $table->decimal('penalty_paid', 12, 2)->default(0.00);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->decimal('closing_principal', 12, 2);
            
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'waived'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('loan_down_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->string('disbursement_number', 50)->unique();
            $table->date('disbursement_date');
            $table->decimal('disbursed_amount', 12, 2);
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_disbursements');
        Schema::dropIfExists('loan_down_payments');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loan_account_members');
        Schema::dropIfExists('loan_accounts');
    }
};
