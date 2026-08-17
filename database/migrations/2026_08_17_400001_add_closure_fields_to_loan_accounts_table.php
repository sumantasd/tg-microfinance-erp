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
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('maturity_date');
            $table->enum('closure_type', ['normal', 'foreclosure', 'settlement', 'write_off'])->nullable()->after('closed_at');
            $table->text('closure_remarks')->nullable()->after('closure_type');
            $table->foreignId('closure_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('closure_remarks');
            $table->timestamp('closure_approved_at')->nullable()->after('closure_approved_by');

            $table->index(['company_id', 'status', 'closure_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_accounts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status', 'closure_type']);
            $table->dropForeign(['closure_approved_by']);
            $table->dropColumn([
                'closed_at',
                'closure_type',
                'closure_remarks',
                'closure_approved_by',
                'closure_approved_at',
            ]);
        });
    }
};
