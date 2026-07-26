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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->string('employee_id')->nullable()->unique()->after('branch_id');
            $table->string('mobile_number')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('mobile_number');
            $table->string('signature_path')->nullable()->after('avatar');
            $table->string('digital_id_number')->nullable()->after('signature_path');
            $table->enum('status', ['active', 'inactive', 'suspended', 'locked'])->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->unsignedBigInteger('created_by')->nullable()->after('last_login_ip');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_id',
                'branch_id',
                'employee_id',
                'mobile_number',
                'avatar',
                'signature_path',
                'digital_id_number',
                'status',
                'last_login_at',
                'last_login_ip',
                'created_by',
                'updated_by',
                'deleted_at',
            ]);
        });
    }
};
