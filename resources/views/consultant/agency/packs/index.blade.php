@extends('consultant.layouts.app')

@section('title', 'Request clients')
@section('page-title', 'Request clients')

@section('content')
@php
    $isTrial = $subscription?->isFreeTrial() ?? false;
    $currentUsed = (int) ($slotSummary['used'] ?? 0);
    $currentLimit = $slotSummary['limit'] ?? '—';
    $oldLines = old('lines', []);
@endphp

<div class="w-full max-w-6xl">
    <div class="mb-6">
        <a href="{{ route('consultant.dashboard') }}" class="text-sm text-brand hover:underline">&larr; Dashboard</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-2">Plans &amp; Billing</h1>
        <p class="mt-2 text-gray-600">
            Your current pack, and buying more client slots.
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

            @if($isExpired)
                <p class="mt-4 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-3 py-2">
                    This pack expired on {{ $expiresAt->format('d M Y') }}. Buy slots below to restore capacity.
                </p>
            @elseif($isExpiring)
                <p class="mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                    Expires in {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }}.
                </p>
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
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Buy client slots</h2>
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
            <p class="text-sm text-gray-600">Request below and we confirm pricing offline.</p>
        </div>
    @endif

    <form action="{{ route('consultant.packs.request-entities') }}" method="POST" class="space-y-6" id="request-clients-form">
        @csrf

        @include('partials.package-request-matrix', [
            'matrix' => $matrix,
            'packages' => $packages,
            'selectedPackage' => null,
            'selectionMode' => 'none',
        ])

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">How many managed clients per package?</h2>
            <p class="text-xs text-gray-500 mb-4">
                Leave a package at 0 to skip it. One managed client = one client workspace at that package depth.
            </p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($packages as $code => $pkg)
                    <label class="flex flex-col gap-1 rounded-lg border border-gray-200 p-3 hover:border-teal-300">
                        <span class="text-sm font-semibold text-gray-900">{{ $pkg['name'] }}</span>
                        <span class="text-[11px] text-gray-500 leading-snug">{{ $pkg['summary'] }}</span>
                        <input
                            type="number"
                            name="lines[{{ $code }}]"
                            min="0"
                            max="500"
                            value="{{ (int) ($oldLines[$code] ?? 0) }}"
                            class="mt-2 w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 line-qty"
                            data-package-code="{{ $code }}"
                        >
                    </label>
                @endforeach
            </div>
            <p id="min10-preferential-tip" class="mt-4 text-xs text-teal-800 bg-teal-50 border border-teal-100 rounded-lg px-3 py-2">
                Preferential rates may apply when you onboard <strong>10+</strong> managed clients in a 12‑month period — sales confirms offline. Asking for fewer than 10 is fine; this is not a hard minimum.
            </p>
            <p class="mt-2 text-xs text-gray-500">Total requested: <span id="lines-total" class="font-semibold text-gray-800">0</span></p>
        </div>

        <script>
            (function () {
                const inputs = document.querySelectorAll('.line-qty');
                const tip = document.getElementById('min10-preferential-tip');
                const totalEl = document.getElementById('lines-total');
                const sync = () => {
                    let total = 0;
                    inputs.forEach((el) => { total += parseInt(el.value, 10) || 0; });
                    if (totalEl) totalEl.textContent = String(total);
                    if (tip) tip.classList.toggle('hidden', total >= 10);
                };
                inputs.forEach((el) => el.addEventListener('input', sync));
                sync();
            })();
        </script>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-1">Optional extras</h2>
            <p class="text-xs text-gray-500 mb-3">
                Tick only what you may need beyond the package defaults. If something is already included in your selection, MENetZero will ignore the duplicate when quoting.
            </p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($extraOptions as $key => $label)
                    <label class="flex items-start gap-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="extras[]"
                            value="{{ $key }}"
                            class="mt-1 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                            @checked(in_array($key, old('extras', []), true))
                        >
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">Notes for MENetZero</label>
            <textarea
                id="message"
                name="message"
                rows="4"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                placeholder="Industries, reporting years, urgency, or clients that need more sites…"
            >{{ old('message') }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700">
                Submit request
            </button>
            <a href="{{ route('consultant.clients.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>

    {{-- Invoices for this agency. Lives here because the agency buys here and
         there is no separate consultant billing screen. --}}
    @if(isset($invoices) && $invoices->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Invoices</h2>
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
        </div>
    @endif

    @if(isset($recentRequests) && $recentRequests->isNotEmpty())
        <div class="mt-10">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Recent requests</h2>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden text-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Clients</th>
                            <th class="px-4 py-2">Package(s)</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentRequests as $req)
                            <tr>
                                <td class="px-4 py-2 text-gray-600">{{ $req->created_at->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ $req->totalEntityCount() }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600">
                                    {{ $req->packageLabel() }}
                                    @if($req->needs_sites_over_5) · extra sites @endif
                                </td>
                                <td class="px-4 py-2">{{ ucfirst($req->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
