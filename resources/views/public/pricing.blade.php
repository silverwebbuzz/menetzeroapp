@extends('layouts.public')

@section('title', 'Pricing — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
@php
    // See config/portals.php — consultant links hidden, routes still live.
    $showConsultant = config('portals.consultant_public');
@endphp
<section class="mkt-hero">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-tagline">Self-serve · pay online in AED</div>
        <h1>Pricing</h1>
        <p class="mkt-lead">
            Scope 1 &amp; 2 with submission-ready MOCCAE and GHG exports from AED 499 a year.
            Add Scope 3, more sites or the IFRS, GRI and UAE ESG frameworks as your reporting grows —
            upgrade from inside your account whenever you need to.
        </p>
        <div class="flex flex-wrap justify-center gap-3 mt-8">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Create your company account</a>
            @if($showConsultant)
                <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Consultant sign up</a>
            @endif
        </div>
        <p class="text-sm text-gray-500 mt-6 max-w-xl mx-auto">
            Already registered?
            <a href="{{ route('login') }}" class="mkt-text-brand hover:underline">{{ $showConsultant ? 'Company sign in' : 'Sign in' }}</a>
            @if($showConsultant)
                ·
                <a href="{{ route('consultant.login') }}" class="mkt-text-brand hover:underline">Consultant sign in</a>
            @endif
        </p>
        <p class="text-sm text-gray-500 mt-4">
            Questions?
            <a href="{{ route('contact') }}" class="mkt-text-brand hover:underline">Contact us</a>
            for a demo account or package discussion.
        </p>
    </div>
</section>

<section class="mkt-section pt-0">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-section-head">
            <h2>What’s included on Essential</h2>
            <p>Everything a single-site company needs to measure Scope 1 &amp; 2 and file it</p>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 mb-10">
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Scope 1 &amp; 2 data entry (full)</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Clean MOCCAE, GHG and Excel exports — no watermark</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> One location, three users, two years of history</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Disclosure form previews</li>
            <li class="flex gap-2"><span class="text-gray-400">—</span> Scope 3, the IEQT export and bulk import are on Carbon</li>
        </ul>
        <div class="rounded-xl border border-teal-100 bg-teal-50/60 p-5 text-sm text-teal-950">
            <strong>Trying it first?</strong> Create an account and enter your Scope 1 &amp; 2 data at no cost — downloads
            are watermarked trial files until you subscribe.
        </div>
    </div>
</section>
@endsection
