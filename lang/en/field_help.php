<?php

/**
 * Central field-level help copy (English v1).
 *
 * Loaded via Laravel's translator: __('field_help.{path}').
 * Add lang/ar/field_help.php with the same keys for Arabic (E.2g).
 *
 * Keys are resolved automatically from framework + section + field in forms —
 * you do not need help_key in config/disclosure.php.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Section callouts (optional — shown once above the field list)
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'disclosure' => [
            'gri' => [
                'energy' => 'Enter energy in gigajoules (GJ). Scope 1 & 2 GHG emissions are calculated separately in Quick Input — do not duplicate tCO₂e here.',
                'water' => 'Water volumes in cubic metres (m³) for the reporting year. Use utility or tanker invoices where available.',
                'waste' => 'Waste masses in metric tonnes for the reporting year. Hazardous vs non-hazardous should follow your local regulator definitions.',
                'social_hr' => 'Workforce metrics usually come from HR. Enterprise plans can import HRIS KPIs into the ESG Scorecard.',
                'health_safety' => 'Safety metrics should align with GRI 403 definitions. LTIFR is required for the UAE ESG Report scorecard.',
                'supply_chain' => 'Supplier screening complements Scope 3 Category 1 data entered in Quick Input.',
            ],
            'ifrs_s2' => [
                'governance' => 'Describe how your board oversees climate — IFRS S2 §5–7. Cross-check consistency with IFRS S1 if you disclose both.',
                'strategy' => 'Cover short, medium, and long-term climate risks and your transition plan — IFRS S2 §8–13.',
                'risk_management' => 'Explain how climate risks are identified, assessed, and monitored — IFRS S2 §14–17.',
            ],
            'ifrs_s1' => [
                'governance' => 'Broader sustainability governance — IFRS S1 §27–29. You may cross-reference your IFRS S2 climate governance.',
                'strategy' => 'Sustainability risks and opportunities beyond climate alone — IFRS S1 §30–33.',
                'risk_management' => 'Enterprise risk processes covering material sustainability topics — IFRS S1 §34–36.',
            ],
        ],
        'esg_report' => [
            'about_report' => 'This metadata appears in the UAE ESG Report PDF cover section. Enterprise can attach an assurance PDF separately.',
            'leadership_message' => 'Typically signed by the CEO or Chair. This is client-owned narrative — MenetZero provides the template only.',
            'about_company' => 'Public-facing company profile for investors and regulators reading your integrated report.',
            'esg_strategy' => 'Summarise ESG priorities that align with your material topics and reduction targets.',
            'community_impact' => 'Optional B4SI-style community investment disclosure for the UAE ESG Report.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFRS S2 disclosure fields
    |--------------------------------------------------------------------------
    */
    'disclosure' => [
        'ifrs_s2' => [
            'governance' => [
                'board_oversight_body' => 'Name the board committee or full board with climate oversight (e.g. Audit & Risk Committee).',
                'oversight_frequency' => 'How often climate is a formal board agenda item — pick the usual cadence, not a one-off workshop.',
                'remuneration_linked' => 'If yes, describe which climate KPIs are in executive or board remuneration.',
            ],
            'strategy' => [
                'scenario_analysis_done' => 'IFRS S2 expects disclosure of whether you have run climate scenarios — even "Planned" is valid if not yet done.',
                'scenarios_used' => 'e.g. 1.5°C, 2°C, NZE — name the scenarios and source (IEA, NGFS, internal).',
                'transition_plan_summary' => 'High-level decarbonisation path — link to reduction targets entered elsewhere in the platform.',
                'financial_impact' => 'Qualitative or quantitative effects on assets, liabilities, revenue, or costs from climate risks/opportunities.',
            ],
            'risk_management' => [
                'erm_integration' => 'Explain how climate fits into your existing enterprise risk register and reporting cycle.',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | IFRS S1 disclosure fields
        |----------------------------------------------------------------------
        */
        'ifrs_s1' => [
            'governance' => [
                'climate_cross_reference' => 'Avoid duplicating IFRS S2 text — summarise or write "See IFRS S2 Climate Governance section".',
                'oversight_frequency' => 'Cadence for sustainability (not only climate) at board level.',
            ],
            'strategy' => [
                'climate_cross_reference' => 'Point readers to IFRS S2 for climate-specific strategy detail.',
            ],
            'risk_management' => [
                'material_topics_process' => 'How you determine and review material sustainability topics — align with GRI 3 / your materiality matrix.',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | GRI disclosure fields
        |----------------------------------------------------------------------
        */
        'gri' => [
            'energy' => [
                'total_energy_gj' => 'Total energy consumed inside the organisation in gigajoules (GJ). Enterprise: partial auto-fill from Quick Input fuel and electricity.',
                'renewable_energy_gj' => 'GJ from renewable sources (on-site solar, certified green tariff, RECs).',
                'renewable_percent' => 'Renewable GJ ÷ total energy GJ × 100. Leave blank if you report absolute GJ only.',
                'energy_intensity_value' => 'Intensity numerator — often total GJ or MWh per chosen denominator.',
                'energy_intensity_denominator' => 'State the denominator clearly (e.g. AED revenue, employees, m² floor area).',
                'methodology_notes' => 'Conversion factors, boundaries, and estimation methods for auditors.',
            ],
            'water' => [
                'withdrawal_total_m3' => 'All water withdrawn from any source in m³ — GRI 303-3.',
                'withdrawal_surface_m3' => 'Surface water only — rivers, lakes, rainwater harvested at scale.',
                'withdrawal_groundwater_m3' => 'Groundwater / borehole abstraction in m³.',
                'withdrawal_municipal_m3' => 'Municipal or third-party supply (e.g. DEWA water bill) in m³.',
                'discharge_total_m3' => 'Water returned to environment or sent to treatment — GRI 303-4.',
                'consumption_total_m3' => 'Withdrawal minus discharge, or direct consumption measurement — GRI 303-5.',
                'water_stressed_areas_notes' => 'Break out volumes in water-stressed regions if material (UAE coastal/arid context).',
            ],
            'waste' => [
                'waste_hazardous_tonnes' => 'Hazardous waste per local regulation (chemicals, oils, clinical, etc.) in tonnes.',
                'waste_non_hazardous_tonnes' => 'General office and operational waste in tonnes — required baseline.',
                'waste_total_tonnes' => 'Sum of hazardous + non-hazardous if not reported separately — GRI 306-3.',
                'waste_recycled_tonnes' => 'Mass diverted to recycling — GRI 306-4.',
                'waste_reuse_tonnes' => 'Reuse, composting, or other diversion excluding recycling.',
                'waste_landfill_tonnes' => 'Mass sent to landfill — GRI 306-5.',
                'waste_incineration_tonnes' => 'Mass incinerated with or without energy recovery.',
            ],
            'social_hr' => [
                'employees_total' => 'Headcount at year-end (or average FTE — state which in methodology notes).',
                'employees_new_hires' => 'New hires during the reporting year — GRI 401-1.',
                'employees_turnover_percent' => 'Voluntary + involuntary turnover as % of average headcount.',
                'training_hours_avg' => 'Total training hours ÷ average employees — GRI 404-1.',
                'parental_leave_return_rate' => 'Employees returning after parental leave ÷ those entitled × 100.',
            ],
            'diversity' => [
                'women_management_percent' => 'Women in management roles ÷ total management × 100 — GRI 405-1.',
                'women_workforce_percent' => 'Women in total workforce ÷ total workforce × 100.',
                'board_diversity_percent' => 'Women on the highest governance body ÷ board seats × 100.',
            ],
            'health_safety' => [
                'hours_worked' => 'Total hours worked by employees and contractors — denominator for LTIFR.',
                'recordable_injuries' => 'Recordable injuries per your OHS policy (lost-time and medical treatment cases).',
                'ltifr' => 'Lost-time injuries × 1,000,000 ÷ hours worked — GRI 403-9. Required for UAE ESG scorecard.',
                'fatalities_employees' => 'Work-related fatalities among employees in the reporting year.',
                'fatalities_contractors' => 'Work-related fatalities among contractors on your sites.',
                'fatalities_total' => 'Total fatalities (employees + contractors) — GRI 403-10.',
            ],
            'supply_chain' => [
                'suppliers_screened_environmental_percent' => 'New suppliers screened on environmental criteria ÷ new suppliers × 100 — GRI 308-1.',
                'suppliers_screened_social_percent' => 'New suppliers screened on social criteria ÷ new suppliers × 100 — GRI 414-1.',
                'scope3_cat1_spend_aed' => 'Optional spend proxy for purchased goods & services — aligns with Scope 3 Cat 1 in Quick Input.',
            ],
            'governance_metrics' => [
                'ethics_incidents' => 'Confirmed incidents of corruption or bribery during the reporting period.',
                'ethics_training_percent' => 'Employees completing ethics / anti-corruption training ÷ total employees × 100.',
                'data_breaches' => 'Substantiated privacy or security breaches requiring external notification.',
                'collective_bargaining_percent' => 'Employees covered by collective bargaining agreements ÷ total employees × 100.',
            ],
            'material_topics_process' => [
                'review_frequency' => 'How often the material topics list is formally reviewed by leadership.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UAE ESG Report narrative fields (framework: esg_report)
    |--------------------------------------------------------------------------
    */
    'esg_report' => [
        'about_report' => [
            'reporting_boundary' => 'State operational or financial control boundary — must match GHG Inventory locations in MenetZero.',
            'frameworks_used' => 'List standards applied (e.g. GRI, IFRS S1/S2, GHG Protocol, MOCCAE). Pre-filled list is in the PDF appendix.',
            'assurance_status' => 'Select "None" if not assured. Enterprise can upload the verifier PDF after limited/reasonable assurance.',
            'assurance_scope' => 'What was assured (e.g. Scope 1 & 2 GHG only, selected GRI metrics).',
            'report_approval' => 'Who approved the report for publication (Board resolution date or management sign-off).',
        ],
        'leadership_message' => [
            'author_name' => 'e.g. "Ahmed Al Maktoum, Chief Executive Officer".',
            'statement' => '2–4 paragraphs on ESG performance, priorities, and stakeholder commitments.',
        ],
        'about_company' => [
            'company_overview' => 'Legal name, ownership, sector, and scale (revenue band or employees).',
            'operating_locations' => 'Emirates, countries, or sites material to this report.',
        ],
        'esg_strategy' => [
            'priority_themes' => 'Bullet or comma-separated themes (climate, workforce, ethics, etc.) matching your materiality matrix.',
        ],
        'community_impact' => [
            'total_investment_aed' => 'Cash and in-kind community spend in AED for the reporting year.',
            'beneficiaries_count' => 'Approximate unique beneficiaries — state estimation method if not exact.',
            'investment_methodology' => 'What is included (donations, volunteering valued at rate, pro bono services).',
        ],
    ],
];
