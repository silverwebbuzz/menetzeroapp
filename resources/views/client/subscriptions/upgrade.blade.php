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
<div class="w-full max-w-7xl mx-auto">
    @if(!$checkoutAvailable)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>Paid upgrades coming soon.</strong> Review plans and features below. Stay on Free to explore Scope 1 &amp; 2, or schedule a downgrade at renewal. Paid checkout will open here when online payments go live.
        </div>
    @endif

    <!-- Header -->
    <div class="mb-6">
        <h1 class="ent-page-title">Choose your plan</h1>
        <p class="ent-page-lead mt-2 max-w-3xl">
            Simple annual pricing in {{ $displayCurrency }}. Pick based on what you need to download and share — every plan includes your full Scope 1 &amp; 2 inventory.
        </p>
        {{-- Currency toggle removed: AED is the only supported currency, so a
             switch with one option is a control that cannot do anything. --}}
    </div>

    <!-- Current Plan Info -->
    @if($currentSubscription)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 space-y-1">
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
            <strong>Upgrades:</strong> apply immediately and cover the same reporting year you are working on —
            they do not add a second year.
            <strong>Downgrades:</strong> take effect at renewal — no refund for unused time.
        </p>
    </div>
    @endif

    {{-- The page is one form in two numbered steps: pick a plan, then check
         out. Everything needed to decide -- the cards, their headline features
         and the full comparison tables -- sits inside step 1, above the
         payment box. The tables used to come after the checkout footer, which
         asked people to commit before the page had told them what they were
         buying. --}}
    <form action="{{ route('subscriptions.process-upgrade') }}" method="POST">
        @csrf

        <!-- Step 1: plan selection -->
        <section id="plan-selection" class="mb-12 scroll-mt-24">
            <div class="flex items-baseline gap-3 mb-4">
                <span class="flex-none w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold flex items-center justify-center">1</span>
                <div>
                    <h2 class="section-heading mb-0">Select a plan</h2>
                    <p class="text-sm text-gray-500">One package covers one reporting year. You can change plan later — upgrades apply immediately, downgrades at renewal.</p>
                </div>
            </div>

            {{-- Decision helper, above the cards rather than below the form.
                 These worked examples ("a one-location cafe...", "a logistics
                 SME...") are what most people actually choose on, so they have
                 to be readable BEFORE the prices, not three sections after the
                 pay button. Collapsed by default so it costs one line of height
                 to anyone who already knows what they want. --}}
            @php $chooserExamples = $planGuide['examples'] ?? []; @endphp
            @if(!empty($chooserExamples))
                <details class="mb-5 bg-white rounded-xl border border-gray-200 group">
                    <summary class="flex items-center justify-between gap-3 px-5 py-3.5 cursor-pointer select-none">
                        <span class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <svg class="w-4 h-4 flex-none text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Not sure which one? Find the story closest to yours
                        </span>
                        <svg class="w-5 h-5 flex-none text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="border-t border-gray-100 p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        @foreach($chooserExamples as $example)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <span class="inline-block text-[11px] font-semibold uppercase tracking-wide text-orange-700 bg-orange-50 border border-orange-200 px-2 py-0.5 rounded mb-2">{{ $example['plan'] ?? 'Plan' }}</span>
                                <p class="text-sm text-gray-800 mb-2">{{ $example['scenario'] ?? '' }}</p>
                                <p class="text-xs text-gray-600 mb-0"><strong class="text-gray-800">You get:</strong> {{ $example['you_get'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 items-stretch">
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
                        $highlights = $cardHighlights[$code] ?? [];
                    @endphp
                    <div class="relative bg-white rounded-xl border-2 {{ $meta['highlight'] ? 'border-orange-400 shadow-sm' : ($isCurrent ? 'border-blue-400' : 'border-gray-200') }} p-5 pt-7 flex flex-col h-full">
                        @if($meta['highlight'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-[11px] font-semibold px-3 py-1 rounded-full whitespace-nowrap">MOST POPULAR</span>
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

                        {{-- Fixed-height name/tagline/price block so the feature
                             lists and buttons line up across all four cards
                             regardless of how long a tagline runs. --}}
                        <h3 class="text-lg font-bold text-gray-900">{{ $plan->plan_name ?? $meta['name'] }}</h3>
                        <p class="text-xs text-gray-500 mt-1 mb-4 min-h-[2.5rem]">{{ $meta['tagline'] }}</p>

                        <div class="pb-4 mb-4 border-b border-gray-100 min-h-[4.5rem]">
                            <div class="text-3xl font-extrabold text-gray-900 leading-none">{{ $priceText }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $priceSub }}</div>
                            @if($change && !$isCurrent && $change['type'] === 'upgrade' && $change['requires_payment'])
                                <div class="text-xs text-emerald-700 mt-2 font-medium">
                                    Pay {{ \App\Services\CurrencyService::format($change['charge_amount'], $change['charge_currency']) }} now
                                    @if(($change['credit_amount'] ?? 0) > 0)
                                        <span class="text-gray-500">(credit {{ \App\Services\CurrencyService::format($change['credit_amount'], $change['charge_currency']) }} applied)</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-gray-400 mt-0.5">Covers your current reporting year</div>
                            @elseif($change && !$isCurrent && in_array($change['type'], ['downgrade', 'downgrade_to_free']))
                                <div class="text-xs text-amber-700 mt-2">At renewal — no charge now</div>
                            @endif
                        </div>

                        {{-- Headline features on the card itself. The full
                             tables below stay authoritative; these are the four
                             lines that decide most choices. --}}
                        @if(!empty($highlights))
                            <ul class="space-y-2 mb-5 text-sm text-gray-700">
                                @foreach($highlights as $highlight)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-4 h-4 mt-0.5 flex-none text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>{{ $highlight }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if(!empty($warnings))
                            <div class="mb-4 rounded border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] text-red-800">
                                @foreach($warnings as $warning)
                                    <p>⚠ {{ $warning }}</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-auto pt-1">
                            @if($isCurrent)
                                <button type="button" disabled class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                    Current plan
                                </button>
                            @elseif(!empty($meta['is_custom']))
                                @if($checkoutAvailable)
                                    <a href="mailto:{{ site_sales_email() }}?subject=Enterprise%20plan%20enquiry"
                                       class="block w-full text-center px-4 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                                        Contact Sales
                                    </a>
                                @else
                                    <button type="button" disabled class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                        Coming soon
                                    </button>
                                @endif
                            @elseif($paymentsBlocked)
                                <button type="button" disabled class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 rounded-lg cursor-not-allowed text-sm font-medium">
                                    Coming soon
                                </button>
                            @else
                                <label class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border-2 {{ in_array($change['type'] ?? '', ['downgrade', 'downgrade_to_free']) ? 'border-amber-400 text-amber-800 hover:bg-amber-50' : 'border-orange-500 text-orange-700 hover:bg-orange-50' }} rounded-lg cursor-pointer text-sm font-medium transition">
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
                <p class="text-red-600 text-sm mt-4">{{ $message }}</p>
            @enderror

            {{-- Full comparison, collapsed by default. Open it and you get both
                 tables; leave it shut and the cards plus the checkout box stay
                 within one screen. --}}
            <details class="mt-6 bg-white rounded-xl border border-gray-200 group">
                <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer select-none">
                    <span>
                        <span class="text-sm font-semibold text-gray-900">Compare every feature</span>
                        <span class="block text-xs text-gray-500 mt-0.5">Full line-by-line breakdown of limits, tools and downloadable reports</span>
                    </span>
                    <svg class="w-5 h-5 flex-none text-gray-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>

                <div class="border-t border-gray-100 px-5 pt-5">
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
                </div>
            </details>
        </section>

        <!-- Step 2: checkout -->
        <section id="checkout-section" class="mb-12 scroll-mt-24">
            <div class="flex items-baseline gap-3 mb-4">
                <span class="flex-none w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold flex items-center justify-center">2</span>
                <div>
                    <h2 class="section-heading mb-0">Confirm and pay</h2>
                    <p class="text-sm text-gray-500">Review what you selected before anything is charged.</p>
                </div>
            </div>

            {{-- Checkout footer.

                 Previously one full-width band: checkbox, coupon, currency note and
                 buttons all stacked hard against the left edge with the actions far
                 right, and nowhere on it did the page say WHAT you were buying or
                 what you would pay. The order summary now restates the selection
                 next to the button that commits to it. --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

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
                                {{-- AED is the only currency now, so this no longer warns
                                     about a possible switch -- it just confirms what will
                                     be charged. The old INR-fallback wording was removed
                                     with the fallback itself. --}}
                                <div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                                    Checkout opens in <strong>AED</strong>. The exact amount is shown on the
                                    payment screen before you pay.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Order summary. Values are mirrored from the selected plan card by
                     the script below; the server re-resolves everything from plan_id
                     on submit, so nothing here can change what is actually charged. --}}
                {{-- Not sticky any more: the sticky element on this page is the
                     bar at the bottom of the viewport, which carries the same
                     selection and the same submit button and follows the reader
                     everywhere, including back up over the cards. --}}
                <aside class="lg:col-span-1 bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Order summary</h3>

                    <div id="summary-empty" class="text-sm text-gray-500">
                        No plan selected yet.
                        <a href="#plan-selection" class="text-orange-700 font-medium hover:underline">Choose a plan</a>
                        to see what you will pay.
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

                        <a href="#plan-selection" class="inline-block mt-3 text-xs font-medium text-orange-700 hover:underline">
                            Change plan
                        </a>
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
        </section>

        {{-- Sticky selection bar.

             The complaint this answers: with the cards at the top and the pay
             button far below, choosing meant scrolling down to check a price
             and back up to pick, with nothing on screen saying what was
             currently selected. This bar is fixed to the bottom of the
             viewport for the whole page, so the current selection, the amount
             due and the submit button are always visible -- and "Change plan"
             jumps back to the cards instead of asking the reader to hunt for
             them. It hides itself when the real checkout box is on screen, so
             the two submit buttons are never both visible at once.

             It sits inside the form on purpose: its button is a real submit
             for the same form, not a script that clicks the other one. --}}
        <div id="sticky-bar"
             class="hidden fixed bottom-0 inset-x-0 z-20 border-t border-gray-200 bg-white/95 backdrop-blur shadow-[0_-2px_12px_rgba(0,0,0,0.06)] print:hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="min-w-0 flex-1">
                    <div id="sticky-empty" class="text-sm text-gray-600">
                        <span class="font-medium text-gray-900">No plan selected.</span>
                        <span class="hidden sm:inline">Pick one above to continue.</span>
                    </div>
                    <div id="sticky-filled" class="hidden items-baseline gap-2 flex-wrap">
                        <span class="text-sm font-semibold text-gray-900" id="sticky-plan"></span>
                        <span class="text-xs text-gray-500" id="sticky-note"></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-none">
                    <div id="sticky-total-wrap" class="hidden text-right">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500 leading-none">Due today</div>
                        <div class="text-lg font-extrabold text-gray-900 leading-tight" id="sticky-total"></div>
                    </div>
                    <a href="#plan-selection" id="sticky-change"
                       class="hidden text-sm font-medium text-gray-600 hover:text-gray-900 whitespace-nowrap">
                        Change plan
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 {{ $checkoutAvailable ? 'bg-orange-600 hover:bg-orange-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} rounded-lg text-sm font-medium whitespace-nowrap">
                        {{ $checkoutAvailable ? 'Continue to payment' : 'Apply change' }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Reference material only. The worked examples moved up next to the
         cards -- they are how people choose, so they belong with the choosing.
         What is left here is what you read after deciding, or not at all.

         pb-28 keeps the last FAQ answer clear of the fixed bar. --}}
    <section class="border-t border-gray-200 pt-10 pb-28">
        @include('plans.partials.human-guide', [
            'guide' => $planGuide,
            'show' => ['intro', 'clarifications', 'faq'],
        ])
    </section>

</div>

<script>
(function () {
    // Mirrors the selected plan card into the order summary and the sticky
    // bar. Display only -- processUpgrade() re-resolves the plan and its price
    // from plan_id, so a stale or tampered value here cannot affect what is
    // charged.
    var radios = document.querySelectorAll('.plan-radio');
    if (!radios.length) { return; }

    var empty  = document.getElementById('summary-empty');
    var filled = document.getElementById('summary-filled');

    var bar          = document.getElementById('sticky-bar');
    var barEmpty     = document.getElementById('sticky-empty');
    var barFilled    = document.getElementById('sticky-filled');
    var barTotalWrap = document.getElementById('sticky-total-wrap');
    var barChange    = document.getElementById('sticky-change');

    // `hidden` and `flex` are both display utilities, so toggling `hidden` on a
    // flex row depends on stylesheet order. Setting display directly avoids the
    // question entirely.
    function show(el, on, display) {
        if (!el) { return; }
        el.classList.toggle('hidden', !on);
        el.style.display = on ? (display || '') : '';
    }

    function render() {
        var picked = document.querySelector('.plan-radio:checked');

        if (!picked) {
            show(empty, true);
            show(filled, false);
            show(barEmpty, true);
            show(barFilled, false);
            show(barTotalWrap, false);
            show(barChange, false);
            return;
        }

        show(empty, false);
        show(filled, true);
        show(barEmpty, false);
        show(barFilled, true, 'flex');
        show(barChange, true);

        var name   = picked.getAttribute('data-plan-name') || '';
        var price  = picked.getAttribute('data-price-text') || '';
        var sub    = picked.getAttribute('data-price-sub') || '';
        var charge = picked.getAttribute('data-charge');
        var type   = picked.getAttribute('data-change-type') || '';

        // charge_amount already accounts for credit from the current plan, so
        // it is the figure to show as due today whenever one was supplied.
        var total = charge || price;

        document.getElementById('summary-plan').textContent = name;
        document.getElementById('summary-price').textContent = price;
        document.getElementById('summary-billing').textContent =
            sub === 'per year' ? 'Billed annually' : sub;
        document.getElementById('summary-total').textContent = total;

        var noteText;
        if (charge && charge !== price) {
            noteText = 'Includes credit from your current package.';
        } else if (type === 'downgrade' || type === 'downgrade_to_free') {
            noteText = 'Takes effect at the end of your current paid period.';
        } else {
            noteText = '';
        }
        document.getElementById('summary-note').textContent = noteText;

        document.getElementById('sticky-plan').textContent = name;
        document.getElementById('sticky-note').textContent =
            (type === 'downgrade' || type === 'downgrade_to_free')
                ? 'Applies at renewal — no charge now'
                : (sub === 'per year' ? 'Billed annually' : sub);

        // A downgrade charges nothing today, so a "Due today" figure next to it
        // would be actively misleading.
        var isDowngrade = (type === 'downgrade' || type === 'downgrade_to_free');
        show(barTotalWrap, !isDowngrade);
        document.getElementById('sticky-total').textContent = total;
    }

    radios.forEach(function (r) { r.addEventListener('change', render); });
    render();

    // The bar duplicates the checkout box's submit button, so it should not be
    // on screen at the same time as the real one -- two identical buttons in
    // view is its own kind of confusing. IntersectionObserver keeps it hidden
    // while the checkout box is visible; without support, the bar simply stays
    // on, which is the safe fallback.
    var checkout = document.getElementById('checkout-section');
    if (bar && checkout && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            show(bar, !entries[0].isIntersecting);
        }, { threshold: 0.2 }).observe(checkout);
    } else {
        show(bar, true);
    }
})();
</script>

@endsection
