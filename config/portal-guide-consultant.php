<?php

return [
    'intro' => [
        'title' => 'How the consultant agency portal works',
        'body' => 'The consultant portal is your agency hub: manage client workspaces, request managed-client capacity, maintain your public directory profile, and respond to leads. When you enter a client workspace, you use the same emissions tools as a company — this guide covers both sides.',
        'tips' => [
            'Free includes one managed client with Free rules (Scope 1 & 2 full, Scope 3 one entry per category, watermarked GHG / Excel / IEQT trial downloads).',
            'Each client has a Primary Reporting Year (PRY) set when you create the engagement.',
            'Request clients — choose Scope Basic, Scope Pro, ESG Starter, ESG Complete, or Enterprise, then how many managed clients you need.',
            'Pricing is confirmed offline; MENetZero activates after payment. In-app pages never show AED amounts.',
            'Each paid request sets package depth for your managed-client workspaces.',
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
            'title' => 'Request clients when ready',
            'body' => 'Need clean exports or more managed clients? Open Agency packs → Request clients. Pick the package depth (Scope Basic … Enterprise) and how many clients. MENetZero quotes offline and activates after payment.',
            'route' => 'consultant.packs.index',
            'link_label' => 'Request clients',
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
                'Billing for the client is usually locked — capacity and upgrades go through Request clients in the agency hub.',
            ],
            'links' => [
                ['route' => 'consultant.workspace.switcher', 'label' => 'Workspaces'],
            ],
        ],
        [
            'id' => 'packs',
            'title' => 'Request clients & capacity',
            'summary' => 'Ask for package depth × managed-client capacity (no public prices).',
            'highlights' => [
                [
                    'title' => 'Request card',
                    'variant' => 'pack-card',
                    'theme' => 'consultant',
                    'caption' => 'Choose Scope Basic / Pro / ESG packages / Enterprise and how many clients — pricing confirmed offline.',
                ],
            ],
            'body' => 'After Free (one managed client), open Agency packs to Request clients. Pick the same package options companies use (Scope Basic through Enterprise), plus how many managed clients. MENetZero confirms rates offline (typically package list × clients) and activates capacity after payment. There is no self-serve checkout.',
            'steps' => [
                'Request clients — choose package, then how many managed clients you need.',
                'Optional extras — additional sites, seats, white-label, assurance, or notes for sales.',
                'Offline payment — MENetZero sends a quote; you pay outside the app.',
                'Activation — once marked paid, admin activates capacity; managed clients get that package depth.',
                'Orders / renewal — history and renewal prompts when relevant.',
            ],
            'tips' => [
                'Scope Basic ≈ clean GHG / MOCCAE / Excel / IEQT. Scope Pro and ESG packages add disclosure PDF suites.',
                'Enterprise is fully custom (branding, implementation, deeper ESG).',
                'Never quote exact AED rates in help answers unless MENetZero has confirmed them for that engagement.',
            ],
            'links' => [
                ['route' => 'consultant.packs.index', 'label' => 'Request clients'],
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
            'body' => 'When you enter a client workspace, use the company Help & guide for detailed steps on locations, Quick Input, reports, and disclosures. Paid managed clients follow the package activated for your agency (Scope Basic through Enterprise).',
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
            'body' => 'Add team members to your agency with roles controlling access to clients, Request clients, and directory settings.',
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
            'a' => 'Free allows data entry and watermarked trial downloads (GHG / Excel / IEQT where offered). Clean official exports need a paid package via Request clients. Check Agency packs for your current entitlements.',
        ],
        [
            'q' => 'How do I add more than one client?',
            'a' => 'Request the number of managed clients you need and choose package depth (Scope Basic … Enterprise). Each active managed client uses one place of your capacity. Pricing is confirmed offline — no public AED grid.',
        ],
        [
            'q' => 'What packages can consultants request?',
            'a' => 'The same capability cards as companies: Scope Basic, Scope Pro, ESG Starter, ESG Complete, and Enterprise. Suggest quote is typically list price × client count; sales may adjust offline.',
        ],
        [
            'q' => 'Where do I get help with DEWA bills and bulk import columns?',
            'a' => 'Open Input Data in a client workspace → Scope 1 & 2 Help Guide, or use the Company portal guide section on Quick Input.',
        ],
    ],
];
