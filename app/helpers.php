<?php

use App\Services\GhgReportService;

if (! function_exists('decode_json_field')) {
    /**
     * Normalize a JSON column that may already be cast to an array (Eloquent).
     */
    function decode_json_field(mixed $value): array
    {
        return \App\Support\JsonField::decode($value);
    }
}

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

if (! function_exists('mail_smtp_scheme')) {
    /**
     * Normalize SMTP scheme for Laravel 12 / Symfony Mailer.
     * Valid DSN schemes: null (STARTTLS on 587), smtps (SSL on 465).
     * Legacy .env values tls/ssl/starttls are mapped here.
     */
    function mail_smtp_scheme(?string $override = null): ?string
    {
        $raw = $override;
        if ($raw === null || $raw === '') {
            $raw = env('MAIL_SCHEME') ?: env('MAIL_ENCRYPTION');
        }
        if ($raw === null || $raw === '') {
            return null;
        }

        return match (strtolower((string) $raw)) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'starttls', 'smtp' => null,
            default => null,
        };
    }
}

if (! function_exists('mail_transport_for_mailbox')) {
    /**
     * SMTP transport name for a logical mailbox (hello, help, noreply).
     * Falls back to smtp_noreply when mailbox-specific credentials are not set.
     */
    function mail_transport_for_mailbox(string $mailbox): string
    {
        $dedicatedUser = match ($mailbox) {
            'hello' => env('MAIL_HELLO_USERNAME'),
            'help' => env('MAIL_HELP_USERNAME'),
            'noreply' => env('MAIL_NOREPLY_USERNAME'),
            default => null,
        };

        if ($dedicatedUser !== null && $dedicatedUser !== '') {
            return match ($mailbox) {
                'hello' => 'smtp_hello',
                'help' => 'smtp_help',
                default => 'smtp_noreply',
            };
        }

        return 'smtp_noreply';
    }
}

if (! function_exists('quick_input_help')) {
    function quick_input_help(string $slug, string $field, array $context = []): ?string
    {
        return \App\Support\FieldHelp::forQuickInput($slug, $field, $context);
    }
}

    /**
     * Per-field help line from lang/{locale}/field_help.php (null if no entry).
     */
    function field_help(?string $framework, string $section, string $field): ?string
    {
        return \App\Support\FieldHelp::field($framework, $section, $field);
    }
}

if (! function_exists('field_help_section')) {
    /**
     * Optional section-level callout from lang/{locale}/field_help.php.
     */
    function field_help_section(?string $framework, string $section): ?string
    {
        return \App\Support\FieldHelp::section($framework, $section);
    }
}
