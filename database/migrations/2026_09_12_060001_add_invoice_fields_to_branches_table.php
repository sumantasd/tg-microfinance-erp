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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address_line_2')->nullable()->after('address');
            $table->string('district')->nullable()->after('city');
            $table->string('country')->default('India')->after('state');
            $table->string('alt_phone')->nullable()->after('phone');
            $table->string('gstin')->nullable()->after('pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'address_line_2',
                'district',
                'country',
                'alt_phone',
                'gstin',
            ]);
        });
    }
};
