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
        if (!Schema::hasTable('bank_deposits')) {
            Schema::create('bank_deposits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->date('deposit_date');
                $table->decimal('amount', 12, 2);
                $table->string('bank_name', 150);
                $table->string('account_number', 50)->nullable();
                $table->string('reference_number', 100); // Slip or transaction reference
                $table->text('description')->nullable();
                
                $table->string('status', 20)->default('pending'); // pending, approved, rejected
                
                $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
                $table->timestamp('submitted_at')->useCurrent();
                
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
    }
};
