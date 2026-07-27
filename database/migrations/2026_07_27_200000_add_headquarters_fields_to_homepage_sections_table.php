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
            $table->string('head_office_title')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('support_box_title')->nullable();
            $table->text('support_box_description')->nullable();
            $table->string('support_button_text')->nullable();
            $table->string('support_button_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropColumn([
                'head_office_title',
                'address',
                'phone',
                'email',
                'support_box_title',
                'support_box_description',
                'support_button_text',
                'support_button_url',
            ]);
        });
    }
};
