<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Location;

/**
 * Resolves UAE emirate and grid utility provider from location / company metadata.
 */
class UaeUtilityContext
{
    /** @var array<string, array{label: string, provider: string, aliases: list<string>}> */
    public const EMIRATES = [
        'dubai' => [
            'label' => 'Dubai',
            'provider' => 'DEWA',
            'aliases' => ['dubai', 'dxb'],
        ],
        'abu_dhabi' => [
            'label' => 'Abu Dhabi',
            'provider' => 'ADDC',
            'aliases' => ['abu dhabi', 'abudhabi', 'abu-dhabi', 'al ain', 'alain'],
        ],
        'sharjah' => [
            'label' => 'Sharjah',
            'provider' => 'SEWA',
            'aliases' => ['sharjah'],
        ],
        'ajman' => [
            'label' => 'Ajman',
            'provider' => 'FEWA',
            'aliases' => ['ajman'],
        ],
        'uaq' => [
            'label' => 'Umm Al Quwain',
            'provider' => 'FEWA',
            'aliases' => ['umm al quwain', 'uaq'],
        ],
        'rak' => [
            'label' => 'Ras Al Khaimah',
            'provider' => 'FEWA',
            'aliases' => ['ras al khaimah', 'rak'],
        ],
        'fujairah' => [
            'label' => 'Fujairah',
            'provider' => 'FEWA',
            'aliases' => ['fujairah'],
        ],
    ];

    /**
     * @return array{key: string, label: string, provider: string}|null
     */
    public static function resolve(Location $location, ?Company $company = null): ?array
    {
        if (!self::isUaeSite($location, $company)) {
            return null;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $location->city,
            $location->address,
            $location->name,
            $company?->emirate,
            $company?->city,
            $company?->state,
            $location->country,
            $company?->country,
        ])));

        $emirateKey = self::resolveEmirateFromText($haystack);
        if ($emirateKey === null && $company?->emirate) {
            $emirateKey = self::normalizeEmirateKey((string) $company->emirate);
        }

        if ($emirateKey && isset(self::EMIRATES[$emirateKey])) {
            return [
                'key' => $emirateKey,
                'label' => self::EMIRATES[$emirateKey]['label'],
                'provider' => self::EMIRATES[$emirateKey]['provider'],
            ];
        }

        return [
            'key' => 'uae',
            'label' => 'UAE',
            'provider' => 'grid utility',
        ];
    }

    public static function isUaeSite(Location $location, ?Company $company = null): bool
    {
        $countryHaystack = strtolower(implode(' ', array_filter([
            $location->country,
            $company?->country,
        ])));

        if (str_contains($countryHaystack, 'uae')
            || str_contains($countryHaystack, 'united arab')
            || str_contains($countryHaystack, 'emirates')) {
            return true;
        }

        $textHaystack = strtolower(implode(' ', array_filter([
            $location->city,
            $location->address,
            $location->name,
            $company?->emirate,
            $company?->city,
        ])));

        return self::resolveEmirateFromText($textHaystack) !== null;
    }

    public static function locationReceivesUtilityBills(Location $location): bool
    {
        return (bool) $location->receives_utility_bills;
    }

    protected static function resolveEmirateFromText(string $haystack): ?string
    {
        foreach (self::EMIRATES as $key => $meta) {
            foreach ($meta['aliases'] as $alias) {
                if (str_contains($haystack, $alias)) {
                    return $key;
                }
            }
        }

        return null;
    }

    protected static function normalizeEmirateKey(string $value): ?string
    {
        $normalized = strtolower(str_replace(['_', '-'], ' ', trim($value)));

        foreach (self::EMIRATES as $key => $meta) {
            if ($normalized === str_replace('_', ' ', $key)
                || $normalized === strtolower($meta['label'])
                || in_array($normalized, $meta['aliases'], true)) {
                return $key;
            }
        }

        return null;
    }
}
