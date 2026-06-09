@extends('layouts.public')

@section('title', 'Pricing — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
@php use App\Services\CurrencyService; @endphp
<div class="max-w-6xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-gray-900">Simple annual pricing for UAE carbon compliance</h1>
        <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
            Try Scope 1 &amp; 2 and all disclosure forms free. Upgrade to export reports, bulk import, and connect with consultants.
        </p>

        <div class="mt-5 inline-flex items-center rounded-lg border border-gray-200 overflow-hidden text-sm">
            <a href="{{ route('currency.switch', 'AED') }}" class="px-4 py-1.5 {{ $currency === 'AED' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">AED</a>
            <a href="{{ route('currency.switch', 'INR') }}" class="px-4 py-1.5 {{ $currency === 'INR' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">INR (₹)</a>
        </div>
        <p class="mt-2 text-xs text-gray-400">Showing prices in {{ $currency }}. Secure checkout in INR (₹) where applicable.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-14">
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
            <div class="relative bg-white rounded-xl border-2 {{ $isGrowth ? 'border-teal-500 shadow-lg' : 'border-gray-200' }} p-6 flex flex-col">
                @if($isGrowth)
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-teal-600 text-white text-[11px] font-semibold px-3 py-1 rounded-full">MOST POPULAR</span>
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
                        <li><span class="text-teal-500 mr-1">✓</span> Scope 1 &amp; 2 + disclosure forms</li>
                        <li><span class="text-teal-500 mr-1">✓</span> In-app preview (no downloads)</li>
                        <li><span class="text-gray-400 mr-1">—</span> Scope 3 locked</li>
                    @elseif($code === 'client_starter')
                        <li><span class="text-teal-500 mr-1">✓</span> GHG, MOCCAE, Excel, IEQT</li>
                        <li><span class="text-teal-500 mr-1">✓</span> Bulk import + help guide</li>
                        <li><span class="text-teal-500 mr-1">✓</span> Scope 3 preview</li>
                    @elseif($isGrowth)
                        <li><span class="text-teal-500 mr-1">✓</span> Everything in Starter</li>
                        <li><span class="text-teal-500 mr-1">✓</span> IFRS S1/S2 PDF</li>
                        <li><span class="text-teal-500 mr-1">✓</span> GRI PDF + content index</li>
                    @else
                        <li><span class="text-teal-500 mr-1">✓</span> Unlimited Scope 3</li>
                        <li><span class="text-teal-500 mr-1">✓</span> Multi-site &amp; API</li>
                        <li><span class="text-teal-500 mr-1">✓</span> Dedicated support</li>
                    @endif
                </ul>

                @if($isEnterprise)
                    <a href="mailto:{{ $settings['sales_email'] ?? 'sales@menetzero.com' }}?subject=Enterprise%20enquiry"
                       class="block text-center px-4 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">Contact sales</a>
                @else
                    <a href="{{ route('register') }}"
                       class="block text-center px-4 py-2.5 {{ $isGrowth ? 'bg-teal-600 hover:bg-teal-700' : 'bg-gray-900 hover:bg-gray-800' }} text-white rounded-lg text-sm font-medium">
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
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Consultant review packs</h2>
        <p class="text-sm text-gray-500 mb-5">Optional human review via verified UAE partners — software prepares, consultants sign off.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($consultantAddOns as $addon)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
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
        Third-party verification is available through our consultant partners — not a substitute for MOCCAE-authorised verification unless contracted.
    </p>
    <p class="text-center text-xs text-gray-400 mt-4">
        Prices exclude applicable taxes.
        <a href="{{ route('terms') }}" class="underline">Terms</a> ·
        <a href="{{ route('refunds') }}" class="underline">Refunds</a>
    </p>
</div>
@endsection
