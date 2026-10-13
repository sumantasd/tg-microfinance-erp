<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('attendances', 'working_duration_minutes')) {
                    $table->unsignedInteger('working_duration_minutes')->nullable()->after('clock_out');
                }
                if (!Schema::hasColumn('attendances', 'working_duration_hours')) {
                    $table->decimal('working_duration_hours', 5, 2)->nullable()->after('working_duration_minutes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (Schema::hasColumn('attendances', 'working_duration_hours')) {
                    $table->dropColumn('working_duration_hours');
                }
                if (Schema::hasColumn('attendances', 'working_duration_minutes')) {
                    $table->dropColumn('working_duration_minutes');
                }
            });
        }
    }
};
