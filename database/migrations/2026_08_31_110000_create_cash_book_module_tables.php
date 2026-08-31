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
        // 1. Master Daily Cash Book Register Table
        Schema::create('cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->date('date');
            $table->foreignId('responsible_staff_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->decimal('opening_balance', 12, 2)->default(0.00);
            
            $table->decimal('total_cash_received', 12, 2)->default(0.00);
            $table->decimal('total_product_received', 12, 2)->default(0.00);
            $table->decimal('total_bank_received', 12, 2)->default(0.00);
            
            $table->decimal('total_cash_payment', 12, 2)->default(0.00);
            $table->decimal('total_product_payment', 12, 2)->default(0.00);
            $table->decimal('total_bank_payment', 12, 2)->default(0.00);
            
            $table->decimal('closing_cash', 12, 2)->default(0.00);
            $table->decimal('physical_cash', 12, 2)->default(0.00);
            $table->decimal('cash_difference', 12, 2)->default(0.00);
            
            $table->string('reconciled_status', 30)->default('balanced'); // balanced, cash_short, cash_excess
            $table->string('status', 20)->default('open'); // open, closed, approved
            
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'date']);
        });

        // 2. Cash Book Particular Items Table (Received & Payment Entries)
        Schema::create('cash_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->constrained('cash_books')->onDelete('cascade');
            $table->string('entry_type', 20); // received, payment
            $table->string('category_code', 50);
            $table->string('particulars');
            $table->date('entry_date');
            
            $table->decimal('cash_amount', 12, 2)->default(0.00);
            $table->decimal('product_amount', 12, 2)->default(0.00);
            $table->decimal('bank_amount', 12, 2)->default(0.00);
            
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->integer('sort_order')->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });

        // 3. Online Collection Details Sub-table
        Schema::create('cash_book_online_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->constrained('cash_books')->onDelete('cascade');
            $table->date('collection_date');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('customer_name');
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->foreignId('group_id')->nullable()->constrained('customer_groups')->onDelete('set null');
            $table->string('group_name')->nullable();
            $table->string('mobile_no', 30)->nullable();
            $table->string('payment_method', 30)->nullable(); // upi, bank_transfer, qr, card
            $table->string('transaction_reference')->nullable();
            $table->foreignId('repayment_id')->nullable()->constrained('loan_repayments')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });

        // 4. Physical Cash Denomination Counter Table
        Schema::create('cash_book_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->constrained('cash_books')->onDelete('cascade');
            $table->integer('denomination'); // 500, 200, 100, 50, 20, 10, 5, 2, 1
            $table->integer('count')->default(0);
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['cash_book_id', 'denomination']);
        });

        // 5. Configurable Particulars Categories Master Table
        Schema::create('cash_book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // received, payment
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_system_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. Cash Book Security Audit Log Table
        Schema::create('cash_book_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_book_id')->constrained('cash_books')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 50); // created, entry_added, entry_updated, entry_deleted, closed, reopened, approved
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_book_audits');
        Schema::dropIfExists('cash_book_categories');
        Schema::dropIfExists('cash_book_denominations');
        Schema::dropIfExists('cash_book_online_collections');
        Schema::dropIfExists('cash_book_entries');
        Schema::dropIfExists('cash_books');
    }
};
