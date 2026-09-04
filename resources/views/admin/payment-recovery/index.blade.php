@extends('admin.layouts.app')

@section('title', 'Payment Recovery | MENetZero')
@section('page-title', 'Payment Recovery')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <p class="text-sm text-gray-500 mb-6 max-w-4xl">
        Payments that were taken but never activated. Activation normally happens twice over — the customer's
        browser returning from checkout, and Razorpay's webhook — so a row appears here only when both missed.
        Enter the Razorpay payment reference and this checks with Razorpay directly: nothing is activated unless
        Razorpay confirms the money was captured for the right amount and order.
    </p>

    @unless($gatewayReady)
        <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded text-sm">
            <strong>Razorpay is not configured.</strong> Payments cannot be verified until its keys are saved in
            <a href="{{ route('admin.payment-gateways.index') }}" class="underline">Payment Gateways</a>.
        </div>
    @endunless

    @php $verified = session('verified_payment'); @endphp

    @if($transactions->isEmpty())
        <div class="bg-white shadow rounded-lg px-5 py-8 text-center text-sm text-gray-500">
            No stuck payments. Every captured payment has been activated.
        </div>
    @else
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <th class="px-5 py-3">Txn</th>
                        <th class="px-5 py-3">Account</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Started</th>
                        <th class="px-5 py-3">Recover</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($transactions as $transaction)
                        @php
                            // Agency purchases store the consultant org in company_id,
                            // so the label has to say which kind of account this is --
                            // otherwise an agency pack reads as a client company.
                            $company = $transaction->company;
                            $isAgency = $company && $company->company_type === 'consultant';
                            $orderRef = $transaction->metadata['razorpay_order_id'] ?? null;
                            $showResult = $verified && (int) $verified['transaction_id'] === (int) $transaction->id;
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4 text-sm font-medium text-gray-900">#{{ $transaction->id }}</td>
                            <td class="px-5 py-4 text-sm">
                                <div class="text-gray-900">{{ $company->name ?? '—' }}</div>
                                <div class="text-xs {{ $isAgency ? 'text-purple-700' : 'text-gray-500' }}">
                                    {{ $isAgency ? 'Consultant agency' : 'Company' }} · ID {{ $transaction->company_id }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600">
                                {{ $transaction->transaction_type }}
                                @if($orderRef)
                                    <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $orderRef }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-900 whitespace-nowrap">
                                {{ strtoupper($transaction->currency) }} {{ number_format((float) $transaction->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $transaction->created_at?->format('d M Y H:i') }}
                            </td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('admin.payment-recovery.verify', $transaction) }}"
                                      class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="text" name="payment_id" required
                                           value="{{ $showResult ? $verified['payment_id'] : '' }}"
                                           placeholder="pay_XXXXXXXXXXXX"
                                           class="border border-gray-300 rounded px-2 py-1.5 text-sm font-mono w-56">
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-gray-800 text-white rounded text-sm hover:bg-gray-900">
                                        Check Razorpay
                                    </button>
                                </form>

                                @if($showResult)
                                    <div class="mt-3 rounded border px-3 py-2 text-xs {{ $verified['ok'] ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-2">
                                            <span class="text-gray-500">Razorpay status</span>
                                            <span class="font-medium">{{ $verified['status'] ?? '—' }}</span>
                                            <span class="text-gray-500">Captured</span>
                                            <span class="font-medium">
                                                {{ strtoupper($verified['currency'] ?? '') }}
                                                {{ $verified['amount'] !== null ? number_format($verified['amount'], 2) : '—' }}
                                            </span>
                                            <span class="text-gray-500">Order</span>
                                            <span class="font-mono">{{ $verified['order_id'] ?? '—' }}</span>
                                            @if($verified['email'])
                                                <span class="text-gray-500">Payer</span>
                                                <span>{{ $verified['email'] }}</span>
                                            @endif
                                        </div>

                                        @if($verified['ok'])
                                            <p class="text-green-800 mb-2">
                                                Razorpay confirms this payment was captured for this transaction.
                                                Activating issues the invoice and emails the customer.
                                            </p>
                                            <form method="POST" action="{{ route('admin.payment-recovery.activate', $transaction) }}"
                                                  onsubmit="return confirm('Activate transaction #{{ $transaction->id }}? This grants the package, issues an invoice and emails the customer.');">
                                                @csrf
                                                <input type="hidden" name="payment_id" value="{{ $verified['payment_id'] }}">
                                                <button type="submit"
                                                        class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                                    Activate &amp; send invoice
                                                </button>
                                            </form>
                                        @else
                                            <p class="text-red-800">{{ $verified['reason'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $transactions->links() }}</div>
    @endif
@endsection
