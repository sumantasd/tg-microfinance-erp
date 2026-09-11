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
        Schema::table('website_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('website_settings', 'theme_preset')) {
                $table->string('theme_preset')->default('navy_gold')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'primary_color')) {
                $table->string('primary_color', 30)->default('#0C2340')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'secondary_color')) {
                $table->string('secondary_color', 30)->default('#C5A059')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'accent_color')) {
                $table->string('accent_color', 30)->default('#D4AF37')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'sidebar_color')) {
                $table->string('sidebar_color', 30)->default('#0C2340')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'sidebar_text_color')) {
                $table->string('sidebar_text_color', 30)->default('#F8FAFC')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'sidebar_active_color')) {
                $table->string('sidebar_active_color', 30)->default('#C5A059')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'header_color')) {
                $table->string('header_color', 30)->default('#FFFFFF')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'header_text_color')) {
                $table->string('header_text_color', 30)->default('#0C2340')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'body_background_color')) {
                $table->string('body_background_color', 30)->default('#F8FAFC')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'card_background_color')) {
                $table->string('card_background_color', 30)->default('#FFFFFF')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'border_color')) {
                $table->string('border_color', 30)->default('#E2E8F0')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'button_radius')) {
                $table->string('button_radius', 30)->default('0.5rem')->nullable();
            }
            if (!Schema::hasColumn('website_settings', 'theme_mode')) {
                $table->string('theme_mode', 20)->default('light')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $columns = [
                'theme_preset',
                'primary_color',
                'secondary_color',
                'accent_color',
                'sidebar_color',
                'sidebar_text_color',
                'sidebar_active_color',
                'header_color',
                'header_text_color',
                'body_background_color',
                'card_background_color',
                'border_color',
                'button_radius',
                'theme_mode',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('website_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
