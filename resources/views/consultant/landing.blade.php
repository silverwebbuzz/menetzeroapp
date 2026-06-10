@extends('layouts.public')

@section('title', 'MENetZero Consultant — Consultants & Agencies')

@section('content')
<section class="mkt-hero mkt-hero-xl">
    <div class="mkt-container max-w-4xl">
        <div class="mkt-tagline">For sustainability professionals</div>
        <h1>
            Freelance consultant or agency —
            <span class="block mkt-text-brand">one powerful portal</span>
        </h1>
        <p class="mkt-lead">
            Run multiple SME carbon workspaces from a single login, deliver MOCCAE-ready inventories and IFRS / GRI reports,
            and grow your practice through the MenetZero verified consultant directory.
        </p>
        <p class="text-sm text-gray-500 mt-4 max-w-2xl mx-auto">
            MenetZero prepares the data layer — you review, sign off, and deliver the professional trust layer UAE SMEs expect.
        </p>
        @if($consultantCount > 0)
            <p class="text-sm text-gray-500 mt-4 mb-6">
                Join <strong class="mkt-text-brand">{{ $consultantCount }}+ verified consultants</strong> already on the platform.
                <a href="{{ route('consultant-list.index') }}" class="mkt-text-brand hover:underline">Browse the directory →</a>
            </p>
        @endif
        <div class="flex flex-wrap justify-center gap-3 mt-6">
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
        <p class="text-xs text-gray-400 mt-4">Start with 1 free trial client · Agency pack pricing after sign-in · Google or email registration</p>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Why consultants choose MenetZero</h2>
            <p>Built for UAE practices that manage carbon inventories for multiple clients — not a single-company self-serve tool</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['One login, many clients', 'Switch between managed SME workspaces without juggling separate accounts or passwords.'],
                ['Growth-grade per client', 'Paid pack clients receive Growth-equivalent tools — MOCCAE, GHG, IFRS, GRI exports per reporting year.'],
                ['Wholesale agency packs', 'Practice pricing for 5, 10, 25 or 50 slots — not retail rates your clients would pay directly.'],
                ['Leads without spam', 'Directory listing routes inquiries through the platform — your phone and email stay private.'],
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
            <h2>Everything consultants need in one place</h2>
            <p>Software prepares SME data — you review, sign off, and deliver the trust layer clients expect</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">1</div>
                    <h3 class="text-xl font-bold text-gray-900">Client workspaces</h3>
                </div>
                <p class="text-gray-500 mb-6">One login to run multiple SME carbon accounts from your agency hub.</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Managed client slots (5 / 10 / 25 / 50)</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Instant workspace switcher — enter any client in one click</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Scope 1 &amp; 2, Scope 3 preview, and disclosure forms per client</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Up to 10 locations and 10 users per managed client workspace</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> 1 free trial client to explore data entry before you buy a pack</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">2</div>
                    <h3 class="text-xl font-bold text-gray-900">Directory listing</h3>
                </div>
                <p class="text-gray-500 mb-6">Optional public profile so SMEs can find and request you through MenetZero.</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Admin-verified listing on the public consultant directory</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Specialties, emirates, languages, and MOCCAE experience shown</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> No phone or email shown publicly — privacy by default</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Leads from subscribers and public visitors in one inbox</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Trade license + CV verification for trust</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">3</div>
                    <h3 class="text-xl font-bold text-gray-900">Wholesale packs</h3>
                </div>
                <p class="text-gray-500 mb-6">Agency pricing designed for practices — not retail client rates.</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Consultant 5, 10, 25 &amp; 50 slot packs</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Extra slots without upgrading pack size</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Reporting year (PRY) unlocks for existing clients mid-contract</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Calendar-year contracts aligned to 31 December</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Wholesale pricing visible after you sign in</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>What each paid client workspace includes</h2>
            <p>Every active slot on an agency pack runs at <strong>Growth-equivalent</strong> entitlements for that reporting year — the same deliverables a direct Growth subscriber receives</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['Emissions engine', ['Scope 1 & 2 quick input and calculations', 'Scope 3 preview (one entry per category)', 'MENA-specific emission factors', 'Dashboard with trends and hotspots']],
                ['Data operations', ['Bulk CSV / Excel import', 'Bulk data export', 'Up to 10 branches per client', '5 years historical data retention']],
                ['UAE compliance', ['GHG inventory PDF', 'MOCCAE Scope 1 & 2 PDF', 'IEQT export for mrv.ae', 'Excel results export']],
                ['Disclosure &amp; ESG', ['IFRS S1 / S2 disclosure forms + PDF export', 'GRI report PDF + content index', 'Full help guide including disclosures', 'Unlimited annual report PDFs per PRY']],
                ['Your workflow', ['Enter client data on their behalf', 'Review calculations before sign-off', 'Export client-ready working papers', 'Separate workspace per client organisation']],
                ['Free trial client', ['1 client included at registration', 'Full Scope 1 & 2 + disclosure preview', 'In-app preview — no PDF downloads', 'Upgrade slot when ready to deliver exports']],
            ] as $block)
                <div class="mkt-feature-card">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{!! $block[0] !!}</h3>
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

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Agency hub — run your whole practice</h2>
            <p>Tools built for consultants managing a portfolio, not a single company</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['Portfolio dashboard', 'See active clients, slot usage, directory status, and quick actions from one consultant home screen.'],
                ['Client workspace switcher', 'Jump into any managed client workspace, enter data, run reports, then exit back to your agency view.'],
                ['Managed client onboarding', 'Add a new SME client, assign them to a slot, and provision their workspace under your agency organisation.'],
                ['Slot &amp; contract management', 'Track how many of your pack slots are in use, renew annually, and add capacity without changing pack tier.'],
                ['Leads inbox', 'Subscriber intro requests and public directory inquiries arrive in one place — respond when you are ready.'],
                ['Team on your practice', 'Up to 10 users on your consultant organisation account — colleagues can share the agency hub login model.'],
            ] as $tool)
                <div class="mkt-feature-card" style="padding:1.25rem;">
                    <h3 class="font-bold text-gray-900 mb-2">{{ $tool[0] }}</h3>
                    <p class="text-sm text-gray-500">{{ $tool[1] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Reports you can prepare for clients</h2>
            <p>On paid pack workspaces, export the full compliance toolkit per reporting year</p>
        </div>
        <div class="mkt-table-wrap max-w-3xl mx-auto">
            <table class="mkt-table">
                <thead>
                    <tr>
                        <th>Deliverable</th>
                        <th>Free trial client</th>
                        <th>Paid pack client</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Scope 1 & 2 calculations', 'Preview in app', 'Full + exports'],
                        ['Disclosure forms (IFRS / GRI)', 'Preview only', 'Preview + PDF export'],
                        ['GHG inventory PDF', '—', '✓'],
                        ['MOCCAE S1 & 2 PDF', '—', '✓'],
                        ['IEQT export (mrv.ae)', '—', '✓'],
                        ['Excel results export', '—', '✓'],
                        ['IFRS S1 / S2 PDF', '—', '✓'],
                        ['GRI PDF + content index', '—', '✓'],
                        ['Bulk CSV / XLS import', '—', '✓'],
                    ] as $row)
                        <tr>
                            <td>{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4 max-w-xl mx-auto">
            MenetZero generates draft working papers. Your professional review and sign-off remain the service you deliver to clients.
        </p>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>Built for every practice size</h2>
            <p>From solo consultants to large agencies — scale slots as your client book grows</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach([
                ['Consultant 5', 'Solo consultant', 'Up to 5 managed SME clients. Ideal for freelancers starting a managed-service offering.'],
                ['Consultant 10', 'Small practice', 'Up to 10 clients. For boutiques with a steady SME portfolio across the UAE.'],
                ['Consultant 25', 'Growing agency', 'Up to 25 clients. For teams running concurrent reporting cycles across emirates.'],
                ['Consultant 50', 'Large agency', 'Up to 50 clients. For established practices with dedicated carbon advisory desks.'],
            ] as $pack)
                <div class="mkt-feature-card">
                    <h3 class="text-lg font-bold mkt-text-brand mb-1">{{ $pack[0] }}</h3>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $pack[1] }}</p>
                    <p class="text-sm text-gray-600">{{ $pack[2] }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-center text-sm text-gray-500 mt-8">
            Need a different scale? <a href="{{ route('contact') }}" class="mkt-text-brand hover:underline">Contact us</a> for enterprise agency arrangements.
        </p>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>How it works</h2>
            <p>From registration to your first managed client in four steps</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['01', 'Register', 'Create your consultant account with practice details — Google or email.'],
                ['02', 'Add a client', 'Start with one free trial workspace or purchase an agency pack when you are ready to export.'],
                ['03', 'Enter &amp; review', 'Open client workspaces — emissions, disclosures, MOCCAE forms, and downloadable reports.'],
                ['04', 'Get listed', 'Complete your directory profile, pass verification, and receive platform leads.'],
            ] as $step)
                <div class="relative">
                    <div class="mkt-step-badge">{{ $step[0] }}</div>
                    <div class="mkt-feature-card pt-8">
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $step[1] }}</h3>
                        <p class="text-gray-500 text-sm">{!! $step[2] !!}</p>
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
                <span class="mkt-tagline" style="background:rgba(14,165,163,0.2);color:#5eead4;">Public directory</span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white">Your listing, your leads — privacy protected</h2>
                <p class="text-lg text-slate-300 mb-6">
                    Approved consultants appear on our public directory. Visitors see your practice name, specialties, emirates served, and experience —
                    not your personal phone or email. When someone wants to work with you, they submit a request and we pass the lead to you.
                </p>
                <ul class="space-y-3 text-slate-300 mb-8 text-sm">
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Public profiles build trust for your practice</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Subscriber intro requests and public visitor inquiries in one leads view</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Contact details shared only as qualified leads — never scraped from the listing</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> MenetZero Growth subscribers get priority intro access to verified consultants</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Upload trade license and CV for admin verification before going live</li>
                </ul>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('consultant-list.index') }}" class="mkt-btn mkt-btn-primary">Browse consultant directory</a>
                    <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-white-outline">Apply to get listed</a>
                </div>
            </div>
            <div class="mkt-glass-panel">
                <h3 class="text-xl font-semibold mb-6 text-white">Agency packs at a glance</h3>
                <div class="space-y-0 text-sm">
                    @foreach([
                        ['Consultant 5', '5 managed client workspaces'],
                        ['Consultant 10', '10 managed client workspaces'],
                        ['Consultant 25', '25 managed client workspaces'],
                        ['Consultant 50', '50 managed client workspaces'],
                        ['Extra slot', 'Add capacity without changing pack'],
                        ['Free trial', '1 client · data entry & preview'],
                    ] as $i => $row)
                        <div class="flex justify-between gap-4 py-3 {{ $i < 5 ? 'border-b border-white/10' : '' }}">
                            <span class="text-slate-300 shrink-0">{{ $row[0] }}</span>
                            <span class="font-semibold text-right {{ $i === 5 ? 'text-teal-300' : 'text-white' }}">{{ $row[1] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-slate-400 mt-4">Pack pricing is visible after you create a consultant account and sign in.</p>
                <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-block mt-4">Get started free</a>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-section-head">
            <h2>Who this portal is for</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-6 text-sm">
            <div class="mkt-feature-card">
                <h3 class="font-bold text-gray-900 mb-3 mkt-text-brand">Consultant portal — you are here</h3>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Sustainability consultants &amp; ESG advisors</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> MOCCAE / GHG Protocol specialists</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Agencies managing multiple SME inventories</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Practices wanting directory leads + wholesale packs</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <h3 class="font-bold text-gray-900 mb-3">Company self-serve — different product</h3>
                <p class="text-gray-500 mb-3">If <em>your own company</em> tracks its emissions (not clients'), use the company portal instead.</p>
                <ul class="space-y-2 text-gray-600 mb-4">
                    <li class="flex items-start"><span class="text-gray-400 mr-2">→</span> <a href="{{ route('pricing') }}" class="mkt-text-brand hover:underline">Company pricing (AED)</a> — Starter, Growth, Enterprise</li>
                    <li class="flex items-start"><span class="text-gray-400 mr-2">→</span> <a href="{{ route('register') }}" class="mkt-text-brand hover:underline">Company sign up</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-section-head">
            <h2>Common questions</h2>
        </div>
        <div class="space-y-4">
            @foreach([
                ['Is the directory listing mandatory?', 'No. You can use MenetZero purely as an agency hub for managed clients. The public directory is optional — apply when you want platform leads.'],
                ['What does the free trial client include?', 'One managed workspace with Scope 1 & 2 data entry and disclosure previews in the app. PDF exports unlock when you assign the client to a paid pack slot.'],
                ['Do my clients need their own MenetZero login?', 'You work inside managed workspaces on their behalf. Your clients do not need separate subscriptions for the work you perform in their workspace.'],
                ['Can I add more clients mid-year?', 'Yes. Buy extra slots pro-rata through 31 December, or unlock a new reporting year for an existing client without upgrading your whole pack.'],
                ['How is this different from company pricing?', 'Company pricing on our public site is for businesses tracking their own emissions. Consultant packs are wholesale rates for practices managing many clients — shown only after consultant sign-in.'],
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

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container max-w-3xl text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to run client workspaces from one login?</h2>
        <p class="text-gray-500 mb-8">
            Register once — your agency hub, client portfolio, and directory profile live on the same consultant account.
            Start free with one trial client, then scale with agency packs when you are ready.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
        <p class="mt-8 text-xs text-gray-400 max-w-xl mx-auto">
            MenetZero does not verify emissions calculations. Consultant engagements are separate professional services.
            Reports are draft working papers for your compliance workflow.
        </p>
    </div>
</section>
@endsection
