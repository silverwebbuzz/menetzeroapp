<?php

namespace App\Data;

class ConsultantOptions
{
    public const EMIRATES = [
        'abu_dhabi' => 'Abu Dhabi',
        'dubai' => 'Dubai',
        'sharjah' => 'Sharjah',
        'ajman' => 'Ajman',
        'uaq' => 'Umm Al Quwain',
        'rak' => 'Ras Al Khaimah',
        'fujairah' => 'Fujairah',
    ];

    public const LANGUAGES = [
        'en' => 'English',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
        'fr' => 'French',
    ];

    public const SPECIALTIES = [
        'scope_1_2' => 'Scope 1 & 2 inventory',
        'scope_3' => 'Scope 3 screening',
        'moccae' => 'MOCCAE / UAE reporting',
        'ghg_protocol' => 'GHG Protocol',
        'ifrs_s2' => 'IFRS S2 climate disclosures',
        'ifrs_s1' => 'IFRS S1 sustainability',
        'gri' => 'GRI standards',
        'ieqt' => 'IEQT / mrv.ae',
        'verification' => 'Third-party verification support',
    ];

    public const DOCUMENT_TYPES = [
        'trade_license' => 'UAE trade license',
        'cv' => 'CV / professional profile',
        'certification' => 'Professional certification',
        'insurance' => 'Professional indemnity insurance',
        'other' => 'Other supporting document',
    ];

    public const REQUIRED_DOCUMENT_TYPES = ['trade_license', 'cv'];

    public const PACK_TYPES = [
        'starter_consultant' => 'Starter + Consultant (~2h review)',
        'growth_consultant' => 'Growth + Consultant (~4h review)',
        'custom' => 'Custom engagement',
    ];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'pending_review' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
    ];

    public static function labelFor(string $map, string $key): string
    {
        return match ($map) {
            'emirate' => self::EMIRATES[$key] ?? $key,
            'language' => self::LANGUAGES[$key] ?? $key,
            'specialty' => self::SPECIALTIES[$key] ?? $key,
            'document' => self::DOCUMENT_TYPES[$key] ?? $key,
            'pack' => self::PACK_TYPES[$key] ?? $key,
            'status' => self::STATUS_LABELS[$key] ?? $key,
            default => $key,
        };
    }
}
