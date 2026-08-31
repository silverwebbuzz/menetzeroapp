<?php

/**
 * Human-friendly copy for company package guidance (legacy upgrade page + helpers).
 * No public AED. Request a package → offline quote → activate.
 */
return [
    'intro' => [
        'title' => 'Choose the package that matches what you need to deliver',
        'body' => 'All packages include Scope 1 and 2 in MENetZero. The difference is clean exports, Scope 3 depth, disclosure PDFs, and how many sites and teammates you can use.',
        'tips' => [
            'Not sure yet? Stay on Free — Scope 1 & 2 are included, plus one Scope 3 entry per category (watermarked trial downloads).',
            'Need official PDF downloads? Request a package — MENetZero confirms pricing offline and activates after payment.',
            'Preparing an integrated UAE ESG report? Request Scope Pro or an ESG package.',
        ],
    ],

    'examples' => [
        [
            'plan' => 'Free',
            'code' => 'client_free',
            'scenario' => 'A 1-location café wants to understand its electricity and LPG use before committing budget.',
            'you_get' => 'Enter data, view dashboards and disclosure previews — watermarked trial downloads only.',
        ],
        [
            'plan' => 'Scope Basic',
            'code' => 'client_scope_basic',
            'scenario' => 'A logistics SME with a few sites must submit a MOCCAE-aligned inventory this year.',
            'you_get' => 'Up to 3 sites, bulk import, and clean GHG / MOCCAE / Excel / IEQT exports.',
        ],
        [
            'plan' => 'Scope Pro',
            'code' => 'client_scope_pro',
            'scenario' => 'A mid-size org needing broader Scope 3 and ESG disclosure PDF exports.',
            'you_get' => 'Up to 10 sites, broader Scope 3, UAE ESG Report / Scorecard / IFRS / GRI exports.',
        ],
        [
            'plan' => 'ESG Starter',
            'code' => 'client_esg_starter',
            'scenario' => 'First integrated UAE ESG delivery with white-label / assurance options.',
            'you_get' => 'Full ESG report set for mid-size orgs (up to 5 sites) plus assurance / white-label options.',
        ],
        [
            'plan' => 'ESG Complete',
            'code' => 'client_esg_complete',
            'scenario' => 'A group needing larger site count and multi-entity consolidation.',
            'you_get' => 'Full ESG suite at larger scale (up to 10 sites) with consolidation options.',
        ],
        [
            'plan' => 'Enterprise',
            'code' => 'client_enterprise',
            'scenario' => 'Custom implementation, extended KPIs, HRIS import, and white-label deployment.',
            'you_get' => 'Custom sites, seats, and workflows — quoted offline with MENetZero.',
        ],
    ],

    'clarifications' => [
        [
            'title' => 'Offline quotes only',
            'body' => 'There is no public AED grid and no self-serve checkout. Submit Request a package; MENetZero confirms the annual quote and activates after payment.',
        ],
        [
            'title' => 'Free is really free',
            'body' => 'You can stay on Free to learn the platform. Paid packages unlock clean exports, more locations, and team seats.',
        ],
        [
            'title' => 'Scope 3 depth varies',
            'body' => 'Free allows one Scope 3 entry per category. Scope Pro and ESG packages expand value-chain capacity; extras can be requested when quoting.',
        ],
    ],

    'faq' => [
        [
            'q' => 'Which package do I need for the UAE ESG Report?',
            'a' => 'Typically Scope Pro, ESG Starter, ESG Complete, or Enterprise. Free and Scope Basic usually preview disclosures only. Enterprise adds extended KPI / white-label / assurance options.',
        ],
        [
            'q' => 'Which package do I need for MOCCAE reporting?',
            'a' => 'Scope Basic or higher for clean GHG / MOCCAE / Excel / IEQT. Free downloads are watermarked trial files.',
        ],
        [
            'q' => 'Can I try before I pay?',
            'a' => 'Yes. Free lets you add a location, enter Scope 1 and 2 data, and preview disclosure forms. Request a package when you need clean exports.',
        ],
        [
            'q' => 'Where do I see prices?',
            'a' => 'You do not publicly. Request a package shows features only; MENetZero confirms AED amounts offline.',
        ],
        [
            'q' => 'We work with a sustainability consultant — do we still need a company package?',
            'a' => 'If your consultant manages your workspace, they request managed-client capacity. If you use MENetZero directly as a company, request the package that matches your download needs.',
        ],
        [
            'q' => 'Can I cancel or go back to Free?',
            'a' => 'You can schedule a return to Free at renewal. Your data stays; clean export features follow Free limits.',
        ],
    ],

    'plan_taglines' => [
        // Live four-tier catalogue. Carbon and ESG name what the buyer gets:
        // Carbon is the inventory and its UAE filings, ESG adds the framework
        // reports on top.
        'client_free' => 'Scope 1 & 2 for one site — preview only',
        'client_carbon' => 'Full Scope 1–3 inventory with MOCCAE, IEQT and GHG reports',
        'client_esg' => 'Everything in Carbon plus IFRS S1 & S2, GRI, SASB and UAE ESG',
        'client_enterprise' => 'Multi-entity consolidation, assurance support and SSO',
        // Superseded codes — still shown to whoever is grandfathered on them.
        'client_scope_basic' => 'Clean GHG / MOCCAE / Excel / IEQT (retired)',
        'client_scope_pro' => 'Broader scopes + ESG disclosure exports (retired)',
        'client_esg_starter' => 'Full ESG pack for mid-size orgs (retired)',
        'client_esg_complete' => 'Larger portfolios + consolidation (retired)',
        'client_starter' => 'Official GHG inventory PDF (retired)',
        'client_growth' => 'Integrated UAE ESG Report (retired)',
    ],
];
