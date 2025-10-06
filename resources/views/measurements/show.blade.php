@extends('layouts.app')

@section('title', 'Measurement Details - MenetZero')
@section('page-title', 'Measurement Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header with Measurement Info -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Measure Emissions</h1>
                <p class="mt-2 text-gray-600">Measure each source of emissions by entering your data for the period.</p>
            </div>
            
            <!-- Enhanced Help Section -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl shadow-lg border border-blue-200 p-6 max-w-sm">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Need Support?</h3>
                        <p class="text-sm text-gray-600">Expert guidance available</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg p-4 mb-4 border border-blue-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-500 rounded-full flex items-center justify-center mr-3">
                            <span class="text-white font-semibold text-sm">SJ</span>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">Sarah Johnson</div>
                            <div class="text-sm text-gray-600">Sustainability Expert</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-2">
                    <button class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-2 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 flex items-center justify-center text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        Call
                    </button>
                    <button class="flex-1 bg-white border border-blue-300 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 transition-all duration-200 flex items-center justify-center text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Chat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Measurement Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Location Card with Inline Edit -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Location</h3>
                <button onclick="toggleLocationEdit()" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </button>
            </div>
            
            <!-- View Mode -->
            <div id="location-view" class="flex items-center">
                <span class="text-2xl mr-3">🇦🇪</span>
                <div>
                    <div class="font-semibold text-gray-900">{{ $measurement->location->name }}</div>
                    <div class="text-sm text-gray-600">{{ $measurement->location->staff_count }} staff members</div>
                </div>
            </div>
            
            <!-- Edit Mode -->
            <div id="location-edit" class="hidden">
                <form method="POST" action="{{ route('locations.update', $measurement->location) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location Name</label>
                        <input type="text" name="name" value="{{ $measurement->location->name }}" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Staff Count</label>
                        <input type="number" name="staff_count" value="{{ $measurement->location->staff_count }}" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div class="flex space-x-2">
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm">
                            Save
                        </button>
                        <button type="button" onclick="toggleLocationEdit()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Measure Period Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Measure Period</h3>
            <div>
                <div class="font-semibold text-gray-900">
                    {{ \Carbon\Carbon::parse($measurement->period_start)->format('M Y') }} - 
                    {{ \Carbon\Carbon::parse($measurement->period_end)->format('M Y') }}
                </div>
                <div class="text-sm text-gray-600 capitalize">{{ $measurement->frequency }} Period</div>
            </div>
        </div>

        <!-- Emissions Summary Card -->
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-6 text-white">
            <h3 class="text-sm font-medium text-teal-100 mb-2">Emissions Calculations</h3>
            <div class="text-3xl font-bold">0.00t</div>
            <div class="text-sm text-teal-100">CO2e</div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Measurement Notes</h3>
            <button onclick="toggleNotesEdit()" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </button>
        </div>
        
        <!-- View Mode -->
        <div id="notes-view">
            @if($measurement->notes)
                <p class="text-gray-700 whitespace-pre-wrap">{{ $measurement->notes }}</p>
            @else
                <p class="text-gray-500 italic">No notes added yet. Click Edit to add notes about this measurement.</p>
            @endif
        </div>
        
        <!-- Edit Mode -->
        <div id="notes-edit" class="hidden">
            <form method="POST" action="{{ route('measurements.update', $measurement) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <textarea name="notes" rows="4" placeholder="Add any additional notes about this measurement..."
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('notes', $measurement->notes) }}</textarea>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm">
                        Save Notes
                    </button>
                    <button type="button" onclick="toggleNotesEdit()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Emission Sources by Scope -->
    @if($emissionBoundaries->count() > 0)
        @foreach(['Scope 1', 'Scope 2', 'Scope 3'] as $scope)
            @if(isset($emissionBoundaries[$scope]) && $emissionBoundaries[$scope]->count() > 0)
                @php
                    $boundary = $emissionBoundaries[$scope]->first();
                    $emissionSources = $boundary->emissionSources();
                @endphp
                
                @if($emissionSources && $emissionSources->count() > 0)
                <div class="mb-8">
                    <!-- Scope Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <h2 class="text-xl font-semibold text-gray-900 mr-2">{{ $scope }}</h2>
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div class="flex items-center">
                            <span class="text-sm text-gray-600 mr-2">Total Scope Emissions</span>
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">0.00 CO₂e</span>
                            <button class="ml-2 text-orange-500 hover:text-orange-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Emission Source Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($emissionSources as $source)
                            @php
                                $existingData = $measurement->measurementData()
                                    ->where('emission_source_id', $source->id)
                                    ->first();
                            @endphp
                            
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                                <!-- Source Title -->
                                <h3 class="font-semibold text-gray-900 text-lg mb-4">{{ $source->name }}</h3>
                                
                                <!-- Current Value -->
                                <div class="mb-4">
                                    <div class="text-lg font-medium text-gray-700">
                                        {{ $existingData ? number_format($existingData->calculated_co2e, 2) : '0.00' }}t CO2e
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <div class="mt-4">
                                    @if($existingData)
                                        <a href="{{ route('measurements.edit-source', ['measurement' => $measurement->id, 'source' => $source->id]) }}" 
                                           class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition text-center inline-block">
                                            Edit
                                        </a>
                                    @else
                                        <a href="{{ route('measurements.calculate-source', ['measurement' => $measurement->id, 'source' => $source->id]) }}" 
                                           class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition text-center inline-block">
                                            Calculate
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif
        @endforeach
    @else
        <!-- No Emission Boundaries Configured -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Emission Sources Configured</h3>
                <p class="text-gray-600 mb-6">
                    This location doesn't have any emission sources configured yet. Please contact your administrator to set up emission boundaries for this location.
                </p>
                <div class="flex justify-center space-x-3">
                    <a href="{{ route('measurements.index') }}" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Back to Measurements
                    </a>
                    <button onclick="location.reload()" 
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Refresh Page
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Progress Summary -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Progress</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ $measurement->measurementData->count() }}</div>
                <div class="text-sm text-gray-600">Sources Completed</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    {{ $emissionBoundaries->flatten()->count() }}
                </div>
                <div class="text-sm text-gray-600">Total Sources</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                    {{ number_format($measurement->measurementData->sum('calculated_co2e'), 2) }}t
                </div>
                <div class="text-sm text-gray-600">Total CO2e</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between items-center mt-8">
        <div class="flex gap-4">
            <a href="{{ route('measurements.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Back to Measurements
            </a>
        </div>
        
        @if($measurement->measurementData->count() > 0)
            <div class="flex gap-4">
                <button onclick="submitMeasurement({{ $measurement->id }})" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Submit for Review
                </button>
            </div>
        @endif
    </div>
</div>

<script>
function deleteSourceData(measurementId, sourceId) {
    if (confirm('Are you sure you want to delete this emission data?')) {
        fetch(`/measurements/${measurementId}/sources/${sourceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete emission data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the data');
        });
    }
}

function submitMeasurement(measurementId) {
    if (confirm('Are you sure you want to submit this measurement for review?')) {
        fetch(`/measurements/${measurementId}/submit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to submit measurement');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the measurement');
        });
    }
}
    </script>

    <script>
    function toggleLocationEdit() {
        const viewMode = document.getElementById('location-view');
        const editMode = document.getElementById('location-edit');
        
        if (viewMode.classList.contains('hidden')) {
            viewMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        } else {
            viewMode.classList.add('hidden');
            editMode.classList.remove('hidden');
        }
    }

    function toggleNotesEdit() {
        const viewMode = document.getElementById('notes-view');
        const editMode = document.getElementById('notes-edit');
        
        if (viewMode.classList.contains('hidden')) {
            viewMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        } else {
            viewMode.classList.add('hidden');
            editMode.classList.remove('hidden');
        }
    }
    </script>
    @endsection