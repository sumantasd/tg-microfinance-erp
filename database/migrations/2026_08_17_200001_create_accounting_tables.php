<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Chart of Accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('account_code', 30);
            $table->string('account_name', 150);
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('account_group', 50);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'account_code']);
            $table->index(['company_id', 'account_type']);
            $table->index(['company_id', 'account_group']);
        });

        // 2. Bank Accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('bank_name', 100);
            $table->string('account_name', 100);
            $table->string('account_number', 50);
            $table->string('ifsc_code', 20)->nullable();
            $table->string('branch_name', 100)->nullable();
            $table->decimal('opening_balance', 18, 4)->default(0.0000);
            $table->decimal('current_balance', 18, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'account_number']);
            $table->index(['company_id', 'branch_id']);
        });

        // 3. Vouchers (Journal / Receipt / Payment / Contra Header)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->string('voucher_number', 50)->unique();
            $table->enum('voucher_type', ['journal', 'receipt', 'payment', 'contra']);
            $table->date('voucher_date');
            $table->decimal('total_debit', 18, 4);
            $table->decimal('total_credit', 18, 4);
            $table->text('narration')->nullable();
            $table->enum('status', ['posted', 'draft', 'cancelled'])->default('posted');
            
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversed_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->text('reversal_reason')->nullable();

            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'voucher_date']);
            $table->index(['company_id', 'voucher_type']);
            $table->index(['financial_year_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 4. Voucher Entries (Double Entry Lines)
        Schema::create('voucher_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('debit', 18, 4)->default(0.0000);
            $table->decimal('credit', 18, 4)->default(0.0000);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index('voucher_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_entries');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('chart_of_accounts');
    }
};
