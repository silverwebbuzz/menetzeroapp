<?php

/*
|--------------------------------------------------------------------------
| MENetZero — Company portal information architecture
|--------------------------------------------------------------------------
|
| SINGLE SOURCE OF TRUTH for the company-portal sidebar, shared by BOTH
| themes. The old theme renders it through
| resources/views/layouts/partials/nav-client.blade.php; the new theme
| through resources/views/themes/new/layouts/partials/nav-client.blade.php.
| Both read this file, so a page added here appears in both themes with the
| same grouping, the same permission gate and the same active-state rules.
|
| The consultant portal shares this automatically: layouts/app.blade.php in
| BOTH themes includes nav-client for company workspaces, so a consultant
| "acting as" a client sees exactly this nav. config/navigation-consultant
| does not exist and is not needed — the agency's OWN portal (managed
| clients, leads, orders) is a different product surface and keeps
| nav-consultant.blade.php.
|
|--------------------------------------------------------------------------
| The organising principle
|--------------------------------------------------------------------------
|
| Data is entered ONCE, in the pillar that owns it (Environmental / Social /
| Governance). Frameworks (IFRS S1, IFRS S2, GRI, UAE ESG) do not own data —
| they READ the registers and generate documents, and live under Reports.
|
| This is the pattern every established ESG platform converges on, and it
| matches the commercial model already implemented in App\Support\PlanGate:
| data entry is free ('Disclosure forms' => allowed: true), while exports are
| plan-gated (canDisclosureExport / EXPORT_* / watermarking). Pillars are the
| free surface; Reports is the paid surface.
|
|--------------------------------------------------------------------------
| Structure of an item
|--------------------------------------------------------------------------
|
|   'label'   string  Sidebar text.
|   'route'   string  Route NAME (not URL). Must exist in routes/web.php.
|                     Rendered with route(); a typo would throw, so every
|                     name here is verified against routes/web.php.
|   'gate'    string  Key from the $gates array the nav partial computes
|                     (see the permission block in nav-client.blade.php).
|                     'always' renders unconditionally.
|   'active'  array   Route-name PREFIXES that light this item up. Matched
|                     with str_starts_with, so 'locations.' covers
|                     locations.index, locations.edit, and so on. Order
|                     inside a group matters only for display.
|   'year'    bool    Whether the link carries the fiscal-year context.
|                     Only disclosure-scoped screens need it. Phase B will
|                     move this to session state; until then the nav
|                     partial appends ?fiscal_year= for these items, which
|                     is what stops a click from silently resetting the
|                     user's reporting year.
|   'feeds'   array   Which framework reports consume this register. Drives
|                     the "Feeds:" lineage line on register pages (Phase D)
|                     and the readiness checklists on framework pages
|                     (Phase E). Display/metadata only — never gating.
|
|--------------------------------------------------------------------------
| The two register pairs (Phase C outcome)
|--------------------------------------------------------------------------
|
| Two pairs of tables look like duplicates. Only one pair actually is, and
| NEITHER is merged — they are disambiguated by name instead, because
| merging would lose meaning. Verified against the report services' model
| imports, not assumed.
|
| TARGETS — genuinely different concerns, keep both:
|   - ReductionTarget ....... carbon only: baseline_tco2e, target_tco2e,
|     sbti_aligned, scope_coverage, plus a transition_actions child table.
|     Read by IfrsS2ReportService and GriReportService.
|     Nav: Environmental > "Climate targets".
|   - EsgSustainabilityTarget  any metric: metric_label + unit, categories
|     water/waste/energy/diversity/social/governance/other.
|     Read by UaeEsgReportService.
|     Nav: Social > "ESG targets".
|   Merging these would force tCO2e semantics onto a water target.
|
| RISK REGISTERS — a true structural duplicate, but still kept apart:
|   climate_risks and sustainability_risks are identical column-for-column
|   except the discriminator: risk_type (closed set: physical|transition)
|   vs topic (free text, max 50). IfrsS2ReportService reads the first;
|   IfrsS1ReportService reads the second. Merging would mean one table with
|   two mutually-exclusive discriminator columns and a framework filter on
|   every read — more complexity than it removes, for no user benefit.
|   Nav: Environmental > "Climate risks" / Governance > "Sustainability
|   risks", so the split is obvious at the point of entry.
|
| No data migration is required for any of this: the tables are unchanged
| and every route stays registered. Only labels and placement moved.
|
*/

return [

    /*
    | Query key used to carry the reporting year on 'year' => true links.
    | Disclosure controllers read this in resolveContext(). Centralised here
    | so Phase B can retire it in one place.
    */
    'fiscal_year_key' => 'fiscal_year',

    /*
    | Pillars, in sidebar order.
    |
    | 'title' => null renders the group with no heading (the Overview group).
    | 'gate' on a group hides the whole group when it fails.
    */
    'groups' => [

        'overview' => [
            'title' => null,
            'gate' => 'always',
            'pillar' => null,
            'items' => [
                [
                    'label' => 'Overview',
                    'icon' => 'grid',
                    'route' => 'client.dashboard',
                    'gate' => 'always',
                    'active' => ['client.dashboard'],
                    'year' => false,
                ],
            ],
        ],

        'environmental' => [
            'title' => 'Environmental',
            'gate' => 'company',
            'pillar' => 'e',
            'items' => [
                [
                    'label' => 'Summary',
                    'icon' => 'chart',
                    'route' => 'env.overview',
                    'gate' => 'disclosures',
                    'active' => ['env.overview'],
                    'year' => false,
                ],
                [
                    'label' => 'Measure',
                    'icon' => 'list',
                    'route' => 'quick-input.index',
                    'gate' => 'quick_input',
                    'active' => ['quick-input.index', 'quick-input.show', 'quick-input.entries', 'env.measure'],
                    'year' => false,
                ],
                [
                    'label' => 'Bulk import',
                    'icon' => 'upload',
                    'route' => 'quick-input.bulk-import.index',
                    'gate' => 'quick_input',
                    'active' => ['quick-input.bulk-import', 'quick-input.scope3-bulk-import', 'env.measure.bulk-import'],
                    'year' => false,
                ],
                [
                    'label' => 'Locations & boundaries',
                    'icon' => 'pin',
                    'route' => 'locations.index',
                    'gate' => 'locations',
                    'active' => ['locations.', 'emission-boundaries.', 'env.locations', 'env.boundaries'],
                    'year' => false,
                ],
                [
                    'label' => 'Climate risks',
                    'icon' => 'snowflake',
                    'route' => 'disclosures.s2.climate-risks.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s2.climate-risks', 'env.climate-risks'],
                    'year' => true,
                    'feeds' => ['s2'],
                ],
                [
                    'label' => 'Opportunities',
                    'icon' => 'bolt',
                    'route' => 'disclosures.s2.climate-opportunities.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s2.climate-opportunities', 'env.opportunities'],
                    'year' => true,
                    'feeds' => ['s2'],
                ],
                [
                    // Climate/carbon targets (ReductionTarget: tCO2e baselines,
                    // SBTi alignment, scope coverage, transition actions).
                    // Distinct from Social > ESG targets — see the register note
                    // at the top of this file.
                    'label' => 'Climate targets',
                    'icon' => 'chart',
                    'route' => 'disclosures.s2.targets.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s2.targets', 'env.targets'],
                    'year' => true,
                    'feeds' => ['s2', 'gri'],
                ],
            ],
        ],

        'social' => [
            'title' => 'Social',
            'gate' => 'disclosures',
            'pillar' => 's',
            'items' => [
                [
                    'label' => 'Summary',
                    'icon' => 'chart',
                    'route' => 'social.overview',
                    'gate' => 'disclosures',
                    'active' => ['social.overview'],
                    'year' => false,
                ],
                [
                    'label' => 'Stakeholders',
                    'icon' => 'users',
                    'route' => 'disclosures.stakeholders.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.stakeholders', 'social.stakeholders'],
                    'year' => true,
                    'feeds' => ['uae_esg'],
                ],
                [
                    'label' => 'Supply chain',
                    'icon' => 'truck',
                    'route' => 'disclosures.supply-chain.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.supply-chain', 'social.supply-chain'],
                    'year' => true,
                    'feeds' => ['uae_esg'],
                ],
                [
                    'label' => 'ESG scorecard',
                    'icon' => 'card',
                    'route' => 'disclosures.esg-scorecard.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.esg-scorecard', 'social.scorecard'],
                    'year' => true,
                    'feeds' => ['sasb', 'uae_esg'],
                ],
                [
                    // Non-carbon targets (EsgSustainabilityTarget: water, waste,
                    // energy, diversity, social, governance — any metric+unit).
                    // Read by the UAE ESG report. Distinct from Environmental >
                    // Climate targets, which is carbon-only.
                    'label' => 'ESG targets',
                    'icon' => 'chart',
                    'route' => 'disclosures.esg-targets.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.esg-targets'],
                    'year' => true,
                    'feeds' => ['uae_esg'],
                ],
            ],
        ],

        'governance' => [
            'title' => 'Governance',
            'gate' => 'disclosures',
            'pillar' => 'g',
            'items' => [
                [
                    'label' => 'Summary',
                    'icon' => 'chart',
                    'route' => 'gov.overview',
                    'gate' => 'disclosures',
                    'active' => ['gov.overview'],
                    'year' => false,
                ],
                [
                    'label' => 'Materiality',
                    'icon' => 'grid',
                    'route' => 'disclosures.materiality-matrix.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.materiality-matrix', 'gov.materiality'],
                    'year' => true,
                    'feeds' => ['s1', 'gri', 'uae_esg'],
                ],
                [
                    // Non-climate sustainability risks (SustainabilityRisk,
                    // free-text 'topic'). Environmental > Climate risks is the
                    // climate half (ClimateRisk, risk_type physical|transition).
                    // Same shape, different scope — see the register note above.
                    'label' => 'Sustainability risks',
                    'icon' => 'shield',
                    'route' => 'disclosures.s1.sustainability-risks.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s1.sustainability-risks', 'gov.risks'],
                    'year' => true,
                    'feeds' => ['s1'],
                ],
                [
                    'label' => 'SASB',
                    'icon' => 'pin',
                    'route' => 'disclosures.sasb.index',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.sasb', 'gov.sasb'],
                    'year' => true,
                    'feeds' => ['sasb'],
                ],
                [
                    'label' => 'Policies',
                    'icon' => 'doc',
                    'route' => 'gov.policies',
                    'gate' => 'disclosures',
                    'active' => ['gov.policies'],
                    'year' => true,
                    'feeds' => ['s1', 'gri', 'uae_esg'],
                ],
            ],
        ],

        /*
        | Reports — the ONLY place frameworks appear. Each entry is a
        | framework lens over the registers above, not a data-entry surface.
        | Exports here are what PlanGate charges for.
        */
        'reports' => [
            'title' => 'Reports',
            'gate' => 'company',
            'pillar' => null,
            'items' => [
                [
                    'label' => 'GHG inventory',
                    'icon' => 'doc',
                    'route' => 'reports.index',
                    'gate' => 'reports',
                    'active' => ['reports.index', 'reports.show', 'reports.export'],
                    'year' => false,
                ],
                [
                    'label' => 'Disclosure hub',
                    'icon' => 'grid',
                    'route' => 'disclosures.hub',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.hub', 'reports.hub'],
                    'year' => true,
                ],
                [
                    'label' => 'UAE ESG report',
                    'icon' => 'doc',
                    'route' => 'disclosures.uae-esg.overview',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.uae-esg', 'reports.uae-esg'],
                    'year' => true,
                ],
                [
                    'label' => 'GRI',
                    'icon' => 'list',
                    'route' => 'disclosures.gri.overview',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.gri', 'reports.gri'],
                    'year' => true,
                ],
                [
                    'label' => 'IFRS S2',
                    'icon' => 'snowflake',
                    'route' => 'disclosures.s2.overview',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s2.overview', 'disclosures.s2.sections', 'disclosures.s2.report', 'reports.s2'],
                    'year' => true,
                ],
                [
                    'label' => 'IFRS S1',
                    'icon' => 'shield',
                    'route' => 'disclosures.s1.overview',
                    'gate' => 'disclosures',
                    'active' => ['disclosures.s1.overview', 'disclosures.s1.sections', 'disclosures.s1.material-topics', 'disclosures.s1.report', 'reports.s1'],
                    'year' => true,
                ],
            ],
        ],
    ],

    /*
    | Settings — rendered in the sidebar foot, visually separated.
    */
    'footer' => [
        'title' => 'Settings',
        'items' => [
            [
                'label' => 'Reporting',
                'icon' => 'cog',
                'route' => 'settings.reporting',
                'gate' => 'admin',
                'active' => ['settings.'],
                'year' => false,
            ],
            [
                'label' => 'Team & access',
                'icon' => 'users',
                'route' => 'roles.index',
                'gate' => 'team',
                'active' => ['roles.', 'staff.'],
                'year' => false,
            ],
            [
                'label' => 'Profile',
                'icon' => 'user',
                'route' => 'client.profile',
                'gate' => 'always',
                'active' => ['client.profile', 'profile.'],
                'year' => false,
            ],
            [
                'label' => 'Billing',
                'icon' => 'card',
                'route' => 'subscriptions.billing',
                'gate' => 'billing',
                'active' => ['subscriptions.'],
                'year' => false,
            ],
            [
                'label' => 'Find a consultant',
                'icon' => 'users',
                'route' => 'client.consultants.index',
                'gate' => 'billing',
                'active' => ['client.consultants.'],
                'year' => false,
            ],
            [
                'label' => 'Help & guide',
                'icon' => 'help',
                'route' => 'client.help',
                'gate' => 'always',
                'active' => ['client.help'],
                'year' => false,
            ],
        ],
    ],

    /*
    | Framework metadata for the 'feeds' keys above. Used by the lineage
    | line on register pages (Phase D) and the readiness checklists on
    | framework pages (Phase E).
    */
    'frameworks' => [
        's1' => ['label' => 'IFRS S1', 'route' => 'disclosures.s1.overview'],
        's2' => ['label' => 'IFRS S2', 'route' => 'disclosures.s2.overview'],
        'gri' => ['label' => 'GRI', 'route' => 'disclosures.gri.overview'],
        'uae_esg' => ['label' => 'UAE ESG', 'route' => 'disclosures.uae-esg.overview'],
        'sasb' => ['label' => 'SASB', 'route' => 'disclosures.sasb.index'],
    ],
];
