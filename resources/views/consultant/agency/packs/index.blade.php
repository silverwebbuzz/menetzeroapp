@extends('consultant.layouts.app')

@section('title', 'Agency Packs')

@section('content')
@php $checkoutAvailable = \App\Models\PaymentGateway::checkoutAvailable(); @endphp

<h1 class="text-2xl font-bold text-gray-900 mb-1">Agency packs</h1>
<p class="text-sm text-gray-600 mb-4">Wholesale pricing for managing client workspaces. Contract runs through 31 Dec {{ $contractYear }}.</p>

@if(!$checkoutAvailable)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-sm text-amber-900">
        <strong>Online checkout coming soon.</strong> Review pack features and pricing below. You can register your practice, use your free trial client, and purchase packs here when payments go live.
    </div>
@endif

@if($subscription)
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-sm text-green-900">
        Active: <strong>{{ $subscription->plan?->plan_name }}</strong> · {{ $slotSummary['used'] }}/{{ $slotSummary['limit'] }} slots used
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
        <h2 class="font-semibold text-gray-900 mb-2">Need more client slots?</h2>
        <p class="text-sm text-gray-600 mb-4">
            Add slots at <strong>AED {{ number_format(\App\Data\ConsultantAgencyPlanMatrix::EXTRA_SLOT_PRICE_AED) }}</strong> each (pro-rata through 31 Dec {{ $contractYear }}).
            You do not need to upgrade to the next pack size.
        </p>
        @if($extraSlotQuote)
            @if($checkoutAvailable)
                <form action="{{ route('consultant.packs.extra-slots') }}" method="POST" class="flex flex-col sm:flex-row sm:items-end gap-3 max-w-xl">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                        <select name="quantity" class="w-full text-sm rounded-lg border-gray-300" required>
                            @for($q = 1; $q <= 10; $q++)
                                <option value="{{ $q }}">{{ $q }} slot{{ $q > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Payment</label>
                        <select name="gateway" class="w-full text-sm rounded-lg border-gray-300" required>
                            <option value="cashfree">Cashfree</option>
                            <option value="razorpay">Razorpay (INR)</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg whitespace-nowrap">
                        Buy extra slots
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">From AED {{ number_format($extraSlotQuote['charge_amount'], 0) }} for 1 slot (pro-rata).</p>
            @else
                <p class="text-xs text-gray-500">From AED {{ number_format($extraSlotQuote['charge_amount'], 0) }} for 1 slot (pro-rata).</p>
                <button type="button" disabled class="mt-3 px-4 py-2 bg-gray-100 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed">
                    Coming soon
                </button>
            @endif
        @endif
    </div>
@endif

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($plans as $plan)
        @php
            $slots = \App\Data\ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
            $quote = $planQuotes[$plan->id] ?? ['charge_amount' => 0, 'pro_rata' => false];
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col">
            <h2 class="font-semibold text-gray-900">{{ $plan->plan_name }}</h2>
            <p class="text-xs text-gray-500 mt-1">{{ $slots }} client slots · Growth per PRY</p>
            <div class="mt-4 text-2xl font-bold text-teal-700">
                AED {{ number_format($quote['charge_amount'], 0) }}
            </div>
            <p class="text-xs text-gray-500 mt-1">
                @if($quote['pro_rata'])
                    Pro-rata through 31 Dec {{ $contractYear }}
                @else
                    Full year {{ $contractYear }}
                @endif
            </p>
            <div class="mt-4 mt-auto">
                @if($checkoutAvailable)
                    <form action="{{ route('consultant.packs.checkout') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <select name="gateway" class="w-full text-sm rounded-lg border-gray-300" required>
                            <option value="cashfree">Cashfree</option>
                            <option value="razorpay">Razorpay (INR)</option>
                        </select>
                        <button type="submit" class="w-full px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg">
                            @if($subscription)
                                Upgrade / change pack
                            @else
                                Purchase pack
                            @endif
                        </button>
                    </form>
                @else
                    <button type="button" disabled class="w-full px-4 py-2 bg-gray-100 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed">
                        Coming soon
                    </button>
                    <p class="text-xs text-gray-400 text-center mt-2">Use your free trial client until checkout opens</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

<p class="text-xs text-gray-500">Enterprise (50+ slots) — contact MenetZero for manual invoicing.</p>
@endsection
