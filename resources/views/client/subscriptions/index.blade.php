@extends('layouts.app')

@section('title', 'Subscription Plans - MenetZero')
@section('page-title', 'Subscription Plans')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Subscription Plans</h1>
        <p class="mt-2 text-gray-600">Choose the plan that best fits your business needs.</p>
    </div>

    <!-- Current Subscription Alert -->
    @if($currentSubscription)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Current Plan: {{ $currentSubscription->plan->plan_name ?? 'N/A' }}</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Expires: {{ $currentSubscription->expires_at->format('F d, Y') }}
                    @if($currentSubscription->auto_renew)
                        <span class="text-green-600">• Auto-renewal enabled</span>
                    @endif
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('subscriptions.current-plan') }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    View Details
                </a>
                <a href="{{ route('subscriptions.upgrade') }}" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Change Plan
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Available Plans -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($availablePlans as $plan)
        <div class="bg-white rounded-lg border-2 {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'border-orange-500 shadow-lg' : 'border-gray-200' }} p-6 hover:shadow-lg transition">
            @if($currentSubscription && $currentSubscription->subscription_plan_id == $plan->id)
            <div class="bg-orange-500 text-white text-xs font-semibold px-3 py-1 rounded-full inline-block mb-4">
                CURRENT PLAN
            </div>
            @endif
            
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->plan_name }}</h3>
            <div class="mb-4">
                <span class="text-4xl font-bold text-gray-900">{{ number_format($plan->price_annual, 0) }}</span>
                <span class="text-gray-600">/year</span>
            </div>
            
            @if($plan->description)
            <p class="text-gray-600 mb-4">{{ $plan->description }}</p>
            @endif

            <!-- Features -->
            @if($plan->features && is_array($plan->features))
            <ul class="space-y-2 mb-6">
                @foreach($plan->features as $feature)
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm text-gray-700">{{ $feature }}</span>
                </li>
                @endforeach
            </ul>
            @endif

            <!-- Limits -->
            @if($plan->limits && is_array($plan->limits))
            <div class="border-t border-gray-200 pt-4 mb-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-2">Plan Limits:</h4>
                <ul class="space-y-1 text-sm text-gray-600">
                    @foreach($plan->limits as $key => $value)
                    <li>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value == -1 ? 'Unlimited' : $value }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Action Button -->
            @if($currentSubscription && $currentSubscription->subscription_plan_id == $plan->id)
            <button disabled class="w-full px-4 py-2 bg-gray-200 text-gray-600 rounded-lg cursor-not-allowed">
                Current Plan
            </button>
            @else
            <a href="{{ route('subscriptions.upgrade', ['plan_id' => $plan->id]) }}" class="block w-full text-center px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                {{ $currentSubscription ? 'Switch to this Plan' : 'Select Plan' }}
            </a>
            @endif
        </div>
        @endforeach
    </div>

    <!-- No Plans Available -->
    @if($availablePlans->isEmpty())
    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        <p class="text-gray-600">No subscription plans available at the moment.</p>
    </div>
    @endif
</div>
@endsection

