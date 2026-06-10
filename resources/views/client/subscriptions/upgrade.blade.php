@extends('layouts.app')

@section('title', 'Upgrade Subscription - MenetZero')
@section('page-title', 'Upgrade Subscription')

@section('content')
@php
    // Render order for the selectable plan cards.
    $planOrder = ['client_free', 'client_starter', 'client_growth', 'client_enterprise'];
    $freeMeta = [
        'name' => 'Free',
        'tagline' => 'Try S1&2 + disclosure forms (preview only)',
        'price_display' => 'AED 0',
        'price_sub' => 'Free forever',
        'is_custom' => false,
        'selectable' => true,
        'highlight' => false,
    ];
@endphp
@php $displayCurrency = \App\Services\CurrencyService::displayCurrency(); @endphp
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Choose your plan</h1>
            <p class="mt-2 text-gray-600">
                MOCCAE-ready inventory on Starter; IFRS &amp; GRI downloads on Growth. Prices in {{ $displayCurrency }} (annual, one-time payment).
            </p>
        </div>
        <div class="inline-flex items-center rounded-lg border border-gray-200 overflow-hidden text-sm self-start">
            <a href="{{ route('currency.switch', 'AED') }}"
               class="px-3 py-1.5 {{ $displayCurrency === 'AED' ? 'bg-orange-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">AED</a>
            <a href="{{ route('currency.switch', 'INR') }}"
               class="px-3 py-1.5 {{ $displayCurrency === 'INR' ? 'bg-orange-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">INR (₹)</a>
        </div>
    </div>

    <!-- Current Plan Info -->
    @if($currentSubscription)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 space-y-1">
        <p class="text-sm text-gray-700">
            <strong>Current Plan:</strong> {{ $currentSubscription->plan->plan_name }}
            (Expires: {{ $currentSubscription->expires_at->format('F d, Y') }})
        </p>
        @php
            $scheduled = app(\App\Services\SubscriptionService::class)->getScheduledRenewalPlan($currentSubscription);
            $scheduledWarnings = $scheduled
                ? app(\App\Services\SubscriptionService::class)->getDowngradeWarnings($company->id, $scheduled)
                : [];
        @endphp
        @if($scheduled)
            <p class="text-sm text-amber-800">
                <strong>Scheduled at renewal:</strong> {{ $scheduled->plan_name }}
                (on {{ $currentSubscription->expires_at->format('F d, Y') }})
            </p>
            @foreach($scheduledWarnings as $warning)
                <p class="text-sm text-red-700">⚠ {{ $warning }}</p>
            @endforeach
        @endif
        <p class="text-xs text-gray-500">
            <strong>Upgrades:</strong> unused time on your current plan is credited toward a <em>full year</em> of the new plan (prevents short-term upgrade abuse).
            <strong>Downgrades:</strong> take effect at renewal — no refund for unused time.
        </p>
    </div>
    @endif

    <!-- Plan selection -->
    <form action="{{ route('subscriptions.process-upgrade') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
            @foreach($planOrder as $code)
                @php
                    $plan = $availablePlans[$code] ?? null;
                    $meta = $code === 'client_free' ? $freeMeta : ($planMeta[$code] ?? null);
                    if (!$plan || !$meta) { continue; }
                    $isCurrent = $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id;
                    $change = $planChanges[$code] ?? null;
                    $warnings = $downgradeWarnings[$code] ?? [];
                @endphp
                <div class="relative bg-white rounded-xl border-2 {{ $meta['highlight'] ? 'border-orange-400' : ($isCurrent ? 'border-blue-400' : 'border-gray-200') }} p-5 flex flex-col">
                    @if($meta['highlight'])
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-[11px] font-semibold px-3 py-1 rounded-full">MOST POPULAR</span>
                    @endif
                    @if($isCurrent)
                        <span class="absolute -top-3 right-4 bg-blue-500 text-white text-[11px] font-semibold px-3 py-1 rounded-full">CURRENT</span>
                    @endif

                    @php
                        $cur = \App\Services\CurrencyService::displayCurrency();
                        $isCustom = !empty($meta['is_custom']);
                        if ($isCustom) {
                            $priceText = 'Custom';
                            $priceSub = 'Contact sales for pricing';
                        } elseif ((float) $plan->price_annual <= 0) {
                            $priceText = \App\Services\CurrencyService::format(0, $cur);
                            $priceSub = 'Free forever';
                        } else {
                            $disp = \App\Services\CurrencyService::displayPrice($plan, $cur);
                            $priceText = \App\Services\CurrencyService::format($disp['amount'], $cur);
                            $priceSub = 'per year';
                        }
                    @endphp
                    <h3 class="text-lg font-bold text-gray-900">{{ $plan->plan_name ?? $meta['name'] }}</h3>
                    <p class="text-xs text-gray-500 mb-3 min-h-[2rem]">{{ $meta['tagline'] }}</p>

                    <div class="mb-4">
                        <div class="text-2xl font-extrabold text-gray-900">{{ $priceText }}</div>
                        <div class="text-xs text-gray-500">{{ $priceSub }}</div>
                        @if($change && !$isCurrent && $change['type'] === 'upgrade' && $change['requires_payment'])
                            <div class="text-xs text-emerald-700 mt-1 font-medium">
                                Pay {{ \App\Services\CurrencyService::format($change['charge_amount'], $change['charge_currency']) }} now
                                @if(($change['credit_amount'] ?? 0) > 0)
                                    <span class="text-gray-500">(credit {{ \App\Services\CurrencyService::format($change['credit_amount'], $change['charge_currency']) }} applied)</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Full 1-year term from upgrade date</div>
                        @elseif($change && !$isCurrent && in_array($change['type'], ['downgrade', 'downgrade_to_free']))
                            <div class="text-xs text-amber-700 mt-1">At renewal — no charge now</div>
                        @endif
                        @if(!empty($warnings))
                            <div class="mt-2 rounded border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] text-red-800">
                                @foreach($warnings as $warning)
                                    <p>⚠ {{ $warning }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-auto">
                        @if($isCurrent)
                            <button type="button" disabled class="w-full px-4 py-2 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                Current plan
                            </button>
                        @elseif(!empty($meta['is_custom']))
                            <a href="mailto:sales@menetzero.com?subject=Enterprise%20plan%20enquiry"
                               class="block w-full text-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                                Contact Sales
                            </a>
                        @else
                            <label class="flex items-center justify-center gap-2 w-full px-4 py-2 border-2 {{ in_array($change['type'] ?? '', ['downgrade', 'downgrade_to_free']) ? 'border-amber-400 text-amber-800 hover:bg-amber-50' : 'border-orange-500 text-orange-700 hover:bg-orange-50' }} rounded-lg cursor-pointer text-sm font-medium transition">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" class="text-orange-600 focus:ring-orange-500">
                                <span>
                                    @if(($change['type'] ?? '') === 'upgrade')
                                        Upgrade to {{ $meta['name'] }}
                                    @elseif(in_array($change['type'] ?? '', ['downgrade', 'downgrade_to_free']))
                                        Downgrade to {{ $meta['name'] }}
                                    @else
                                        Select {{ $meta['name'] }}
                                    @endif
                                </span>
                            </label>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @error('plan_id')
            <p class="text-red-600 text-sm mb-4">{{ $message }}</p>
        @enderror

        <!-- Submit (annual billing only) -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-10">
            <div class="flex flex-col gap-5">
                <div>
                    <p class="text-sm font-medium text-gray-700">Billed annually</p>
                    <label class="flex items-center mt-2">
                        <input type="checkbox" name="auto_renew" value="1" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-gray-600 text-sm">Remind me to renew next year (one-time payment each year — no card mandate)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Coupon code <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="e.g. LAUNCH50"
                           class="w-full max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
                    @error('coupon_code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Payment method (only needed for upgrades / new paid plans) --}}
                <div id="payment-method-section">
                    <p class="text-sm font-medium text-gray-700 mb-2">Payment method <span class="text-gray-400 font-normal">(for upgrades &amp; new plans)</span></p>
                    @if($enabledGateways->isEmpty())
                        <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Online payment isn't configured yet. You can switch to the Free plan, or contact sales for paid plans.
                        </p>
                    @else
                        <div class="flex flex-wrap gap-3">
                            @foreach($enabledGateways as $i => $gw)
                                <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 text-sm">
                                    <input type="radio" name="gateway" value="{{ $gw->gateway }}" {{ $i === 0 ? 'checked' : '' }}
                                           class="text-orange-600 focus:ring-orange-500">
                                    <span class="font-medium text-gray-800">{{ $gw->label }}</span>
                                    @if(!$gw->isLive())
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800">Test</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        @error('gateway')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-gray-400 mt-2">
                            @if($displayCurrency === 'AED')
                                Checkout opens in <strong>AED</strong> when enabled on Cashfree. While AED activation is pending, you will be charged the <strong>INR (₹) equivalent</strong> automatically. UAE/international cards supported; UPI &amp; NetBanking are India-only.
                            @else
                                Checkout opens in <strong>INR (₹)</strong>. The exact amount is shown on the payment screen.
                            @endif
                        </p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <a href="{{ route('subscriptions.billing') }}" class="px-5 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Back to billing</a>
                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium">Continue</button>
                </div>
            </div>
        </div>
    </form>

    @include('client.subscriptions.partials.comparison-table', [
        'title' => 'Data & operations',
        'rows' => $operationsRows,
        'columns' => $comparisonColumns,
        'labels' => $comparisonLabels,
        'plans' => $availablePlans,
    ])

    @include('client.subscriptions.partials.comparison-table', [
        'title' => 'Report downloads',
        'rows' => $downloadRows,
        'columns' => $comparisonColumns,
        'labels' => $comparisonLabels,
        'plans' => $availablePlans,
    ])

    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Consultant review packs</h2>
        <p class="text-sm text-gray-500 mb-4">Optional human review via verified consultants. Checkout integration coming soon — contact sales to bundle today.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($consultantAddOns as $addon)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="flex items-baseline justify-between gap-2 mb-2">
                        <h3 class="text-base font-bold text-gray-900">{{ $addon['name'] }}</h3>
                        <span class="text-sm font-semibold text-emerald-700">{{ $addon['price'] }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">{{ $addon['description'] }}</p>
                    <span class="inline-block text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">For {{ $addon['for_plan'] }} subscribers</span>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
