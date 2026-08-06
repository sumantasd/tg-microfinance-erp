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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 20);
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20);
            $table->text('address');
            $table->string('city', 50);
            $table->string('state', 50);
            $table->string('pincode', 10);
            $table->decimal('vault_cash_limit', 18, 4)->default(0.0000);
            $table->decimal('current_vault_balance', 18, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
