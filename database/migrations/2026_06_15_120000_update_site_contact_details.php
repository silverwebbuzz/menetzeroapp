<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Align public contact details with MeNetZero mailboxes:
 * support → help@menetzero.com, sales → hello@menetzero.com
 */
return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'support_email' => 'help@menetzero.com',
            'sales_email' => 'hello@menetzero.com',
            'support_phone' => '+91 9998010029',
        ];

        foreach ($updates as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        // Contact details are intentionally not reverted.
    }
};
