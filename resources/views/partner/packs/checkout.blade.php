@extends('partner.layouts.app')

@section('title', 'Complete Payment')

@section('content')
@php
    $meta = $transaction->metadata ?? [];
    $amountMinor = (int) round(((float) $transaction->amount) * 100);
@endphp

<div class="max-w-lg mx-auto">
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Complete agency pack payment</h1>
        <p class="text-gray-600 text-sm mb-6">{{ $plan?->plan_name ?? 'Agency pack' }} · contract {{ $meta['contract_year'] ?? now()->year }}</p>

        @php
            $chargeSymbol = \App\Services\CurrencyService::symbol($transaction->currency);
            $chargeLabel = $chargeSymbol . ' ' . number_format((float) $transaction->amount, 0);
        @endphp
        <div class="bg-gray-50 rounded-lg p-5 mb-6">
            <div class="text-3xl font-extrabold text-gray-900">{{ $chargeLabel }}</div>
            <div class="text-xs text-gray-500 mt-1">via {{ $gateway->label ?? 'payment gateway' }}</div>
        </div>

        @if($gateway && $gateway->gateway === 'razorpay')
            <button id="payBtn" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
                Pay {{ $chargeLabel }}
            </button>
            <form id="razorpayForm" method="POST" action="{{ route('partner.packs.payment.razorpay') }}" class="hidden">
                @csrf
                <input type="hidden" name="transaction_id" value="{{ $transaction->id }}">
                <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
                <input type="hidden" name="razorpay_order_id" id="rzp_order_id">
                <input type="hidden" name="razorpay_signature" id="rzp_signature">
            </form>
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
            <script>
                document.getElementById('payBtn').onclick = function () {
                    new Razorpay({
                        key: @json($gateway->key_id),
                        amount: @json($amountMinor),
                        currency: @json($transaction->currency),
                        order_id: @json($meta['razorpay_order_id'] ?? ''),
                        name: @json(config('app.name')),
                        description: @json($plan?->plan_name ?? 'Agency pack'),
                        handler: function (r) {
                            document.getElementById('rzp_payment_id').value = r.razorpay_payment_id;
                            document.getElementById('rzp_order_id').value = r.razorpay_order_id;
                            document.getElementById('rzp_signature').value = r.razorpay_signature;
                            document.getElementById('razorpayForm').submit();
                        }
                    }).open();
                };
            </script>
        @elseif(!empty($meta['cashfree_payment_session_id']))
            <div id="cf-root" class="min-h-[120px]"></div>
            <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
            <script>
                const cashfree = Cashfree({ mode: @json(config('app.env') === 'production' ? 'production' : 'sandbox') });
                cashfree.checkout({
                    paymentSessionId: @json($meta['cashfree_payment_session_id']),
                    redirectTarget: document.getElementById('cf-root')
                });
            </script>
        @else
            <p class="text-sm text-red-600">Payment session unavailable. Go back and try again.</p>
        @endif

        <a href="{{ route('partner.packs.index') }}" class="mt-4 inline-block text-sm text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</div>
@endsection
