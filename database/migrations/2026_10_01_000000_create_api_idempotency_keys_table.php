<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 120);
            $table->string('endpoint', 100);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('request_hash', 64);
            $table->string('status', 20)->default('processing');
            $table->integer('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'endpoint', 'idempotency_key'], 'idempotency_user_endpoint_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
