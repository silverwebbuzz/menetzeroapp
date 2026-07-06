<?php

return [
    'intro' => [
        'title' => 'How the company portal works',
        'body' => 'MENetZero helps your organisation measure greenhouse gas emissions, build a GHG inventory, and prepare integrated UAE ESG reports, IFRS/GRI disclosures, and MOCCAE-ready exports. Use this guide to understand each area of the portal and the order we recommend for first-time setup.',
        'tips' => [
            'Your Primary Reporting Year (PRY) is the calendar year you report against — most entries are tagged to a location and year.',
            'Permissions depend on your role. Company admins see billing and team settings; other users may only view or enter data.',
            'Growth unlocks the unified UAE ESG Report PDF, ESG Scorecard, and disclosure exports. Enterprise adds extended indexes, HRIS import, assurance PDF upload, and white-label report covers.',
            'If a sustainability consultant manages your account, some billing and team settings may be handled by them.',
        ],
    ],

    'workflow' => [
        [
            'title' => 'Complete company profile',
            'body' => 'Add business name, sector, country, and contact details under My Profile. This feeds reports and disclosures.',
            'route' => 'client.profile',
            'link_label' => 'My Profile',
        ],
        [
            'title' => 'Add locations',
            'body' => 'Create each site you operate — office, warehouse, retail branch, etc. Mark one as head office if applicable.',
            'route' => 'locations.index',
            'link_label' => 'Locations',
        ],
        [
            'title' => 'Define emission boundaries',
            'body' => 'For each location, choose which emission categories apply to your business (electricity, fuel, fleet, etc.).',
            'route' => 'locations.index',
            'link_label' => 'Locations',
        ],
        [
            'title' => 'Enter activity data',
            'body' => 'Use Quick Input forms or bulk import for Scope 1 & 2. Add one row per bill or receipt (e.g. monthly DEWA invoice).',
            'route' => 'quick-input.index',
            'link_label' => 'Input Data',
        ],
        [
            'title' => 'Review dashboard & GHG inventory',
            'body' => 'Check totals on the dashboard, then open GHG Inventory for scope breakdown and export options.',
            'route' => 'reports.index',
            'link_label' => 'GHG Inventory',
        ],
        [
            'title' => 'Complete disclosures & export reports',
            'body' => 'Fill IFRS S1/S2 and GRI sections, add narrative chapters in the UAE ESG Report, then download PDFs and indexes when ready (Growth plan and above).',
            'route' => 'disclosures.hub',
            'link_label' => 'Disclosures',
        ],
        [
            'title' => 'Publish UAE ESG Report (Growth+)',
            'body' => 'Complete leadership message, strategy, and about sections, then export the integrated UAE ESG Report PDF — GHG numbers pull automatically from your inventory.',
            'route' => 'disclosures.uae-esg.overview',
            'link_label' => 'UAE ESG Report',
        ],
    ],

    'sections' => [
        [
            'id' => 'dashboard',
            'title' => 'Dashboard',
            'summary' => 'Your emissions snapshot and setup progress.',
            'highlights' => [
                [
                    'title' => 'Total emissions KPI',
                    'variant' => 'kpi-total',
                    'theme' => 'company',
                    'caption' => 'Your headline tCO₂e number for the active reporting period.',
                ],
                [
                    'title' => 'Scope breakdown',
                    'variant' => 'kpi-scopes',
                    'theme' => 'company',
                    'caption' => 'Scope 1, 2, and 3 each get a card once data is entered.',
                ],
                [
                    'title' => 'Setup reminder',
                    'variant' => 'setup-prompt',
                    'theme' => 'company',
                    'caption' => 'Appears until you finish profile and add your first location.',
                ],
            ],
            'body' => 'The dashboard shows total emissions, scope split, and progress indicators. If onboarding is incomplete, you will see prompts to finish your business profile or add first locations.',
            'steps' => [
                'KPI cards summarise tCO₂e for the active reporting period.',
                'Charts break down emissions by scope where data exists.',
                'Use quick links to jump to Input Data or reports.',
            ],
            'links' => [
                ['route' => 'client.dashboard', 'label' => 'Open dashboard'],
            ],
        ],
        [
            'id' => 'locations',
            'title' => 'Locations & emission boundaries',
            'summary' => 'Define where you operate and what you measure at each site.',
            'highlights' => [
                [
                    'title' => 'Location record',
                    'variant' => 'location-card',
                    'theme' => 'company',
                    'caption' => 'Each site gets a name, address, and optional Head Office tag.',
                ],
                [
                    'title' => 'Search & filter',
                    'variant' => 'location-search',
                    'theme' => 'company',
                    'caption' => 'Find locations quickly when you manage many sites.',
                ],
                [
                    'title' => 'Emission boundaries',
                    'variant' => 'boundary-checklist',
                    'theme' => 'company',
                    'caption' => 'Tick only the activity types that apply — this controls which Quick Input forms appear.',
                ],
            ],
            'body' => 'Locations represent physical sites. Emission boundaries tell MENetZero which activity types apply — so you only see relevant Quick Input forms.',
            'steps' => [
                ['title' => 'Add a location', 'body' => 'Name, address, and type (office, retail, industrial, etc.).'],
                ['title' => 'Set head office', 'body' => 'Optional — used as default on some reports.'],
                ['title' => 'Emission boundaries', 'body' => 'Open a location → Emission boundaries → tick categories that apply (electricity, natural gas, fleet, etc.).'],
            ],
            'tips' => [
                'Create separate locations for sites with different utility accounts or operations.',
                'Boundaries can be updated later if your operations change.',
            ],
            'links' => [
                ['route' => 'locations.index', 'label' => 'Manage locations'],
            ],
        ],
        [
            'id' => 'quick-input',
            'title' => 'Input Data (Quick Input)',
            'summary' => 'Enter Scope 1, 2, and 3 activity data.',
            'highlights' => [
                [
                    'title' => 'Year & location (pick first)',
                    'variant' => 'year-location-form',
                    'theme' => 'company',
                    'caption' => 'Required on every input form — matches the bill or receipt you are entering.',
                ],
                [
                    'title' => 'Action required notice',
                    'variant' => 'action-required',
                    'theme' => 'company',
                    'caption' => 'The form stays locked until both fields are selected.',
                ],
                [
                    'title' => 'Source in the sidebar',
                    'variant' => 'scope-nav',
                    'theme' => 'company',
                    'caption' => 'Choose electricity, fuel, fleet, etc. under Scope 1, 2, or 3.',
                ],
                [
                    'title' => 'One row per bill',
                    'variant' => 'entry-row',
                    'theme' => 'company',
                    'caption' => 'View Entries lists every submission — e.g. one DEWA invoice per row.',
                ],
            ],
            'body' => 'Quick Input is the main data entry area. Choose a source (electricity, fuel, flights, etc.), select location and reporting year, enter quantity and unit — the platform calculates tCO₂e automatically.',
            'steps' => [
                'View Entries — table of all entries with filters by scope, location, and year.',
                'Input forms — cards grouped by Scope 1 (direct), Scope 2 (purchased energy), Scope 3 (value chain).',
                'Bulk import — upload Excel/CSV for Scope 1 & 2 (Starter plan and above).',
                'Export CSV — download filtered entries for audit or external analysis.',
            ],
            'tips' => [
                'One row = one bill or period (e.g. January DEWA invoice).',
                'Read the Scope 1 & 2 Help Guide before your first bulk upload — it explains DEWA bills, fuel receipts, and valid units.',
                'Scope 3 requires Starter or higher; managed clients on agency packs follow the consultant’s plan.',
            ],
            'links' => [
                ['route' => 'quick-input.index', 'label' => 'Input Data'],
                ['route' => 'quick-input.help-guide', 'label' => 'Scope 1 & 2 data guide'],
            ],
        ],
        [
            'id' => 'reports',
            'title' => 'GHG Inventory & exports',
            'summary' => 'Official inventory views and file exports.',
            'highlights' => [
                [
                    'title' => 'Export controls',
                    'variant' => 'report-filters',
                    'theme' => 'company',
                    'caption' => 'Pick the reporting year, then download Excel or PDF.',
                ],
                [
                    'title' => 'Scope totals',
                    'variant' => 'report-scope-table',
                    'theme' => 'company',
                    'caption' => 'Aggregated tCO₂e by scope and category from your Quick Input entries.',
                ],
            ],
            'body' => 'The GHG Inventory report aggregates all entered data by scope, category, and location for your selected reporting year. Export to Excel or PDF for auditors, MOCCAE submissions, or internal review.',
            'steps' => [
                'Select reporting year and filters on the reports page.',
                'Review scope totals and activity breakdown.',
                'Export Excel for working papers or PDF for sharing.',
                'IEQT export available where configured for UAE reporting formats.',
            ],
            'links' => [
                ['route' => 'reports.index', 'label' => 'GHG Inventory'],
            ],
        ],
        [
            'id' => 'disclosures',
            'title' => 'Disclosures & sustainability reports',
            'summary' => 'IFRS S1/S2, GRI, UAE ESG Report, ESG Scorecard, and SASB (Growth+).',
            'highlights' => [
                [
                    'title' => 'Disclosures hub',
                    'variant' => 'disclosure-hub',
                    'theme' => 'company',
                    'caption' => 'Jump into IFRS S1, IFRS S2, GRI, UAE ESG Report, Scorecard, or SASB.',
                ],
                [
                    'title' => 'Section progress',
                    'variant' => 'disclosure-card',
                    'theme' => 'company',
                    'caption' => 'Each framework shows completion % — work through sections in any order.',
                ],
            ],
            'body' => 'Disclosures capture narrative and structured sustainability information beyond raw emissions. On Growth and above you can export PDFs and indexes; Free and Starter can preview forms without downloading.',
            'steps' => [
                'Disclosures hub — overview of completion status across frameworks.',
                'IFRS S2 — climate-related disclosures, risks, targets, and GHG emissions (§29).',
                'IFRS S1 — general sustainability-related financial disclosures.',
                'GRI — material topics, social/safety sections, and GRI content index CSV.',
                'UAE ESG Report — unified PDF matching the UAE standard report index (narrative + auto GHG + indexes).',
                'ESG Scorecard — multi-year KPI tables; import manual KPIs via CSV on Growth.',
                'ESG Depth — stakeholder register, materiality matrix, supply chain suppliers, sustainability targets.',
                'SASB — optional sector index (logistics, real estate, etc.).',
                'ESG Dashboard — visual summary linking metrics and disclosure progress.',
            ],
            'tips' => [
                'Plan access gates PDF/CSV exports — Free and Starter preview only; Growth unlocks integrated UAE ESG Report, Scorecard, GRI, and IFRS downloads.',
                'GHG totals in disclosure PDFs always come from your Quick Input inventory — never re-enter emissions in narrative forms.',
                'Reporting Settings (admin) controls methodology defaults used in reports.',
            ],
            'links' => [
                ['route' => 'disclosures.hub', 'label' => 'Disclosures hub'],
                ['route' => 'disclosures.uae-esg.overview', 'label' => 'UAE ESG Report'],
                ['route' => 'settings.reporting', 'label' => 'Reporting settings'],
            ],
        ],
        [
            'id' => 'enterprise-esg',
            'title' => 'Enterprise ESG add-ons',
            'summary' => 'Extended indexes, HRIS feed, assurance upload, and white-label PDF (Enterprise plan only).',
            'body' => 'Enterprise builds on Growth with deeper KPI packs and integrations. Growth clients keep today’s ~25 KPI scorecard and standard UAE ESG PDF — Enterprise features are additional, not replacements.',
            'steps' => [
                'GRI content index (80+ rows) — download extended CSV from the GRI overview page.',
                'ESG Scorecard enterprise export — 80+ KPI Excel workbook from the Scorecard page.',
                'HRIS KPI import — upload a CSV from your HR system to populate workforce and safety metrics (audit log kept).',
                'Energy from activity — electricity and fuel Quick Input entries auto-estimate GJ on the enterprise scorecard.',
                'Assurance PDF — attach your verifier’s limited/reasonable assurance statement on the UAE ESG Report overview.',
                'White-label UAE ESG PDF — download a branded cover PDF without MenetZero branding on the cover page.',
            ],
            'tips' => [
                'Enterprise-only buttons are hidden on Growth accounts — contact sales to upgrade.',
                'HRIS import rows are tagged separately from manual scorecard edits and will not be overwritten by UI saves.',
                'Arabic bilingual PDF is not yet available — English reports are fully supported.',
            ],
            'links' => [
                ['route' => 'disclosures.esg-scorecard.index', 'label' => 'ESG Scorecard'],
                ['route' => 'disclosures.uae-esg.overview', 'label' => 'UAE ESG Report'],
                ['route' => 'subscriptions.upgrade', 'label' => 'View plans'],
            ],
        ],
        [
            'id' => 'team',
            'title' => 'Team & access',
            'summary' => 'Invite colleagues and control permissions.',
            'highlights' => [
                [
                    'title' => 'Team member row',
                    'variant' => 'team-invite',
                    'theme' => 'company',
                    'caption' => 'Each person gets a role — limit access to measurements, reports, or billing.',
                ],
            ],
            'body' => 'Company admins can invite staff, assign roles, and control who can view or edit locations, measurements, reports, and disclosures.',
            'steps' => [
                'Team & Access — list of members and pending invitations.',
                'Create custom roles with module-level permissions (view / add / edit / delete).',
                'Invite by email — user receives a link to join your company workspace.',
            ],
            'tips' => [
                'Give data entry staff access to measurements only; keep billing restricted to admins.',
            ],
            'links' => [
                ['route' => 'roles.index', 'label' => 'Team & Access'],
            ],
        ],
        [
            'id' => 'billing',
            'title' => 'Plan & billing',
            'summary' => 'Subscription, upgrades, and payment history.',
            'highlights' => [
                [
                    'title' => 'Plan card',
                    'variant' => 'plan-card',
                    'theme' => 'company',
                    'caption' => 'Compare tiers — bulk import, Scope 3, and PDF exports vary by plan.',
                ],
            ],
            'body' => 'View your current plan, upgrade for bulk import / Scope 3 / disclosures, manage payment methods, and download invoices. Not available when your account is fully managed by a consultant agency.',
            'steps' => [
                'Current plan — features included and renewal date.',
                'Upgrade — compare Starter, Professional, and Enterprise tiers.',
                'Billing — payment methods and transaction history.',
            ],
            'links' => [
                ['route' => 'subscriptions.billing', 'label' => 'Plan & billing'],
                ['route' => 'subscriptions.upgrade', 'label' => 'View plans'],
            ],
        ],
        [
            'id' => 'consultants',
            'title' => 'Consultants directory',
            'summary' => 'Find and engage sustainability consultants.',
            'highlights' => [
                [
                    'title' => 'Consultant listing',
                    'variant' => 'consultant-card',
                    'theme' => 'company',
                    'caption' => 'Browse verified practices and request an introduction from your admin.',
                ],
            ],
            'body' => 'Browse verified consultants on the platform, request an introduction, or purchase advisory services. Your company admin manages consultant relationships.',
            'steps' => [
                'Browse the in-app consultant directory.',
                'Request intro or checkout for listed services.',
                'Track orders under Consultants → Orders.',
            ],
            'links' => [
                ['route' => 'client.consultants.index', 'label' => 'Consultants'],
            ],
        ],
    ],

    'faq' => [
        [
            'q' => 'What is the difference between Quick Input and GHG Inventory?',
            'a' => 'Quick Input is where you enter raw activity data (kWh, litres, km, etc.). GHG Inventory is the aggregated report built from those entries.',
        ],
        [
            'q' => 'Which plan do I need for the UAE ESG Report and Scorecard?',
            'a' => 'Growth or Enterprise. Growth includes the integrated UAE ESG Report PDF, ESG Scorecard Excel (~25 KPIs), GRI/IFRS exports, and SASB index. Enterprise adds 80+ KPI/GRI packs, HRIS import, assurance PDF upload, and white-label report covers.',
        ],
        [
            'q' => 'Which plan do I need for bulk import and Scope 3?',
            'a' => 'Bulk import and Scope 3 preview typically require Starter or higher. Check Plan & billing for your current entitlements.',
        ],
        [
            'q' => 'Can I have multiple companies under one login?',
            'a' => 'Yes. If you belong to more than one company, use the company switcher in the top header after sign-in.',
        ],
        [
            'q' => 'Who should I contact for support?',
            'a' => 'Open Help & Guide and click “Email us for support”, or use the contact page at ' . rtrim((string) env('APP_URL', 'https://app.menetzero.com'), '/') . '/contact. Messages go to ' . config('mail.addresses.help.address', 'help@menetzero.com') . '.',
        ],
    ],
];
