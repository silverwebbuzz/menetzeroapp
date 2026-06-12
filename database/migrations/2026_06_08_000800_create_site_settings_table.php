<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simple key/value store for site-wide settings: company/contact details
 * (used on policy pages + footer) and currency display preferences.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            'company_legal_name' => 'Silver Webbuzz Private Limited',
            'brand_name' => 'MeNetZero',
            'support_email' => 'help@menetzero.com',
            'sales_email' => 'hello@menetzero.com',
            'support_phone' => '+91 9998010029',
            'address_line' => '',
            'city' => '',
            'country' => 'India',
            'business_hours' => 'Monday – Saturday, 10:00 AM to 7:00 PM (IST)',
            'default_currency' => 'AED',
            'currency_auto_detect' => '1',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('site_settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
