@extends('layouts.app')

@section('title', 'Complete Payment - MenetZero')
@section('page-title', 'Complete Payment')

@section('content')
@php
    $meta = $transaction->metadata ?? [];
    $amountMinor = (int) round(((float) $transaction->amount) * 100);
@endphp
<div class="portal-narrow">
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif
    @if(!empty($meta['charged_in_inr_fallback']))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Prices on our site are shown in {{ $meta['display_currency'] ?? 'AED' }}, but Cashfree has not enabled that currency on our account yet.
            You will pay the INR equivalent below.
        </div>
    @endif
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Complete your payment</h1>
        <p class="text-gray-600 text-sm mb-6">You're subscribing to the <strong>{{ $plan->plan_name ?? 'selected' }}</strong> plan (annual).</p>

        @php
            $chargeSymbol = \App\Services\CurrencyService::symbol($transaction->currency);
            $chargeLabel = $chargeSymbol . ' ' . number_format((float) $transaction->amount, 0);
        @endphp
        <div class="bg-gray-50 rounded-lg p-5 mb-6">
            <div class="text-3xl font-extrabold text-gray-900">{{ $chargeLabel }}</div>
            <div class="text-xs text-gray-500 mt-1">Annual plan · billed once via {{ $gateway->label }}</div>
            @if($transaction->currency === 'AED')
                <p class="text-xs text-gray-400 mt-2">Cashfree will charge in AED. Your account receives settlement in INR per Cashfree international rules.</p>
            @else
                <p class="text-xs text-gray-400 mt-2">Charged in Indian Rupees (INR).</p>
            @endif
        </div>

        <button id="payBtn" class="w-full px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium">
            Pay {{ $chargeLabel }}
        </button>
        <p class="mt-4 text-xs text-gray-400">
            Payment is processed securely by {{ $gateway->label }}. Don't close this window.
        </p>
        <a href="{{ route('subscriptions.upgrade') }}" class="mt-4 inline-block text-sm text-gray-500 hover:text-gray-700">Cancel and go back</a>
    </div>

    @if($gateway->gateway === 'razorpay')
        <form id="razorpayForm" method="POST" action="{{ route('subscriptions.payment.razorpay') }}" class="hidden">
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
            description: @json('Subscription: ' . ($plan->plan_name ?? '')),
            prefill: {
                name: @json($user->name ?? ''),
                email: @json($user->email ?? '')
            },
            theme: { color: '#ea580c' },
            handler: function (response) {
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id;
                document.getElementById('rzp_order_id').value = response.razorpay_order_id;
                document.getElementById('rzp_signature').value = response.razorpay_signature;
                document.getElementById('razorpayForm').submit();
            },
            modal: {
                ondismiss: function () { /* user closed the popup; stay on page */ }
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
@endif
@endsection
