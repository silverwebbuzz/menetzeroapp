@extends('admin.layouts.app')

@section('title', 'Subscription Plans | MENetZero')
@section('page-title', 'Subscription Plans')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Subscription Plans</h2>
            <a href="{{ route('admin.subscription-plans.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                + New Plan
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Price (Annual)</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Billing</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Active</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($plans as $plan)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $plan->plan_code }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $plan->plan_name }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $plan->plan_category }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ number_format($plan->price_annual, 2) }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $plan->currency }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ ucfirst($plan->billing_cycle) }}</td>
                            <td class="px-4 py-2 text-gray-500">
                                <span class="px-2 py-1 text-xs rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $plan->is_active ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.subscription-plans.edit', $plan->id) }}" class="text-purple-600 hover:text-purple-900 text-sm">Edit</a>
                                    <form action="{{ route('admin.subscription-plans.destroy', $plan->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this subscription plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-4 text-center text-gray-500">
                                No subscription plans found. <a href="{{ route('admin.subscription-plans.create') }}" class="text-purple-600 hover:underline">Create one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
