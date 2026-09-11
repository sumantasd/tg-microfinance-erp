<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE loan_settlement_requests MODIFY COLUMN request_type ENUM('foreclosure', 'settlement_ots', 'write_off', 'borrower_death') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE loan_settlement_requests MODIFY COLUMN request_type ENUM('foreclosure', 'settlement_ots', 'write_off') NOT NULL");
        }
    }
};
