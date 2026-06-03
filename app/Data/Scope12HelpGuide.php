<?php

namespace App\Data;

/**
 * Layman-friendly Scope 1 & 2 data guide for UAE clients.
 * Used by the in-app help page and Excel "Data Guide" sheet.
 */
class Scope12HelpGuide
{
    public static function intro(): array
    {
        return [
            'title' => 'First time reporting emissions?',
            'summary' => 'You do not need to be an environmental expert. For most UAE offices, you only need utility bills, fuel receipts, and maybe fleet records. This guide explains — in plain language — what each field means, which unit to use, and exactly where to find the number on your documents.',
            'typical_office' => [
                'DEWA or ADDC electricity bill (total kWh)',
                'District cooling bill if your building uses Empower / Tabreed (not everyone has this)',
                'Diesel receipts if you run a backup generator',
                'Fuel card or mileage log for company vehicles',
                'AC service invoice if gas was refilled that year (optional — many offices skip this)',
            ],
            'tips' => [
                'One row in the spreadsheet = one bill or one month of data.',
                'Use the same location name as shown in MENetZero → Locations.',
                'If you are unsure, start with electricity only — you can add more later.',
                'Delete example rows that do not apply to your business.',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function categories(): array
    {
        return [
            [
                'id' => 'electricity',
                'scope' => 2,
                'title' => 'Purchased electricity',
                'icon' => 'bolt',
                'plain' => 'Power you buy from the grid — lights, AC, computers, everything on your DEWA/ADDC meter.',
                'who_needs' => 'Almost every business in UAE.',
                'category_value' => 'electricity',
                'sub_type' => 'Leave blank',
                'units' => [
                    ['unit' => 'kWh', 'when' => 'Always use this — it is on every electricity bill', 'example' => '50,000'],
                ],
                'where_uae' => [
                    ['source' => 'DEWA bill (Dubai)', 'look_for' => 'Total consumption in kWh — usually on page 1, "Consumption" or "Usage" section', 'field' => 'quantity = kWh number, region = Dubai'],
                    ['source' => 'ADDC bill (Abu Dhabi)', 'look_for' => 'Total kWh for the billing period', 'field' => 'region = Abu Dhabi'],
                    ['source' => 'SEWA / other emirates', 'look_for' => 'Total kWh', 'field' => 'region = UAE'],
                ],
                'example_row' => [
                    'location_name' => 'Your office name',
                    'fiscal_year' => '2025',
                    'entry_date' => '2025-01-31',
                    'category' => 'electricity',
                    'sub_type' => '(blank)',
                    'quantity' => '50000',
                    'unit' => 'kWh',
                    'region' => 'Dubai',
                    'notes' => 'January DEWA bill',
                ],
                'mistakes' => [
                    'Do not enter AED amount — only kWh.',
                    'Do not use monthly cost — use energy units (kWh).',
                    'Set region to Dubai for DEWA (more accurate CO₂ calculation).',
                ],
            ],
            [
                'id' => 'heat-steam-cooling',
                'scope' => 2,
                'title' => 'District cooling ( chilled water )',
                'icon' => 'snowflake',
                'plain' => 'Some Dubai/Abu Dhabi buildings get AC via district cooling (chilled water) instead of individual AC units. You pay Empower, Tabreed, or similar — not DEWA for this part.',
                'who_needs' => 'Only if you receive a separate district cooling bill. If all cooling is on your DEWA bill, skip this.',
                'category_value' => 'heat-steam-cooling',
                'sub_type' => 'Cooling',
                'units' => [
                    ['unit' => 'kWh', 'when' => 'If your cooling bill shows kWh or electrical equivalent', 'example' => '10,000'],
                    ['unit' => 'RT', 'when' => 'If billed in RT (refrigeration tonnes) — common on Tabreed invoices', 'example' => '850'],
                ],
                'where_uae' => [
                    ['source' => 'Empower / Tabreed / ADC Energy invoice', 'look_for' => 'Consumption in kWh or RT for the period', 'field' => 'sub_type = Cooling, unit matches bill'],
                ],
                'example_row' => [
                    'location_name' => 'Your office name',
                    'fiscal_year' => '2025',
                    'category' => 'heat-steam-cooling',
                    'sub_type' => 'Cooling',
                    'quantity' => '10000',
                    'unit' => 'kWh',
                    'region' => 'UAE',
                    'notes' => 'Empower district cooling — Q1',
                ],
                'mistakes' => [
                    'Do not double-count: if cooling is only on DEWA, do not add a cooling row.',
                    'Match unit to what the supplier bill shows (kWh vs RT).',
                ],
            ],
            [
                'id' => 'natural-gas',
                'scope' => 1,
                'title' => 'Natural gas',
                'icon' => 'flame',
                'plain' => 'Gas piped into your site for boilers, kitchen, or industrial heat — not LPG cylinders.',
                'who_needs' => 'Sites with a gas utility connection (common in some industrial units, hotels, kitchens).',
                'category_value' => 'natural-gas',
                'sub_type' => 'Natural gas',
                'units' => [
                    ['unit' => 'cubic metres', 'when' => 'Most gas bills in UAE show m³ or Nm³ — use this', 'example' => '5,000'],
                    ['unit' => 'kWh (Net CV)', 'when' => 'Only if your bill shows kWh instead of m³', 'example' => '52,000'],
                ],
                'where_uae' => [
                    ['source' => 'Gas utility invoice', 'look_for' => 'Volume consumed in m³ or kWh', 'field' => 'sub_type = Natural gas'],
                ],
                'example_row' => [
                    'location_name' => 'Your site name',
                    'fiscal_year' => '2025',
                    'category' => 'natural-gas',
                    'sub_type' => 'Natural gas',
                    'quantity' => '5000',
                    'unit' => 'cubic metres',
                    'region' => 'UAE',
                    'notes' => 'Quarterly gas bill',
                ],
                'mistakes' => [
                    'LPG cylinders are NOT natural gas — use fuel → LPG instead.',
                    'Do not mix m³ and kWh on the same row — pick what the bill shows.',
                ],
            ],
            [
                'id' => 'fuel',
                'scope' => 1,
                'title' => 'Diesel, petrol & LPG ( stationary )',
                'icon' => 'droplet',
                'plain' => 'Fuel burned in generators, forklifts, or equipment on your site — not vehicle travel (that is separate).',
                'who_needs' => 'Anyone with backup generators, diesel forklifts, LPG for catering, or petrol for fixed equipment.',
                'category_value' => 'fuel',
                'sub_type' => 'See table below — copy exact text',
                'units' => [
                    ['unit' => 'litres', 'when' => 'Always for fuel category — from pump meter or invoice', 'example' => '500'],
                ],
                'sub_types' => [
                    ['value' => 'Diesel (100% mineral diesel)', 'use_when' => 'Generator, diesel forklift, backup power'],
                    ['value' => 'Petrol (100% mineral petrol)', 'use_when' => 'Petrol-powered stationary equipment'],
                    ['value' => 'LPG', 'use_when' => 'LPG cylinders for cooking, heating, workshops'],
                    ['value' => 'Propane', 'use_when' => 'Propane gas bottles'],
                ],
                'where_uae' => [
                    ['source' => 'ENOC / ADNOC / Emarat fuel receipt', 'look_for' => 'Litres dispensed', 'field' => 'fuel_category = Liquid fuels or Gaseous fuels'],
                    ['source' => 'Generator log sheet', 'look_for' => 'Litres added per fill-up', 'field' => 'Sum litres for the year or enter monthly rows'],
                    ['source' => 'LPG supplier delivery note', 'look_for' => 'Litres or convert kg to litres if needed', 'field' => 'sub_type = LPG'],
                ],
                'example_row' => [
                    'location_name' => 'Your site name',
                    'fiscal_year' => '2025',
                    'category' => 'fuel',
                    'sub_type' => 'Diesel (100% mineral diesel)',
                    'quantity' => '500',
                    'unit' => 'litres',
                    'fuel_category' => 'Liquid fuels',
                    'region' => 'UAE',
                    'notes' => 'Generator diesel — H1 2025',
                ],
                'mistakes' => [
                    'Company car fuel is usually "vehicle" not "fuel" — unless it is stationary equipment.',
                    'Copy sub_type exactly including brackets — e.g. Diesel (100% mineral diesel).',
                ],
            ],
            [
                'id' => 'vehicle',
                'scope' => 1,
                'title' => 'Company vehicles',
                'icon' => 'truck',
                'plain' => 'Cars, vans, or trucks your company owns or operates — emissions from driving.',
                'who_needs' => 'Any business with company cars, delivery vans, or fleet vehicles.',
                'category_value' => 'vehicle',
                'sub_type' => 'Diesel or Petrol',
                'units' => [
                    ['unit' => 'km', 'when' => 'Best option — total distance driven in the period', 'example' => '1,200'],
                    ['unit' => 'litres', 'when' => 'If you only have fuel-card data, not mileage', 'example' => '95'],
                ],
                'where_uae' => [
                    ['source' => 'Fleet mileage log / GPS report', 'look_for' => 'Total km per vehicle per month', 'field' => 'unit = km'],
                    ['source' => 'Fuel card statement (ENOC, ADNOC)', 'look_for' => 'Litres per vehicle', 'field' => 'unit = litres'],
                    ['source' => 'Salik + odometer', 'look_for' => 'Estimate km from odometer readings', 'field' => 'unit = km'],
                ],
                'example_row' => [
                    'location_name' => 'Your office name',
                    'fiscal_year' => '2025',
                    'category' => 'vehicle',
                    'sub_type' => 'Diesel',
                    'quantity' => '1200',
                    'unit' => 'km',
                    'vehicle_type' => 'Average (up to 3.5 tonnes)',
                    'region' => 'UAE',
                    'notes' => 'Delivery van — January km',
                ],
                'mistakes' => [
                    'Do not enter vehicle purchase price — only fuel use or distance.',
                    'sub_type is simply Diesel or Petrol — not the long diesel fuel name used in "fuel" category.',
                ],
            ],
            [
                'id' => 'refrigerants',
                'scope' => 1,
                'title' => 'AC & refrigeration gas ( top-ups )',
                'icon' => 'wind',
                'plain' => 'When an AC technician adds refrigerant gas during a service — a small leak refill. Not the full capacity of the unit.',
                'who_needs' => 'Only when AC/chiller was serviced and gas was added. Many offices have zero rows some years.',
                'category_value' => 'refrigerants',
                'sub_type' => 'Gas type from service report — see table',
                'units' => [
                    ['unit' => 'kg', 'when' => 'Always — weight of gas added', 'example' => '2.5'],
                ],
                'sub_types' => [
                    ['value' => 'HFC-134a', 'use_when' => 'Older car AC, some chillers'],
                    ['value' => 'R-410A', 'use_when' => 'Very common in UAE split AC units'],
                    ['value' => 'HFC-32', 'use_when' => 'Newer inverter AC units'],
                    ['value' => 'R-407C', 'use_when' => 'Commercial refrigeration'],
                ],
                'where_uae' => [
                    ['source' => 'AC maintenance invoice / service report', 'look_for' => 'Kg of gas charged or "top-up" amount', 'field' => 'Gas type name on report'],
                    ['source' => 'Facility management report', 'look_for' => 'Refrigerant refill records', 'field' => 'quantity in kg'],
                ],
                'example_row' => [
                    'location_name' => 'Your office name',
                    'fiscal_year' => '2025',
                    'category' => 'refrigerants',
                    'sub_type' => 'R-410A',
                    'quantity' => '1.8',
                    'unit' => 'kg',
                    'region' => 'UAE',
                    'notes' => 'Split AC service — gas top-up only',
                ],
                'mistakes' => [
                    'Do NOT enter the AC unit\'s total gas capacity (e.g. "unit holds 3 kg") — only what was added.',
                    'If no AC service with gas refill this year, skip this category entirely.',
                ],
            ],
            [
                'id' => 'process',
                'scope' => 1,
                'title' => 'Industrial process emissions',
                'icon' => 'cog',
                'plain' => 'Emissions from manufacturing processes — cement, steel, chemicals. Not relevant for typical offices or shops.',
                'who_needs' => 'Factories and industrial plants only. Skip if you are an office, retail, or service business.',
                'category_value' => 'process',
                'sub_type' => 'Product type — see table',
                'units' => [
                    ['unit' => 'tonnes', 'when' => 'Tonnes of product manufactured in the period', 'example' => '150'],
                ],
                'sub_types' => [
                    ['value' => 'Cement Production', 'use_when' => 'Cement plants'],
                    ['value' => 'Steel and Iron Production', 'use_when' => 'Steel mills'],
                    ['value' => 'Aluminium Production', 'use_when' => 'Aluminium smelters'],
                    ['value' => 'Ammonia Production', 'use_when' => 'Chemical / fertilizer plants'],
                ],
                'where_uae' => [
                    ['source' => 'Production records', 'look_for' => 'Tonnes output for reporting period', 'field' => 'Match sub_type to product'],
                ],
                'example_row' => [
                    'location_name' => 'Factory site',
                    'fiscal_year' => '2025',
                    'category' => 'process',
                    'sub_type' => 'Cement Production',
                    'quantity' => '150',
                    'unit' => 'tonnes',
                    'region' => 'UAE',
                    'notes' => 'Monthly cement output',
                ],
                'mistakes' => [
                    'Offices and retail should not use this category.',
                ],
            ],
        ];
    }

    /** Flat rows for Excel "Data Guide" sheet */
    public static function excelGuideRows(): array
    {
        $rows = [
            ['Category', 'Scope', 'What is it? (plain English)', 'sub_type', 'Unit', 'Where to find it (UAE)', 'Example quantity'],
        ];

        foreach (self::categories() as $cat) {
            $units = $cat['units'] ?? [];
            $firstUnit = $units[0] ?? ['unit' => '', 'example' => ''];
            $where = isset($cat['where_uae'][0]) ? $cat['where_uae'][0]['source'] . ' — ' . $cat['where_uae'][0]['look_for'] : '';

            $rows[] = [
                $cat['category_value'],
                'Scope ' . $cat['scope'],
                $cat['plain'],
                is_string($cat['sub_type']) ? $cat['sub_type'] : '',
                $firstUnit['unit'],
                $where,
                $firstUnit['example'] ?? '',
            ];

            for ($i = 1; $i < count($units); $i++) {
                $rows[] = ['', '', '', '', $units[$i]['unit'], $units[$i]['when'], $units[$i]['example']];
            }
        }

        return $rows;
    }

    public static function columnHelp(): array
    {
        return [
            ['column' => 'location_name', 'required' => true, 'explain' => 'Your site or office name — must match exactly what you set up in MENetZero → Locations.'],
            ['column' => 'fiscal_year', 'required' => true, 'explain' => 'The reporting year you are entering data for, e.g. 2025.'],
            ['column' => 'entry_date', 'required' => false, 'explain' => 'Date of the bill or end of period (YYYY-MM-DD). Optional — defaults to today if blank.'],
            ['column' => 'category', 'required' => true, 'explain' => 'Type of emission: electricity, fuel, vehicle, natural-gas, refrigerants, process, or heat-steam-cooling.'],
            ['column' => 'sub_type', 'required' => 'Depends', 'explain' => 'More detail — fuel name, gas type, Diesel/Petrol for vehicles. Blank only for electricity. See help guide per category.'],
            ['column' => 'quantity', 'required' => true, 'explain' => 'The number from your bill — kWh, litres, km, kg, etc. Numbers only, no commas.'],
            ['column' => 'unit', 'required' => true, 'explain' => 'Must match quantity — e.g. kWh with electricity, litres with diesel. Wrong unit = wrong calculation.'],
            ['column' => 'region', 'required' => false, 'explain' => 'Dubai (DEWA), Abu Dhabi (ADDC), or UAE. Important for electricity accuracy.'],
            ['column' => 'fuel_category', 'required' => false, 'explain' => 'Only for fuel rows: Liquid fuels or Gaseous fuels.'],
            ['column' => 'vehicle_type', 'required' => false, 'explain' => 'Only for vehicles — e.g. Average (up to 3.5 tonnes). Optional; system uses average if blank.'],
            ['column' => 'notes', 'required' => false, 'explain' => 'Your reminder — e.g. "January DEWA bill". Helps you audit later.'],
        ];
    }
}
