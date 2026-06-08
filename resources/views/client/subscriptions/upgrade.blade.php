@extends('layouts.app')

@section('title', 'Upgrade Subscription - MenetZero')
@section('page-title', 'Upgrade Subscription')

@section('content')
@php
    // Render order for the selectable plan cards.
    $planOrder = ['client_free', 'client_starter', 'client_growth', 'client_enterprise'];
    $freeMeta = [
        'name' => 'Free',
        'tagline' => 'Scope 1 & 2 · 1 location · 2 users',
        'price_display' => 'AED 0',
        'price_sub' => 'Free forever',
        'is_custom' => false,
        'selectable' => true,
        'highlight' => false,
    ];
@endphp
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Choose your plan</h1>
        <p class="mt-2 text-gray-600">Scope 1 &amp; 2 carbon accounting. Prices in AED (billed annually).</p>
    </div>

    <!-- Current Plan Info -->
    @if($currentSubscription)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-gray-700">
            <strong>Current Plan:</strong> {{ $currentSubscription->plan->plan_name }}
            (Expires: {{ $currentSubscription->expires_at->format('F d, Y') }})
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
                            <label class="flex items-center justify-center gap-2 w-full px-4 py-2 border-2 border-orange-500 text-orange-700 rounded-lg hover:bg-orange-50 cursor-pointer text-sm font-medium transition">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" class="text-orange-600 focus:ring-orange-500">
                                <span>Select {{ $meta['name'] }}</span>
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
                        <span class="ml-2 text-gray-600 text-sm">Auto-renew each year (uncheck to cancel after this year)</span>
                    </label>
                </div>

                {{-- Payment method (only needed for paid plans) --}}
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Payment method <span class="text-gray-400 font-normal">(for paid plans)</span></p>
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
                        <p class="text-xs text-gray-400 mt-2">Payments are processed securely in INR (₹). The exact amount is shown on the payment screen.</p>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                    <a href="{{ route('subscriptions.index') }}" class="px-5 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium">Continue</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Feature comparison -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Compare plans</h2>
        <p class="text-sm text-gray-500 mb-4">
            Features marked <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-medium">Coming soon</span> are on our roadmap.
        </p>
        <div class="overflow-x-auto bg-white rounded-xl border border-gray-200">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="text-left font-semibold text-gray-700 px-5 py-3 w-1/3">Feature</th>
                        @foreach($comparisonColumns as $code)
                            <th class="text-center font-semibold text-gray-700 px-5 py-3">{{ $availablePlans[$code]->plan_name ?? $planMeta[$code]['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($featureRows as $row)
                        <tr>
                            <td class="px-5 py-3 text-gray-800">
                                <span class="font-medium">{{ $row['label'] }}</span>
                                @if(!empty($row['coming_soon']))
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-medium align-middle">Coming soon</span>
                                @endif
                            </td>
                            @foreach($comparisonColumns as $code)
                                @php $cell = $row['cells'][$code] ?? false; @endphp
                                <td class="px-5 py-3 text-center {{ !empty($row['coming_soon']) ? 'text-gray-400' : 'text-gray-700' }}">
                                    @if($cell === true)
                                        <svg class="w-5 h-5 mx-auto {{ !empty($row['coming_soon']) ? 'text-gray-300' : 'text-green-500' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    @elseif($cell === false)
                                        <span class="text-gray-300">—</span>
                                    @else
                                        {{ $cell }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scope 3 Add-On -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-1">Scope 3 Add-On</h2>
        <p class="text-sm text-gray-500 mb-4">
            Scope 3 (value-chain) emissions are offered as a separate service — not bundled into standard plans.
            Items marked <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-medium">Coming soon</span> are on the roadmap.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach($scope3AddOns as $addon)
                <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-col">
                    <h3 class="text-base font-bold text-gray-900 mb-1">{{ $addon['name'] }}</h3>
                    <div class="text-sm font-semibold text-emerald-700 mb-3">{{ $addon['price_display'] }}</div>
                    <ul class="space-y-1.5 text-sm text-gray-600 flex-1">
                        @foreach($addon['includes'] as $item)
                            <li class="flex items-start {{ !empty($item['soon']) ? 'text-gray-400' : '' }}">
                                @if(!empty($item['soon']))
                                    <svg class="w-4 h-4 text-gray-300 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-emerald-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                @endif
                                <span>
                                    {{ $item['label'] }}
                                    @if(!empty($item['soon']))
                                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-medium">Coming soon</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="mailto:sales@menetzero.com?subject=Scope%203%20Add-On%20enquiry"
                       class="mt-4 block w-full text-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                        Contact Sales
                    </a>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
