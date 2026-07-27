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
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->string('cta_heading')->nullable();
            $table->text('cta_description')->nullable();
            $table->string('cta_button1_text')->nullable();
            $table->string('cta_button1_url')->nullable();
            $table->string('cta_button2_text')->nullable();
            $table->string('cta_button2_url')->nullable();
            $table->string('cta_bg_style')->default('primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropColumn([
                'cta_heading',
                'cta_description',
                'cta_button1_text',
                'cta_button1_url',
                'cta_button2_text',
                'cta_button2_url',
                'cta_bg_style',
            ]);
        });
    }
};
