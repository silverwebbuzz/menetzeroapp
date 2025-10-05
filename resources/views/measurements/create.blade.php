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

    <form method="POST" action="{{ route('measurements.store') }}" class="space-y-6">
        @csrf
        
        <!-- Location Selection -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Location Selection</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available Periods</label>
                    <div id="available-periods" class="text-sm text-gray-600">
                        Select a location to see available measurement periods.
                    </div>
                </div>
            </div>
        </div>

        <!-- Measurement Period -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Period</h3>
            <p class="text-sm text-gray-600 mb-4">Select a measurement period from the available options below, or choose a custom period.</p>
            
            <!-- Available Periods Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Available Periods</label>
                <div id="available-periods" class="text-sm text-gray-600">
                    Select a location to see available measurement periods.
                </div>
            </div>
            
            <!-- Custom Period Input -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="period_start" class="block text-sm font-medium text-gray-700 mb-2">
                        Period Start <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="period_start" id="period_start" required
                           value="{{ old('period_start') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('period_start') border-red-500 @enderror">
                    @error('period_start')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="period_end" class="block text-sm font-medium text-gray-700 mb-2">
                        Period End <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="period_end" id="period_end" required
                           value="{{ old('period_end') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('period_end') border-red-500 @enderror">
                    @error('period_end')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Measurement Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="frequency" class="block text-sm font-medium text-gray-700 mb-2">
                        Frequency <span class="text-red-500">*</span>
                    </label>
                    <select name="frequency" id="frequency" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('frequency') border-red-500 @enderror">
                        <option value="">Select frequency...</option>
                        <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('frequency') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="half_yearly" {{ old('frequency') == 'half_yearly' ? 'selected' : '' }}>Half Yearly</option>
                        <option value="annually" {{ old('frequency') == 'annually' ? 'selected' : '' }}>Annually</option>
                    </select>
                    @error('frequency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-2">
                        Fiscal Year <span class="text-red-500">*</span>
                    </label>
                    <select name="fiscal_year" id="fiscal_year" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('fiscal_year') border-red-500 @enderror">
                        @for($year = date('Y'); $year >= 2020; $year--)
                            <option value="{{ $year }}" {{ old('fiscal_year', date('Y')) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                    @error('fiscal_year')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fiscal_year_start_month" class="block text-sm font-medium text-gray-700 mb-2">
                        Fiscal Year Start <span class="text-red-500">*</span>
                    </label>
                    <select name="fiscal_year_start_month" id="fiscal_year_start_month" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('fiscal_year_start_month') border-red-500 @enderror">
                        <option value="">Select month...</option>
                        <option value="JAN" {{ old('fiscal_year_start_month') == 'JAN' ? 'selected' : '' }}>January</option>
                        <option value="FEB" {{ old('fiscal_year_start_month') == 'FEB' ? 'selected' : '' }}>February</option>
                        <option value="MAR" {{ old('fiscal_year_start_month') == 'MAR' ? 'selected' : '' }}>March</option>
                        <option value="APR" {{ old('fiscal_year_start_month') == 'APR' ? 'selected' : '' }}>April</option>
                        <option value="MAY" {{ old('fiscal_year_start_month') == 'MAY' ? 'selected' : '' }}>May</option>
                        <option value="JUN" {{ old('fiscal_year_start_month') == 'JUN' ? 'selected' : '' }}>June</option>
                        <option value="JUL" {{ old('fiscal_year_start_month') == 'JUL' ? 'selected' : '' }}>July</option>
                        <option value="AUG" {{ old('fiscal_year_start_month') == 'AUG' ? 'selected' : '' }}>August</option>
                        <option value="SEP" {{ old('fiscal_year_start_month') == 'SEP' ? 'selected' : '' }}>September</option>
                        <option value="OCT" {{ old('fiscal_year_start_month') == 'OCT' ? 'selected' : '' }}>October</option>
                        <option value="NOV" {{ old('fiscal_year_start_month') == 'NOV' ? 'selected' : '' }}>November</option>
                        <option value="DEC" {{ old('fiscal_year_start_month') == 'DEC' ? 'selected' : '' }}>December</option>
                    </select>
                    @error('fiscal_year_start_month')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
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
            <button type="submit" 
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
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
        
        if (locationId) {
            // Show loading state
            availablePeriodsDiv.innerHTML = '<div class="text-blue-600">Loading available periods...</div>';
            
            // Fetch available periods for this location
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
                        data.periods.forEach(period => {
                            html += `<div class="p-2 bg-gray-50 rounded border cursor-pointer hover:bg-gray-100 transition" 
                                     onclick="selectPeriod('${period.start}', '${period.end}')">
                                        <div class="font-medium text-gray-900">${period.label}</div>
                                        <div class="text-sm text-gray-600">${period.start} to ${period.end}</div>
                                     </div>`;
                        });
                        html += '</div>';
                        availablePeriodsDiv.innerHTML = html;
                    } else {
                        availablePeriodsDiv.innerHTML = '<div class="text-gray-600">No available periods found for this location.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading periods:', error);
                    availablePeriodsDiv.innerHTML = '<div class="text-red-600">Error loading periods. Please try again or enter dates manually.</div>';
                });
        } else {
            availablePeriodsDiv.innerHTML = 'Select a location to see available measurement periods.';
        }
    });
    
    // Function to select a period
    window.selectPeriod = function(startDate, endDate) {
        document.getElementById('period_start').value = startDate;
        document.getElementById('period_end').value = endDate;
    };
});
</script>
@endsection
