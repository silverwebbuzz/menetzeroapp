@extends('layouts.public')

@section('title', 'MIDDLE EAST NET Zero - Carbon Emissions Tracking')

@section('content')
<section class="mkt-hero mkt-hero-xl">
    <div class="mkt-container max-w-4xl">
        <div class="mkt-tagline">For companies · self-serve</div>
        <h1>
            Everything you need to
            <span class="block mkt-text-brand">measure and report your carbon footprint</span>
        </h1>
        <p class="mkt-lead mkt-lead-lg">
            UAE-focused carbon management for businesses that want to track Scope 1 &amp; 2, prepare MOCCAE submissions,
            and export IFRS / GRI disclosures — without hiring a consultant to do the data entry.
        </p>
        <p class="text-sm text-gray-500 mt-4 max-w-2xl mx-auto">
            Start free with Scope 1 &amp; 2 and disclosure previews. Paid plan upgrades are coming soon — register now and switch plans from your account when checkout opens.
        </p>
        <x-payments-notice class="mt-6" />
        <div class="flex flex-wrap justify-center gap-3 mt-6">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Company sign up</a>
            <a href="{{ route('pricing') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">View company pricing</a>
        </div>
        <p class="text-xs text-gray-400 mt-4">Free plan available · Annual plans in AED · Google or email registration</p>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Why UAE companies choose MenetZero</h2>
            <p>Built for Middle East SMEs and mid-market organisations — not generic global carbon software</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['MOCCAE-ready from day one', 'Generate GHG inventories, MOCCAE Scope 1 & 2 PDFs, and IEQT exports aligned with UAE reporting workflows.'],
                ['Start free, upgrade when ready', 'Try Scope 1 & 2 calculations and disclosure forms at no cost. Pay only when you need PDF exports and bulk data tools.'],
                ['IFRS & GRI on Growth', 'Complete sustainability disclosure forms in-app and download IFRS S1/S2 and GRI report PDFs on the Growth plan.'],
                ['Human review when you need it', 'Connect with verified UAE consultants from the directory — optional review packs for professional sign-off.'],
            ] as $item)
                <div class="mkt-feature-card">
                    <h3 class="text-base font-bold text-gray-900 mb-2">{{ $item[0] }}</h3>
                    <p class="text-sm text-gray-500">{{ $item[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Complete Scope 1, 2 &amp; 3 coverage</h2>
            <p>Track direct emissions, purchased energy, and value-chain impacts with MENA-specific emission factors</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">1</div>
                    <h3 class="text-xl font-bold text-gray-900">Scope 1</h3>
                </div>
                <p class="mb-6 text-gray-500">Direct emissions from sources you own or control</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Company vehicles and fleet fuel</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> On-site fuel combustion and generators</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Refrigerant leaks and fugitive emissions</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Quick-input workflows with auto-calculation</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">2</div>
                    <h3 class="text-xl font-bold text-gray-900">Scope 2</h3>
                </div>
                <p class="mb-6 text-gray-500">Indirect emissions from purchased electricity and energy</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Electricity consumption by location</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> District cooling, steam, and heating</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> UAE / MENA grid emission factors</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Location-based reporting per branch</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">3</div>
                    <h3 class="text-xl font-bold text-gray-900">Scope 3</h3>
                </div>
                <p class="mb-6 text-gray-500">Value-chain emissions across GHG Protocol categories</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Business travel, commuting, and logistics</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Purchased goods, waste, and supply chain</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Preview on Starter &amp; Growth (1 entry per category)</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Unlimited Scope 3 on Enterprise</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Plans built for every stage</h2>
            <p>From free exploration to full ESG disclosure exports — pick the plan that matches your reporting needs</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach([
                ['Free', 'Try before you buy', 'AED 0', 'free', [
                    'Scope 1 & 2 quick input',
                    'Disclosure forms — in-app preview',
                    '1 location · 1 user',
                    'Scope 3 locked',
                ]],
                ['Starter', 'MOCCAE-ready inventory', 'AED 1,499 / yr', 'paid', [
                    'GHG, MOCCAE & IEQT PDF exports',
                    'Bulk CSV / Excel import',
                    '3 locations · 5 users',
                    'Scope 3 preview (1 per category)',
                ]],
                ['Growth', 'IFRS & GRI downloads', 'AED 2,499 / yr', 'paid', [
                    'Everything in Starter',
                    'IFRS S1/S2 + GRI PDF exports',
                    '10 locations · 10 users',
                    'Full consultant directory access',
                ]],
                ['Enterprise', 'Multi-site & unlimited S3', 'Custom', 'enterprise', [
                    'Unlimited Scope 3 entries',
                    'Unlimited locations & users',
                    'API access & dedicated support',
                    'Priority consultant introductions',
                ]],
            ] as $plan)
                <div class="mkt-feature-card flex flex-col h-full {{ $plan[0] === 'Growth' ? 'ring-2 ring-teal-500/30' : '' }}">
                    @if($plan[0] === 'Growth')
                        <span class="text-xs font-semibold text-teal-700 mb-2">MOST POPULAR</span>
                    @endif
                    <h3 class="text-lg font-bold mkt-text-brand mb-1">{{ $plan[0] }}</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ $plan[1] }}</p>
                    <div class="text-xl font-extrabold text-gray-900 mb-4">{{ $plan[2] }}</div>
                    <ul class="space-y-2 text-sm text-gray-600 flex-1 mb-5">
                        @foreach($plan[4] as $feat)
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> {{ $feat }}</li>
                        @endforeach
                    </ul>
                    <x-plan-purchase-cta
                        :tier="$plan[3]"
                        :highlight="$plan[0] === 'Growth'"
                        class="mkt-btn-sm"
                    />
                </div>
            @endforeach
        </div>
        <p class="text-center text-sm text-gray-500 mt-8">
            <a href="{{ route('pricing') }}" class="mkt-text-brand hover:underline">See full plan comparison →</a>
        </p>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Platform capabilities</h2>
            <p>Everything your sustainability, finance, and operations teams need in one workspace</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['Emission calculations', ['Automated Scope 1 & 2 from activity data', 'Built-in emission factor library for MENA', 'Dashboard with totals, trends, and hotspots', 'Historical data retention (2–5 years by plan)']],
                ['UAE & regional compliance', ['GHG Protocol-aligned inventory methodology', 'MOCCAE Scope 1 & 2 report PDFs', 'IEQT export for mrv.ae submission', 'Working papers for your audit trail']],
                ['ESG disclosures', ['IFRS S1 / S2 climate disclosure forms', 'GRI standards reporting + content index', 'Preview on Free & Starter — export on Growth', 'Guided help including disclosure walkthroughs']],
                ['Data management', ['Manual quick-input for every emission source', 'Bulk CSV / Excel import (Starter+)', 'Bulk data export for analysis', 'Document storage per organisation']],
                ['Multi-location & team access', ['Track emissions per branch or site', 'Invite colleagues with role-based access', 'Up to 10 users on Growth', 'Unlimited users on Enterprise']],
                ['Consultant marketplace', ['Browse verified UAE consultants publicly', 'Request introductions from your account', 'Optional review packs for professional sign-off', 'Growth subscribers get full directory connect']],
            ] as $block)
                <div class="mkt-feature-card">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $block[0] }}</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        @foreach($block[1] as $line)
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> {{ $line }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Reports &amp; exports by plan</h2>
            <p>Know exactly which deliverables unlock at each tier</p>
        </div>
        <div class="mkt-table-wrap">
            <table class="mkt-table">
                <thead>
                    <tr>
                        <th>Deliverable</th>
                        <th>Free</th>
                        <th>Starter</th>
                        <th>Growth</th>
                        <th>Enterprise</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Scope 1 & 2 calculations', 'In-app', '✓', '✓', '✓'],
                        ['GHG inventory PDF', '—', '✓', '✓', '✓'],
                        ['MOCCAE S1 & 2 PDF', '—', '✓', '✓', '✓'],
                        ['IEQT export (mrv.ae)', '—', '✓', '✓', '✓'],
                        ['Excel results export', '—', '✓', '✓', '✓'],
                        ['Bulk CSV / XLS import', '—', '✓', '✓', '✓'],
                        ['Disclosure forms (IFRS / GRI)', 'Preview', 'Preview', 'Export PDF', 'Full'],
                        ['IFRS S1 / S2 PDF', '—', '—', '✓', '✓'],
                        ['GRI PDF + content index', '—', '—', '✓', '✓'],
                        ['Scope 3', 'Locked', 'Preview', 'Preview', 'Unlimited'],
                    ] as $row)
                        <tr>
                            <td>{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                            <td>{{ $row[3] }}</td>
                            <td>{{ $row[4] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4 max-w-2xl mx-auto">
            Reports are draft working papers for your compliance workflow. Third-party verification is available through our
            <a href="{{ route('consultant-list.index') }}" class="mkt-text-brand hover:underline">consultant directory</a>.
        </p>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Optional consultant review</h2>
            <p>Software prepares your data — a verified consultant can review, sign off, and add the human trust layer</p>
        </div>
        <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
            @foreach(\App\Data\CommercialPlanComparison::consultantAddOns() as $addon)
                <div class="mkt-feature-card">
                    <div class="flex justify-between items-baseline gap-2 mb-2">
                        <h3 class="font-bold text-gray-900">{{ $addon['name'] }}</h3>
                        <span class="text-sm font-semibold text-gray-500">{{ $addon['price'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">For {{ $addon['for_plan'] }} plan subscribers</p>
                    <p class="text-sm text-gray-600 mb-3">{{ $addon['description'] }}</p>
                    <span class="mkt-btn mkt-btn-coming-soon mkt-btn-sm" style="display:inline-flex;">Checkout coming soon</span>
                </div>
            @endforeach
        </div>
        <p class="text-center text-sm text-gray-500 mt-6">
            <a href="{{ route('consultant-list.index') }}" class="mkt-text-brand hover:underline">Browse verified consultants →</a>
        </p>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Get started in four simple steps</h2>
            <p>From sign-up to your first compliance-ready export</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['01', 'Create your account', 'Company sign up with Google or email — add your organisation and first location.'],
                ['02', 'Enter emission data', 'Use quick input or bulk import to capture Scope 1 & 2 activity data across your sites.'],
                ['03', 'Review your dashboard', 'See calculated totals, explore disclosure forms, and identify carbon hotspots.'],
                ['04', 'Export & report', 'Upgrade to Starter or Growth to download MOCCAE, GHG, IFRS, and GRI deliverables.'],
            ] as $step)
                <div class="relative">
                    <div class="mkt-step-badge">{{ $step[0] }}</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $step[1] }}</h3>
                        <p class="text-gray-500 text-sm">{{ $step[2] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-dark">
    <div class="mkt-container">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="mkt-tagline" style="background:rgba(14,165,163,0.2);color:#5eead4;">For sustainability professionals</span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white">Are you a carbon consultant in the UAE?</h2>
                <p class="text-lg text-slate-300 mb-6">
                    This home page is for <strong class="text-white">companies</strong> tracking their own emissions.
                    If you manage carbon inventories for multiple clients, the consultant portal is built for you —
                    agency packs, client workspaces, and a verified directory listing.
                </p>
                <ul class="space-y-3 text-slate-300 mb-8 text-sm">
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Manage multiple SME workspaces from one login</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Wholesale agency packs — pricing after consultant sign-in</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Qualified leads from subscribers and public directory visitors</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> 1 free trial client to get started</li>
                </ul>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('consultant.landing') }}" class="mkt-btn mkt-btn-primary">Explore consultant portal</a>
                    <a href="{{ route('consultant-list.index') }}" class="mkt-btn mkt-btn-white-outline">Browse directory</a>
                </div>
            </div>
            <div class="mkt-glass-panel">
                <h3 class="text-xl font-semibold mb-4 text-white">Not sure which portal?</h3>
                <div class="space-y-4 text-sm text-slate-300">
                    <div class="pb-4 border-b border-white/10">
                        <p class="font-semibold text-white mb-1">Company portal — you are here</p>
                        <p>Your organisation tracks its own emissions. Self-serve plans from Free to Enterprise.</p>
                        <a href="{{ route('pricing') }}" class="text-teal-300 hover:underline text-xs mt-2 inline-block">Company pricing (AED) →</a>
                    </div>
                    <div>
                        <p class="font-semibold text-white mb-1">Consultant portal</p>
                        <p>You run carbon workspaces for multiple client companies. Agency packs and directory leads.</p>
                        <a href="{{ route('consultant.register') }}" class="text-teal-300 hover:underline text-xs mt-2 inline-block">Create consultant account →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-section-head">
            <h2>Common questions</h2>
        </div>
        <div class="space-y-4">
            @foreach([
                ['Can I really start for free?', 'Yes. The Free plan includes Scope 1 & 2 data entry and disclosure form previews in the app. PDF exports and bulk import unlock on paid plans.'],
                ['Which plan do I need for MOCCAE reporting?', 'Starter (AED 1,499/year) includes GHG inventory PDF, MOCCAE Scope 1 & 2 PDF, and IEQT export for mrv.ae — plus bulk import and Scope 3 preview.'],
                ['When do I need Growth?', 'Choose Growth (AED 2,499/year) if you need downloadable IFRS S1/S2 and GRI report PDFs, more locations (up to 10), and full access to connect with verified consultants.'],
                ['Do I need a consultant to use MenetZero?', 'No. The platform is self-serve for companies. Consultants are optional — for review, sign-off, or if you prefer expert guidance alongside the software.'],
                ['I manage emissions for clients, not my own company', 'Use the consultant portal instead. Company pricing on this site is for organisations tracking their own footprint. Visit /consultant for agency features.'],
            ] as $faq)
                <details class="mkt-feature-card group" style="padding:1.25rem;">
                    <summary class="font-semibold text-gray-900 cursor-pointer list-none flex justify-between items-center gap-4">
                        {{ $faq[0] }}
                        <span class="text-gray-400 group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <p class="text-sm text-gray-500 mt-3 leading-relaxed">{{ $faq[1] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container max-w-3xl text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to measure your organisation's footprint?</h2>
        <p class="text-gray-500 mb-8">
            Create your company account in minutes. Start free, explore Scope 1 &amp; 2, and upgrade to paid plans when checkout opens.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Company sign up</a>
            <a href="{{ route('pricing') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">View pricing</a>
            <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
        <p class="mt-8 text-xs text-gray-400 max-w-xl mx-auto">
            Annual plans priced in AED. Reports are draft working papers for your compliance workflow.
        </p>
    </div>
</section>
@endsection
