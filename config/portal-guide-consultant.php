<?php

return [
    'intro' => [
        'title' => 'How the consultant agency portal works',
        'body' => 'The consultant portal is your agency hub: manage client workspaces, purchase agency packs, maintain your public directory profile, and respond to leads. When you enter a client workspace, you use the same emissions tools as a company — this guide covers both sides.',
        'tips' => [
            'Free trial includes one managed client slot — enough to test the full workflow before buying a pack.',
            'Each client has a Primary Reporting Year (PRY) set when you create the engagement.',
            'Paid packs unlock exports, additional client slots, and Scope 3 for managed clients.',
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
            'body' => 'Create a client company record, set PRY, and assign your free trial or paid slot.',
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
            'title' => 'Upgrade agency pack when ready',
            'body' => 'Buy a pack for more client slots, exports, and advanced features across your portfolio.',
            'route' => 'consultant.packs.index',
            'link_label' => 'Agency packs',
        ],
    ],

    'sections' => [
        [
            'id' => 'dashboard',
            'title' => 'Agency dashboard',
            'summary' => 'Portfolio overview across all managed clients.',
            'image' => [
                'src' => 'images/help/consultant/dashboard.png',
                'variant' => 'dashboard',
                'theme' => 'consultant',
                'alt' => 'Agency dashboard with portfolio KPIs and client slot usage',
                'caption' => 'Start here each day — portfolio totals, slots, directory status, and leads.',
            ],
            'body' => 'The consultant dashboard shows aggregate emissions, active clients, slot usage, directory status, and new leads. Use it as your daily starting point.',
            'steps' => [
                'Portfolio emissions — combined tCO₂e across clients with data.',
                'Client slots — used vs available on your current pack or trial.',
                'Directory status — draft, pending review, approved, or rejected.',
                'Quick actions — add client, open workspaces, view packs.',
            ],
            'links' => [
                ['route' => 'consultant.dashboard', 'label' => 'Dashboard'],
            ],
        ],
        [
            'id' => 'clients',
            'title' => 'Managed clients',
            'summary' => 'Create and maintain client company records.',
            'image' => [
                'src' => 'images/help/consultant/clients.png',
                'variant' => 'clients',
                'theme' => 'consultant',
                'alt' => 'Managed clients list with PRY and enter workspace actions',
                'caption' => 'Each row is one client — set PRY when you create the engagement.',
            ],
            'body' => 'Each managed client is a separate company workspace in MENetZero. You define the client name, sector, PRY, and contact details. One slot is consumed per active client.',
            'steps' => [
                'Add client — creates the company and links it to your agency.',
                'Edit client — update PRY, display name, or engagement settings.',
                'Archive or remove — frees a slot when a engagement ends (subject to pack rules).',
            ],
            'tips' => [
                'Set PRY correctly at creation — it drives default year filters in Quick Input and reports.',
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
            'image' => [
                'src' => 'images/help/consultant/workspaces.png',
                'variant' => 'workspaces',
                'theme' => 'consultant',
                'alt' => 'Client workspace switcher with agency mode header',
                'caption' => 'The header shows agency mode and client name — use Back to Agency Hub to exit.',
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
                'Billing for the client may be locked when you manage them — upgrades go through agency packs.',
            ],
            'links' => [
                ['route' => 'consultant.workspace.switcher', 'label' => 'Workspaces'],
            ],
        ],
        [
            'id' => 'packs',
            'title' => 'Agency packs & billing',
            'summary' => 'Wholesale pricing for multiple client slots.',
            'image' => [
                'src' => 'images/help/consultant/packs.png',
                'variant' => 'packs',
                'theme' => 'consultant',
                'alt' => 'Agency pack pricing cards with slot counts',
                'caption' => 'Compare packs by client slots and Growth-level exports per client.',
            ],
            'body' => 'Agency packs bundle client slots, export rights, and feature access. Buy or upgrade from Agency packs; pay via Razorpay or Cashfree. Extra slots and year unlocks are available on some plans.',
            'steps' => [
                'Compare packs — slots, features, and price per year.',
                'Checkout — select pack and complete payment.',
                'Renewal — extend before expiry from Renewal in the nav (when due).',
                'Orders — history of pack purchases.',
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
            'image' => [
                'src' => 'images/help/consultant/directory.png',
                'variant' => 'directory',
                'theme' => 'consultant',
                'alt' => 'Directory profile editor and verification documents',
                'caption' => 'Complete your profile and upload documents before submitting for review.',
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
            'image' => [
                'src' => 'images/help/consultant/client-tools.png',
                'variant' => 'client-tools',
                'theme' => 'consultant',
                'alt' => 'Client workspace with sidebar navigation and Quick Input form',
                'caption' => 'Inside a workspace the left nav matches the company portal — Locations, Input Data, Reports.',
            ],
            'body' => 'When you enter a client workspace, use the company Help & guide for detailed steps on locations, Quick Input, reports, and disclosures. Key tasks consultants perform most often:',
            'steps' => [
                'Locations & emission boundaries — set up sites and applicable categories.',
                'Quick Input & bulk import — enter DEWA, fuel, fleet, and Scope 3 data.',
                'GHG Inventory — review totals and export Excel/PDF for the client.',
                'Disclosures — complete IFRS S1/S2 and GRI sections for reporting cycles.',
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
            'image' => [
                'src' => 'images/help/consultant/team.png',
                'variant' => 'team',
                'theme' => 'consultant',
                'alt' => 'Agency team list with invite action',
                'caption' => 'Add colleagues to help manage clients, packs, and directory settings.',
            ],
            'body' => 'Add team members to your agency with roles controlling access to clients, packs, and directory settings.',
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
            'a' => 'The agency hub (/consultant/dashboard) manages your practice, clients, and packs. A client workspace is the company portal for one client — where you enter emissions data and run reports.',
        ],
        [
            'q' => 'Can I export reports on the free trial?',
            'a' => 'Trial clients can use most data entry features; full exports and higher limits typically require a paid agency pack. Check your pack details on Agency packs.',
        ],
        [
            'q' => 'How do I add more than one client?',
            'a' => 'Upgrade to an agency pack with the slot count you need. Each active managed client uses one slot.',
        ],
        [
            'q' => 'Where do I get help with DEWA bills and bulk import columns?',
            'a' => 'Open Input Data in a client workspace → Scope 1 & 2 Help Guide, or use the Company portal guide section on Quick Input.',
        ],
    ],
];
