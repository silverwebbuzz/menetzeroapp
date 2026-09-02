<?php

/**
 * Human-friendly copy for the company package guidance on the upgrade page.
 *
 * KEEP THIS IN STEP WITH THE CATALOGUE. The live self-serve tiers are
 * client_free · client_carbon · client_esg · client_enterprise
 * (SubscriptionPlanSeeder::ACTIVE_CODES). Everything else is retired: those
 * rows still exist so grandfathered subscribers keep their entitlements, but
 * nobody can buy them, so they must not appear in `examples` or the FAQ.
 *
 * This file previously described a six-tier line-up (Scope Basic, Scope Pro,
 * ESG Starter, ESG Complete) and told users prices were not public. Both were
 * out of date: the upgrade page renders each plan's price_annual, and checkout
 * is self-serve through Razorpay. Copy that contradicts the page it sits on is
 * worse than no copy, so when the catalogue moves, this moves with it.
 */
return [
    'intro' => [
        'title' => 'Choose the package that matches what you need to deliver',
        'body' => 'Every package includes your full Scope 1 and 2 inventory. What changes is Scope 3 depth, '
                . 'which reports you can download without a watermark, and how many sites and teammates you get.',
        'tips' => [
            'Not sure yet? Stay on Free — Scope 1 & 2 for one site, plus one Scope 3 entry per category. Downloads are watermarked trial files.',
            'Need clean MOCCAE, IEQT or GHG reports you can actually submit? That is Carbon.',
            'Need IFRS S1 & S2, GRI, SASB or the UAE ESG Report? That is ESG.',
        ],
    ],

    // Four entries, one per live tier. "You get" lines are deliberately close
    // to plan_taglines below so the two cannot tell a user different stories.
    'examples' => [
        [
            'plan' => 'Free',
            'code' => 'client_free',
            'scenario' => 'A one-location café wants to understand its electricity and LPG use before committing any budget.',
            'you_get' => 'Enter data, view dashboards and preview disclosures — watermarked trial downloads only.',
        ],
        [
            'plan' => 'Carbon',
            'code' => 'client_carbon',
            'scenario' => 'A logistics SME with a few sites has to submit a MOCCAE-aligned inventory this year.',
            'you_get' => 'Full Scope 1–3 inventory with clean MOCCAE, IEQT and GHG reports you can submit.',
        ],
        [
            'plan' => 'ESG',
            'code' => 'client_esg',
            'scenario' => 'A mid-size organisation whose investors and lenders ask for IFRS and GRI disclosures, not just a carbon number.',
            'you_get' => 'Everything in Carbon, plus IFRS S1 & S2, GRI, SASB and the UAE ESG Report.',
        ],
        [
            'plan' => 'Enterprise',
            'code' => 'client_enterprise',
            'scenario' => 'A group consolidating several entities, with external assurance and SSO requirements.',
            'you_get' => 'Multi-entity consolidation, assurance support and SSO — scoped and quoted with our team.',
        ],
    ],

    'clarifications' => [
        [
            'title' => 'Free is really free',
            'body' => 'There is no time limit and no card required. Stay on Free as long as you like to learn the platform. '
                    . 'Paid packages unlock clean exports, deeper Scope 3, more sites and team seats.',
        ],
        [
            'title' => 'Your data is yours',
            'body' => 'Moving between packages never deletes what you have entered. If you return to Free, your data stays — '
                    . 'only the download and capacity limits change.',
        ],
        [
            'title' => 'Scope 3 depth varies',
            'body' => 'Free allows one Scope 3 entry per category, which is enough to see how it works. '
                    . 'Carbon and ESG open up full value-chain capacity.',
        ],
    ],

    'faq' => [
        [
            'q' => 'Which package do I need for MOCCAE reporting?',
            'a' => 'Carbon or above. Carbon produces clean MOCCAE, IEQT, GHG and Excel files you can submit. '
                 . 'Free downloads are watermarked trial files, which are fine for checking your numbers but '
                 . 'not accepted for regulatory submission.',
        ],
        [
            'q' => 'Which package do I need for the UAE ESG Report?',
            'a' => 'ESG or Enterprise. Carbon covers your emissions inventory and its UAE filings; ESG adds the '
                 . 'framework reports on top — IFRS S1 & S2, GRI, SASB and the UAE ESG Report. Enterprise adds '
                 . 'multi-entity consolidation and assurance support.',
        ],
        [
            'q' => 'Can I try before I pay?',
            'a' => 'Yes. Free lets you add a location, enter Scope 1 and 2 data, try one Scope 3 entry per category, '
                 . 'and preview every disclosure form. Upgrade when you need clean exports or more capacity.',
        ],
        [
            'q' => 'What does it cost?',
            'a' => 'Prices are shown on each package above and billed annually in AED. Enterprise is quoted, because '
                 . 'it depends on how many entities and seats you need — contact us and we will scope it with you.',
        ],
        [
            'q' => 'How do I pay, and is my card stored?',
            'a' => 'Checkout is online and takes one annual payment. No card is stored and nothing is charged '
                 . 'automatically — we email you before your package expires so you can choose to renew.',
        ],
        [
            'q' => 'We work with a sustainability consultant — do we still need our own package?',
            'a' => 'If your consultant manages your workspace, they hold the capacity and you do not need your own. '
                 . 'If you use MENetZero directly as a company, choose the package that matches the reports you need.',
        ],
        [
            'q' => 'Can I change package or go back to Free?',
            'a' => 'Yes. Upgrades apply immediately. Downgrades and a return to Free take effect at the end of your '
                 . 'current paid period, so you keep what you paid for. Your data is kept either way.',
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
