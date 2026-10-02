<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_settings')) {
            Schema::table('website_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('website_settings', 'ta_rate_per_km')) {
                    $table->decimal('ta_rate_per_km', 8, 2)->default(4.00)->after('loan_insurance_enabled');
                }
                if (!Schema::hasColumn('website_settings', 'ta_rate_per_km_bike')) {
                    $table->decimal('ta_rate_per_km_bike', 8, 2)->default(4.00)->after('ta_rate_per_km');
                }
                if (!Schema::hasColumn('website_settings', 'ta_rate_per_km_car')) {
                    $table->decimal('ta_rate_per_km_car', 8, 2)->default(8.00)->after('ta_rate_per_km_bike');
                }
                if (!Schema::hasColumn('website_settings', 'ta_max_daily_limit')) {
                    $table->decimal('ta_max_daily_limit', 8, 2)->default(600.00)->after('ta_rate_per_km_car');
                }
                if (!Schema::hasColumn('website_settings', 'ta_min_distance_km')) {
                    $table->decimal('ta_min_distance_km', 8, 2)->default(2.00)->after('ta_max_daily_limit');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('website_settings')) {
            Schema::table('website_settings', function (Blueprint $table) {
                $columns = ['ta_rate_per_km', 'ta_rate_per_km_bike', 'ta_rate_per_km_car', 'ta_max_daily_limit', 'ta_min_distance_km'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('website_settings', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
