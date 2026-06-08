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
            'company_legal_name' => 'Middle East Net Zero',
            'brand_name' => 'MENetZero',
            'support_email' => 'support@menetzero.com',
            'sales_email' => 'sales@menetzero.com',
            'support_phone' => '+971 4 000 0000',
            'address_line' => 'Business Bay',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'business_hours' => 'Sunday – Thursday, 9:00 AM to 6:00 PM (GST)',
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
