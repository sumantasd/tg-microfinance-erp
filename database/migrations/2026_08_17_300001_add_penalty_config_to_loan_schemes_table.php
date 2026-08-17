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
            $table->enum('penalty_type', [
                'none',
                'percentage_one_time',
                'percentage_per_day',
                'flat_one_time',
                'flat_per_day'
            ])->default('percentage_one_time')->after('insurance_fee_percentage');

            $table->decimal('flat_penalty_amount', 10, 2)->default(0.00)->after('penalty_type');
            $table->decimal('max_penalty_amount', 10, 2)->nullable()->after('grace_period_days');
            $table->decimal('max_penalty_percentage', 5, 2)->nullable()->after('max_penalty_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_schemes', function (Blueprint $table) {
            $table->dropColumn([
                'penalty_type',
                'flat_penalty_amount',
                'max_penalty_amount',
                'max_penalty_percentage',
            ]);
        });
    }
};
