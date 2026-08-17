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
        Schema::create('loan_settlement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('loan_account_id')->constrained('loan_accounts')->restrictOnDelete();

            $table->enum('request_type', ['foreclosure', 'settlement_ots', 'write_off']);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'completed'])->default('pending_approval');

            $table->date('as_of_date');

            // Financial Breakdown
            $table->decimal('principal_outstanding', 12, 2);
            $table->decimal('accrued_interest', 12, 2)->default(0.00);
            $table->decimal('unearned_interest_rebate', 12, 2)->default(0.00);
            $table->decimal('fee_outstanding', 12, 2)->default(0.00);
            $table->decimal('penalty_outstanding', 12, 2)->default(0.00);
            $table->decimal('foreclosure_fee', 12, 2)->default(0.00);
            $table->decimal('discount_concession_amount', 12, 2)->default(0.00);
            $table->decimal('final_settlement_amount', 12, 2);

            $table->date('valid_until_date');

            // Audit-Safe Actor Foreign Keys
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_remarks')->nullable();

            $table->text('rejection_reason')->nullable();

            // Execution Links
            $table->foreignId('repayment_id')->nullable()->constrained('loan_repayments')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['loan_account_id', 'request_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_settlement_requests');
    }
};
