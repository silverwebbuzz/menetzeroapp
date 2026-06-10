@extends('layouts.public')

@section('title', 'MENetZero Consultant — Consultants & Agencies')

@section('content')
{{-- Hero --}}
<section class="py-20 px-4">
    <div class="max-w-4xl mx-auto text-center">
        <div class="mkt-tagline">For sustainability professionals</div>
        <h1 class="text-4xl md:text-6xl font-bold mb-6 text-gray-900 leading-tight">
            Freelance consultant or agency —
            <span class="block text-teal-600">one powerful portal</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-500 mb-8 max-w-2xl mx-auto">
            Manage SME carbon workspaces, purchase agency packs, and list in the MenetZero verified consultant directory for qualified leads.
        </p>
        @if($consultantCount > 0)
            <p class="text-sm text-gray-500 mb-8">
                Join <strong class="text-teal-600">{{ $consultantCount }}+ verified consultants</strong> already on the platform.
                <a href="{{ route('consultant-list.index') }}" class="text-teal-600 hover:underline ml-1">Browse the directory →</a>
            </p>
        @endif
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create consultant account</a>
            <a href="{{ route('consultant.login') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Sign in</a>
        </div>
    </div>
</section>

{{-- Three pillars --}}
<section class="py-20 px-4 mkt-section-bg">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything consultants need in one place</h2>
            <p class="text-xl text-gray-500">Software prepares SME data — you review, sign off, and deliver the trust layer clients expect</p>
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

{{-- How it works --}}
<section class="py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">How it works</h2>
            <p class="text-xl text-gray-500">From registration to your first managed client in four steps</p>
        </div>
        <div class="grid md:grid-cols-4 gap-8">
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

{{-- Directory + CTA split --}}
<section class="py-20 px-4 mkt-section-dark">
    <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-semibold mb-4">Public directory</span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Your listing, your leads — privacy protected</h2>
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
            <div class="bg-white/5 border border-white/10 rounded-2xl p-8">
                <h3 class="text-xl font-semibold mb-6">Agency pack snapshot</h3>
                <div class="space-y-4 text-sm">
                    <div class="flex justify-between py-3 border-b border-white/10">
                        <span class="text-slate-300">Consultant 5</span>
                        <span class="font-semibold text-white">AED 6,495 / yr</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-white/10">
                        <span class="text-slate-300">Consultant 10</span>
                        <span class="font-semibold text-white">AED 9,990 / yr</span>
                    </div>
                    <div class="flex justify-between py-3 border-b border-white/10">
                        <span class="text-slate-300">Extra slot</span>
                        <span class="font-semibold text-white">AED 1,299 / slot</span>
                    </div>
                    <div class="flex justify-between py-3">
                        <span class="text-slate-300">Free trial</span>
                        <span class="font-semibold text-teal-300">1 client · data entry</span>
                    </div>
                </div>
                <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-primary w-full mt-6 justify-center">Get started free</a>
            </div>
        </div>
    </div>
</section>

{{-- Final CTA --}}
<section class="py-20 px-4 mkt-section-bg">
    <div class="max-w-3xl mx-auto text-center">
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
