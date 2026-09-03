@extends('consultant.layouts.app')

@section('title', 'Plans & Billing')
@section('page-title', 'Plans & Billing')

@section('content')
@php
    $isTrial = $subscription?->isFreeTrial() ?? false;
    $currentUsed = (int) ($slotSummary['used'] ?? 0);
    $currentLimit = $slotSummary['limit'] ?? '—';
@endphp

<div class="w-full max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('consultant.dashboard') }}" class="text-sm text-brand hover:underline">&larr; Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Plans &amp; Billing</h1>
        <p class="mt-2 text-gray-600">
            Your current pack, buying client slots, and your invoices.
        </p>
    </div>

    {{-- CURRENT PLAN. The page previously stated only "Current: <plan> ·
         used/limit", with no term dates -- an agency could not tell when its
         capacity lapses, which is the single most important fact on a billing
         page. expires_at was already on the model and simply unused. --}}
    @if($subscription)
        @php
            $expiresAt = $subscription->expires_at;
            $daysLeft = $expiresAt ? (int) now()->startOfDay()->diffInDays($expiresAt, false) : null;
            $isExpired = $daysLeft !== null && $daysLeft < 0;
            $isExpiring = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 45;
        @endphp

        <div class="bg-white rounded-xl border {{ $isExpired ? 'border-red-300' : ($isExpiring ? 'border-amber-300' : 'border-gray-200') }} p-5 mb-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500">Current plan</div>
                    <div class="text-xl font-semibold text-gray-900 mt-1">
                        {{ $subscription->plan?->plan_name ?? 'Plan' }}
                        @if($isTrial)
                            <span class="ml-1 align-middle text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Free trial</span>
                        @endif
                    </div>
                    @if($isTrial)
                        <p class="text-xs text-gray-500 mt-1">1 client, watermarked downloads. Buy slots below for clean exports.</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <div class="text-gray-500 text-xs">Client slots</div>
                        <div class="font-semibold text-gray-900">{{ $currentUsed }} / {{ $currentLimit }} used</div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Contract year</div>
                        <div class="font-semibold text-gray-900">{{ $contractYear }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">Term started</div>
                        <div class="font-semibold text-gray-900">{{ $subscription->starts_at?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500 text-xs">{{ $isExpired ? 'Expired' : 'Expires' }}</div>
                        <div class="font-semibold {{ $isExpired ? 'text-red-600' : ($isExpiring ? 'text-amber-700' : 'text-gray-900') }}">
                            {{ $expiresAt?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Renew is offered only inside the 45-day window, because
                 RenewalController::index() redirects away outside it
                 ("Renewal opens within 45 days of a capacity package expiry").
                 A button that lands on a redirect is worse than no button. --}}
            @if($isExpired)
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                    <span>This pack expired on {{ $expiresAt->format('d M Y') }}.</span>
                    <a href="{{ route('consultant.renewal.index') }}" class="font-semibold underline">Renew now</a>
                </div>
            @elseif($isExpiring)
                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    <span>Expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}.</span>
                    <a href="{{ route('consultant.renewal.index') }}" class="font-semibold underline">Renew now</a>
                </div>
            @endif
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- SELF-SERVE PURCHASE.

         Totals are computed in Blade from ConsultantAgencyPlanMatrix::SLOT_PRICING
         and recomputed in JS as the quantity changes. The JS figure is a
         PREVIEW only -- processCheckout() re-quotes server-side through
         resolvePackPurchase(), so a tampered form cannot change what is
         charged, and a mid-year purchase is pro-rated there rather than here.

         Enterprise is absent by design: it has no list price, so it is bought
         through the request form below. --}}
    @if ($checkoutAvailable && $buyablePacks->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">
                {{ $subscription && !$isTrial ? 'Add client slots' : 'Choose a plan' }}
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Each slot is one managed client for the contract year. Minimum {{ $minSlots }} to start;
                blocks of five cost less per slot.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($buyablePacks as $pack)
                    @php
                        $code = $pack['plan_code'];
                        $planId = $planIds[$code] ?? null;
                        $bands = $slotPricing[$code];
                        $clientList = $pack['client_list_price_aed'] ?? null;
                    @endphp

                    {{-- No row in subscription_plans means the seeder has not run;
                         showing a Buy button that 404s on plan_id would be worse
                         than showing nothing. --}}
                    @if ($planId)
                        <div class="rounded-xl border border-gray-200 bg-white p-5">
                            <h3 class="font-semibold text-gray-900">{{ $pack['plan_name'] }}</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                AED {{ number_format($bands['entry']) }} per slot / year
                                @if ($clientList)
                                    <span class="text-gray-400">&middot; your client would pay AED {{ number_format($clientList) }}</span>
                                @endif
                            </p>

                            <form action="{{ route('consultant.packs.checkout') }}" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $planId }}">

                                <div class="flex items-end gap-3">
                                    <div>
                                        <label for="qty_{{ $code }}" class="block text-xs text-gray-500 mb-1">Slots</label>
                                        <input type="number" id="qty_{{ $code }}" name="quantity"
                                               value="{{ $suggestedSlots }}" min="{{ $minSlots }}" max="500" step="1"
                                               data-entry="{{ $bands['entry'] }}"
                                               data-single="{{ $bands['single'] }}"
                                               data-block5="{{ $bands['block5'] }}"
                                               data-total="total_{{ $code }}"
                                               class="js-slot-qty w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-xs text-gray-500">Total</div>
                                        <div id="total_{{ $code }}" class="text-lg font-semibold text-gray-900">
                                            {{-- Priced through the same band helper the server quote uses,
                                                 so the figure is right before JS runs and matches what
                                                 processCheckout() re-quotes. --}}
                                            AED {{ number_format(\App\Data\ConsultantAgencyPlanMatrix::extraSlotPriceAed($code, $suggestedSlots)) }}
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="mt-4 w-full py-2.5 bg-brand hover:opacity-90 text-white font-medium rounded-lg text-sm">
                                    Buy now
                                </button>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Charged once for the contract year. A mid-year purchase is pro-rated to 31 December,
                so the amount confirmed at checkout may be lower than shown here.
            </p>

            <script>
                // Mirrors ConsultantAgencyPlanMatrix::extraSlotPriceAed(): blocks
                // of five at the block rate, remainder at the single rate. This
                // is a PREVIEW -- processCheckout() re-quotes server-side, so a
                // changed value here cannot change what is charged.
                (function () {
                    var inputs = document.querySelectorAll('.js-slot-qty');

                    function price(qty, single, block5) {
                        var blocks = Math.floor(qty / 5);
                        var remainder = qty % 5;
                        return (blocks * 5 * block5) + (remainder * single);
                    }

                    function sync(el) {
                        var target = document.getElementById(el.dataset.total);
                        if (!target) { return; }

                        var qty = parseInt(el.value, 10);
                        var min = parseInt(el.min, 10) || 1;
                        if (isNaN(qty) || qty < min) { qty = min; }

                        var total = price(qty, parseInt(el.dataset.single, 10), parseInt(el.dataset.block5, 10));
                        target.textContent = 'AED ' + total.toLocaleString('en-AE');
                    }

                    inputs.forEach(function (el) {
                        el.addEventListener('input', function () { sync(el); });
                        sync(el);
                    });
                })();
            </script>
        </div>

        <div class="mb-6 border-t border-gray-200 pt-6">
            <h2 class="text-lg font-semibold text-gray-900">Prefer an invoice, or need Enterprise?</h2>
            <p class="text-sm text-gray-600">Slot prices are listed above and you can buy them directly. Use this form only for an invoice, a custom arrangement, or Enterprise.</p>
        </div>
    @endif

    {{-- Invoices. This sat at the very bottom of the page, below the
         Enterprise request form and the recent-requests table, so an agency
         looking for a receipt for something it had already bought had to
         scroll past two purchase forms to reach it. Buying comes first, then
         what you have already bought. There is no separate consultant billing
         screen, so this page is it.

         Deliberately OUTSIDE the checkout-availability condition: receipts for
         money already paid must not disappear if the gateway is switched off. --}}
    @if(isset($invoices))
        <div class="mb-8">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Invoices</h2>
            @if($invoices->isEmpty())
                <div class="bg-white rounded-xl border border-gray-200 px-4 py-6 text-sm text-gray-500 text-center">
                    No invoices yet. They appear here after your first purchase, and are emailed to you too.
                </div>
            @else
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden text-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Invoice</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Description</th>
                            <th class="px-4 py-2 text-right">Amount</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $invoice->issued_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $invoice->description }}</td>
                                <td class="px-4 py-2 text-right">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('consultant.invoices.download', $invoice) }}" class="text-brand hover:underline">Download</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    @endif

</div>
@endsection
