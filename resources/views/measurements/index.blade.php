@extends('layouts.app')

@section('title', 'Measurements - MenetZero')
@section('page-title', 'Measurements')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Measurements</h1>
            <p class="mt-2 text-gray-600">Manage your carbon footprint measurements and data collection.</p>
        </div>
        <a href="{{ route('locations.index') }}" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
            Manage Locations
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('measurements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <select name="location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="not_verified" {{ request('status') == 'not_verified' ? 'selected' : '' }}>Not Verified</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Year</label>
                <select name="fiscal_year" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All Years</option>
                    @for($year = date('Y'); $year >= 2020; $year--)
                        <option value="{{ $year }}" {{ request('fiscal_year') == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search measurements..." 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>
            
            <div class="md:col-span-4 flex justify-end space-x-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Apply Filters
                </button>
                <a href="{{ route('measurements.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Measurements List - Grouped by Location and Year -->
    @if($measurements->count() > 0)

        <div class="space-y-8">
            @foreach($groupedMeasurements as $locationName => $yearGroups)
                <!-- Location Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Location Header -->
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">{{ $locationName }}</h2>
                                    <p class="text-sm text-gray-600">{{ $yearGroups->flatten()->count() }} measurement(s) across {{ $yearGroups->count() }} year(s)</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Total CO2e</div>
                                <div class="text-lg font-bold text-orange-600">
                                    {{ number_format($yearGroups->flatten()->sum('total_co2e'), 2) }} kg
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Years Container - Vertical Listing -->
                    <div class="p-6">
                        <div class="space-y-6">
                            @foreach($yearGroups as $year => $yearMeasurements)
                                <!-- Year Section -->
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <!-- Year Header -->
                                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-semibold text-gray-900">Fiscal Year {{ $year }}</h3>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                                <span>{{ $yearMeasurements->count() }} measurement(s)</span>
                                                <span class="text-orange-600 font-medium">
                                                    {{ number_format($yearMeasurements->sum('total_co2e'), 2) }} kg CO2e
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Measurements List - Full Width Listing -->
                                    <div class="bg-white">
                                        @foreach($yearMeasurements as $index => $measurement)
                                            <div class="border-b border-gray-100 last:border-b-0">
                                                <div class="px-4 py-4 hover:bg-gray-50 transition-colors">
                                                    <div class="flex items-center justify-between">
                                                        <!-- Left Side - Period Info -->
                                                        <div class="flex-1">
                                                            <div class="flex items-center space-x-6">
                                                                <!-- Period -->
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        {{ $measurement->period_start->format('M d') }} - {{ $measurement->period_end->format('M d, Y') }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">
                                                                        {{ ucfirst(str_replace('_', ' ', $measurement->frequency)) }}
                                                                    </div>
                                                                </div>

                                                                <!-- Status -->
                                                                <div class="flex-shrink-0">
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                                        @if($measurement->status === 'draft') bg-gray-100 text-gray-800
                                                                        @elseif($measurement->status === 'submitted') bg-blue-100 text-blue-800
                                                                        @elseif($measurement->status === 'under_review') bg-yellow-100 text-yellow-800
                                                                        @elseif($measurement->status === 'not_verified') bg-red-100 text-red-800
                                                                        @elseif($measurement->status === 'verified') bg-green-100 text-green-800
                                                                        @endif">
                                                                        {{ ucfirst(str_replace('_', ' ', $measurement->status)) }}
                                                                    </span>
                                                                </div>

                                                                <!-- CO2e -->
                                                                <div class="flex-shrink-0 text-right">
                                                                    <div class="text-sm text-gray-600">Total CO2e</div>
                                                                    <div class="text-sm font-bold text-orange-600">
                                                                        {{ number_format($measurement->total_co2e, 2) }} kg
                                                                    </div>
                                                                </div>

                                                                <!-- Data Points -->
                                                                <div class="flex-shrink-0 text-right">
                                                                    <div class="text-xs text-gray-500">
                                                                        {{ $measurement->measurementData->count() }} data point(s)
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Right Side - Actions -->
                                                        <div class="flex-shrink-0 ml-4">
                                                            <div class="flex items-center space-x-2">
                                                                <a href="{{ route('measurements.show', $measurement) }}" 
                                                                   class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                                                    View
                                                                </a>
                                                                @if($measurement->canBeEdited())
                                                                    <a href="{{ route('measurements.edit', $measurement) }}" 
                                                                       class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                                                                        Edit
                                                                    </a>
                                                                @endif
                                                                @if($measurement->canBeSubmitted())
                                                                    <form method="POST" action="{{ route('measurements.submit', $measurement) }}" class="inline">
                                                                        @csrf
                                                                        <button type="submit" class="px-3 py-1 text-xs bg-orange-600 text-white rounded hover:bg-orange-700 transition">
                                                                            Submit
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $measurements->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No measurements found</h3>
            <p class="text-gray-600 mb-6">Get started by creating your first measurement.</p>
            <a href="{{ route('locations.index') }}" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Manage Locations
            </a>
        </div>
    @endif
</div>
@endsection
