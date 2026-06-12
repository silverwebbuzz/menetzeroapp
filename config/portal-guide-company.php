<?php

return [
    'intro' => [
        'title' => 'How the company portal works',
        'body' => 'MENetZero helps your organisation measure greenhouse gas emissions, build a GHG inventory, and prepare IFRS, GRI, and MOCCAE-ready exports. Use this guide to understand each area of the portal and the order we recommend for first-time setup.',
        'tips' => [
            'Your Primary Reporting Year (PRY) is the calendar year you report against — most entries are tagged to a location and year.',
            'Permissions depend on your role. Company admins see billing and team settings; other users may only view or enter data.',
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
            'body' => 'Fill IFRS S1/S2, GRI, and ESG sections, then preview or download PDF reports when ready.',
            'route' => 'disclosures.hub',
            'link_label' => 'Disclosures',
        ],
    ],

    'sections' => [
        [
            'id' => 'dashboard',
            'title' => 'Dashboard',
            'summary' => 'Your emissions snapshot and setup progress.',
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
            'summary' => 'IFRS S1, IFRS S2, GRI, and ESG dashboard.',
            'body' => 'Disclosures capture narrative and structured sustainability information beyond raw emissions. Complete sections progressively; many fields pull from your inventory automatically.',
            'steps' => [
                'Disclosures hub — overview of completion status across frameworks.',
                'IFRS S2 — climate-related disclosures, risks, targets, and GHG emissions (§29).',
                'IFRS S1 — general sustainability-related financial disclosures.',
                'GRI — material topics and GRI-aligned narrative sections.',
                'ESG Dashboard — visual summary linking metrics and disclosure progress.',
                'Preview and PDF export when sections are ready.',
            ],
            'tips' => [
                'Plan access may gate advanced disclosure modules — check Plan & billing if a section is locked.',
                'Reporting Settings (admin) controls methodology defaults used in reports.',
            ],
            'links' => [
                ['route' => 'disclosures.hub', 'label' => 'Disclosures hub'],
                ['route' => 'settings.reporting', 'label' => 'Reporting settings'],
            ],
        ],
        [
            'id' => 'team',
            'title' => 'Team & access',
            'summary' => 'Invite colleagues and control permissions.',
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
            'q' => 'Which plan do I need for bulk import and Scope 3?',
            'a' => 'Bulk import and Scope 3 preview typically require Starter or higher. Check Plan & billing for your current entitlements.',
        ],
        [
            'q' => 'Can I have multiple companies under one login?',
            'a' => 'Yes. If you belong to more than one company, use the company switcher in the top header after sign-in.',
        ],
        [
            'q' => 'Who should I contact for support?',
            'a' => 'Use the contact form on menetzero.com or email your account manager. For data methodology questions, your assigned consultant (if any) can also assist.',
        ],
    ],
];
