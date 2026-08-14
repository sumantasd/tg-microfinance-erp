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
        // 1. Loan Schemes Master
        Schema::create('loan_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->enum('loan_type', ['cash', 'product', 'both'])->default('cash');
            $table->enum('applicant_type', ['individual', 'group', 'both'])->default('individual');
            $table->decimal('min_amount', 12, 2);
            $table->decimal('max_amount', 12, 2);
            $table->enum('interest_type', ['flat', 'reducing_balance'])->default('flat');
            $table->decimal('interest_rate_per_annum', 5, 2);
            $table->unsignedInteger('min_tenure_months');
            $table->unsignedInteger('max_tenure_months');
            $table->enum('repayment_frequency', ['weekly', 'bi_weekly', 'monthly'])->default('monthly');
            $table->decimal('processing_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('insurance_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('late_fee_percentage', 5, 2)->default(0.00);
            $table->unsignedInteger('grace_period_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Product Catalog Master
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('sku', 50)->unique();
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->string('model_number', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('tax_percentage', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Branch Inventory Stock
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'product_id'], 'unique_branch_product_stock');
        });

        // 4. Generic Inventory Stock Movement Ledger
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('movement_code', 50)->unique();
            $table->enum('movement_type', [
                'opening_stock',
                'purchase_in',
                'product_loan_issue',
                'product_loan_return',
                'sales_issue',
                'sales_return',
                'adjustment',
                'transfer_in',
                'transfer_out'
            ]);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_value', 12, 2);
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('loan_schemes');
    }
};
