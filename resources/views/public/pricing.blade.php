@extends('layouts.public')

@section('title', 'Pricing — ' . ($settings['brand_name'] ?? 'MENetZero'))

@section('content')
@php use App\Services\CurrencyService; @endphp
<div class="max-w-6xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-gray-900">Simple, annual pricing</h1>
        <p class="mt-3 text-gray-600">Carbon accounting for Scope 1 &amp; 2, with Scope 3 available as an add-on.</p>

        <div class="mt-5 inline-flex items-center rounded-lg border border-gray-200 overflow-hidden text-sm">
            <a href="{{ route('currency.switch', 'AED') }}" class="px-4 py-1.5 {{ $currency === 'AED' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">AED</a>
            <a href="{{ route('currency.switch', 'INR') }}" class="px-4 py-1.5 {{ $currency === 'INR' ? 'bg-teal-600 text-white' : 'text-gray-600' }}">INR (₹)</a>
        </div>
        <p class="mt-2 text-xs text-gray-400">Showing prices in {{ $currency }}. Payments are processed securely in INR (₹).</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach($plans as $plan)
            @php
                $price = CurrencyService::displayPrice($plan, $currency);
                $limits = $plan->limits ?? [];
                $isFree = (float) $plan->price_annual <= 0;
                $isEnterprise = stripos($plan->plan_code, 'enterprise') !== false;
                $fmtLimit = fn($v) => ($v === -1 || $v === '-1') ? 'Unlimited' : $v;
            @endphp
            <div class="bg-white rounded-xl border-2 {{ $isEnterprise ? 'border-gray-300' : 'border-gray-200' }} p-6 flex flex-col">
                <h3 class="text-lg font-bold text-gray-900">{{ $plan->plan_name }}</h3>
                <p class="text-xs text-gray-500 mb-4 min-h-[2.5rem]">{{ $plan->description }}</p>

                <div class="mb-4">
                    @if($isEnterprise)
                        <div class="text-2xl font-extrabold text-gray-900">Custom</div>
                        <div class="text-xs text-gray-500">Contact sales</div>
                    @elseif($isFree)
                        <div class="text-2xl font-extrabold text-gray-900">{{ CurrencyService::format(0, $currency) }}</div>
                        <div class="text-xs text-gray-500">Free forever</div>
                    @else
                        <div class="text-2xl font-extrabold text-gray-900">{{ CurrencyService::format($price['amount'], $currency) }}</div>
                        <div class="text-xs text-gray-500">per year</div>
                    @endif
                </div>

                <ul class="space-y-2 text-sm text-gray-600 flex-1 mb-5">
                    <li class="flex items-center gap-2"><span class="text-teal-500">✓</span> Users: {{ $fmtLimit($limits['users'] ?? '—') }}</li>
                    <li class="flex items-center gap-2"><span class="text-teal-500">✓</span> Locations: {{ $fmtLimit($limits['locations'] ?? '—') }}</li>
                    <li class="flex items-center gap-2"><span class="text-teal-500">✓</span> Scope 1 &amp; 2 calculations</li>
                    <li class="flex items-center gap-2"><span class="text-teal-500">✓</span> Annual carbon report (PDF)</li>
                </ul>

                @if($isEnterprise)
                    <a href="mailto:{{ $settings['sales_email'] ?? 'sales@menetzero.com' }}?subject=Enterprise%20enquiry" class="block text-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">Contact Sales</a>
                @else
                    <a href="{{ route('register') }}" class="block text-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-sm font-medium">Get started</a>
                @endif
            </div>
        @endforeach
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">
        Prices are exclusive of applicable taxes. See our
        <a href="{{ route('terms') }}" class="underline">Terms</a> and
        <a href="{{ route('refunds') }}" class="underline">Refunds &amp; Cancellations</a> policy.
    </p>
</div>
@endsection
