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
        // 1. Add cancellation tracking columns to inventory_transfer_items
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transfer_items', 'cancelled_quantity')) {
                $table->integer('cancelled_quantity')->default(0)->after('shortage_quantity');
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('discrepancy_remarks');
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancellation_reason')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }
        });

        // 2. Create batch cancellation audit tables
        if (!Schema::hasTable('inventory_transfer_cancellations')) {
            Schema::create('inventory_transfer_cancellations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at');
                $table->string('scope', 30)->default('item'); // 'item', 'category', 'all_remaining'
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->integer('total_cancelled_quantity')->default(0);
                $table->text('reason');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_transfer_cancellation_items')) {
            Schema::create('inventory_transfer_cancellation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cancellation_id')->constrained('inventory_transfer_cancellations')->cascadeOnDelete();
                $table->foreignId('transfer_item_id')->constrained('inventory_transfer_items')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('cancelled_quantity')->default(0);
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_cancellation_items');
        Schema::dropIfExists('inventory_transfer_cancellations');

        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_quantity', 'cancellation_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};
