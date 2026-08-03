<?php

namespace App\Support;

/**
 * Stamp text for Free / trial package downloads (Phase 2).
 * Paid packages must not apply this watermark.
 */
class ExportWatermark
{
    public const SHORT = 'MENetZero Free Trial — Draft / Not for regulatory submission';

    public static function bannerText(?string $generatedAt = null): string
    {
        $when = $generatedAt ?? now()->format('Y-m-d H:i');

        return self::SHORT.' — Generated '.$when.' (excl. VAT packages unlock clean files)';
    }

    public static function pdfDiagonal(): string
    {
        return "MENetZero Free Trial\nDraft — Not for submission";
    }

    public static function filenameSuffix(): string
    {
        return '-TRIAL-WATERMARK';
    }
}
