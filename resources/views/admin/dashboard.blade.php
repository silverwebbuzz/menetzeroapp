@extends('layouts.app')

@section('content')
    <div class="max-w-6xl mx-auto py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Super Admin Dashboard</h1>
            <p class="mt-2 text-sm text-gray-600">
                Overview of companies, users, and subscriptions across the MENetZero platform.
            </p>
        </div>

        @isset($stats)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-sm text-gray-500">Total Companies</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['total_companies'] ?? 0) }}
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-sm text-gray-500">Client Companies</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['total_clients'] ?? 0) }}
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-sm text-gray-500">Total Users</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['total_users'] ?? 0) }}
                    </div>
                </div>

                <div class="bg-white shadow rounded-lg p-4">
                    <div class="text-sm text-gray-500">Active Client Subscriptions</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($stats['active_client_subscriptions'] ?? 0) }}
                    </div>
                </div>
            </div>
        @endisset

        @isset($recentCompanies)
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">Recent Companies</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created At
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentCompanies as $company)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        {{ $company->name }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $company->email }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $company->company_type ?? 'client' }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ optional($company->created_at)->format('Y-m-d') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-sm text-gray-500 text-center">
                                        No companies found yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endisset
    </div>
@endsection


