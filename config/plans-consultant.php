<?php

/**
 * Consultant portal guidance copy — buying client capacity online, multi-package capacity.
 * See documentation/CONSULTANT_MULTI_PACKAGE_PLAN.md.
 */
return [
    'intro' => [
        'title' => 'Grow managed-client capacity when you are ready',
        'tips' => [
            'Free includes 1 managed client with Free rules (watermarked trial downloads).',
            'Agency packs — buy the slots you need at Carbon or ESG depth and pay online.',
            'After activation you hold separate capacity rows (each with its own expiry). Pick a depth when adding a client.',
            'Prices are shown in AED on Agency packs and charged at checkout.',
        ],
    ],

    'examples' => [
        [
            'pack' => 'Free',
            'you_get' => 'One managed client for Scope 1 & 2 exploration and watermarked trial files.',
        ],
        [
            'pack' => 'Mixed purchase example',
            'you_get' => 'e.g. Carbon ×5 then ESG ×5 → two capacity rows, each with its own expiry.',
        ],
        [
            'pack' => 'ESG × N clients',
            'you_get' => 'Disclosure PDF suites when those packages are activated for those slots.',
        ],
        [
            'pack' => 'Enterprise',
            'you_get' => 'Custom / white-label deployments — contact sales for a quote.',
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
            'body' => 'Free = 1 client, data entry + watermarked trials. Paid = one or more package depths × slot counts, activated once payment clears. Free stays when paid rows are added.',
        ],
        [
            'title' => 'Multiple packages on one agency',
            'body' => 'You can hold Basic, Pro, ESG, and Enterprise capacity at once. Each has its own places and expiry. When you Add client, choose which package the workspace uses.',
        ],
        [
            'title' => 'Preferential ≥10 clients',
            'body' => 'Sales/contract policy only. Buying fewer than 10 is fine — contact sales if you expect to onboard 10+ in 12 months.',
        ],
    ],

    'faq' => [
        [
            'q' => 'How do I add more clients?',
            'a' => 'Open Agency packs, choose the package depth and number of slots, and pay online. Capacity activates once payment clears — then Add client and pick a package with remaining places.',
        ],
        [
            'q' => 'What does each paid client get?',
            'a' => 'Entitlements follow the package of the capacity row you assign when creating the client (Carbon through Enterprise).',
        ],
        [
            'q' => 'Where do I see prices?',
            'a' => 'On Agency packs, in AED, before you check out. Preferential bands for large volumes are agreed with sales.',
        ],
        [
            'q' => 'What about renewals?',
            'a' => 'Within ~45 days of capacity expiry you will see renewal nudges. Renew for the next year from Agency packs. Mid-year top-ups can have different expiry dates per row.',
        ],
        [
            'q' => 'Is directory listing the same as paid capacity?',
            'a' => 'No. Directory profile and leads are separate from managed-client capacity.',
        ],
    ],
];
