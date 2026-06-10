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
            Manage SME carbon workspaces, purchase agency packs, and list in the MenetZero verified consultant directory for qualified leads.
        </p>
        @if($consultantCount > 0)
            <p class="text-sm text-gray-500 mt-4 mb-6">
                Join <strong class="mkt-text-brand">{{ $consultantCount }}+ verified consultants</strong> already on the platform.
                <a href="{{ route('consultant-list.index') }}" class="mkt-text-brand hover:underline">Browse the directory →</a>
            </p>
        @endif
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
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
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Emissions, MOCCAE &amp; disclosure per client</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> IFRS / GRI exports on Growth workspaces</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> 1 free trial client to get started</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">2</div>
                    <h3 class="text-xl font-bold text-gray-900">Directory listing</h3>
                </div>
                <p class="text-gray-500 mb-6">Optional public profile so SMEs can find and request you through MenetZero.</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Admin-verified listing on /consultant-list</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> No phone or email shown publicly</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Leads routed through the platform</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Trade license + CV verification</li>
                </ul>
            </div>
            <div class="mkt-feature-card">
                <div class="flex items-center mb-4">
                    <div class="mkt-scope-number">3</div>
                    <h3 class="text-xl font-bold text-gray-900">Wholesale packs</h3>
                </div>
                <p class="text-gray-500 mb-6">Agency pricing designed for practices — not retail client rates.</p>
                <ul class="space-y-3 text-sm text-gray-600">
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Consultant 5 from AED 6,495 / year</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Extra slots without upgrading pack size</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Reporting year unlocks mid-contract</li>
                    <li class="flex items-start"><span class="mkt-checkmark">✓</span> Calendar-year contract alignment</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section">
    <div class="mkt-container">
        <div class="mkt-section-head">
            <h2>How it works</h2>
            <p>From registration to your first managed client in four steps</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['01', 'Register', 'Create your account at /consultant with practice details'],
                ['02', 'Add a client', 'Start with one free trial workspace or purchase an agency pack'],
                ['03', 'Enter data', 'Open client workspaces — emissions, disclosures, and reports'],
                ['04', 'Get listed', 'Complete your directory profile and receive platform leads'],
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
                <span class="mkt-tagline" style="background:rgba(14,165,163,0.2);color:#5eead4;">Public directory</span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white">Your listing, your leads — privacy protected</h2>
                <p class="text-lg text-slate-300 mb-6">
                    Approved consultants appear on our public directory. Visitors see your practice name, specialties, and experience —
                    not your personal phone or email. When someone wants to work with you, they submit a request and we pass the lead to you.
                </p>
                <ul class="space-y-3 text-slate-300 mb-8 text-sm">
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Public profiles build trust for your practice</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> Contact details shared only as qualified leads</li>
                    <li class="flex items-start gap-2"><span class="text-teal-400 mt-0.5">✓</span> MenetZero subscribers get priority intro access</li>
                </ul>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('consultant-list.index') }}" class="mkt-btn mkt-btn-primary">Browse consultant directory</a>
                    <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-white-outline">Apply to get listed</a>
                </div>
            </div>
            <div class="mkt-glass-panel">
                <h3 class="text-xl font-semibold mb-6 text-white">Agency pack snapshot</h3>
                <div class="space-y-0 text-sm">
                    @foreach([
                        ['Consultant 5', 'AED 6,495 / yr'],
                        ['Consultant 10', 'AED 9,990 / yr'],
                        ['Extra slot', 'AED 1,299 / slot'],
                        ['Free trial', '1 client · data entry'],
                    ] as $i => $row)
                        <div class="flex justify-between py-3 {{ $i < 3 ? 'border-b border-white/10' : '' }}">
                            <span class="text-slate-300">{{ $row[0] }}</span>
                            <span class="font-semibold {{ $i === 3 ? 'text-teal-300' : 'text-white' }}">{{ $row[1] }}</span>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-block mt-6">Get started free</a>
            </div>
        </div>
    </div>
</section>

<section class="mkt-section mkt-section-bg">
    <div class="mkt-container max-w-3xl text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to run client workspaces from one login?</h2>
        <p class="text-gray-500 mb-8">
            Register once — your agency hub and directory profile live on the same consultant account.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
        <p class="mt-8 text-xs text-gray-400 max-w-xl mx-auto">
            MenetZero does not verify emissions calculations. Consultant engagements are separate professional services.
        </p>
    </div>
</section>
@endsection
