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
            and publish an integrated UAE ESG Report with IFRS / GRI disclosures — without hiring a consultant to do the data entry.
        </p>
        <p class="text-sm text-gray-500 mt-4 max-w-2xl mx-auto">
            Start free with Scope 1 &amp; 2, try Scope 3 (one entry per category), and download watermarked trial reports.
            When you need clean exports or more capacity, upgrade from inside your account and pay online in AED.
        </p>
        <x-payments-notice class="mt-6" />
        <div class="flex flex-wrap justify-center gap-3 mt-6">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Company sign up</a>
            <a href="{{ route('pricing') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Explore Free</a>
        </div>
        <p class="text-xs text-gray-400 mt-4">Free for all · Upgrade your package when ready · Google or email registration</p>
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
                ['Start free, request when ready', 'Try Scope 1 & 2 and watermarked trial downloads at no cost. Upgrade your package for official clean exports and higher limits.'],
                ['UAE ESG on higher packages', 'Build narrative chapters, auto-pull GHG totals, and download the integrated UAE ESG Report PDF plus ESG Scorecard on ESG packages.'],
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
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> One entry per category on Free; more on Scope Pro / ESG packages</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Higher Scope 3 intensity available on request</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Packages matched to your reporting needs</h2>
            <p>Explore Free publicly — AED pricing is shown when you upgrade from your account</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach([
                ['Free', 'Try the workflow', [
                    'Scope 1 & 2 full entry',
                    'Scope 3 — one entry per category',
                    'Watermarked trial downloads',
                    'Disclosure form previews',
                ]],
                ['Scope Basic', 'MOCCAE-ready inventory', [
                    'Clean GHG, MOCCAE & IEQT exports',
                    'Bulk CSV / Excel import',
                    'Up to 3 sites',
                    'Request from Plan & billing',
                ]],
                ['Scope Pro / ESG', 'Broader scopes & disclosures', [
                    'Everything in Scope Basic, expanded',
                    'UAE ESG Report PDF + scorecard options',
                    'IFRS / GRI export packages',
                    'Request from Plan & billing',
                ]],
                ['Enterprise', 'Custom & white-label', [
                    'Custom implementation',
                    'White-label report covers',
                    'Extended KPI / assurance workflows',
                    'Talk to MENetZero',
                ]],
            ] as $plan)
                <div class="mkt-feature-card flex flex-col h-full">
                    <h3 class="text-lg font-bold mkt-text-brand mb-1">{{ $plan[0] }}</h3>
                    <p class="text-xs text-gray-500 mb-4">{{ $plan[1] }}</p>
                    <ul class="space-y-2 text-sm text-gray-600 flex-1 mb-5">
                        @foreach($plan[2] as $feat)
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> {{ $feat }}</li>
                        @endforeach
                    </ul>
                    @if($plan[0] === 'Free')
                        <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-sm">Explore Free</a>
                    @elseif($plan[0] === 'Enterprise')
                        <a href="{{ route('contact') }}" class="mkt-btn mkt-btn-outline mkt-btn-sm">Contact us</a>
                    @else
                        <a href="{{ route('register') }}" class="mkt-btn mkt-btn-outline mkt-btn-sm">Sign up to request</a>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-center text-sm text-gray-500 mt-8">
            <a href="{{ route('pricing') }}" class="mkt-text-brand hover:underline">Learn what’s included on Free →</a>
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
                ['Emission calculations', ['Automated Scope 1 & 2 from activity data', 'Built-in emission factor library for MENA', 'Dashboard with totals, trends, and hotspots', 'Historical data retention by package']],
                ['UAE & regional compliance', ['GHG Protocol-aligned inventory methodology', 'MOCCAE Scope 1 & 2 report PDFs', 'IEQT export for mrv.ae submission', 'Working papers for your audit trail']],
                ['ESG disclosures', ['Integrated UAE ESG Report PDF (Scope Pro / ESG packages)', 'ESG Scorecard with multi-year KPI tables', 'IFRS S1 / S2 and GRI + content index', 'SASB sector index (optional)', 'Preview on Free & Scope Basic — export on Scope Pro+']],
                ['Data management', ['Manual quick-input for every emission source', 'Bulk CSV / Excel import (Scope Basic+)', 'Bulk data export for analysis', 'Document storage per organisation']],
                ['Multi-location & team access', ['Track emissions per branch or site', 'Invite colleagues with role-based access', 'Site and seat limits by package', 'Custom scale on Enterprise']],
                ['Consultant marketplace', ['Browse verified UAE consultants publicly', 'Request introductions from your account', 'Optional review support for professional sign-off', 'Paid packages unlock fuller directory connect']],
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
            <h2>Reports &amp; exports by package</h2>
            <p>Capabilities only — no public prices. Upgrade your package from your account when ready.</p>
        </div>
        <div class="mkt-table-wrap">
            <table class="mkt-table">
                <thead>
                    <tr>
                        <th>Deliverable</th>
                        <th>Free</th>
                        <th>Scope Basic</th>
                        <th>Scope Pro</th>
                        <th>ESG Starter</th>
                        <th>ESG Complete</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Sites / branches', '1', 'Up to 3', 'Up to 10', 'Up to 5', 'Up to 10'],
                        ['Scope 1 & 2 calculations', 'In-app', '✓', '✓', '✓', '✓'],
                        ['Clean GHG / MOCCAE / IEQT / Excel', 'Watermarked', '✓', '✓', '✓', '✓'],
                        ['Bulk CSV / Excel import', '—', '✓', '✓', '✓', '✓'],
                        ['Scope 3 categories', '1 entry each', 'Limited', 'Broader', 'Broad', 'Broad'],
                        ['Disclosure forms (IFRS / GRI)', 'Preview', 'Preview', 'Export', 'Export', 'Export'],
                        ['UAE ESG Report PDF', '—', '—', '✓', '✓', '✓'],
                        ['ESG Scorecard', '—', '—', '✓', '✓', '✓'],
                        ['IFRS S1 / S2 & GRI exports', '—', '—', '✓', '✓', '✓'],
                        ['White-label / assurance options', '—', '—', '—', '✓', '✓'],
                        ['Multi-entity consolidation', '—', '—', '—', '✓', '✓'],
                    ] as $row)
                        <tr>
                            <td>{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                            <td>{{ $row[3] }}</td>
                            <td>{{ $row[4] }}</td>
                            <td>{{ $row[5] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4 max-w-2xl mx-auto">
            Enterprise is custom (white-label, extended KPIs, implementation) — talk to MENetZero.
            Reports are draft working papers for your compliance workflow. Third-party verification is available through our
            <a href="{{ route('consultant-list.index') }}" class="mkt-text-brand hover:underline">consultant directory</a>.
        </p>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Optional consultant support</h2>
            <p>Software prepares your data — a verified consultant can review, sign off, and add the human trust layer</p>
        </div>
        <div class="max-w-2xl mx-auto mkt-feature-card text-center">
            <p class="text-sm text-gray-600 mb-4">
                Browse verified UAE consultants and request an introduction from your account. Advisory engagement is arranged offline — no public service prices here.
            </p>
            <a href="{{ route('consultant-list.index') }}" class="mkt-btn mkt-btn-primary mkt-btn-sm">Browse consultants</a>
        </div>
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
                ['04', 'Export & report', 'Upgrade your package for clean MOCCAE/GHG exports, or an ESG package for UAE ESG Report, Scorecard, IFRS, and GRI deliverables.'],
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
                    agency workspaces, managed clients, and a verified directory listing.
                </p>
                <ul class="space-y-3 text-slate-300 mb-8 text-sm">
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Manage multiple SME workspaces from one login</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Preferential entity rates — request after consultant sign-in</li>
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
                        <p>Your organisation tracks its own emissions. Start Free, then request a package when ready.</p>
                        <a href="{{ route('pricing') }}" class="text-teal-300 hover:underline text-xs mt-2 inline-block">Explore Free →</a>
                    </div>
                    <div>
                        <p class="font-semibold text-white mb-1">Consultant portal</p>
                        <p>You run carbon workspaces for multiple client companies. Request entities and directory leads.</p>
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
                ['Can I really start for free?', 'Yes. Free includes Scope 1 & 2, Scope 3 (one entry per category), and watermarked trial downloads. Official clean exports unlock after your package is activated.'],
                ['How do I get a paid package?', 'Sign in, open Plan & billing, and request a package. MENetZero confirms features and pricing offline, then activates after payment.'],
                ['Which package do I need for MOCCAE?', 'A Scope Basic–style package typically covers clean GHG inventory PDF, MOCCAE Scope 1 & 2 PDF, and IEQT — confirm when you request.'],
                ['What about full UAE ESG / IFRS / GRI?', 'Those sit on ESG packages (and Enterprise for white-label / custom). Request the package that matches your disclosure needs.'],
                ['Do I need a consultant to use MenetZero?', 'No. The platform is self-serve for companies. Consultants are optional — for review, sign-off, or if you prefer expert guidance alongside the software.'],
                ['I manage emissions for clients, not my own company', 'Use the consultant portal instead. Explore Free here is for organisations tracking their own footprint. Visit /consultant for agency features.'],
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
            Create your company account in minutes. Start Free, then request a package when you need clean exports.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Company sign up</a>
            <a href="{{ route('pricing') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Explore Free</a>
            <a href="{{ route('login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
        <p class="mt-8 text-xs text-gray-400 max-w-xl mx-auto">
            No public AED list prices. Reports on Free are watermarked trial working papers.
        </p>
    </div>
</section>
@endsection
