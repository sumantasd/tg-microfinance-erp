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
        // 1. Add Loan Fee settings to website_settings table
        Schema::table('website_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('website_settings', 'loan_processing_fee_percentage')) {
                $table->decimal('loan_processing_fee_percentage', 5, 2)->default(1.00)->after('footer_text');
            }
            if (!Schema::hasColumn('website_settings', 'loan_processing_fee_enabled')) {
                $table->boolean('loan_processing_fee_enabled')->default(true)->after('loan_processing_fee_percentage');
            }
            if (!Schema::hasColumn('website_settings', 'loan_insurance_percentage')) {
                $table->decimal('loan_insurance_percentage', 5, 2)->default(1.00)->after('loan_processing_fee_enabled');
            }
            if (!Schema::hasColumn('website_settings', 'loan_insurance_enabled')) {
                $table->boolean('loan_insurance_enabled')->default(true)->after('loan_insurance_percentage');
            }
        });

        // 2. Add upfront charge tracking columns to loan_accounts table
        Schema::table('loan_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_accounts', 'upfront_charges_paid')) {
                $table->decimal('upfront_charges_paid', 12, 2)->default(0.00)->after('other_charges_amount');
            }
            if (!Schema::hasColumn('loan_accounts', 'upfront_payment_status')) {
                $table->enum('upfront_payment_status', ['pending', 'partial', 'paid'])->default('pending')->after('upfront_charges_paid');
            }
        });

        // 3. Create dedicated loan_upfront_payments table
        if (!Schema::hasTable('loan_upfront_payments')) {
            Schema::create('loan_upfront_payments', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number', 50)->unique();
                $table->foreignId('loan_account_id')->constrained('loan_accounts')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->decimal('processing_fee_paid', 12, 2)->default(0.00);
                $table->decimal('insurance_fee_paid', 12, 2)->default(0.00);
                $table->date('payment_date');
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_upfront_payments');

        Schema::table('loan_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('loan_accounts', 'upfront_payment_status')) {
                $table->dropColumn('upfront_payment_status');
            }
            if (Schema::hasColumn('loan_accounts', 'upfront_charges_paid')) {
                $table->dropColumn('upfront_charges_paid');
            }
        });

        Schema::table('website_settings', function (Blueprint $table) {
            if (Schema::hasColumn('website_settings', 'loan_insurance_enabled')) {
                $table->dropColumn('loan_insurance_enabled');
            }
            if (Schema::hasColumn('website_settings', 'loan_insurance_percentage')) {
                $table->dropColumn('loan_insurance_percentage');
            }
            if (Schema::hasColumn('website_settings', 'loan_processing_fee_enabled')) {
                $table->dropColumn('loan_processing_fee_enabled');
            }
            if (Schema::hasColumn('website_settings', 'loan_processing_fee_percentage')) {
                $table->dropColumn('loan_processing_fee_percentage');
            }
        });
    }
};
