<?php

namespace App\Data;

use App\Services\Scope3BulkImportService;

/**
 * Plain-language Scope 3 guide for UAE clients.
 *
 * Category rows are derived from Scope3BulkImportService::referenceCombinations() so the
 * guide, the Excel Reference sheet, and the importer can never drift apart — there is one
 * source of truth for what is valid.
 */
class Scope3HelpGuide
{
    public static function intro(): array
    {
        return [
            'title' => 'Reporting Scope 3 for the first time?',
            'summary' => 'Scope 3 is everything outside your own walls — what you buy, how goods move, how staff travel, and what you throw away. You report ONE TOTAL per category per year, not one row per employee or per flight. That is how audited reports do it too: DP World\'s 2024 report discloses its entire Scope 3 footprint as ten category totals.',
            'start_here' => [
                'Cat 1 — Annual supplier spend in AED (from finance, excluding VAT)',
                'Cat 3 — Reuse the same kWh and fuel litres you already entered for Scope 1 & 2',
                'Cat 5 — Tonnes of waste from your waste contractor\'s annual summary',
                'Cat 6 — Business flights, from your travel agent report',
                'Cat 7 — Staff commuting, from a short staff survey',
            ],
            'tips' => [
                'You do not have to report all 15 categories. Start with the ones you have data for.',
                'Activity data (kWh, km, tonnes) is better quality than spend data (AED) — use it where you can.',
                'Copy activity_type and unit EXACTLY from the Reference sheet. A wrong unit is the most common error.',
                'Have per-employee or per-flight detail? Use the calculator sheets in the workbook — they total it for you.',
            ],
        ];
    }

    /**
     * Column-by-column explanation of the import template.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function columnHelp(): array
    {
        return [
            [
                'column' => 'location_name',
                'required' => true,
                'plain' => 'Which of your sites this data belongs to.',
                'detail' => 'Must match a location name in MENetZero exactly, including spelling and spacing. The template\'s "Your Locations" sheet lists yours — copy from there.',
            ],
            [
                'column' => 'fiscal_year',
                'required' => true,
                'plain' => 'The reporting year, e.g. 2025.',
                'detail' => 'Must be a year your plan allows you to edit. Historical years may be locked depending on your package.',
            ],
            [
                'column' => 'entry_date',
                'required' => false,
                'plain' => 'Optional date for this entry (YYYY-MM-DD).',
                'detail' => 'Use the period end date, e.g. 2025-12-31 for a full-year total. Leave blank if you only have an annual figure.',
            ],
            [
                'column' => 'category',
                'required' => true,
                'plain' => 'Which of the 15 GHG Protocol Scope 3 categories this is.',
                'detail' => 'Flexible: "Cat 6", "6", "business-travel" and "Business Travel" all work.',
            ],
            [
                'column' => 'activity_type',
                'required' => true,
                'plain' => 'What specifically you are reporting within that category.',
                'detail' => 'Copy EXACTLY from the Reference sheet — e.g. "Flight - Long-haul Economy". This decides which emission factor is applied, so a typo means the row is rejected.',
            ],
            [
                'column' => 'quantity',
                'required' => true,
                'plain' => 'The number itself.',
                'detail' => 'Digits only — no commas, no currency symbols, no unit text. Must be zero or greater.',
            ],
            [
                'column' => 'unit',
                'required' => true,
                'plain' => 'The unit that number is measured in.',
                'detail' => 'Copy EXACTLY from the Reference sheet. Each activity type accepts only specific units — see the units section below.',
            ],
            [
                'column' => 'notes',
                'required' => false,
                'plain' => 'Free text for your own reference.',
                'detail' => 'Useful for recording where the number came from, e.g. "from Q4 waste contractor report".',
            ],
        ];
    }

    /**
     * The units Scope 3 accepts, with what they mean.
     *
     * @return array<int, array<string, string>>
     */
    public static function unitHelp(): array
    {
        return [
            ['unit' => 'AED', 'means' => 'Money spent, excluding VAT', 'watch' => 'Spend-based estimates are lower quality than activity data — prefer kWh / km / tonnes where available.'],
            ['unit' => 'kWh', 'means' => 'Electricity or energy', 'watch' => 'Same number as on your utility bill.'],
            ['unit' => 'litres', 'means' => 'Liquid fuel volume', 'watch' => 'For Cat 3, reuse the litres already entered under Scope 1.'],
            ['unit' => 'cubic metres', 'means' => 'Gas or water volume (m³)', 'watch' => 'Gas bills and water bills both use m³.'],
            ['unit' => 'tonnes', 'means' => 'Weight — 1 tonne = 1,000 kg', 'watch' => 'Waste reports sometimes use kg. Divide by 1,000.'],
            ['unit' => 'km', 'means' => 'Distance travelled', 'watch' => 'Used for cars, taxis and motorbikes — where the vehicle, not the passenger, is counted.'],
            ['unit' => 'passenger.km', 'means' => 'Passengers × kilometres', 'watch' => '3 people travelling 100 km = 300 passenger.km. Used for flights, rail and bus.'],
            ['unit' => 'tonne.km', 'means' => 'Tonnes carried × kilometres', 'watch' => '2 tonnes moved 150 km = 300 tonne.km. Used for freight.'],
            ['unit' => 'm2', 'means' => 'Floor area in square metres', 'watch' => 'Only for leased assets where you cannot get actual kWh.'],
            ['unit' => 'FTE working hour', 'means' => 'Full-time-equivalent hours worked', 'watch' => 'Used for homeworking. 10 staff × 1,800 hours = 18,000.'],
        ];
    }

    /**
     * One block per GHG category, built from the importer's reference table.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function categories(): array
    {
        $meta = self::categoryMeta();
        $grouped = [];

        foreach (Scope3BulkImportService::referenceCombinations() as [$catNumber, $slug, $activity, $unit, $where]) {
            $grouped[$catNumber] ??= [
                'number' => $catNumber,
                'slug' => $slug,
                'title' => $meta[$catNumber]['title'] ?? ('Category ' . $catNumber),
                'plain' => $meta[$catNumber]['plain'] ?? '',
                'who_needs' => $meta[$catNumber]['who_needs'] ?? '',
                'activities' => [],
            ];

            $grouped[$catNumber]['activities'][] = [
                'activity_type' => $activity,
                'unit' => $unit,
                'where' => $where,
            ];
        }

        ksort($grouped);

        return array_values($grouped);
    }

    /**
     * Human context per category — what it is and who actually needs it.
     *
     * @return array<int, array<string, string>>
     */
    private static function categoryMeta(): array
    {
        return [
            1 => [
                'title' => 'Cat 1 — Purchased Goods & Services',
                'plain' => 'Everything you buy to run the business: supplies, services, materials, water.',
                'who_needs' => 'Every business. Usually the largest Scope 3 category.',
            ],
            2 => [
                'title' => 'Cat 2 — Capital Goods',
                'plain' => 'Big one-off purchases: equipment, vehicles, machinery, building works.',
                'who_needs' => 'Anyone who bought assets this year. Skip if you bought nothing major.',
            ],
            3 => [
                'title' => 'Cat 3 — Fuel & Energy Related Activities',
                'plain' => 'Emissions from producing and delivering the energy you used — the part not already counted in Scope 1 & 2.',
                'who_needs' => 'Everyone with electricity or fuel use. Reuse your existing Scope 1 & 2 numbers.',
            ],
            4 => [
                'title' => 'Cat 4 — Upstream Transport & Distribution',
                'plain' => 'Goods being shipped TO you, and transport services you pay for.',
                'who_needs' => 'Anyone receiving physical goods — retail, manufacturing, F&B.',
            ],
            5 => [
                'title' => 'Cat 5 — Waste Generated in Operations',
                'plain' => 'Waste your operations produce and how it is treated.',
                'who_needs' => 'Every business. Ask your waste contractor for an annual tonnage summary.',
            ],
            6 => [
                'title' => 'Cat 6 — Business Travel',
                'plain' => 'Staff travelling for work: flights, rail, taxis, hire cars.',
                'who_needs' => 'Most businesses. Use the Calc: Flights sheet if you have a trip list.',
            ],
            7 => [
                'title' => 'Cat 7 — Employee Commuting',
                'plain' => 'Staff getting to and from work, plus home working.',
                'who_needs' => 'Every business with employees. Use the Calc: Commuting sheet.',
            ],
            8 => [
                'title' => 'Cat 8 — Upstream Leased Assets',
                'plain' => 'Space or equipment you lease IN, where the energy is not already in your Scope 2.',
                'who_needs' => 'Only if you lease space whose utilities you do not pay directly.',
            ],
            9 => [
                'title' => 'Cat 9 — Downstream Transport & Distribution',
                'plain' => 'Getting your products TO customers.',
                'who_needs' => 'Anyone shipping goods out — e-commerce, wholesale, manufacturing.',
            ],
            10 => [
                'title' => 'Cat 10 — Processing of Sold Products',
                'plain' => 'Energy others use to further process what you sold them.',
                'who_needs' => 'Only manufacturers selling intermediate products. Most businesses skip this.',
            ],
            11 => [
                'title' => 'Cat 11 — Use of Sold Products',
                'plain' => 'Energy your products consume over their lifetime in customers\' hands.',
                'who_needs' => 'Only if you sell energy-using products. Most service businesses skip this.',
            ],
            12 => [
                'title' => 'Cat 12 — End-of-Life Treatment of Sold Products',
                'plain' => 'What happens to your products and packaging when customers dispose of them.',
                'who_needs' => 'Anyone selling physical products or packaging.',
            ],
            13 => [
                'title' => 'Cat 13 — Downstream Leased Assets',
                'plain' => 'Property or equipment you own and lease OUT to others.',
                'who_needs' => 'Landlords and equipment lessors only.',
            ],
            14 => [
                'title' => 'Cat 14 — Franchises',
                'plain' => 'Emissions from outlets operating under your brand.',
                'who_needs' => 'Franchisors only.',
            ],
            15 => [
                'title' => 'Cat 15 — Investments',
                'plain' => 'Your share of emissions from what you invest in or lend to.',
                'who_needs' => 'Banks, funds and investment companies. Most others skip this.',
            ],
        ];
    }
}
