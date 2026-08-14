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
        // 1. Customer Groups Master Table
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            
            $table->string('group_code', 50)->unique();
            $table->string('name', 150);
            $table->foreignId('leader_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            
            $table->string('meeting_day', 30)->nullable(); // Monday, Tuesday...
            $table->string('meeting_time', 20)->nullable(); // 10:00 AM
            $table->string('meeting_location', 255)->nullable();
            $table->date('formation_date');
            $table->string('status', 20)->default('active'); // active, inactive, closed
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
            $table->index('group_code');
        });

        // 2. Customer Group Membership Pivot Table
        Schema::create('customer_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('customer_groups')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            $table->string('role', 30)->default('member'); // group_leader, member
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->string('status', 20)->default('active'); // active, inactive, left

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_group_members');
        Schema::dropIfExists('customer_groups');
    }
};
