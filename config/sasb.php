<?php

/**
 * SASB sector metrics (Phase D).
 *
 * Metrics resolve from GHG inventory, GRI disclosures, or manual esg_kpi_snapshots.
 */
return [
    'sectors' => [
        'TR-MT' => [
            'label' => 'Transportation — Marine (Ports & Logistics)',
            'industry' => 'Marine Transportation',
            'metrics' => [
                'TR-MT-110a.1' => [
                    'label' => 'Gross global Scope 1 emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope1',
                ],
                'TR-MT-110a.2' => [
                    'label' => 'Discussion of long-term and short-term strategy to manage Scope 1 emissions',
                    'unit' => 'narrative',
                    'source' => 'gri',
                    'section' => 'supply_chain',
                    'field' => 'supplier_screening_policy',
                ],
                'TR-MT-120a.1' => [
                    'label' => 'Air emissions of NOx, SOx, and particulate matter (PM)',
                    'unit' => 'metric tonnes',
                    'source' => 'manual',
                    'metric_key' => 'sasb_tr_mt_air_emissions',
                ],
                'TR-MT-160a.1' => [
                    'label' => 'Total energy consumed',
                    'unit' => 'GJ',
                    'source' => 'gri',
                    'section' => 'energy',
                    'field' => 'total_energy_gj',
                ],
                'TR-MT-320a.1' => [
                    'label' => 'Lost time injury rate (LTIR)',
                    'unit' => 'rate',
                    'source' => 'gri',
                    'section' => 'health_safety',
                    'field' => 'ltifr',
                ],
                'TR-MT-320a.2' => [
                    'label' => 'Work-related fatalities',
                    'unit' => 'count',
                    'source' => 'gri',
                    'section' => 'health_safety',
                    'field' => 'fatalities_total',
                ],
            ],
        ],
        'TR-RO' => [
            'label' => 'Transportation — Road (Logistics & Fleet)',
            'industry' => 'Road Transportation',
            'metrics' => [
                'TR-RO-110a.1' => [
                    'label' => 'Gross global Scope 1 emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope1',
                ],
                'TR-RO-110a.2' => [
                    'label' => 'Gross global Scope 2 emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope2_location',
                ],
                'TR-RO-110a.3' => [
                    'label' => 'Scope 3 emissions — fuel and energy related',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope3',
                ],
                'TR-RO-320a.1' => [
                    'label' => 'Lost time injury rate (LTIR)',
                    'unit' => 'rate',
                    'source' => 'gri',
                    'section' => 'health_safety',
                    'field' => 'ltifr',
                ],
                'TR-RO-320a.2' => [
                    'label' => 'Work-related fatalities',
                    'unit' => 'count',
                    'source' => 'gri',
                    'section' => 'health_safety',
                    'field' => 'fatalities_total',
                ],
            ],
        ],
        'IF-RE' => [
            'label' => 'Infrastructure — Real Estate (Buildings)',
            'industry' => 'Real Estate',
            'metrics' => [
                'IF-RE-130a.1' => [
                    'label' => 'Energy consumption data coverage as % of floor area',
                    'unit' => '%',
                    'source' => 'manual',
                    'metric_key' => 'sasb_if_re_energy_coverage',
                ],
                'IF-RE-130a.2' => [
                    'label' => 'Total energy consumed by portfolio',
                    'unit' => 'GJ',
                    'source' => 'gri',
                    'section' => 'energy',
                    'field' => 'total_energy_gj',
                ],
                'IF-RE-140a.1' => [
                    'label' => 'Water withdrawal — total',
                    'unit' => 'm³',
                    'source' => 'gri',
                    'section' => 'water',
                    'field' => 'withdrawal_total_m3',
                ],
                'IF-RE-410a.1' => [
                    'label' => 'Scope 1 GHG emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope1',
                ],
                'IF-RE-410a.2' => [
                    'label' => 'Scope 2 GHG emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope2_location',
                ],
            ],
        ],
        'EM-EP' => [
            'label' => 'Extractives — Oil & Gas (Exploration & Production)',
            'industry' => 'Oil & Gas — Exploration & Production',
            'metrics' => [
                'EM-EP-110a.1' => [
                    'label' => 'Gross global Scope 1 emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope1',
                ],
                'EM-EP-110a.2' => [
                    'label' => 'Gross global Scope 2 emissions',
                    'unit' => 'tCO₂e',
                    'source' => 'ghg',
                    'field' => 'scope2_location',
                ],
                'EM-EP-320a.1' => [
                    'label' => 'Lost time injury rate (LTIR)',
                    'unit' => 'rate',
                    'source' => 'gri',
                    'section' => 'health_safety',
                    'field' => 'ltifr',
                ],
            ],
        ],
    ],
];
