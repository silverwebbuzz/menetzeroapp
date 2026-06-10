@extends('layouts.public')

@section('title', 'Pricing — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
@php use App\Services\CurrencyService; @endphp

<section class="mkt-hero">
    <div class="mkt-container max-w-4xl">
        <div class="mkt-tagline">Simple annual pricing</div>
        <h1>UAE carbon compliance plans</h1>
        <p class="mkt-lead">
            Try Scope 1 &amp; 2 and all disclosure forms free. Upgrade to export reports, bulk import, and connect with consultants.
        </p>
        <div class="mt-6 flex flex-col items-center gap-2">
            <span class="mkt-currency-toggle">
                <a href="{{ route('currency.switch', 'AED') }}" class="{{ $currency === 'AED' ? 'active' : '' }}">AED</a>
                <a href="{{ route('currency.switch', 'INR') }}" class="{{ $currency === 'INR' ? 'active' : '' }}">INR (₹)</a>
            </span>
            <p class="text-xs text-gray-400">Showing prices in {{ $currency }}. Secure checkout in INR (₹) where applicable.</p>
        </div>
    </div>
</section>

<section class="mkt-section pt-0">
    <div class="mkt-container">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-14">
            @foreach($comparisonColumns as $code)
                @php
                    $plan = $plansByCode[$code] ?? null;
                    $label = $comparisonLabels[$code] ?? ['name' => $code, 'tagline' => ''];
                    $isGrowth = $code === 'client_growth';
                    $isEnterprise = $code === 'client_enterprise';
                    $isFree = $code === 'client_free';
                @endphp
                @if(!$plan && !$isFree)
                    @continue
                @endif
                <div class="mkt-pricing-card {{ $isGrowth ? 'popular' : '' }}">
                    @if($isGrowth)
                        <span class="mkt-pricing-badge">MOST POPULAR</span>
                    @endif
                    <h3 class="text-lg font-bold text-gray-900">{{ $plan->plan_name ?? $label['name'] }}</h3>
                    <p class="text-xs text-gray-500 mb-4 min-h-[2.5rem]">{{ $plan->description ?? $label['tagline'] }}</p>

                    <div class="mb-5">
                        @if($isEnterprise)
                            <div class="text-2xl font-extrabold text-gray-900">Custom</div>
                            <div class="text-xs text-gray-500">AED 20,000+ / year</div>
                        @elseif($isFree || !$plan || (float) ($plan->price_annual ?? 0) <= 0)
                            <div class="text-2xl font-extrabold text-gray-900">{{ CurrencyService::format(0, $currency) }}</div>
                            <div class="text-xs text-gray-500">Free forever</div>
                        @else
                            @php $price = CurrencyService::displayPrice($plan, $currency); @endphp
                            <div class="text-2xl font-extrabold text-gray-900">{{ CurrencyService::format($price['amount'], $currency) }}</div>
                            <div class="text-xs text-gray-500">per year</div>
                        @endif
                    </div>

                    <ul class="space-y-2 text-sm text-gray-600 flex-1 mb-5">
                        @if($isFree)
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Scope 1 &amp; 2 + disclosure forms</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> In-app preview (no downloads)</li>
                            <li class="flex items-start"><span class="text-gray-400 mr-2">—</span> Scope 3 locked</li>
                        @elseif($code === 'client_starter')
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> GHG, MOCCAE, Excel, IEQT</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Bulk import + help guide</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Scope 3 preview</li>
                        @elseif($isGrowth)
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Everything in Starter</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> IFRS S1/S2 PDF</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> GRI PDF + content index</li>
                        @else
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Unlimited Scope 3</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Multi-site &amp; API</li>
                            <li class="flex items-start"><span class="mkt-checkmark">✓</span> Dedicated support</li>
                        @endif
                    </ul>

                    @if($isEnterprise)
                        <a href="mailto:{{ $settings['sales_email'] ?? 'sales@menetzero.com' }}?subject=Enterprise%20enquiry"
                           class="mkt-btn mkt-btn-dark mkt-btn-block">Contact sales</a>
                    @else
                        <a href="{{ route('register') }}"
                           class="mkt-btn mkt-btn-block {{ $isGrowth ? 'mkt-btn-primary' : 'mkt-btn-dark' }}">
                            Get started
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        @include('client.subscriptions.partials.comparison-table', [
            'title' => 'Compare — data & operations',
            'rows' => $operationsRows,
            'columns' => $comparisonColumns,
            'labels' => $comparisonLabels,
            'plans' => $plansByCode,
        ])

        @include('client.subscriptions.partials.comparison-table', [
            'title' => 'Compare — report downloads',
            'rows' => $downloadRows,
            'columns' => $comparisonColumns,
            'labels' => $comparisonLabels,
            'plans' => $plansByCode,
        ])

        <div class="mb-12">
            <div class="mkt-section-head text-left mb-8" style="margin-bottom:2rem;">
                <h2 class="text-left" style="max-width:none;margin:0;">Consultant review packs</h2>
                <p class="text-left" style="max-width:none;margin:0.5rem 0 0;">Optional human review via verified UAE consultants — software prepares, consultants sign off.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($consultantAddOns as $addon)
                    <div class="mkt-feature-card" style="padding:1.25rem;">
                        <div class="flex justify-between items-baseline gap-2 mb-2">
                            <h3 class="font-bold text-gray-900">{{ $addon['name'] }}</h3>
                            <span class="text-sm font-semibold text-teal-700">{{ $addon['price'] }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ $addon['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="text-center text-sm text-gray-500 max-w-2xl mx-auto">
            Reports are draft working papers for your compliance workflow.
            Third-party verification is available through our <a href="{{ route('consultant-list.index') }}" class="text-teal-600 hover:underline">consultant directory</a> — not a substitute for MOCCAE-authorised verification unless contracted.
        </p>
        <p class="text-center text-xs text-gray-400 mt-4">
            Prices exclude applicable taxes.
            <a href="{{ route('terms') }}" class="underline">Terms</a> ·
            <a href="{{ route('refunds') }}" class="underline">Refunds</a>
        </p>
    </div>
</section>
@endsection
