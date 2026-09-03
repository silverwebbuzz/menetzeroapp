<?php

return [
    'intro' => [
        'title' => 'How the consultant agency portal works',
        'body' => 'The consultant portal is your agency hub: manage client workspaces, request managed-client capacity, maintain your public directory profile, and respond to leads. When you enter a client workspace, you use the same emissions tools as a company — this guide covers both sides.',
        'tips' => [
            'Free includes one managed client with Free rules (Scope 1 & 2 full, Scope 3 one entry per category, watermarked GHG / Excel / IEQT trial downloads).',
            'Each client has a Primary Reporting Year (PRY) set when you create the engagement.',
            'Agency packs — buy the managed-client slots you need at each package depth (you can mix depths, e.g. Carbon ×5 and ESG ×5).',
            'AED prices are shown on Agency packs and charged at checkout; capacity activates once payment clears.',
            'After activation you may hold several capacity rows at once — each with its own package depth, slot count, and expiry. Pick a depth when adding a client.',
        ],
    ],

    'workflow' => [
        [
            'title' => 'Complete your consultant profile',
            'body' => 'Add practice name, bio, services, and credentials. Submit for review to appear in the public directory.',
            'route' => 'consultant.profile.edit',
            'link_label' => 'Edit profile',
        ],
        [
            'title' => 'Upload verification documents',
            'body' => 'Add trade licence, certifications, or other documents required for directory approval.',
            'route' => 'consultant.documents.index',
            'link_label' => 'Documents',
        ],
        [
            'title' => 'Add your first managed client',
            'body' => 'Create a client company record, set PRY, and use your Free capacity (one client) or paid capacity after activation.',
            'route' => 'consultant.clients.create',
            'link_label' => 'Add client',
        ],
        [
            'title' => 'Enter the client workspace',
            'body' => 'Switch into the client’s portal to add locations, enter emissions, and run reports on their behalf.',
            'route' => 'consultant.workspace.switcher',
            'link_label' => 'Workspaces',
        ],
        [
            'title' => 'Set up locations & input data',
            'body' => 'Inside the workspace: add sites, set boundaries, enter Quick Input data or bulk import.',
            'route' => 'quick-input.index',
            'link_label' => 'Input Data (in workspace)',
        ],
        [
            'title' => 'Buy client capacity when ready',
            'body' => 'Need clean exports or more managed clients? Open Agency packs, pick the package depth and slot count, and pay online — capacity activates once payment clears, as one row per package.',
            'route' => 'consultant.packs.index',
            'link_label' => 'Agency packs',
        ],
    ],

    'sections' => [
        [
            'id' => 'dashboard',
            'title' => 'Agency dashboard',
            'summary' => 'Portfolio overview across all managed clients.',
            'highlights' => [
                [
                    'title' => 'Portfolio emissions',
                    'variant' => 'kpi-total',
                    'theme' => 'consultant',
                    'caption' => 'Combined tCO₂e across all clients that have entered data.',
                ],
                [
                    'title' => 'Client capacity',
                    'variant' => 'slot-usage',
                    'theme' => 'consultant',
                    'caption' => 'How many managed clients you have vs your Free or paid capacity.',
                ],
            ],
            'body' => 'The consultant dashboard shows aggregate emissions, active clients, capacity used, directory status, and new leads. Use it as your daily starting point.',
            'steps' => [
                'Portfolio emissions — combined tCO₂e across clients with data.',
                'Managed clients — used vs available on your Free or paid capacity.',
                'Directory status — draft, pending review, approved, or rejected.',
                'Quick actions — add client, open workspaces, request clients.',
            ],
            'links' => [
                ['route' => 'consultant.dashboard', 'label' => 'Dashboard'],
            ],
        ],
        [
            'id' => 'clients',
            'title' => 'Managed clients',
            'summary' => 'Create and maintain client company records.',
            'highlights' => [
                [
                    'title' => 'Client row',
                    'variant' => 'client-row',
                    'theme' => 'consultant',
                    'caption' => 'Shows client name, PRY, and the Enter workspace action.',
                ],
            ],
            'body' => 'Each managed client is a separate company workspace in MENetZero. You define the client name, sector, PRY, and contact details. One place of capacity is used per active client.',
            'steps' => [
                'Add client — creates the company and links it to your agency.',
                'Edit client — update PRY, display name, or engagement settings.',
                'Archive or remove — frees capacity when an engagement ends.',
            ],
            'tips' => [
                'Set PRY correctly at creation — it drives default year filters in Quick Input and reports.',
                'Say “managed client” in the app — sales docs may still say “entity”; both mean one client company.',
            ],
            'links' => [
                ['route' => 'consultant.clients.index', 'label' => 'Managed clients'],
                ['route' => 'consultant.clients.create', 'label' => 'Add client'],
            ],
        ],
        [
            'id' => 'workspaces',
            'title' => 'Client workspaces',
            'summary' => 'Switch into a client’s portal to do the work.',
            'highlights' => [
                [
                    'title' => 'Agency mode header',
                    'variant' => 'agency-header',
                    'theme' => 'consultant',
                    'caption' => 'Always shows which client you are working on and their PRY.',
                ],
                [
                    'title' => 'Enter workspace',
                    'variant' => 'client-row',
                    'theme' => 'consultant',
                    'caption' => 'Opens the full company portal for that client.',
                ],
            ],
            'body' => 'Entering a workspace opens the company portal as that client. The header shows you are acting as agency with options to switch clients or return to the agency hub.',
            'steps' => [
                'Workspaces page — list all engagements with Enter / View actions.',
                'Enter workspace — full edit access (subject to your role).',
                'Read-only mode — view client data without making changes (where offered).',
                'Exit workspace — return to consultant dashboard via “Back to Agency Hub” in the header.',
            ],
            'tips' => [
                'While inside a workspace, left navigation matches the company portal: Locations, Input Data, Reports, Disclosures.',
                'Billing for the client is usually locked — capacity and upgrades go through Agency packs in the agency hub.',
            ],
            'links' => [
                ['route' => 'consultant.workspace.switcher', 'label' => 'Workspaces'],
            ],
        ],
        [
            'id' => 'packs',
            'title' => 'Agency packs & capacity',
            'summary' => 'Buy package depth × managed-client capacity online.',
            'highlights' => [
                [
                    'title' => 'Pack card',
                    'variant' => 'pack-card',
                    'theme' => 'consultant',
                    'caption' => 'Pick a package depth and slot count. AED prices shown before checkout.',
                ],
            ],
            'body' => 'After Free (one managed client), open Agency packs and buy the capacity you need at each depth (Carbon or ESG). Each purchase creates its own subscription row with its own slot count and expiry, so you can mix depths by buying more than once. Enterprise is quoted by sales.',
            'steps' => [
                'Agency packs — choose a package depth and how many slots you need.',
                'Checkout — pay online in AED; capacity activates when payment clears (Free stays).',
                'Add client — choose which package depth has remaining places for that workspace.',
                'Orders / renewal — history and renewal prompts when relevant.',
            ],
            'tips' => [
                'Carbon ≈ clean GHG / MOCCAE / Excel / IEQT plus full Scope 3. ESG adds the disclosure PDF suites.',
                'Enterprise is fully custom (branding, implementation, deeper ESG) — contact sales.',
                'Capacity is per package row — Carbon slots cannot be used for an ESG client.',
            ],
            'links' => [
                ['route' => 'consultant.packs.index', 'label' => 'Agency packs'],
                ['route' => 'consultant.orders.index', 'label' => 'Orders'],
            ],
        ],
        [
            'id' => 'directory',
            'title' => 'Directory profile & leads',
            'summary' => 'Public listing and inbound enquiries.',
            'highlights' => [
                [
                    'title' => 'Directory profile fields',
                    'variant' => 'directory-profile',
                    'theme' => 'consultant',
                    'caption' => 'Headline and services appear on your public listing once approved.',
                ],
                [
                    'title' => 'Inbound lead',
                    'variant' => 'lead-row',
                    'theme' => 'consultant',
                    'caption' => 'Companies request intros — respond from the Leads page.',
                ],
            ],
            'body' => 'Once approved, your practice appears on the public consultant directory. SMEs can request introductions; you manage leads in the portal.',
            'steps' => [
                'Profile — headline, services, regions, and logo.',
                'Documents — upload proof for verification team.',
                'Submit for review — moves status to pending until approved.',
                'Leads — respond to intro requests from companies.',
            ],
            'links' => [
                ['route' => 'consultant.profile.edit', 'label' => 'Profile'],
                ['route' => 'consultant.intro-requests.index', 'label' => 'Leads'],
            ],
        ],
        [
            'id' => 'client-tools',
            'title' => 'Working inside a client workspace',
            'summary' => 'Same tools as the company portal — on behalf of your client.',
            'highlights' => [
                [
                    'title' => 'Agency mode header',
                    'variant' => 'agency-header',
                    'theme' => 'consultant',
                    'caption' => 'Confirms you are inside a client workspace, not the agency hub.',
                ],
                [
                    'title' => 'Year & location picker',
                    'variant' => 'year-location-form',
                    'theme' => 'company',
                    'caption' => 'Same Quick Input flow as the company portal — pick these before entering data.',
                ],
                [
                    'title' => 'Input sources',
                    'variant' => 'scope-nav',
                    'theme' => 'company',
                    'caption' => 'Sidebar lists Scope 1, 2, and 3 forms available for this client.',
                ],
            ],
            'body' => 'When you enter a client workspace, use the company Help & guide for detailed steps on locations, Quick Input, reports, and disclosures. Each managed client follows the package depth of the capacity row it was created under (Carbon through Enterprise).',
            'steps' => [
                'Locations & emission boundaries — set up sites and applicable categories.',
                'Quick Input & bulk import — enter DEWA, fuel, fleet, and Scope 3 data (within package limits).',
                'GHG Inventory — review totals and export Excel/PDF for the client (clean when a paid package is active).',
                'Disclosures — complete forms where available; PDF exports depend on entitlements.',
                'ESG Depth — stakeholder register, materiality matrix, suppliers, and targets when unlocked.',
                'Scope 1 & 2 Help Guide — field-by-field bulk import reference (linked from Input Data).',
            ],
            'links' => [
                ['route' => 'consultant.company-guide', 'label' => 'Company portal guide'],
            ],
        ],
        [
            'id' => 'team',
            'title' => 'Agency team & access',
            'summary' => 'Invite colleagues to your consultant account.',
            'highlights' => [
                [
                    'title' => 'Invite colleague',
                    'variant' => 'team-invite',
                    'theme' => 'consultant',
                    'caption' => 'Add team members who can help manage clients and directory settings.',
                ],
            ],
            'body' => 'Add team members to your agency with roles controlling access to clients, Agency packs, and directory settings.',
            'steps' => [
                'Invite staff by email.',
                'Assign roles with module permissions.',
                'Manage pending invitations and remove access when needed.',
            ],
            'links' => [
                ['route' => 'consultant.team.index', 'label' => 'Team & Access'],
            ],
        ],
    ],

    'faq' => [
        [
            'q' => 'What is the difference between the agency hub and a client workspace?',
            'a' => 'The agency hub (/consultant/dashboard) manages your practice, managed clients, and capacity requests. A client workspace is the company portal for one client — where you enter emissions data and run reports.',
        ],
        [
            'q' => 'Can I export clean reports on Free?',
            'a' => 'Free allows data entry and watermarked trial downloads (GHG / Excel / IEQT where offered). Clean official exports need a paid package bought from Agency packs. Check Agency packs for your current entitlements.',
        ],
        [
            'q' => 'How do I add more than one client?',
            'a' => 'Buy slots from Agency packs at the depth you need (buy more than once to mix depths). After activation, Add client and choose a package that still has remaining places. AED prices are shown before checkout.',
        ],
        [
            'q' => 'What packages can consultants request?',
            'a' => 'The same capability tiers as companies: Carbon, ESG and Enterprise. Each purchase covers one depth, so buy more than once to mix. The price is the package list price × slots.',
        ],
        [
            'q' => 'Can I hold Basic and ESG capacity at the same time?',
            'a' => 'Yes. Activation creates separate capacity rows. When you add a client, pick which package depth that workspace should use.',
        ],
        [
            'q' => 'Where do I get help with DEWA bills and bulk import columns?',
            'a' => 'Open Input Data in a client workspace → Scope 1 & 2 Help Guide, or use the Company portal guide section on Quick Input.',
        ],
    ],
];
