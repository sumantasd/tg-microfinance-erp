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
        Schema::table('product_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('product_purchases', 'is_inventory_processed')) {
                $table->boolean('is_inventory_processed')->default(false)->after('purchase_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('product_purchases', 'is_inventory_processed')) {
                $table->dropColumn('is_inventory_processed');
            }
        });
    }
};
