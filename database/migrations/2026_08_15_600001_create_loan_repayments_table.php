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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50)->default('cash');
            $table->string('reference_number', 100)->nullable();
            
            // Allocation Breakdown
            $table->decimal('penalty_paid', 12, 2)->default(0.00);
            $table->decimal('fee_paid', 12, 2)->default(0.00);
            $table->decimal('interest_paid', 12, 2)->default(0.00);
            $table->decimal('principal_paid', 12, 2)->default(0.00);
            
            $table->enum('adjustment_mode', ['none', 'reduce_tenure', 'reduce_emi'])->default('reduce_tenure');
            
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
