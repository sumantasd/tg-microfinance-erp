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
        // 1. Customers Table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            
            $table->string('customer_code', 50)->unique();
            $table->string('member_number', 50)->nullable()->unique();
            $table->string('customer_type', 30)->default('individual'); // individual, group_member, micro_enterprise, corporate
            $table->string('status', 20)->default('active'); // active, inactive, blacklisted, deceased, closed
            
            $table->string('profile_photo_path')->nullable();
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->string('father_husband_guardian_name', 100)->nullable();
            
            $table->string('mobile_number', 20);
            $table->string('alternate_contact', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 20)->default('male'); // male, female, other
            $table->string('marital_status', 20)->nullable(); // single, married, divorced, widowed
            
            $table->string('occupation', 100)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->date('registration_date');
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
            $table->index('mobile_number');
        });

        // 2. Customer Addresses Table
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('address_type', 20)->default('present'); // present, permanent, business
            
            $table->string('address_line');
            $table->string('village_area', 100)->nullable();
            $table->string('post_office', 100)->nullable();
            $table->string('police_station', 100)->nullable();
            $table->string('district', 100);
            $table->string('state', 100);
            $table->string('pin_code', 10);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Customer KYC Documents Table
        Schema::create('customer_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->string('kyc_document_type', 50); // aadhaar, pan, voter_id, ration_card, passport, driving_license, other
            $table->string('document_number', 50);
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size_kb')->default(0);
            
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('verification_status', 20)->default('pending'); // pending, verified, rejected
            
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'verification_status']);
        });

        // 4. Customer Guarantors Table
        Schema::create('customer_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->string('full_name', 100);
            $table->string('relationship', 50);
            $table->string('mobile', 20);
            $table->string('alternate_contact', 20)->nullable();
            $table->text('address');
            $table->string('occupation', 100)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            
            $table->string('kyc_type', 50)->nullable();
            $table->string('kyc_number', 50)->nullable();
            $table->string('kyc_document_path')->nullable();
            $table->string('verification_status', 20)->default('pending'); // pending, verified, rejected
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Customer Nominees Table
        Schema::create('customer_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->string('nominee_name', 100);
            $table->string('relationship', 50);
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->text('address')->nullable();
            $table->decimal('share_percentage', 5, 2)->default(100.00);
            
            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name', 100)->nullable();
            $table->string('guardian_relationship', 50)->nullable();
            $table->string('guardian_contact', 20)->nullable();
            $table->text('guardian_address')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_nominees');
        Schema::dropIfExists('customer_guarantors');
        Schema::dropIfExists('customer_kyc_documents');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
