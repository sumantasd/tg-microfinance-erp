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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            
            $table->enum('loan_type', ['cash', 'product'])->default('cash');
            $table->enum('borrower_type', ['individual', 'group'])->default('individual');
            
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignId('loan_scheme_id')->constrained('loan_schemes')->cascadeOnDelete();
            
            $table->date('application_date');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->integer('tenure_months');
            $table->string('repayment_frequency', 50);
            
            // Loan Scheme Terms Snapshots
            $table->string('interest_type', 50);
            $table->decimal('interest_rate_per_annum', 5, 2);
            $table->decimal('processing_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('processing_fee_amount', 12, 2)->default(0.00);
            $table->decimal('insurance_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('insurance_fee_amount', 12, 2)->default(0.00);
            $table->decimal('late_fee_percentage', 5, 2)->default(0.00);
            $table->integer('grace_period_days')->default(0);
            
            $table->text('purpose')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'cancelled'
            ])->default('draft');
            
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loan_application_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_application_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_sku_snapshot', 50);
            $table->string('product_name_snapshot', 255);
            $table->integer('quantity');
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->decimal('total_value', 12, 2);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_application_products');
        Schema::dropIfExists('loan_application_members');
        Schema::dropIfExists('loan_applications');
    }
};
