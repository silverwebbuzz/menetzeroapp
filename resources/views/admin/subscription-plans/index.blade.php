@extends('admin.layouts.app')

@section('title', 'Subscription Plans | MENetZero')
@section('page-title', 'Subscription Plans')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">Subscription Plans</h2>
            {{-- Creation/editing UI can be enhanced later; for now we focus on listing existing plans --}}
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
                        <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Active</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($plans as $plan)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">
                                {{ $plan->plan_code }}
                            </td>
                            <td class="px-4 py-2 text-gray-900">
                                {{ $plan->plan_name }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $plan->plan_category }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ number_format($plan->price_annual, 2) }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $plan->currency }}
                            </td>
                            <td class="px-4 py-2 text-gray-500">
                                {{ $plan->is_active ? 'Yes' : 'No' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                No subscription plans found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection



