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
        Schema::table('departments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('profile_photo_path', 255)->nullable()->after('user_id');
            $table->string('middle_name', 50)->nullable()->after('first_name');
            $table->string('blood_group', 10)->nullable()->after('gender');
            $table->string('emergency_contact_name', 100)->nullable()->after('phone');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            
            // Personal Details
            $table->string('father_name', 100)->nullable()->after('emergency_contact_phone');
            $table->string('mother_name', 100)->nullable()->after('father_name');
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->default('single')->after('mother_name');
            $table->string('aadhaar_number', 20)->nullable()->after('marital_status');
            $table->string('pan_number', 20)->nullable()->after('aadhaar_number');
            $table->string('voter_id', 20)->nullable()->after('pan_number');
            $table->string('driving_license', 30)->nullable()->after('voter_id');
            $table->string('passport_number', 30)->nullable()->after('driving_license');

            // Employment Details
            $table->foreignId('reporting_manager_id')->nullable()->after('designation_id')->constrained('employees')->nullOnDelete();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'probationary'])->default('full_time')->after('joining_date');
            $table->date('probation_end_date')->nullable()->after('employment_type');
            $table->date('confirmation_date')->nullable()->after('probation_end_date');

            // Salary & Banking Details
            $table->enum('salary_type', ['monthly', 'daily', 'hourly', 'commission'])->default('monthly')->after('basic_salary');
            $table->string('bank_name', 100)->nullable()->after('salary_type');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_ifsc', 20)->nullable()->after('bank_account_number');

            // User Account Control
            $table->boolean('login_enabled')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reporting_manager_id']);
            $table->dropColumn([
                'profile_photo_path',
                'middle_name',
                'blood_group',
                'emergency_contact_name',
                'emergency_contact_phone',
                'father_name',
                'mother_name',
                'marital_status',
                'aadhaar_number',
                'pan_number',
                'voter_id',
                'driving_license',
                'passport_number',
                'reporting_manager_id',
                'employment_type',
                'probation_end_date',
                'confirmation_date',
                'salary_type',
                'bank_name',
                'bank_account_number',
                'bank_ifsc',
                'login_enabled',
            ]);
        });
    }
};
