@extends('partner.layouts.app')

@section('title', 'Agency Packs')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-1">Agency packs</h1>
<p class="text-sm text-gray-600 mb-6">Wholesale pricing for managing multiple client workspaces. Contract runs through 31 Dec {{ $contractYear }}.</p>

@if($subscription)
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-sm text-green-900">
        Active: <strong>{{ $subscription->plan?->plan_name }}</strong> · {{ $slotSummary['used'] }}/{{ $slotSummary['limit'] }} slots used
    </div>
@endif

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @foreach($plans as $plan)
        @php
            $slots = \App\Data\PartnerPlanMatrix::slotCountForPlanCode($plan->plan_code);
            $quote = app(\App\Services\PartnerSubscriptionService::class)->resolvePackPurchase($partner, $plan, $contractYear);
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl p-5 flex flex-col">
            <h2 class="font-semibold text-gray-900">{{ $plan->plan_name }}</h2>
            <p class="text-xs text-gray-500 mt-1">{{ $slots }} client slots · Growth per PRY</p>
            <div class="mt-4 text-2xl font-bold text-indigo-700">
                AED {{ number_format($quote['charge_amount'], 0) }}
            </div>
            <p class="text-xs text-gray-500 mt-1">
                @if($quote['pro_rata'])
                    Pro-rata through 31 Dec {{ $contractYear }}
                @else
                    Full year {{ $contractYear }}
                @endif
            </p>
            <form action="{{ route('partner.packs.checkout') }}" method="POST" class="mt-4 mt-auto space-y-2">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <select name="gateway" class="w-full text-sm rounded-lg border-gray-300" required>
                    <option value="cashfree">Cashfree</option>
                    <option value="razorpay">Razorpay (INR)</option>
                </select>
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Purchase pack
                </button>
            </form>
        </div>
    @endforeach
</div>

<p class="text-xs text-gray-500">Enterprise (50+ slots) — contact MenetZero for manual invoicing.</p>
@endsection
