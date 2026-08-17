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
        // 1. Audit Table for Incremental Penalty Charges
        Schema::create('loan_penalty_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->foreignId('loan_installment_id')->constrained('loan_installments')->cascadeOnDelete();
            $table->date('charge_date');
            $table->integer('dpd_at_charge');
            $table->decimal('charge_amount', 10, 2);
            $table->string('calculation_type', 50);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['loan_installment_id', 'charge_date'], 'unique_inst_penalty_charge_date');
        });

        // 2. Penalty Waiver Table
        Schema::create('loan_penalty_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
            $table->foreignId('loan_installment_id')->nullable()->constrained('loan_installments')->nullOnDelete();
            $table->decimal('waived_amount', 10, 2);
            $table->date('waiver_date');
            $table->text('waiver_reason');
            $table->foreignId('authorized_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_penalty_waivers');
        Schema::dropIfExists('loan_penalty_charges');
    }
};
