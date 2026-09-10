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
        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('paid_amount', 15, 2);
            $table->string('payment_method'); // cash, bank_transfer, upi, cheque, other
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('paid_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_payments');
    }
};
