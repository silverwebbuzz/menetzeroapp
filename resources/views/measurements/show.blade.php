@extends('layouts.app')

@section('title', 'Measurement Details - MenetZero')
@section('page-title', 'Measurement Details')

@section('content')
<div class="w-full">
    <!-- Header with Measurement Info -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Measure Emissions</h1>
                <p class="mt-2 text-gray-600">Measure each source of emissions by entering your data for the period.</p>
            </div>
        </div>
    </div>

    <!-- Measurement Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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

            <!-- Staff Information Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500">Staff Information</h3>
                    <button onclick="toggleStaffEdit()" class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </button>
                </div>
                
                <!-- View Mode -->
                <div id="staff-view">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Staff:</span>
                            <span class="font-semibold text-gray-900">{{ $measurement->staff_count ?? 'Not set' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Work from Home:</span>
                            <span class="font-semibold text-gray-900">
                                @if($measurement->staff_work_from_home)
                                    {{ $measurement->work_from_home_percentage ?? 100 }}%
                                @else
                                    No
                                @endif
                            </span>
                        </div>
                        @if($measurement->staff_work_from_home && $measurement->staff_count)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Office Staff:</span>
                                <span class="font-semibold text-gray-900">
                                    {{ $measurement->staff_count - round(($measurement->staff_count * ($measurement->work_from_home_percentage ?? 100)) / 100) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Edit Mode -->
                <div id="staff-edit" class="hidden">
                    <form method="POST" action="{{ route('measurements.update', $measurement) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Staff Count</label>
                            <input type="number" name="staff_count" value="{{ $measurement->staff_count }}" 
                                   min="1" max="10000"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="staff_work_from_home" value="1" 
                                       {{ $measurement->staff_work_from_home ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-sm text-gray-700">Staff work from home</span>
                            </label>
                        </div>
                        <div id="wfh-percentage" class="{{ $measurement->staff_work_from_home ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Work from Home Percentage</label>
                            <input type="number" name="work_from_home_percentage" 
                                   value="{{ $measurement->work_from_home_percentage ?? 100 }}" 
                                   min="0" max="100" step="0.01"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm">
                                Save Staff Info
                            </button>
                            <button type="button" onclick="toggleStaffEdit()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <!-- Emissions Summary Card -->
        <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-6 text-white">
            <h3 class="text-sm font-medium text-teal-100 mb-2">Emissions Calculations</h3>
            <div class="text-3xl font-bold">{{ number_format($measurement->total_co2e ?? 0, 2) }}t</div>
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
                    $scopeNumber = str_replace('Scope ', '', $scope);
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
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">{{ number_format($measurement->{"scope_{$scopeNumber}_co2e"} ?? 0, 2) }} CO₂e</span>
                            <button class="ml-2 text-orange-500 hover:text-orange-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Emission Sources Listing -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-2/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emission Source</th>
                                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Emissions</th>
                                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($emissionSources as $source)
                                        @php
                                            $existingData = $measurement->measurementData()
                                                ->where('emission_source_id', $source->id)
                                                ->get()
                                                ->keyBy('field_name');
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900 mb-1">{{ $source->name }}</div>
                                                <div class="text-sm text-gray-500 leading-relaxed">{{ $source->description ?? 'No description available' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    @php
                                                        $co2e = $measurement->emission_source_co2e[$source->id] ?? 0;
                                                    @endphp
                                                    {{ number_format($co2e, 2) }}t CO2e
                                                </div>
                                                @if($existingData && $existingData->count() > 0)
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        Last updated: {{ $existingData->first()->updated_at->format('M d, Y') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($existingData && $existingData->count() > 0)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Completed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                @if($existingData && $existingData->count() > 0)
                                                    <a href="{{ route('measurements.edit-source', ['measurement' => $measurement->id, 'source' => $source->id]) }}" 
                                                       class="inline-flex items-center px-3 py-1 border border-orange-300 text-orange-700 rounded-md hover:bg-orange-50 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                @else
                                                    <a href="{{ route('measurements.calculate-source', ['measurement' => $measurement->id, 'source' => $source->id]) }}" 
                                                       class="inline-flex items-center px-3 py-1 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Calculate
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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
                    {{ number_format($measurement->total_co2e, 2) }}t
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

    function toggleStaffEdit() {
        const viewMode = document.getElementById('staff-view');
        const editMode = document.getElementById('staff-edit');
        
        if (viewMode.classList.contains('hidden')) {
            viewMode.classList.remove('hidden');
            editMode.classList.add('hidden');
        } else {
            viewMode.classList.add('hidden');
            editMode.classList.remove('hidden');
        }
    }

    // Handle work from home checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const wfhCheckbox = document.querySelector('input[name="staff_work_from_home"]');
        const wfhPercentage = document.getElementById('wfh-percentage');
        
        if (wfhCheckbox) {
            wfhCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    wfhPercentage.classList.remove('hidden');
                } else {
                    wfhPercentage.classList.add('hidden');
                }
            });
        }
    });
    </script>
    @endsection