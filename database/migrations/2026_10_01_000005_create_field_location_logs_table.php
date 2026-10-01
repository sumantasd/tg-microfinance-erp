<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('event_type', 30)->default('location_ping'); // ping_in, ping_out, location_ping, field_visit
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->integer('battery_level')->nullable();
            $table->string('location_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('offline_sync_id', 100)->nullable()->index();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['user_id', 'recorded_at']);
            $table->index(['branch_id', 'recorded_at']);
        });

        // Add rate per km & verified distance to travel_allowance_claims if missing
        if (Schema::hasTable('travel_allowance_claims')) {
            Schema::table('travel_allowance_claims', function (Blueprint $table) {
                if (!Schema::hasColumn('travel_allowance_claims', 'verified_distance_km')) {
                    $table->decimal('verified_distance_km', 8, 2)->nullable()->after('distance_km');
                }
                if (!Schema::hasColumn('travel_allowance_claims', 'cash_book_entry_id')) {
                    $table->foreignId('cash_book_entry_id')->nullable()->after('payment_reference')->constrained('cash_book_entries')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_location_logs');
    }
};
