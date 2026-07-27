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
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('calc_enabled')->default(true);
            $table->string('calc_title')->default('Loan Rate Estimator');
            $table->string('calc_subtitle')->default('Instant repayment calculation');
            $table->string('calc_default_amount')->default('5000');
            $table->string('calc_min_amount')->default('500');
            $table->string('calc_max_amount')->default('25000');
            $table->json('calc_tenure_options')->nullable();
            $table->string('calc_interest_rate')->default('12.5% P.A.');
            $table->string('calc_type')->default('reducing_balance');
            $table->string('calc_rounding_type')->default('nearest_integer');
            $table->string('calc_cta_text')->default('Proceed with Application');
            $table->string('calc_cta_url')->default('/apply-loan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'calc_enabled',
                'calc_title',
                'calc_subtitle',
                'calc_default_amount',
                'calc_min_amount',
                'calc_max_amount',
                'calc_tenure_options',
                'calc_interest_rate',
                'calc_type',
                'calc_rounding_type',
                'calc_cta_text',
                'calc_cta_url',
            ]);
        });
    }
};
