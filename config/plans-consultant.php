<?php

/**
 * Consultant portal guidance copy — Request clients (offline), not self-serve packs.
 */
return [
    'intro' => [
        'title' => 'Grow managed-client capacity when you are ready',
        'tips' => [
            'Free includes 1 managed client with Free rules (watermarked trial downloads).',
            'Request clients — choose package depth (Scope Basic … Enterprise) and how many you need.',
            'Pricing is confirmed offline — no public AED list and no self-serve checkout.',
        ],
    ],

    'examples' => [
        [
            'pack' => 'Free',
            'you_get' => 'One managed client for Scope 1 & 2 exploration and watermarked trial files.',
        ],
        [
            'pack' => 'Scope Basic × N clients',
            'you_get' => 'Clean GHG / MOCCAE / Excel / IEQT per paid managed client (inventory-focused).',
        ],
        [
            'pack' => 'Scope Pro / ESG × N clients',
            'you_get' => 'Disclosure PDF suites when those packages are activated for your capacity.',
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
            'body' => 'Free = 1 client, data entry + watermarked trials. Paid = package depth you request, activated after offline payment.',
        ],
        [
            'title' => 'Preferential ≥10 clients',
            'body' => 'Sales/contract policy only. Requesting fewer than 10 is fine — MENetZero may adjust quotes offline when you onboard 10+ in 12 months.',
        ],
    ],

    'faq' => [
        [
            'q' => 'How do I add more clients?',
            'a' => 'Open Request clients, choose a package, enter how many managed clients you need, and submit. MENetZero quotes offline and activates after payment.',
        ],
        [
            'q' => 'What does each paid client get?',
            'a' => 'Entitlements follow the package you request (Scope Basic through Enterprise). Scope Basic is inventory-focused; Scope Pro / ESG packages add disclosure PDF suites.',
        ],
        [
            'q' => 'Where do I see prices?',
            'a' => 'You do not in the app. Preferential bands may be discussed offline — never treated as a public list.',
        ],
        [
            'q' => 'What about renewals?',
            'a' => 'Within ~45 days of capacity expiry you will see renewal nudges. Request clients for the next year offline — there is no self-serve checkout.',
        ],
        [
            'q' => 'Is directory listing the same as paid capacity?',
            'a' => 'No. Directory profile and leads are separate from managed-client capacity.',
        ],
    ],
];
