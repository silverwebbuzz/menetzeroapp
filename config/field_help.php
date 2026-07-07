<?php

/**
 * Field help i18n settings.
 *
 * Copy lives in lang/{locale}/field_help.php (English: lang/en/field_help.php).
 * Forms resolve keys as disclosure.{framework}.{section}.{field} or esg_report.{section}.{field}.
 * Quick Input: quick_input.{slug}.{field} then quick_input._common.{field} (DB help_text is fallback only).
 *
 * To add Arabic later: copy lang/en/field_help.php → lang/ar/field_help.php, then set
 * user locale (e.g. App::setLocale('ar') in middleware from profile preference).
 */
return [
    'fallback_locale' => 'en',
];
