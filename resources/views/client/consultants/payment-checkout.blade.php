@extends('layouts.app')

@section('title', 'Pay consultant pack')

@section('content')
@php
    $meta = $transaction->metadata ?? [];
    $amountMinor = (int) round(((float) $transaction->amount) * 100);
    $user = auth()->user();
@endphp
<div class="portal-narrow">
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Complete consultant payment</h1>
        <p class="text-gray-600 text-sm mb-6">
            <strong>{{ $packName }}</strong>
            @if($consultant) with {{ $consultant->company_name }} @endif
        </p>

        @php
            $chargeSymbol = \App\Services\CurrencyService::symbol($transaction->currency);
            $chargeLabel = $chargeSymbol . ' ' . number_format((float) $transaction->amount, 0);
        @endphp
        <div class="bg-gray-50 rounded-lg p-5 mb-6">
            <div class="text-3xl font-extrabold text-gray-900">{{ $chargeLabel }}</div>
            <div class="text-xs text-gray-500 mt-1">Held in escrow · {{ $gateway->label }}</div>
        </div>

        <button id="payBtn" class="w-full px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-medium">
            Pay {{ $chargeLabel }}
        </button>
        <a href="{{ route('client.consultants.index') }}" class="mt-4 inline-block text-sm text-gray-500 hover:text-gray-700">Cancel</a>
    </div>

    @if($gateway->gateway === 'razorpay')
        <form id="razorpayForm" method="POST" action="{{ route('client.consultants.payment.razorpay') }}" class="hidden">
            @csrf
            <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
            <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
            <input type="hidden" name="razorpay_signature" id="rzp_signature">
        </form>
    @endif
</div>

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
