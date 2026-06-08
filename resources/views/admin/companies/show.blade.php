@extends('admin.layouts.app')

@section('title', 'Company Details | MENetZero')
@section('page-title', 'Company Details')

@section('content')
    @isset($company)
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-500">Name</dt>
                        <dd class="text-gray-900">{{ $company->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $company->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Type</dt>
                        <dd class="text-gray-900">{{ $company->company_type ?? 'client' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">Created At</dt>
                        <dd class="text-gray-900">{{ optional($company->created_at)->format('Y-m-d') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Users</h2>
                @if($company->users->isEmpty())
                    <p class="text-sm text-gray-500">No users linked to this company.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->users as $user)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <div class="text-gray-900">{{ $user->name }}</div>
                                    <div class="text-gray-500 text-xs">{{ $user->email }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Subscriptions</h2>
                @if($company->clientSubscriptions->isEmpty())
                    <p class="text-sm text-gray-500">No subscriptions found.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->clientSubscriptions as $sub)
                            @php
                                $provision = app(\App\Services\SubscriptionService::class)->getProvisionLabel($sub);
                            @endphp
                            <li class="py-2">
                                <div class="text-gray-900 font-medium">{{ optional($sub->plan)->plan_name ?? 'Unknown plan' }}</div>
                                <div class="text-gray-500 text-xs">
                                    Status: {{ $sub->status }} · Expires: {{ optional($sub->expires_at)->format('Y-m-d') }}
                                    @if($provision) · <span class="text-purple-700">{{ $provision }}</span> @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if(($company->company_type ?? 'client') === 'client')
            <div class="bg-white shadow rounded-lg p-4 border-l-4 border-purple-500">
                <h2 class="text-md font-semibold text-gray-900 mb-1">Grant complimentary plan</h2>
                <p class="text-xs text-gray-500 mb-4">Special cases only. Client sees full plan features with a &ldquo;Complimentary&rdquo; label — no payment or billing history.</p>
                <form action="{{ route('admin.companies.grant-subscription', $company->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Plan</label>
                        <select name="plan_id" required class="w-full border rounded-lg px-3 py-2">
                            @foreach($grantPlans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->plan_name }} (AED {{ number_format($plan->price_annual, 0) }}/yr)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Duration (months)</label>
                        <input type="number" name="duration_months" value="12" min="1" max="60" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">Reason (shown to client)</label>
                        <input type="text" name="note" required placeholder="e.g. Pilot partner, NGO programme, launch promo" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700" onclick="return confirm('Grant this plan at no charge?')">
                            Grant complimentary access
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Locations</h2>
                @if($company->locations->isEmpty())
                    <p class="text-sm text-gray-500">No locations defined for this company.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->locations as $location)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <div class="text-gray-900">{{ $location->name }}</div>
                                    <div class="text-gray-500 text-xs">
                                        {{ $location->city }}{{ $location->country ? ', '.$location->country : '' }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @else
        <p class="text-gray-500 text-sm">Company data not available.</p>
    @endisset
@endsection


