{{--
    MENetZero 2.0 - Consultant payment checkout (Phase 6 body migration).

    HIGHEST-RISK FILE IN THIS GROUP. Three third-party payment SDKs bind to
    this page's DOM. The shell is re-skinned; the gateway scripts are copied
    VERBATIM from the original and must stay that way.

    CONTRACT THE SCRIPTS DEPEND ON - do not rename, wrap, or reorder:
        #payBtn              clicked by all three gateways
        #razorpayForm        submitted by the Razorpay handler
        #rzp_payment_id      \
        #rzp_order_id         > populated from the Razorpay response
        #rzp_signature       /

    The Razorpay hidden form is rendered OUTSIDE the panel, still inside the
    page wrapper. The original hides it with class="hidden", which comes from
    the Tailwind CDN - NOT from app-shell.css. Both shells load that CDN, so
    the class resolves either way, but relying on a remote stylesheet to hide
    a payment relay form is fragile: if the CDN is slow or blocked, four raw
    inputs flash on screen mid-payment. The class is KEPT for parity and an
    inline style="display:none" is added as a local belt-and-braces hide.

    EACH GATEWAY AUTO-STARTS. rzp.open() / startCheckout() run immediately on
    load, not only on click. The button is a RETRY affordance, not the trigger.
    Removing the auto-start would look identical in a screenshot and would
    break the expected redirect flow.

    RAW PHP BLOCKS: two of them, both kept in their original positions and order.
    The first computes $meta / $amountMinor / $user BEFORE any markup; the
    second computes $chargeSymbol / $chargeLabel. $amountMinor is Razorpay's
    minor-unit integer - (int) round(amount * 100) - and must not be recomputed
    from the formatted label.

    CURRENCY: this page IS currency-aware via CurrencyService::symbol(), unlike
    the AED-fixed consultant orders ledger.

    Controller data: $transaction $packName $consultant $gateway
--}}
@extends('layouts.app')

@section('title', 'Pay consultant pack - MenetZero')
@section('page-title', 'Payment')

@push('styles')
    <style>
        .pc-wrap { max-width: 460px; }
        .pc-amount { font-size: 32px; font-weight: 700; color: var(--ink);
            letter-spacing: -.02em; line-height: 1.1; }
        .pc-meta { font-size: 11.5px; color: var(--ink-3); margin-top: 6px; }
        .pc-sub { font-size: 12.5px; color: var(--ink-2); line-height: 1.6; }
        .pc-cancel { display: inline-block; margin-top: 14px; font-size: 12.5px;
            color: var(--ink-3); text-decoration: none; }
        .pc-cancel:hover { color: var(--ink-2); text-decoration: underline; }
    </style>
@endpush

@section('content')
@php
    $meta = $transaction->metadata ?? [];
    $amountMinor = (int) round(((float) $transaction->amount) * 100);
    $user = auth()->user();
@endphp
<div class="mnz-stack pc-wrap" data-pillar="g">

    <div class="mnz-pagehead">
        <div>
            <div class="mnz-kicker">Governance · Assurance</div>
            <h1>Complete consultant payment</h1>
            <p class="pc-sub">
                <strong style="color:var(--ink)">{{ $packName }}</strong>
                @if($consultant) with {{ $consultant->company_name }} @endif
            </p>
        </div>
    </div>

    @php
        $chargeSymbol = \App\Services\CurrencyService::symbol($transaction->currency);
        $chargeLabel = $chargeSymbol . ' ' . number_format((float) $transaction->amount, 0);
    @endphp

    <div class="mnz-panel">
        <div class="mnz-panel__body" style="text-align:center">
            <div class="pc-amount">{{ $chargeLabel }}</div>
            <div class="pc-meta">Held in escrow · {{ $gateway->label }}</div>
        </div>
        <div class="mnz-panel__foot">
            <button id="payBtn" class="mnz-btn mnz-btn--primary mnz-btn--lg" style="width:100%">
                Pay {{ $chargeLabel }}
            </button>
            <div style="text-align:center">
                <a href="{{ route('client.consultants.index') }}" class="pc-cancel">Cancel</a>
            </div>
        </div>
    </div>

    {{-- Razorpay's hidden relay form. Populated by the handler in the script
         block below, then submitted programmatically. --}}
    @if($gateway->gateway === 'razorpay')
        <form id="razorpayForm" method="POST" action="{{ route('client.consultants.payment.razorpay') }}" class="hidden" style="display:none">
            @csrf
            <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
            <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
            <input type="hidden" name="razorpay_signature" id="rzp_signature">
        </form>
    @endif

</div>

{{-- Gateway SDK blocks: copied verbatim from the original view. --}}
@if($gateway->gateway === 'razorpay')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function () {
        var options = {
            key: @json($gateway->key_id),
            amount: @json($amountMinor),
            currency: @json($transaction->currency),
            order_id: @json($meta['razorpay_order_id'] ?? ''),
            name: @json(config('app.name', 'MenetZero')),
            description: @json($packName),
            prefill: { name: @json($user->name ?? ''), email: @json($user->email ?? '') },
            theme: { color: '#0d9488' },
            handler: function (response) {
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                document.getElementById('rzp_order_id').value = response.razorpay_order_id;
                document.getElementById('rzp_signature').value = response.razorpay_signature;
                document.getElementById('razorpayForm').submit();
            }
        };
        var rzp = new Razorpay(options);
        document.getElementById('payBtn').addEventListener('click', function () { rzp.open(); });
        rzp.open();
    })();
</script>
@elseif($gateway->gateway === 'cashfree')
<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script>
    (function () {
        var cashfree = Cashfree({ mode: @json($gateway->isLive() ? 'production' : 'sandbox') });
        function startCheckout() {
            cashfree.checkout({
                paymentSessionId: @json($meta['cashfree_payment_session_id'] ?? ''),
                redirectTarget: '_self'
            });
        }
        document.getElementById('payBtn').addEventListener('click', startCheckout);
        startCheckout();
    })();
</script>
@elseif($gateway->gateway === 'stripe')
<script>
    (function () {
        var sessionUrl = @json($meta['stripe_session_url'] ?? null);
        function startCheckout() {
            if (!sessionUrl) {
                return;
            }
            window.location.href = sessionUrl;
        }
        document.getElementById('payBtn').addEventListener('click', startCheckout);
        startCheckout();
    })();
</script>
@endif
@endsection
