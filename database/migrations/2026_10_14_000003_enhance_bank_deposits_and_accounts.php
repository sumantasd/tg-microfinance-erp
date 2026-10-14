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
        // 1. Add optional account nickname/display name to bank_accounts
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'account_nickname')) {
                $table->string('account_nickname', 100)->nullable()->after('branch_name');
            }
        });

        // 2. Add bank_account_id, receipt tracking, and rejection audit fields to bank_deposits
        Schema::table('bank_deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_deposits', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('branch_id')->constrained('bank_accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_deposits', 'receipt_path')) {
                $table->string('receipt_path', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('bank_deposits', 'receipt_original_name')) {
                $table->string('receipt_original_name', 255)->nullable()->after('receipt_path');
            }
            if (!Schema::hasColumn('bank_deposits', 'receipt_mime_type')) {
                $table->string('receipt_mime_type', 100)->nullable()->after('receipt_original_name');
            }
            if (!Schema::hasColumn('bank_deposits', 'receipt_size')) {
                $table->unsignedBigInteger('receipt_size')->nullable()->after('receipt_mime_type');
            }
            if (!Schema::hasColumn('bank_deposits', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bank_deposits', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            if (Schema::hasColumn('bank_deposits', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
            if (Schema::hasColumn('bank_deposits', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            $table->dropColumn([
                'receipt_path',
                'receipt_original_name',
                'receipt_mime_type',
                'receipt_size',
                'rejected_at',
            ]);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('bank_accounts', 'account_nickname')) {
                $table->dropColumn('account_nickname');
            }
        });
    }
};
