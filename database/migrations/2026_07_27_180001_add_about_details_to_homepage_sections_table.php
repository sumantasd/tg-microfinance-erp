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
            $table->string('mission_title')->nullable();
            $table->text('mission_description')->nullable();
            $table->string('mission_icon')->nullable();
            $table->string('vision_title')->nullable();
            $table->text('vision_description')->nullable();
            $table->string('vision_icon')->nullable();
            $table->string('governance_title')->nullable();
            $table->text('governance_description')->nullable();
            $table->json('governance_bullets')->nullable();
            $table->string('governance_icon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropColumn([
                'mission_title',
                'mission_description',
                'mission_icon',
                'vision_title',
                'vision_description',
                'vision_icon',
                'governance_title',
                'governance_description',
                'governance_bullets',
                'governance_icon',
            ]);
        });
    }
};
