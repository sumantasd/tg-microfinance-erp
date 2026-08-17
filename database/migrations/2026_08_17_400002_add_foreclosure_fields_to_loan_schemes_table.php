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
        Schema::table('loan_schemes', function (Blueprint $table) {
            $table->boolean('allow_foreclosure')->default(true)->after('max_penalty_percentage');
            $table->enum('foreclosure_fee_type', ['none', 'percentage', 'flat'])->default('none')->after('allow_foreclosure');
            $table->decimal('foreclosure_fee_percentage', 5, 2)->default(0.00)->after('foreclosure_fee_type');
            $table->decimal('foreclosure_flat_fee', 12, 2)->default(0.00)->after('foreclosure_fee_percentage');
            $table->integer('min_months_before_foreclosure')->default(0)->after('foreclosure_flat_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_schemes', function (Blueprint $table) {
            $table->dropColumn([
                'allow_foreclosure',
                'foreclosure_fee_type',
                'foreclosure_fee_percentage',
                'foreclosure_flat_fee',
                'min_months_before_foreclosure',
            ]);
        });
    }
};
