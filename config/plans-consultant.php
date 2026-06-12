<?php

/**
 * Human-friendly copy for consultant agency packs page.
 */
return [
    'intro' => [
        'title' => 'Agency packs — one subscription, many client workspaces',
        'body' => 'Each slot is one managed client company. When you open their workspace, you use the same emissions tools they would — and on a paid pack each client gets Growth-level exports for their primary reporting year (PRY).',
        'tips' => [
            'Start free with one trial client — enter data and learn the workflow.',
            'Count how many active clients you expect this calendar year, then pick the smallest pack that fits.',
            'You can buy extra slots mid-year without changing pack size.',
        ],
    ],

    'how_it_works' => [
        [
            'title' => 'Add a client',
            'body' => 'Create a managed workspace for each SME you support (e.g. "Al Noor Trading LLC").',
        ],
        [
            'title' => 'Enter their emissions data',
            'body' => 'Switch into the client workspace — locations, Quick Input, Scope 1–3, disclosures — as if you were their in-house team.',
        ],
        [
            'title' => 'Export reports on a paid pack',
            'body' => 'Paid packs unlock Growth-equivalent PDFs per client for their PRY. Trial is data entry only — no exports.',
        ],
        [
            'title' => 'Renew or grow each calendar year',
            'body' => 'Contracts run through 31 December. Renew your pack or add slots when you take on more clients.',
        ],
    ],

    'examples' => [
        [
            'pack' => 'Consultant 5',
            'code' => 'consultant_5',
            'scenario' => 'You personally serve four manufacturing SMEs in Dubai and Sharjah.',
            'you_get' => 'Five client slots — room for one more client without upgrading.',
        ],
        [
            'pack' => 'Consultant 10',
            'code' => 'consultant_10',
            'scenario' => 'A two-person practice onboarding hospitality and retail clients across the UAE.',
            'you_get' => 'Ten slots with a lower per-client price than the 5-pack.',
        ],
        [
            'pack' => 'Consultant 25',
            'code' => 'consultant_25',
            'scenario' => 'A growing advisory firm standardising inventory work for mid-market clients.',
            'you_get' => 'Twenty-five slots — suited when client count is steadily increasing.',
        ],
        [
            'pack' => 'Consultant 50',
            'code' => 'consultant_50',
            'scenario' => 'A regional consultancy running parallel GHG projects for many entities.',
            'you_get' => 'Fifty slots at the best per-client rate in self-serve packs.',
        ],
    ],

    'clarifications' => [
        [
            'title' => 'What is a "slot"?',
            'body' => 'One active managed client at a time. If you finish a project and remove a client, that slot opens for someone new.',
        ],
        [
            'title' => 'What is PRY?',
            'body' => 'Primary Reporting Year — the calendar year you and the client treat as the main inventory period. Exports on paid packs focus on that year unless you unlock another year.',
        ],
        [
            'title' => 'Free trial vs paid pack',
            'body' => 'Trial = 1 client, data entry only (like company Free). Paid pack = exports and Growth features inside each client workspace.',
        ],
        [
            'title' => 'Calendar-year contract',
            'body' => 'Packs are priced through 31 December. Joining mid-year? You pay a pro-rata amount for the rest of the year.',
        ],
    ],

    'pack_hints' => [
        'consultant_5' => 'Best for solo consultants with up to five active clients.',
        'consultant_10' => 'Best for small teams serving roughly 6–10 clients per year.',
        'consultant_25' => 'Best for established practices with a steady client pipeline.',
        'consultant_50' => 'Best for agencies running many parallel engagements.',
    ],

    'faq' => [
        [
            'q' => 'Does each client need their own MeNetZero login?',
            'a' => 'No. You work inside managed workspaces from your consultant account. Optional client logins can be added separately if you want them to co-enter data.',
        ],
        [
            'q' => 'What if I need more than my pack allows?',
            'a' => 'Buy extra slots (AED 1,299 each, pro-rata through year-end) or upgrade to the next pack size when checkout is open.',
        ],
        [
            'q' => 'Can I export MOCCAE / IFRS reports for clients?',
            'a' => 'On a paid pack, each active client gets Growth-level downloads for their PRY — GHG inventory, IFRS S1/S2, and GRI exports inside their workspace.',
        ],
        [
            'q' => 'What happens to client data if I downgrade or don\'t renew?',
            'a' => 'Client workspaces remain, but export and slot limits follow your active pack. Plan renewals before year-end to avoid interrupting active projects.',
        ],
        [
            'q' => 'Is directory listing included?',
            'a' => 'Directory profile and leads are separate from agency packs. Complete your consultant profile to appear in the MeNetZero directory.',
        ],
        [
            'q' => 'Need more than 50 clients?',
            'a' => 'Contact us for Enterprise agency pricing, manual invoicing, and custom terms.',
        ],
        [
            'q' => 'When will online checkout be available?',
            'a' => 'If checkout shows "Coming soon", review packs here and use your free trial client meanwhile. Purchases will complete on this page when payments go live.',
        ],
    ],
];
