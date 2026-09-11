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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->date('sale_date');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('balance_amount', 15, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->string('payment_status')->default('paid');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('invoice_type'); // direct_sale, product_loan
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->morphs('invoiceable');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('balance_due', 15, 2)->default(0.00);
            $table->string('status')->default('issued'); // draft, issued, paid, partially_paid, due, cancelled
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('item_name');
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('line_total', 15, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
