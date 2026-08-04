<?php

/**
 * Consultant portal guidance copy — Request clients (offline), multi-package capacity.
 * See documentation/CONSULTANT_MULTI_PACKAGE_PLAN.md.
 */
return [
    'intro' => [
        'title' => 'Grow managed-client capacity when you are ready',
        'tips' => [
            'Free includes 1 managed client with Free rules (watermarked trial downloads).',
            'Request clients — enter how many you need per package (mix depths in one request).',
            'After activation you hold separate capacity rows (each with its own expiry). Pick a depth when adding a client.',
            'Pricing is confirmed offline — no public AED list and no self-serve checkout.',
        ],
    ],

    'examples' => [
        [
            'pack' => 'Free',
            'you_get' => 'One managed client for Scope 1 & 2 exploration and watermarked trial files.',
        ],
        [
            'pack' => 'Mixed request example',
            'you_get' => 'e.g. Scope Basic ×5 + ESG Starter ×5 → two capacity rows after offline payment.',
        ],
        [
            'pack' => 'Scope Pro / ESG × N clients',
            'you_get' => 'Disclosure PDF suites when those packages are activated for those slots.',
        ],
        [
            'pack' => 'Enterprise',
            'you_get' => 'Custom / white-label deployments — quoted offline.',
        ],
    ],

    'clarifications' => [
        [
            'title' => 'What is a managed client?',
            'body' => 'One end-company workspace under your agency. Sales docs may say “entity” — same meaning.',
        ],
        [
            'title' => 'Primary Reporting Year (PRY)',
            'body' => 'The main inventory year for that engagement. Paid exports focus on the PRY unless you negotiate more.',
        ],
        [
            'title' => 'Free vs paid',
            'body' => 'Free = 1 client, data entry + watermarked trials. Paid = one or more package depths × slot counts, activated after offline payment. Free stays when paid rows are added.',
        ],
        [
            'title' => 'Multiple packages on one agency',
            'body' => 'You can hold Basic, Pro, ESG, and Enterprise capacity at once. Each has its own places and expiry. When you Add client, choose which package the workspace uses.',
        ],
        [
            'title' => 'Preferential ≥10 clients',
            'body' => 'Sales/contract policy only. Requesting fewer than 10 is fine — MENetZero may adjust quotes offline when you onboard 10+ in 12 months.',
        ],
    ],

    'faq' => [
        [
            'q' => 'How do I add more clients?',
            'a' => 'Open Request clients, enter quantities for one or more packages, and submit. MENetZero quotes offline and activates after payment. Then Add client and pick a package with remaining places.',
        ],
        [
            'q' => 'What does each paid client get?',
            'a' => 'Entitlements follow the package of the capacity row you assign when creating the client (Scope Basic through Enterprise).',
        ],
        [
            'q' => 'Where do I see prices?',
            'a' => 'You do not in the app. Preferential bands may be discussed offline — never treated as a public list.',
        ],
        [
            'q' => 'What about renewals?',
            'a' => 'Within ~45 days of capacity expiry you will see renewal nudges. Request clients for the next year offline — there is no self-serve checkout. Mid-year top-ups can have different expiry dates per row.',
        ],
        [
            'q' => 'Is directory listing the same as paid capacity?',
            'a' => 'No. Directory profile and leads are separate from managed-client capacity.',
        ],
    ],
];
