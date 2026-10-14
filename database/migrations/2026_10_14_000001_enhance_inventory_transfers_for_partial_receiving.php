<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expand status column to include 'partially_received' and 'discrepancy'
        if (Schema::hasTable('inventory_transfers')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('draft', 'requested', 'approved', 'in_transit', 'partially_received', 'received', 'discrepancy', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'");
            } else {
                Schema::table('inventory_transfers', function (Blueprint $table) {
                    $table->string('status', 50)->default('draft')->change();
                });
            }
        }

        // 2. Add receiving tracking columns to inventory_transfer_items
        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_transfer_items', 'received_quantity')) {
                $table->integer('received_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'damaged_quantity')) {
                $table->integer('damaged_quantity')->default(0)->after('received_quantity');
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'shortage_quantity')) {
                $table->integer('shortage_quantity')->default(0)->after('damaged_quantity');
            }
            if (!Schema::hasColumn('inventory_transfer_items', 'discrepancy_remarks')) {
                $table->text('discrepancy_remarks')->nullable()->after('shortage_quantity');
            }
        });

        // 3. Backfill existing fully received transfers safely
        $receivedTransferIds = DB::table('inventory_transfers')
            ->where('status', 'received')
            ->pluck('id');

        if ($receivedTransferIds->isNotEmpty()) {
            DB::table('inventory_transfer_items')
                ->whereIn('transfer_id', $receivedTransferIds)
                ->update([
                    'received_quantity' => DB::raw('quantity'),
                ]);
        }

        // 4. Create separate receipt history tables
        if (!Schema::hasTable('inventory_transfer_receipts')) {
            Schema::create('inventory_transfer_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
                $table->string('receipt_number', 60)->unique();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at');
                $table->integer('total_good_quantity')->default(0);
                $table->integer('total_damaged_quantity')->default(0);
                $table->integer('total_shortage_quantity')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_transfer_receipt_items')) {
            Schema::create('inventory_transfer_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('receipt_id')->constrained('inventory_transfer_receipts')->cascadeOnDelete();
                $table->foreignId('transfer_item_id')->constrained('inventory_transfer_items')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('good_quantity')->default(0);
                $table->integer('damaged_quantity')->default(0);
                $table->integer('shortage_quantity')->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_receipt_items');
        Schema::dropIfExists('inventory_transfer_receipts');

        Schema::table('inventory_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'damaged_quantity', 'shortage_quantity', 'discrepancy_remarks']);
        });

        if (Schema::hasTable('inventory_transfers')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE inventory_transfers MODIFY COLUMN status ENUM('draft', 'requested', 'approved', 'in_transit', 'received', 'rejected', 'cancelled') NOT NULL DEFAULT 'draft'");
            }
        }
    }
};
