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
        <a href="{{ route('measurements.create') }}" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
            Add New Measurement
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

    <!-- Measurements List -->
    @if($measurements->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($measurements as $measurement)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $measurement->location->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $measurement->period_start->format('M d, Y') }} - {{ $measurement->period_end->format('M d, Y') }}</p>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full 
                            @if($measurement->status === 'draft') bg-gray-100 text-gray-800
                            @elseif($measurement->status === 'submitted') bg-blue-100 text-blue-800
                            @elseif($measurement->status === 'under_review') bg-yellow-100 text-yellow-800
                            @elseif($measurement->status === 'not_verified') bg-red-100 text-red-800
                            @elseif($measurement->status === 'verified') bg-green-100 text-green-800
                            @endif">
                            {{ $measurement->status_display }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Frequency:</span>
                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $measurement->frequency)) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Fiscal Year:</span>
                            <span class="font-medium">{{ $measurement->fiscal_year }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total CO2e:</span>
                            <span class="font-medium text-orange-600">{{ number_format($measurement->total_co2e, 2) }} kg</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Data Points:</span>
                            <span class="font-medium">{{ $measurement->measurementData->count() }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <div class="flex space-x-2">
                            <a href="{{ route('measurements.show', $measurement) }}" 
                               class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                View
                            </a>
                            @if($measurement->canBeEdited())
                                <a href="{{ route('measurements.edit', $measurement) }}" 
                                   class="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                                    Edit
                                </a>
                            @endif
                        </div>
                        
                        @if($measurement->canBeSubmitted())
                            <form method="POST" action="{{ route('measurements.submit', $measurement) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-sm bg-orange-600 text-white rounded hover:bg-orange-700 transition">
                                    Submit
                                </button>
                            </form>
                        @endif
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
            <a href="{{ route('measurements.create') }}" class="px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Add New Measurement
            </a>
        </div>
    @endif
</div>
@endsection
