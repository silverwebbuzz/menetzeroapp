@extends('layouts.app')

@section('title', 'Upgrade Subscription - MenetZero')
@section('page-title', 'Upgrade Subscription')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Change Subscription Plan</h1>
        <p class="mt-2 text-gray-600">Select a new plan to upgrade or downgrade your subscription.</p>
    </div>

    <!-- Current Plan Info -->
    @if($currentSubscription)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-gray-700">
            <strong>Current Plan:</strong> {{ $currentSubscription->plan->plan_name }} 
            (Expires: {{ $currentSubscription->expires_at->format('F d, Y') }})
        </p>
    </div>
    @endif

    <!-- Available Plans -->
    <form action="{{ route('subscriptions.process-upgrade') }}" method="POST">
        @csrf
        
        <div class="space-y-4 mb-6">
            @foreach($availablePlans as $plan)
            <div class="bg-white rounded-lg border-2 {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'border-orange-500' : 'border-gray-200' }} p-6 hover:border-orange-300 transition cursor-pointer">
                <label class="flex items-start cursor-pointer">
                    <input type="radio" name="plan_id" value="{{ $plan->id }}" 
                           {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'checked' : '' }}
                           class="mt-1 mr-4 w-5 h-5 text-orange-600 focus:ring-orange-500">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xl font-bold text-gray-900">{{ $plan->plan_name }}</h3>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-gray-900">{{ number_format($plan->price_annual, 0) }}</span>
                                <span class="text-gray-600">/year</span>
                            </div>
                        </div>
                        @if($plan->description)
                        <p class="text-gray-600 mb-3">{{ $plan->description }}</p>
                        @endif
                        
                        @if($plan->features && is_array($plan->features))
                        <ul class="space-y-1 text-sm text-gray-700">
                            @foreach(array_slice($plan->features, 0, 3) as $feature)
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </label>
            </div>
            @endforeach
        </div>

        @error('plan_id')
            <p class="text-red-600 text-sm mb-4">{{ $message }}</p>
        @enderror

        <!-- Billing Cycle -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Billing Cycle</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="billing_cycle" value="annual" checked class="mr-2 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700">Annual (Recommended - Save more)</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="billing_cycle" value="monthly" class="mr-2 text-orange-600 focus:ring-orange-500">
                    <span class="text-gray-700">Monthly</span>
                </label>
            </div>
        </div>

        <!-- Auto Renew -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="auto_renew" value="1" checked class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                <span class="ml-2 text-gray-700">Enable auto-renewal</span>
            </label>
            <p class="text-sm text-gray-500 mt-1 ml-6">Your subscription will automatically renew before expiration.</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('subscriptions.index') }}" class="px-6 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                Update Subscription
            </button>
        </div>
    </form>
</div>
@endsection

