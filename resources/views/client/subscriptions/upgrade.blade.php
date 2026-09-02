@extends('layouts.app')

@section('title', 'Upgrade Subscription - MenetZero')
@section('page-title', 'Upgrade Subscription')

@section('content')
@php
    // Render order for the selectable plan cards: the live four-tier
    // catalogue, Free through Enterprise.
    $planOrder = ['client_free', 'client_carbon', 'client_esg', 'client_enterprise'];

    // A subscriber grandfathered on a retired plan must still see the card for
    // the plan they are actually paying for -- otherwise the page shows no
    // current plan at all and the only apparent options are changes. Their code
    // is appended rather than inserted so the live ladder still reads in order.
    $currentCode = $currentSubscription?->plan?->plan_code;
    if ($currentCode && ! in_array($currentCode, $planOrder, true)) {
        $planOrder[] = $currentCode;
    }
    $planGuide = config('plans-company');
    $planTaglines = $planGuide['plan_taglines'] ?? [];
    $freeMeta = [
        'name' => 'Free',
        'tagline' => $planTaglines['client_free'] ?? 'Try S1&2 + disclosure forms (preview only)',
        'price_display' => 'AED 0',
        'price_sub' => 'Free forever',
        'is_custom' => false,
        'selectable' => true,
        'highlight' => false,
    ];
@endphp
@php
    $displayCurrency = \App\Services\CurrencyService::displayCurrency();
    $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable();
@endphp
<div class="w-full">
    @if(!$checkoutAvailable)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Paid upgrades coming soon.</strong> Review plans and features below. Stay on Free to explore Scope 1 &amp; 2, or schedule a downgrade at renewal. Paid checkout will open here when online payments go live.
        </div>
    @endif

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="ent-page-title">Choose your plan</h1>
            <p class="ent-page-lead mt-2">
                Simple annual pricing in {{ $displayCurrency }}. Pick based on what you need to download and share — not every feature name on the list below.
            </p>
        </div>
        <div class="inline-flex items-center rounded-lg border border-gray-200 overflow-hidden text-sm self-start">
            <a href="{{ route('currency.switch', 'AED') }}"
               class="px-3 py-1.5 {{ $displayCurrency === 'AED' ? 'bg-brand text-white' : 'text-gray-600 hover:bg-gray-50' }}">AED</a>
            <a href="{{ route('currency.switch', 'INR') }}"
               class="px-3 py-1.5 {{ $displayCurrency === 'INR' ? 'bg-brand text-white' : 'text-gray-600 hover:bg-gray-50' }}">INR (₹)</a>
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
            <strong>Upgrades:</strong> unused time on your current plan is credited toward a full year on the new plan.
            <strong>Downgrades:</strong> take effect at renewal — no refund for unused time.
        </p>
    </div>
    @endif

    <!-- Plan selection -->
    <h2 class="section-heading mb-3">Select a plan</h2>
    <form action="{{ route('subscriptions.process-upgrade') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
            @foreach($planOrder as $code)
                @php
                    $plan = $availablePlans[$code] ?? null;
                    $meta = $code === 'client_free' ? $freeMeta : ($planMeta[$code] ?? null);
                    if ($meta && isset($planTaglines[$code])) {
                        $meta['tagline'] = $planTaglines[$code];
                    }
                    if (!$plan || !$meta) { continue; }
                    $isCurrent = $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id;
                    $change = $planChanges[$code] ?? null;
                    $warnings = $downgradeWarnings[$code] ?? [];
                    $isPaidUpgrade = ($change['type'] ?? '') === 'upgrade' && ($change['requires_payment'] ?? false);
                    $paymentsBlocked = !$checkoutAvailable && $isPaidUpgrade;
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
                            @if($checkoutAvailable)
                                <a href="mailto:{{ site_sales_email() }}?subject=Enterprise%20plan%20enquiry"
                                   class="block w-full text-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                                    Contact Sales
                                </a>
                            @else
                                <button type="button" disabled class="w-full px-4 py-2 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                    Coming soon
                                </button>
                            @endif
                        @elseif($paymentsBlocked)
                            <button type="button" disabled class="w-full px-4 py-2 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                Coming soon
                            </button>
                        @else
                            <label class="flex items-center justify-center gap-2 w-full px-4 py-2 border-2 {{ in_array($change['type'] ?? '', ['downgrade', 'downgrade_to_free']) ? 'border-amber-400 text-amber-800 hover:bg-amber-50' : 'border-orange-500 text-orange-700 hover:bg-orange-50' }} rounded-lg cursor-pointer text-sm font-medium transition">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" class="plan-radio text-orange-600 focus:ring-orange-500"
                                       data-plan-name="{{ $plan->plan_name ?? $meta['name'] }}"
                                       data-price-text="{{ $priceText }}"
                                       data-price-sub="{{ $priceSub }}"
                                       data-change-type="{{ $change['type'] ?? '' }}"
                                       @if($isPaidUpgrade)data-charge="{{ \App\Services\CurrencyService::format($change['charge_amount'], $change['charge_currency']) }}"@endif>
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

        {{-- Checkout footer.

             Previously one full-width band: checkbox, coupon, currency note and
             buttons all stacked hard against the left edge with the actions far
             right, and nowhere on it did the page say WHAT you were buying or
             what you would pay. The order summary now restates the selection
             next to the button that commits to it. --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start mb-10">

            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Payment options</h3>

                <div class="space-y-5">
                    <div>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="auto_renew" value="1" checked
                                   class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="text-sm">
                                <span class="font-medium text-gray-900">Remind me to renew next year</span>
                                <span class="block text-gray-600 mt-0.5">
                                    We will email you before your package expires. No card is stored and nothing is
                                    charged automatically.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1">
                            Coupon code <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="text" id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}"
                               placeholder="e.g. LAUNCH50"
                               class="w-full max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">
                        <p class="text-xs text-gray-500 mt-1">Applied at checkout — the discount is shown before you pay.</p>
                        @error('coupon_code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Razorpay is the only gateway, so there is nothing to choose.
                         The radio group and its 'gateway' form field were removed with
                         Cashfree and Stripe; processUpgrade() resolves razorpay
                         directly and no longer validates a submitted gateway name. --}}
                    <div id="payment-method-section" class="border-t border-gray-100 pt-5">
                        @if($enabledGateways->isEmpty())
                            <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                Online payment isn't configured yet. You can switch to the Free plan, or contact sales for paid plans.
                            </p>
                        @else
                            {{-- This warns that you may be charged in a different currency
                                 than the one displayed, so it must be readable. It was
                                 text-gray-400: the lightest text on the page. --}}
                            <div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                @if($displayCurrency === 'AED')
                                    Checkout opens in <strong>AED</strong>. If AED is still being activated with our
                                    payment provider you will be charged the <strong>INR (&#8377;) equivalent</strong>
                                    automatically, and told the exact amount before you pay.
                                @else
                                    Checkout opens in <strong>INR (&#8377;)</strong>. The exact amount is shown on the
                                    payment screen before you pay.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Order summary. Values are mirrored from the selected plan card by
                 the script below; the server re-resolves everything from plan_id
                 on submit, so nothing here can change what is actually charged. --}}
            <aside class="lg:col-span-1 lg:sticky lg:top-6 bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Order summary</h3>

                <div id="summary-empty" class="text-sm text-gray-500">
                    Select a package above to see what you will pay.
                </div>

                <div id="summary-filled" class="hidden">
                    <div class="text-lg font-bold text-gray-900" id="summary-plan"></div>
                    <div class="text-xs text-gray-500" id="summary-billing">Billed annually</div>

                    <div class="border-t border-gray-100 mt-4 pt-4 flex items-baseline justify-between">
                        <span class="text-sm text-gray-600">Package price</span>
                        <span class="text-sm font-medium text-gray-900" id="summary-price"></span>
                    </div>

                    <div class="border-t border-gray-200 mt-3 pt-3 flex items-baseline justify-between">
                        <span class="text-sm font-semibold text-gray-900">Due today</span>
                        <span class="text-xl font-extrabold text-gray-900" id="summary-total"></span>
                    </div>

                    <p class="text-xs text-gray-500 mt-1" id="summary-note"></p>
                </div>

                <div class="mt-5 space-y-2">
                    <button type="submit"
                            class="w-full px-6 py-2.5 {{ $checkoutAvailable ? 'bg-orange-600 hover:bg-orange-700 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300' }} rounded-lg text-sm font-medium">
                        {{ $checkoutAvailable ? 'Continue to payment' : 'Apply change (downgrades only)' }}
                    </button>
                    <a href="{{ route('subscriptions.billing') }}"
                       class="block w-full text-center px-5 py-2 text-sm text-gray-600 hover:text-gray-900">
                        Back to billing
                    </a>
                    @if(!$checkoutAvailable)
                        <p class="text-xs text-gray-500 text-center">Paid upgrades show as Coming soon until checkout opens.</p>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    @include('client.subscriptions.partials.comparison-table', [
        'title' => 'Data & operations',
        'subtitle' => 'What you can do inside the platform day to day',
        'rows' => $operationsRows,
        'columns' => $comparisonColumns,
        'labels' => $comparisonLabels,
        'plans' => $availablePlans,
    ])

    @include('client.subscriptions.partials.comparison-table', [
        'title' => 'Report downloads',
        'subtitle' => 'PDF and export files you can share externally',
        'rows' => $downloadRows,
        'columns' => $comparisonColumns,
        'labels' => $comparisonLabels,
        'plans' => $availablePlans,
    ])

    @include('plans.partials.human-guide', [
        'guide' => $planGuide,
        'show' => ['examples', 'clarifications', 'intro'],
    ])

    <div class="mb-12">
        <h2 class="section-heading mb-1">Optional consultant review</h2>
        <p class="text-sm text-gray-500 mb-4">Want a verified UAE consultant to review your inventory before you submit? These add-ons pair with your subscription — contact sales to bundle today.</p>
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

    @include('plans.partials.human-guide', [
        'guide' => $planGuide,
        'show' => ['faq'],
    ])

</div>

<script>
(function () {
    // Mirrors the selected plan card into the order summary. Display only —
    // processUpgrade() re-resolves the plan and its price from plan_id, so a
    // stale or tampered value here cannot affect what is charged.
    var radios = document.querySelectorAll('.plan-radio');
    if (!radios.length) { return; }

    var empty  = document.getElementById('summary-empty');
    var filled = document.getElementById('summary-filled');

    function render() {
        var picked = document.querySelector('.plan-radio:checked');

        if (!picked) {
            empty.classList.remove('hidden');
            filled.classList.add('hidden');
            return;
        }

        empty.classList.add('hidden');
        filled.classList.remove('hidden');

        var price  = picked.getAttribute('data-price-text') || '';
        var sub    = picked.getAttribute('data-price-sub') || '';
        var charge = picked.getAttribute('data-charge');
        var type   = picked.getAttribute('data-change-type') || '';

        document.getElementById('summary-plan').textContent = picked.getAttribute('data-plan-name') || '';
        document.getElementById('summary-price').textContent = price;
        document.getElementById('summary-billing').textContent =
            sub === 'per year' ? 'Billed annually' : sub;

        // charge_amount already accounts for credit from the current plan, so
        // it is the figure to show as due today whenever one was supplied.
        document.getElementById('summary-total').textContent = charge || price;

        var note = document.getElementById('summary-note');
        if (charge && charge !== price) {
            note.textContent = 'Includes credit from your current package.';
        } else if (type === 'downgrade' || type === 'downgrade_to_free') {
            note.textContent = 'Takes effect at the end of your current paid period.';
        } else {
            note.textContent = '';
        }
    }

    radios.forEach(function (r) { r.addEventListener('change', render); });
    render();
})();
</script>

@endsection
