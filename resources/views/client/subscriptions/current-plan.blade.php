@extends('layouts.app')

@section('title', 'Current Subscription - MenetZero')
@section('page-title', 'Current Subscription')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Current Subscription</h1>
        <p class="mt-2 text-gray-600">View and manage your current subscription plan.</p>
    </div>

    <!-- Subscription Details Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $subscription->plan->plan_name ?? 'N/A' }}</h2>
                    <p class="text-gray-600 mt-1">{{ $subscription->plan->description ?? '' }}</p>
                </div>
                <div class="text-right">
                    @if(!empty($isComplimentary))
                        <div class="text-lg font-bold text-purple-700">Complimentary</div>
                        <div class="text-gray-500 text-sm">No charge</div>
                    @else
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($subscription->plan->price_annual ?? 0, 0) }}</div>
                        <div class="text-gray-600">per year</div>
                    @endif
                </div>
            </div>

            @if(!empty($provisionLabel))
            <div class="mb-6 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-900">
                {{ $provisionLabel }}
            </div>
            @endif

            <!-- Status Badge -->
            <div class="mb-6">
                <span class="px-3 py-1 rounded-full text-sm font-semibold 
                    {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($subscription->status) }}
                </span>
                @if(!empty($cancellationScheduled))
                <span class="ml-2 px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                    Cancels {{ $subscription->expires_at->format('M d, Y') }}
                </span>
                @elseif($subscription->auto_renew)
                <span class="ml-2 px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                    Renewal reminder enabled
                </span>
                @endif
            </div>

            @if(!empty($cancellationScheduled))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <strong>Cancellation scheduled:</strong>
                Your {{ $subscription->plan->plan_name }} plan remains <strong>fully active until
                {{ $subscription->expires_at->format('F d, Y') }}</strong>. It will not renew after that date.
            </div>
            @endif

            @if(!empty($scheduledPlan))
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <strong>Scheduled change at renewal:</strong>
                Your plan will switch to <strong>{{ $scheduledPlan->plan_name }}</strong> on
                {{ $subscription->expires_at->format('F d, Y') }}.
                Until then you keep all {{ $subscription->plan->plan_name }} features.
                @if(!empty($scheduledDowngradeWarnings))
                    <div class="mt-2 space-y-1 text-red-800">
                        @foreach($scheduledDowngradeWarnings as $warning)
                            <p>⚠ {{ $warning }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

            <!-- Subscription Dates -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Start Date</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->started_at->format('F d, Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Expiry Date</label>
                    <p class="text-lg font-semibold text-gray-900">{{ $subscription->expires_at->format('F d, Y') }}</p>
                </div>
            </div>

            <!-- Days Remaining -->
            @php
                $daysRemaining = now()->diffInDays($subscription->expires_at, false);
            @endphp
            <div class="mb-6">
                <label class="text-sm font-medium text-gray-500">Days Remaining</label>
                <p class="text-lg font-semibold {{ $daysRemaining < 30 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $daysRemaining > 0 ? $daysRemaining . ' days' : 'Expired' }}
                </p>
            </div>

            <!-- Plan Features -->
            @if($subscription->plan->features && is_array($subscription->plan->features))
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Features</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($subscription->plan->features as $feature)
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Plan Limits -->
            @if($subscription->plan->limits && is_array($subscription->plan->limits))
            <div class="border-t border-gray-200 pt-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Limits</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($subscription->plan->limits as $key => $value)
                    <div>
                        <label class="text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                        <p class="text-lg font-semibold text-gray-900">{{ $value == -1 ? 'Unlimited' : $value }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="border-t border-gray-200 pt-6 flex gap-4">
                <a href="{{ route('subscriptions.upgrade') }}" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Change Plan
                </a>
                <a href="{{ route('subscriptions.billing') }}" class="px-6 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Billing Info
                </a>
                @if(!empty($isPaidPlan) && empty($cancellationScheduled))
                <form action="{{ route('subscriptions.cancel') }}" method="POST" class="inline" onsubmit="return confirm('Your plan will stay active until {{ $subscription->expires_at->format('F d, Y') }} and will not renew after that. Continue?')">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50">
                        Cancel at renewal
                    </button>
                </form>
                @elseif(!empty($cancellationScheduled))
                <form action="{{ route('subscriptions.resume') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-white border border-green-300 text-green-700 rounded-lg hover:bg-green-50">
                        Keep my plan (undo cancel)
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

