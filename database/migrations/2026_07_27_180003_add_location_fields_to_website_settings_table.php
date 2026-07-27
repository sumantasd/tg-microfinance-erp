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
            $table->string('location_heading')->default('Headquarters & Branch Network');
            $table->text('location_description')->nullable();
            $table->string('support_box_title')->default('Direct Inquiries & Assistance');
            $table->text('support_box_desc')->nullable();
            $table->string('support_box_button_text')->default('Contact Support Team');
            $table->string('support_box_button_url')->default('/contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'location_heading',
                'location_description',
                'support_box_title',
                'support_box_desc',
                'support_box_button_text',
                'support_box_button_url',
            ]);
        });
    }
};
