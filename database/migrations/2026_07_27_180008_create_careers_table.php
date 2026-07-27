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
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('location')->default('Multiple Branch Locations');
            $table->string('job_type')->default('Full-Time'); // Full-Time, Part-Time, Contract
            $table->text('short_description')->nullable();
            $table->longText('requirements')->nullable();
            $table->string('application_email')->nullable();
            $table->date('deadline')->nullable();
            $table->string('apply_button_text')->default('Apply for Position');
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
        Schema::dropIfExists('careers');
    }
};
