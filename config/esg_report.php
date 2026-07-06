<?php

/**
 * UAE ESG Report — unified report schema (Phase 0+).
 *
 * Narrative sections use CompanyDisclosure with framework = 'esg_report'.
 * Quantitative GHG data is never stored here — always read from Measurement / GhgReportService.
 *
 * @see documentation/UAE_ESG_REPORT_GAP_ANALYSIS_AND_ROADMAP.md
 */
return [
    'framework_label' => 'UAE ESG Report',

    'frameworks_disclosed' => [
        'GHG Protocol Corporate Standard',
        'GRI Standards',
        'IFRS S1 General Sustainability-related Disclosures',
        'IFRS S2 Climate-related Disclosures',
        'UAE MOCCAE / IEQT (Scope 1 & 2)',
    ],

    /**
     * Narrative chapters matching the UAE Standard client index.
     * Stored via DisclosureService pattern (framework: esg_report).
     */
    'sections' => [
        'about_report' => [
            'title' => 'About This Report',
            'reference' => 'UAE ESG Report — Report metadata',
            'description' => 'Purpose, scope, reporting boundary, frameworks, and assurance status.',
            'fields' => [
                'report_purpose' => ['label' => 'Purpose of this report', 'type' => 'textarea', 'required' => true],
                'reporting_boundary' => ['label' => 'Reporting boundary (operational control / financial control)', 'type' => 'textarea', 'required' => true],
                'frameworks_used' => ['label' => 'Frameworks and standards applied', 'type' => 'textarea', 'required' => true],
                'assurance_status' => ['label' => 'Independent assurance', 'type' => 'select', 'options' => ['None', 'Planned', 'Limited — GHG only', 'Limited — selected metrics', 'Reasonable'], 'required' => true],
                'assurance_scope' => ['label' => 'Assurance scope (if applicable)', 'type' => 'textarea', 'required' => false],
                'report_approval' => ['label' => 'Report approval (e.g. approved by Board / management)', 'type' => 'text', 'required' => false],
                'contact_feedback' => ['label' => 'Feedback contact (e.g. sustainability@company.com)', 'type' => 'text', 'required' => false],
            ],
        ],
        'leadership_message' => [
            'title' => 'Message from Leadership',
            'reference' => 'UAE ESG Report — CEO / leadership statement',
            'description' => 'Signed statement from senior leadership on ESG priorities and performance.',
            'fields' => [
                'author_name' => ['label' => 'Author name and title', 'type' => 'text', 'required' => true],
                'statement' => ['label' => 'Leadership message', 'type' => 'textarea', 'required' => true],
            ],
        ],
        'about_company' => [
            'title' => 'About the Company',
            'reference' => 'UAE ESG Report — Company profile',
            'description' => 'Organisation overview, activities, and operating locations.',
            'fields' => [
                'company_overview' => ['label' => 'Company overview', 'type' => 'textarea', 'required' => true],
                'activities_value_chain' => ['label' => 'Activities, brands, products, and services', 'type' => 'textarea', 'required' => true],
                'operating_locations' => ['label' => 'Where we operate (countries / emirates)', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'esg_strategy' => [
            'title' => 'ESG Strategy',
            'reference' => 'UAE ESG Report — Strategy',
            'description' => 'ESG priorities, themes, and strategic direction.',
            'fields' => [
                'strategy_summary' => ['label' => 'ESG strategy summary', 'type' => 'textarea', 'required' => true],
                'priority_themes' => ['label' => 'Priority ESG themes', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'future_outlook' => [
            'title' => 'Future Outlook',
            'reference' => 'UAE ESG Report — Forward-looking priorities',
            'description' => 'Forward-looking ESG priorities and commitments.',
            'fields' => [
                'outlook' => ['label' => 'Forward-looking priorities', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'awards' => [
            'title' => 'Awards & Recognition',
            'reference' => 'UAE ESG Report — Awards',
            'description' => 'Awards, certifications, and external recognition received.',
            'fields' => [
                'awards_list' => ['label' => 'Awards and recognition (year, award, issuer)', 'type' => 'textarea', 'required' => false],
            ],
        ],
    ],

    'completeness_weights' => [
        'about_report' => 15,
        'leadership_message' => 10,
        'about_company' => 10,
        'esg_strategy' => 10,
        'materiality' => 10,
        'ghg_inventory' => 25,
        'ifrs_s2_climate' => 10,
        'gri_index' => 10,
    ],

    /**
     * Material topic / GRI code → UN SDG goal (Phase A.8).
     * Extend as needed; used for SDG mapping appendix only.
     */
    'sdg_map' => [
        'climate' => ['goals' => [7, 13], 'label' => 'Affordable and clean energy; Climate action'],
        'energy' => ['goals' => [7], 'label' => 'Affordable and clean energy'],
        'water' => ['goals' => [6], 'label' => 'Clean water and sanitation'],
        'waste' => ['goals' => [12], 'label' => 'Responsible consumption and production'],
        'workforce' => ['goals' => [8], 'label' => 'Decent work and economic growth'],
        'health_safety' => ['goals' => [3, 8], 'label' => 'Good health; Decent work'],
        'diversity' => ['goals' => [5], 'label' => 'Gender equality'],
        'community' => ['goals' => [11], 'label' => 'Sustainable cities and communities'],
        'anti_corruption' => ['goals' => [16], 'label' => 'Peace, justice and strong institutions'],
        'supply_chain' => ['goals' => [12, 17], 'label' => 'Responsible production; Partnerships'],
    ],

    /**
     * IFRS S2 paragraph index rows (Phase A.5).
     * report_location is resolved at runtime from section completeness.
     */
    'ifrs_s2_index' => [
        ['paragraph' => 'IFRS S2.6 (a) and (b)', 'topic' => 'Governance', 'section' => 'governance'],
        ['paragraph' => 'IFRS S2.8', 'topic' => 'Strategy — risks and opportunities', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S2.9', 'topic' => 'Strategy — business model', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S2.10', 'topic' => 'Climate-related risks and opportunities', 'section' => 'climate_risks'],
        ['paragraph' => 'IFRS S2.14', 'topic' => 'Strategy and decision-making', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S2.15', 'topic' => 'Financial position and performance', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S2.22', 'topic' => 'Climate resilience', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S2.24', 'topic' => 'Risk management — processes', 'section' => 'risk_management'],
        ['paragraph' => 'IFRS S2.25', 'topic' => 'Risk management — integration', 'section' => 'risk_management'],
        ['paragraph' => 'IFRS S2.29', 'topic' => 'GHG emissions metrics', 'section' => 'ghg'],
        ['paragraph' => 'IFRS S2.33', 'topic' => 'Climate-related targets', 'section' => 'reduction_targets'],
    ],

    /**
     * IFRS S1 paragraph index rows (Phase A.6).
     */
    'ifrs_s1_index' => [
        ['paragraph' => 'IFRS S1.27–29', 'topic' => 'Governance', 'section' => 'governance'],
        ['paragraph' => 'IFRS S1.30–33', 'topic' => 'Strategy', 'section' => 'strategy'],
        ['paragraph' => 'IFRS S1.34–36', 'topic' => 'Risk management', 'section' => 'risk_management'],
        ['paragraph' => 'IFRS S1 (material topics)', 'topic' => 'Material sustainability topics', 'section' => 'material_topics'],
    ],
];
