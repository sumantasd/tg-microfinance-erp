<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->unique()->constrained('employees')->onDelete('cascade');
            $table->decimal('basic_salary', 18, 4)->default(0.0000);
            $table->decimal('hra', 18, 4)->default(0.0000);
            $table->decimal('conveyance_allowance', 18, 4)->default(0.0000);
            $table->decimal('special_allowance', 18, 4)->default(0.0000);
            $table->decimal('pf_deduction', 18, 4)->default(0.0000);
            $table->decimal('tax_deduction', 18, 4)->default(0.0000);
            $table->decimal('other_deduction', 18, 4)->default(0.0000);
            $table->decimal('gross_salary', 18, 4)->default(0.0000);
            $table->decimal('net_salary', 18, 4)->default(0.0000);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross', 18, 4)->default(0.0000);
            $table->decimal('total_deductions', 18, 4)->default(0.0000);
            $table->decimal('total_net_payout', 18, 4)->default(0.0000);
            $table->enum('status', ['draft', 'approved', 'disbursed'])->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'month', 'year']);
        });

        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payroll_id')->constrained('payrolls')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('basic_salary', 18, 4)->default(0.0000);
            $table->decimal('hra', 18, 4)->default(0.0000);
            $table->decimal('conveyance_allowance', 18, 4)->default(0.0000);
            $table->decimal('special_allowance', 18, 4)->default(0.0000);
            $table->decimal('pf_deduction', 18, 4)->default(0.0000);
            $table->decimal('tax_deduction', 18, 4)->default(0.0000);
            $table->decimal('other_deduction', 18, 4)->default(0.0000);
            $table->decimal('gross_salary', 18, 4)->default(0.0000);
            $table->decimal('total_deductions', 18, 4)->default(0.0000);
            $table->decimal('net_salary', 18, 4)->default(0.0000);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_structures');
    }
};
