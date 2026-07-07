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
 *   quick_input.{slug}.{field}                  — Quick Input forms
 *   quick_input._common.{field}                 — shared across all sources
 *   sections.quick_input.{slug}                 — optional source intro callout
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

    /**
     * Quick Input: lang key quick_input.{slug}.{field}, then quick_input._common.{field}.
     * Optional context['variant'] tries quick_input.{slug}.{field}_{variant} first.
     */
    public static function forQuickInput(string $slug, string $fieldName, array $context = []): ?string
    {
        $slug = self::normalizeSlug($slug);
        $fieldName = trim($fieldName);
        if ($slug === '' || $fieldName === '') {
            return null;
        }

        $variant = isset($context['variant']) ? trim((string) $context['variant']) : '';
        if ($variant !== '') {
            $variantKey = "field_help.quick_input.{$slug}.{$fieldName}_{$variant}";
            if (($text = self::resolve($variantKey)) !== null) {
                return $text;
            }
        }

        $sourceKey = "field_help.quick_input.{$slug}.{$fieldName}";
        if (($text = self::resolve($sourceKey)) !== null) {
            return $text;
        }

        return self::resolve("field_help.quick_input._common.{$fieldName}");
    }

    public static function quickInputSection(string $slug): ?string
    {
        $slug = self::normalizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        return self::resolve("field_help.sections.quick_input.{$slug}");
    }

    private static function normalizeSlug(string $slug): string
    {
        return trim(str_replace('_', '-', $slug), '/');
    }

    private static function resolve(string $key): ?string
    {
        $text = __($key);

        return ($text === $key || $text === '') ? null : $text;
    }
}
