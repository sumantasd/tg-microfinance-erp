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
        Schema::create('interest_rates', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('product_type')->default('loan'); // loan, savings
            $table->string('amount_range')->nullable();
            $table->string('tenure_options')->nullable();
            $table->string('interest_rate');
            $table->string('interest_method')->default('Reducing Balance'); // Flat, Reducing Balance, Daily Reducing
            $table->string('processing_fee')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interest_rates');
    }
};
