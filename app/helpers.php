<?php

use App\Services\GhgReportService;

if (! function_exists('co2e_t')) {
    /**
     * Format stored kg CO₂e for display as metric tonnes (tCO₂e).
     */
    function co2e_t(float|int|string|null $kg, int $decimals = 2): string
    {
        return GhgReportService::formatTonnes($kg, $decimals);
    }
}

if (! function_exists('co2e_tonne')) {
    /**
     * Convert stored kg CO₂e to metric tonnes (float).
     */
    function co2e_tonne(float|int|string|null $kg): float
    {
        return GhgReportService::kgToTonnes($kg);
    }
}

if (! function_exists('site_support_email')) {
    function site_support_email(): string
    {
        $email = \App\Models\SiteSetting::get('support_email');

        return ($email !== null && $email !== '')
            ? $email
            : config('mail.addresses.help.address', 'help@menetzero.com');
    }
}

if (! function_exists('site_sales_email')) {
    function site_sales_email(): string
    {
        $email = \App\Models\SiteSetting::get('sales_email');

        return ($email !== null && $email !== '')
            ? $email
            : config('mail.addresses.hello.address', 'hello@menetzero.com');
    }
}

if (! function_exists('site_support_phone')) {
    function site_support_phone(): string
    {
        $phone = \App\Models\SiteSetting::get('support_phone');

        return ($phone !== null && $phone !== '')
            ? $phone
            : '+91 9998010029';
    }
}
