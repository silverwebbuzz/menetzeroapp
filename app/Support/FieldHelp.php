<?php

namespace App\Support;

/**
 * Resolves per-field and per-section help copy from lang/{locale}/field_help.php.
 *
 * Key convention (dot path after "field_help."):
 *   disclosure.{framework}.{section}.{field}  — IFRS S1/S2, GRI
 *   esg_report.{section}.{field}              — UAE ESG Report narrative
 *   sections.disclosure.{framework}.{section}   — optional section intro callout
 *   sections.esg_report.{section}
 *
 * Future locales: add lang/ar/field_help.php with the same keys; set App::locale() from user/profile.
 */
class FieldHelp
{
    public static function field(?string $framework, string $section, string $field): ?string
    {
        if ($framework === null || $framework === '') {
            return null;
        }

        $key = $framework === 'esg_report'
            ? "field_help.esg_report.{$section}.{$field}"
            : "field_help.disclosure.{$framework}.{$section}.{$field}";

        return self::resolve($key);
    }

    public static function section(?string $framework, string $section): ?string
    {
        if ($framework === null || $framework === '') {
            return null;
        }

        $prefix = $framework === 'esg_report' ? 'esg_report' : "disclosure.{$framework}";
        $key = "field_help.sections.{$prefix}.{$section}";

        return self::resolve($key);
    }

    public static function line(string $dotPath): ?string
    {
        $key = str_starts_with($dotPath, 'field_help.')
            ? $dotPath
            : "field_help.{$dotPath}";

        return self::resolve($key);
    }

    private static function resolve(string $key): ?string
    {
        $text = __($key);

        return ($text === $key || $text === '') ? null : $text;
    }
}
