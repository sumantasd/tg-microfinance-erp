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
            $table->string('legal_name')->nullable()->after('company_name');
            $table->string('company_type')->nullable()->after('legal_name'); // e.g., Private Limited, NBFC-MFI
            $table->string('tagline')->nullable()->after('company_type');
            $table->string('registration_number')->nullable()->after('tagline');
            $table->string('gstin')->nullable()->after('registration_number');
            $table->string('pan')->nullable()->after('gstin');
            $table->string('cin')->nullable()->after('pan');
            $table->string('alternate_phone')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('whatsapp')->nullable()->after('website');
            $table->string('address_line_1')->nullable()->after('address');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('district')->nullable()->after('city');
            $table->string('state')->nullable()->after('district');
            $table->string('country')->default('India')->after('state');
            $table->string('pin_code')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'company_type',
                'tagline',
                'registration_number',
                'gstin',
                'pan',
                'cin',
                'alternate_phone',
                'website',
                'whatsapp',
                'address_line_1',
                'address_line_2',
                'city',
                'district',
                'state',
                'country',
                'pin_code',
            ]);
        });
    }
};
