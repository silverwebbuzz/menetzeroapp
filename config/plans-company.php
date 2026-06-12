<?php

/**
 * Human-friendly copy for the company subscription upgrade page.
 * Keep language simple — sustainability managers, finance, and ops teams.
 */
return [
    'intro' => [
        'title' => 'Choose the plan that matches what you need to deliver',
        'body' => 'All plans let you track Scope 1 and 2 emissions in MeNetZero. The difference is what you can download, share with auditors, and how many sites and team members you can use.',
        'tips' => [
            'Not sure yet? Stay on Free — enter a few months of utility bills and explore the dashboard.',
            'Need a PDF for MOCCAE or a landlord? Starter includes your GHG inventory report.',
            'Reporting to investors or preparing IFRS / GRI disclosures? Growth unlocks those downloads.',
        ],
    ],

    'examples' => [
        [
            'plan' => 'Free',
            'code' => 'client_free',
            'scenario' => 'A 1-location café wants to understand its electricity and LPG use before committing budget.',
            'you_get' => 'Enter data, view dashboards and disclosure previews — no paid report downloads.',
        ],
        [
            'plan' => 'Starter',
            'code' => 'client_starter',
            'scenario' => 'A logistics SME with 2–3 warehouses must submit a MOCCAE-aligned inventory this year.',
            'you_get' => 'Up to 3 locations, bulk import, and downloadable GHG inventory PDF.',
        ],
        [
            'plan' => 'Growth',
            'code' => 'client_growth',
            'scenario' => 'A manufacturing group preparing investor-ready climate disclosures for 10 sites.',
            'you_get' => 'IFRS S1/S2 and GRI report downloads, full disclosure workspace, consultant directory access.',
        ],
        [
            'plan' => 'Enterprise',
            'code' => 'client_enterprise',
            'scenario' => 'A holding company with many entities, custom workflows, and training needs.',
            'you_get' => 'Unlimited scale, tailored onboarding — pricing agreed with our team.',
        ],
    ],

    'clarifications' => [
        [
            'title' => 'One payment per year',
            'body' => 'Plans are billed annually as a single payment (not monthly subscriptions). You choose whether to renew each year — we do not store card mandates.',
        ],
        [
            'title' => 'Upgrades vs downgrades',
            'body' => 'Upgrading mid-year credits unused time on your current plan toward a full year on the new plan. Downgrading takes effect at your next renewal — we do not refund unused time on the higher plan.',
        ],
        [
            'title' => 'Free is really free',
            'body' => 'You can stay on Free indefinitely to learn the platform. Paid plans unlock exports, more locations, and team seats.',
        ],
        [
            'title' => 'Scope 3 is separate',
            'body' => 'Value-chain (Scope 3) reporting is an add-on for most organisations. Starter and Growth include limited Scope 3 entry; full Scope 3 programmes are quoted separately.',
        ],
    ],

    'faq' => [
        [
            'q' => 'Which plan do I need for MOCCAE reporting?',
            'a' => 'Starter or above. Starter includes a downloadable GHG inventory PDF aligned for UAE inventory submissions. Growth adds IFRS and GRI exports if you also disclose to investors.',
        ],
        [
            'q' => 'Can I try before I pay?',
            'a' => 'Yes. Free lets you add one location, enter Scope 1 and 2 data, and preview disclosure forms. Upgrade when you need PDF downloads or more locations.',
        ],
        [
            'q' => 'What happens if I upgrade today?',
            'a' => 'You pay the difference (with credit for unused time on your current plan) and receive a full 12-month term on the new plan from the upgrade date.',
        ],
        [
            'q' => 'Can I add more users or locations later?',
            'a' => 'Yes — upgrade to a higher tier when you need more seats or branches. Enterprise is best when limits on Growth are not enough.',
        ],
        [
            'q' => 'Do prices include VAT?',
            'a' => 'Prices shown are in AED or INR as selected. Any applicable taxes are shown at checkout when online payment is enabled.',
        ],
        [
            'q' => 'We work with a sustainability consultant — do we still need Growth?',
            'a' => 'If your consultant manages your data in their agency workspace, they may cover exports under their pack. If you use MeNetZero directly as a company, choose the plan that matches your download needs.',
        ],
        [
            'q' => 'Can I cancel or go back to Free?',
            'a' => 'You can schedule a downgrade to Free at renewal. Your data stays in the account; export features lock according to the Free plan limits.',
        ],
    ],

    'plan_taglines' => [
        'client_free' => 'Explore the platform — enter data, preview reports',
        'client_starter' => 'Official GHG inventory PDF for regulators & stakeholders',
        'client_growth' => 'Investor-ready IFRS & GRI downloads + more capacity',
        'client_enterprise' => 'Multi-entity organisations — custom scope & support',
    ],
];
