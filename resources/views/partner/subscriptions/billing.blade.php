@extends('layouts.app')

@section('title', 'Billing Information - MenetZero')
@section('page-title', 'Billing Information')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Billing Information</h1>
        <p class="mt-2 text-gray-600">Manage your billing details and payment methods.</p>
    </div>

    <!-- Billing Details Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Subscription Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Plan Name</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->plan_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Billing Cycle</label>
                    <p class="text-lg font-semibold text-gray-900">{{ ucfirst($subscription->billing_cycle) }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Amount</label>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ $subscription->plan->currency ?? 'AED' }} {{ number_format($subscription->plan->price_annual ?? 0, 2) }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Payment Method</label>
                    <p class="text-lg font-semibold text-gray-900">{{ ucfirst($subscription->payment_method ?? 'Not set') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Next Billing Date</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->expires_at->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Auto Renewal</label>
                    <p class="text-lg font-semibold {{ $subscription->auto_renew ? 'text-green-600' : 'text-gray-900' }}">
                        {{ $subscription->auto_renew ? 'Enabled' : 'Disabled' }}
                    </p>
                </div>
            </div>

            <!-- Stripe Information -->
            @if($subscription->stripe_customer_id)
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Gateway</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Stripe Customer ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $subscription->stripe_customer_id }}</p>
                    </div>
                    @if($subscription->stripe_subscription_id)
                    <div>
                        <label class="text-sm font-medium text-gray-500">Stripe Subscription ID</label>
                        <p class="text-sm text-gray-900 font-mono">{{ $subscription->stripe_subscription_id }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Company Billing Address -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Billing Address</h2>
            <div class="text-gray-700">
                <p class="font-semibold">{{ $company->name }}</p>
                @if($company->address)
                <p>{{ $company->address }}</p>
                @endif
                @if($company->city || $company->state || $company->country)
                <p>
                    @if($company->city){{ $company->city }}, @endif
                    @if($company->state){{ $company->state }}, @endif
                    @if($company->country){{ $company->country }}@endif
                </p>
                @endif
                @if($company->postal_code)
                <p>{{ $company->postal_code }}</p>
                @endif
            </div>
            <a href="{{ route('client.profile') }}" class="mt-4 inline-block text-orange-600 hover:text-orange-800 text-sm">
                Update billing address →
            </a>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-4">
        <a href="{{ route('partner.subscriptions.current-plan') }}" class="px-6 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Back to Subscription
        </a>
        <a href="{{ route('partner.subscriptions.payment-history') }}" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
            View Payment History
        </a>
    </div>
</div>
@endsection

