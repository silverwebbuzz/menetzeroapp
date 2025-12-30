@extends('layouts.app')

@section('title', 'Business Locations - MenetZero')
@section('page-title', 'Business Locations')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Business Locations</h1>
        <p class="mt-2 text-gray-600">Add the locations your business operated from during the period. Note: You need to add all locations that are under the operational control of your company.</p>
    </div>

    <!-- Add New Location Button -->
    <div class="flex justify-end mb-6">
        <a href="{{ route('locations.create') }}" class="inline-flex items-center gap-2 px-6 py-3 border border-orange-500 text-orange-600 bg-white rounded-lg hover:bg-orange-50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add New Location
        </a>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search by location</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search by location" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
                <select name="filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="">All</option>
                    <option value="active" {{ request('filter') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('filter') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="head_office" {{ request('filter') == 'head_office' ? 'selected' : '' }}>Head Office</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sort</label>
                <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Sort alphabetically</option>
                    <option value="created" {{ request('sort') == 'created' ? 'selected' : '' }}>Recently created</option>
                    <option value="staff" {{ request('sort') == 'staff' ? 'selected' : '' }}>By staff count</option>
                </select>
            </div>
            
            <div class="md:col-span-3 flex gap-2">
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Apply Filters
                </button>
                <a href="{{ route('locations.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Locations List -->
    @if($locations->count() > 0)
        <div class="space-y-4">
            @foreach($locations as $location)
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <!-- Left Section: Icon + Location Details -->
                        <div class="flex items-center space-x-4 flex-1">
                            <!-- Location Flag/Icon -->
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                @if($location->country === 'UAE' || $location->country === 'United Arab Emirates')
                                    <span class="text-2xl">🇦🇪</span>
                                @else
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                @endif
                            </div>
                            
                            <!-- Location Details -->
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $location->name }}</h3>
                                <p class="text-gray-600 text-sm">{{ $location->full_address }}</p>
                                <p class="text-sm text-gray-500">{{ $location->location_type }}</p>
                            </div>
                        </div>
                        
                        <!-- Right Section: Stats + Toggles + Actions -->
                        <div class="flex items-center space-x-8">
                            <!-- Stats -->
                            <div class="flex items-center space-x-6 text-sm">
                                <div class="text-center">
                                    <div class="font-semibold text-gray-900">Staff</div>
                                    <div class="text-gray-600">{{ $location->staff_count ?? 'N/A' }}</div>
                                </div>
                                
                                <div class="text-center">
                                    <div class="font-semibold text-gray-900">Fiscal Year</div>
                                    <div class="text-gray-600">{{ $location->fiscal_year_start }}</div>
                                </div>
                            </div>
                            
                            <!-- Toggles -->
                            <div class="flex items-center space-x-6">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Head Office</span>
                                    <form method="POST" action="{{ route('locations.toggle-head-office', $location) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 {{ $location->is_head_office ? 'bg-orange-600' : 'bg-gray-200' }}">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $location->is_head_office ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Deactivate</span>
                                    <form method="POST" action="{{ route('locations.toggle-status', $location) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 {{ !$location->is_active ? 'bg-orange-600' : 'bg-gray-200' }}">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ !$location->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('locations.edit', $location) }}" 
                                   class="px-4 py-2 border border-orange-500 text-orange-600 bg-white rounded-lg hover:bg-orange-50 transition">
                                    Edit
                                </a>
                                <a href="{{ route('emission-boundaries.index', $location) }}" 
                                   class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                                    New Measurement
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $locations->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No locations found</h3>
            <p class="text-gray-600 mb-6">Get started by adding your first business location.</p>
            <a href="{{ route('locations.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Location
            </a>
        </div>
    @endif

    <!-- Bottom Add Button -->
    @if($locations->count() > 0)
        <div class="mt-8 text-center">
            <a href="{{ route('locations.create') }}" class="inline-flex items-center gap-2 px-6 py-3 border border-orange-500 text-orange-600 bg-white rounded-lg hover:bg-orange-50 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add New Location
            </a>
        </div>
    @endif
</div>
@endsection
