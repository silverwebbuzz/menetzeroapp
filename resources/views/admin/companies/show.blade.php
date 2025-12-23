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

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-3">Active Subscriptions</h2>
                @if($company->clientSubscriptions->isEmpty())
                    <p class="text-sm text-gray-500">No subscriptions found.</p>
                @else
                    <ul class="divide-y divide-gray-200 text-sm">
                        @foreach ($company->clientSubscriptions as $sub)
                            <li class="py-2 flex items-center justify-between">
                                <div>
                                    <div class="text-gray-900">
                                        {{ optional($sub->plan)->plan_name ?? 'Unknown plan' }}
                                    </div>
                                    <div class="text-gray-500 text-xs">
                                        Status: {{ $sub->status }} · Expires: {{ optional($sub->expires_at)->format('Y-m-d') }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

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


