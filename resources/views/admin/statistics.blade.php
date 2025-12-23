@extends('admin.layouts.app')

@section('title', 'System Statistics | MENetZero')
@section('page-title', 'System Statistics')

@section('content')
    @isset($stats)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-2">Companies</h2>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>Total: <span class="font-semibold text-gray-900">{{ $stats['companies']['total'] }}</span></li>
                    <li>Clients: <span class="font-semibold text-gray-900">{{ $stats['companies']['clients'] }}</span></li>
                    <li>Partners: <span class="font-semibold text-gray-900">{{ $stats['companies']['partners'] }}</span></li>
                    <li>Active: <span class="font-semibold text-gray-900">{{ $stats['companies']['active'] }}</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-2">Users</h2>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>Total: <span class="font-semibold text-gray-900">{{ $stats['users']['total'] }}</span></li>
                    <li>Active: <span class="font-semibold text-gray-900">{{ $stats['users']['active'] }}</span></li>
                    <li>Admins: <span class="font-semibold text-gray-900">{{ $stats['users']['admins'] }}</span></li>
                </ul>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-md font-semibold text-gray-900 mb-2">Subscriptions</h2>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>Active client subscriptions:
                        <span class="font-semibold text-gray-900">{{ $stats['subscriptions']['client_active'] }}</span>
                    </li>
                    <li>Total annual revenue (active):
                        <span class="font-semibold text-gray-900">
                            {{ number_format($stats['subscriptions']['total_revenue'], 2) }} AED
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-500">No statistics available.</p>
    @endisset
@endsection



