@extends('layouts.public')

@section('title', 'Explore Free — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
<section class="mkt-hero">
    <div class="mkt-container max-w-3xl">
        <div class="mkt-tagline">No public prices · explore first</div>
        <h1>Explore Free</h1>
        <p class="mkt-lead">
            Start with MENetZero Free — measure Scope 1 &amp; 2, try Scope 3 (one entry per category),
            and download watermarked trial reports. When you need official clean exports or more capacity,
            request a package from inside your account. Pricing is confirmed offline by our team.
        </p>
        <div class="flex flex-wrap justify-center gap-3 mt-8">
            <a href="{{ route('register') }}" class="mkt-btn mkt-btn-primary mkt-btn-lg">Company — Explore Free</a>
            <a href="{{ route('consultant.register') }}" class="mkt-btn mkt-btn-outline mkt-btn-lg">Consultant — Explore Free</a>
        </div>
        <p class="text-sm text-gray-500 mt-6 max-w-xl mx-auto">
            Already registered?
            <a href="{{ route('login') }}" class="mkt-text-brand hover:underline">Company sign in</a>
            ·
            <a href="{{ route('consultant.login') }}" class="mkt-text-brand hover:underline">Consultant sign in</a>
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
            <h2>What’s included on Free</h2>
            <p>Enough to learn the workflow — not for final regulatory submission without a package</p>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 mb-10">
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Scope 1 &amp; 2 data entry (full)</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Scope 3 — all categories, one entry each</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Watermarked GHG / MOCCAE / Excel / IEQT trial downloads</li>
            <li class="flex gap-2"><span class="mkt-checkmark">✓</span> Disclosure form previews</li>
            <li class="flex gap-2"><span class="text-gray-400">—</span> Official clean PDF packs after your package is activated</li>
        </ul>
        <div class="rounded-xl border border-teal-100 bg-teal-50/60 p-5 text-sm text-teal-950">
            <strong>Consultants:</strong> Free includes one managed client entity. Request more entities from the consultant portal after you sign in — preferential annual rates are confirmed offline.
        </div>
    </div>
</section>
@endsection
