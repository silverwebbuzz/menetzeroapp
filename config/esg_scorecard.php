<?php

/**
 * ESG Scorecard metric definitions (Phase B).
 *
 * source:
 *   ghg   — live from Measurement / IfrsS2ReportService (never stored as manual)
 *   gri   — live from GRI disclosure sections
 *   manual — stored in esg_kpi_snapshots
 */
return [
    'categories' => [
        'environment' => [
            'title' => 'Environmental Performance',
            'metrics' => [
                'scope_1_tco2e' => [
                    'label' => 'Scope 1 GHG emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope1',
                    'decimals' => 4,
                ],
                'scope_2_tco2e' => [
                    'label' => 'Scope 2 GHG emissions (location-based)',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope2_location',
                    'decimals' => 4,
                ],
                'scope_3_tco2e' => [
                    'label' => 'Scope 3 GHG emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope3',
                    'decimals' => 2,
                ],
                'total_ghg_tco2e' => [
                    'label' => 'Total GHG emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'total',
                    'decimals' => 2,
                ],
                'energy_gj' => [
                    'label' => 'Total energy consumption (GRI 302-1)',
                    'unit' => 'GJ',
                    'source' => 'gri',
                    'section' => 'energy',
                    'field' => 'total_energy_gj',
                    'decimals' => 2,
                ],
                'renewable_energy_percent' => [
                    'label' => 'Renewable energy share',
                    'unit' => '%',
                    'source' => 'gri',
                    'section' => 'energy',
                    'field' => 'renewable_percent',
                    'decimals' => 1,
                ],
                'water_withdrawal_m3' => [
                    'label' => 'Water withdrawal (GRI 303-3)',
                    'unit' => 'm³',
                    'source' => 'gri',
                    'section' => 'water',
                    'field' => 'withdrawal_total_m3',
                    'decimals' => 2,
                ],
                'water_consumption_m3' => [
                    'label' => 'Water consumption (GRI 303-5)',
                    'unit' => 'm³',
                    'source' => 'gri',
                    'section' => 'water',
                    'field' => 'consumption_total_m3',
                    'decimals' => 2,
                ],
                'waste_total_tonnes' => [
                    'label' => 'Total waste generated (GRI 306-3)',
                    'unit' => 'tonnes',
                    'source' => 'gri',
                    'section' => 'waste',
                    'field' => 'waste_total_tonnes',
                    'decimals' => 2,
                ],
                'waste_recycled_tonnes' => [
                    'label' => 'Waste diverted — recycling (GRI 306-4)',
                    'unit' => 'tonnes',
                    'source' => 'gri',
                    'section' => 'waste',
                    'field' => 'waste_recycled_tonnes',
                    'decimals' => 2,
                ],
                'environmental_incidents' => [
                    'label' => 'Environmental incidents / non-compliance',
                    'unit' => 'count',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
            ],
        ],
        'social' => [
            'title' => 'Social Performance',
            'metrics' => [
                'employees_total' => [
                    'label' => 'Total employees (headcount)',
                    'unit' => 'employees',
                    'source' => 'gri',
                    'section' => 'social_hr',
                    'field' => 'employees_total',
                    'decimals' => 0,
                ],
                'employees_new_hires' => [
                    'label' => 'New employee hires',
                    'unit' => 'employees',
                    'source' => 'gri',
                    'section' => 'social_hr',
                    'field' => 'employees_new_hires',
                    'decimals' => 0,
                ],
                'turnover_percent' => [
                    'label' => 'Employee turnover',
                    'unit' => '%',
                    'source' => 'gri',
                    'section' => 'social_hr',
                    'field' => 'employees_turnover_percent',
                    'decimals' => 1,
                ],
                'training_hours_avg' => [
                    'label' => 'Average training hours per employee (GRI 404-1)',
                    'unit' => 'hours',
                    'source' => 'gri',
                    'section' => 'social_hr',
                    'field' => 'training_hours_avg',
                    'decimals' => 1,
                ],
                'women_management_percent' => [
                    'label' => 'Women in management (GRI 405-1)',
                    'unit' => '%',
                    'source' => 'gri',
                    'section' => 'diversity',
                    'field' => 'women_management_percent',
                    'decimals' => 1,
                ],
                'women_workforce_percent' => [
                    'label' => 'Women in total workforce',
                    'unit' => '%',
                    'source' => 'gri',
                    'section' => 'diversity',
                    'field' => 'women_workforce_percent',
                    'decimals' => 1,
                ],
                'ltifr' => [
                    'label' => 'Lost-time injury frequency rate (LTIFR)',
                    'unit' => 'per million hours',
                    'source' => 'manual',
                    'decimals' => 2,
                ],
                'fatalities' => [
                    'label' => 'Work-related fatalities',
                    'unit' => 'count',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
                'community_investment_aed' => [
                    'label' => 'Community investment',
                    'unit' => 'AED',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
            ],
        ],
        'governance' => [
            'title' => 'Governance Performance',
            'metrics' => [
                'ethics_incidents' => [
                    'label' => 'Confirmed incidents of corruption / ethics breaches',
                    'unit' => 'count',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
                'data_breaches' => [
                    'label' => 'Data privacy / security breaches',
                    'unit' => 'count',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
                'board_women_percent' => [
                    'label' => 'Women on board / governance body',
                    'unit' => '%',
                    'source' => 'manual',
                    'decimals' => 1,
                ],
                'collective_bargaining_percent' => [
                    'label' => 'Employees covered by collective bargaining',
                    'unit' => '%',
                    'source' => 'manual',
                    'decimals' => 1,
                ],
                'supplier_audits' => [
                    'label' => 'Supplier sustainability audits conducted',
                    'unit' => 'count',
                    'source' => 'manual',
                    'decimals' => 0,
                ],
            ],
        ],
    ],
];
