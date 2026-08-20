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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('supplier_code', 50);
            $table->enum('supplier_type', ['individual', 'company', 'distributor', 'manufacturer', 'other'])->default('company');
            $table->string('supplier_name', 150);
            $table->string('company_name', 150)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('mobile', 20);
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('pincode', 15)->nullable();
            $table->string('country', 50)->default('India');
            
            $table->decimal('opening_balance', 18, 4)->default(0.0000);
            $table->enum('opening_balance_type', ['payable', 'receivable'])->default('payable');
            $table->decimal('credit_limit', 18, 4)->default(0.0000);
            $table->string('payment_terms', 100)->nullable();

            $table->string('bank_name', 100)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('branch_name', 100)->nullable();

            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'supplier_code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'mobile']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
