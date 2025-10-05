@extends('layouts.app')

@section('title', 'Create Measurement - MenetZero')
@section('page-title', 'Create Measurement')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create New Measurement</h1>
        <p class="mt-2 text-gray-600">Set up a new carbon footprint measurement period for your location.</p>
    </div>

    <form method="POST" action="{{ route('measurements.store') }}" class="space-y-6" onsubmit="console.log('Form submitting...', this); return true;">
        @csrf
        
        <!-- Location Selection -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Location Selection</h3>
            
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Select Location <span class="text-red-500">*</span>
                </label>
                <select name="location_id" id="location_id" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('location_id') border-red-500 @enderror">
                    <option value="">Choose a location...</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }} ({{ $location->city }}, {{ $location->country }})
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Measurement Period Selection -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" id="measurement-period-section" style="display: none;">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Measurement Period</h3>
            <p class="text-sm text-gray-600 mb-4">Choose a measurement period based on your location's settings.</p>
            
            <!-- Available Periods -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Periods</label>
                <div id="available-periods" class="text-sm text-gray-600">
                    Loading periods...
                </div>
            </div>
            
            <!-- Hidden inputs for selected period -->
            <input type="hidden" name="period_start" id="period_start" value="{{ old('period_start') }}">
            <input type="hidden" name="period_end" id="period_end" value="{{ old('period_end') }}">
            <input type="hidden" name="frequency" id="frequency" value="{{ old('frequency') }}">
            <input type="hidden" name="fiscal_year" id="fiscal_year" value="{{ old('fiscal_year', date('Y')) }}">
            <input type="hidden" name="fiscal_year_start_month" id="fiscal_year_start_month" value="{{ old('fiscal_year_start_month') }}">
        </div>

        <!-- Additional Notes -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
            
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes (Optional)
                </label>
                <textarea name="notes" id="notes" rows="3"
                          placeholder="Add any additional notes about this measurement..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-6">
            <a href="{{ route('measurements.index') }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" id="create-measurement-btn"
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                    disabled
                    onclick="console.log('Button clicked, disabled:', this.disabled);">
                Create Measurement
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('location_id');
    const availablePeriodsDiv = document.getElementById('available-periods');
    
    locationSelect.addEventListener('change', function() {
        const locationId = this.value;
        const measurementSection = document.getElementById('measurement-period-section');
        
        if (locationId) {
            // Show the measurement period section
            measurementSection.style.display = 'block';
            
            // Show loading state
            availablePeriodsDiv.innerHTML = '<div class="text-blue-600">Loading available periods...</div>';
            
            // Fetch available periods for this location
            console.log('Fetching periods for location ID:', locationId);
            fetch(`/measurements/available-periods/${locationId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received periods data:', data);
                    if (data.periods && data.periods.length > 0) {
                        let html = '<div class="space-y-2">';
                        data.periods.forEach((period, index) => {
                            html += `<label class="flex items-center p-3 bg-gray-50 rounded border cursor-pointer hover:bg-orange-50 hover:border-orange-300 transition">
                                        <input type="radio" name="selected_period" value="${index}" 
                                               class="mr-3 text-orange-600 focus:ring-orange-500" 
                                               onchange="selectPeriod('${period.start}', '${period.end}', '${period.frequency || 'annually'}', '${period.fiscal_year || new Date().getFullYear()}', '${period.fiscal_start || 'JAN'}')">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-900">${period.label}</div>
                                            <div class="text-sm text-gray-600">${period.start} to ${period.end}</div>
                                        </div>
                                     </label>`;
                        });
                        html += '</div>';
                        availablePeriodsDiv.innerHTML = html;
                    } else {
                        availablePeriodsDiv.innerHTML = '<div class="text-gray-600">No available periods found for this location.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading periods:', error);
                    availablePeriodsDiv.innerHTML = '<div class="text-red-600">Error loading periods. Please try again.</div>';
                });
        } else {
            // Hide the measurement period section
            measurementSection.style.display = 'none';
            availablePeriodsDiv.innerHTML = 'Select a location to see available measurement periods.';
        }
    });
    
    // Function to select a period
    window.selectPeriod = function(startDate, endDate, frequency, fiscalYear, fiscalStart) {
        console.log('selectPeriod called with:', { startDate, endDate, frequency, fiscalYear, fiscalStart });
        
        document.getElementById('period_start').value = startDate;
        document.getElementById('period_end').value = endDate;
        document.getElementById('frequency').value = frequency;
        document.getElementById('fiscal_year').value = fiscalYear;
        document.getElementById('fiscal_year_start_month').value = fiscalStart;
        
        // Enable the create button
        const button = document.getElementById('create-measurement-btn');
        button.disabled = false;
        button.classList.remove('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
        button.classList.add('bg-orange-600', 'hover:bg-orange-700');
        
        console.log('Button enabled, disabled:', button.disabled);
        console.log('Form values:', {
            period_start: document.getElementById('period_start').value,
            period_end: document.getElementById('period_end').value,
            frequency: document.getElementById('frequency').value,
            fiscal_year: document.getElementById('fiscal_year').value,
            fiscal_year_start_month: document.getElementById('fiscal_year_start_month').value
        });
    };
});
</script>
@endsection
