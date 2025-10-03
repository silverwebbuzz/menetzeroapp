@extends('layouts.app')

@section('title', 'Location Details - MenetZero')
@section('page-title', 'Location Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $location->name }}</h2>
                <p class="text-gray-600">{{ $location->full_address }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('locations.edit', $location) }}" class="px-4 py-2 border border-orange-500 text-orange-600 bg-white rounded-lg hover:bg-orange-50 transition">
                    Edit
                </a>
                <a href="{{ route('locations.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
                <div class="space-y-2">
                    <div>
                        <span class="font-medium text-gray-700">Location Type:</span>
                        <span class="text-gray-900">{{ $location->location_type ?? 'Not specified' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Fiscal Year Start:</span>
                        <span class="text-gray-900">{{ $location->fiscal_year_start }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $location->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Head Office:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $location->is_head_office ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $location->is_head_office ? 'Yes' : 'No' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Staff Information -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Staff Information</h3>
                <div class="space-y-2">
                    <div>
                        <span class="font-medium text-gray-700">Total Staff (FTE):</span>
                        <span class="text-gray-900">{{ $location->staff_count ?? 'Not specified' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Work from Home:</span>
                        <span class="text-gray-900">{{ $location->staff_work_from_home ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <!-- Building Details -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Building Details</h3>
                <div class="space-y-2">
                    <div>
                        <span class="font-medium text-gray-700">Receives Utility Bills:</span>
                        <span class="text-gray-900">{{ $location->receives_utility_bills ? 'Yes' : 'No' }}</span>
                    </div>
                    @if(!$location->receives_utility_bills)
                        <div>
                            <span class="font-medium text-gray-700">Pays Electricity Proportion:</span>
                            <span class="text-gray-900">{{ $location->pays_electricity_proportion ? 'Yes' : 'No' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Shared Building Services:</span>
                            <span class="text-gray-900">{{ $location->shared_building_services ? 'Yes' : 'No' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Measurement Period -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Measurement Period</h3>
                <div class="space-y-2">
                    <div>
                        <span class="font-medium text-gray-700">Reporting Period:</span>
                        <span class="text-gray-900">{{ $location->reporting_period ?? 'Not specified' }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Measurement Frequency:</span>
                        <span class="text-gray-900">{{ $location->measurement_frequency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="flex space-x-4">
                    <form method="POST" action="{{ route('locations.toggle-status', $location) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 {{ $location->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition">
                            {{ $location->is_active ? 'Deactivate' : 'Activate' }} Location
                        </button>
                    </form>
                    
                    @if(!$location->is_head_office)
                        <form method="POST" action="{{ route('locations.toggle-head-office', $location) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                Set as Head Office
                            </button>
                        </form>
                    @endif
                </div>
                
                <div class="text-sm text-gray-500">
                    Created: {{ $location->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
