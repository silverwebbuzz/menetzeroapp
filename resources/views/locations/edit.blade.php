@extends('layouts.app')

@section('title', 'Edit Location - MenetZero')
@section('page-title', 'Edit Location')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Location</h2>
        
        <form method="POST" action="{{ route('locations.update', $location) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Business Location</label>
                    <input type="text" name="name" value="{{ old('name', $location->name) }}" 
                           placeholder="Enter the building name, street name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500" required>
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                        <select name="country" id="country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select a country</option>
                            <option value="UAE" {{ old('country', $location->country) == 'UAE' ? 'selected' : '' }}>United Arab Emirates</option>
                            <option value="SA" {{ old('country', $location->country) == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                            <option value="KW" {{ old('country', $location->country) == 'KW' ? 'selected' : '' }}>Kuwait</option>
                            <option value="QA" {{ old('country', $location->country) == 'QA' ? 'selected' : '' }}>Qatar</option>
                            <option value="BH" {{ old('country', $location->country) == 'BH' ? 'selected' : '' }}>Bahrain</option>
                            <option value="OM" {{ old('country', $location->country) == 'OM' ? 'selected' : '' }}>Oman</option>
                            <option value="US" {{ old('country', $location->country) == 'US' ? 'selected' : '' }}>United States</option>
                            <option value="UK" {{ old('country', $location->country) == 'UK' ? 'selected' : '' }}>United Kingdom</option>
                            <option value="IN" {{ old('country', $location->country) == 'IN' ? 'selected' : '' }}>India</option>
                            <option value="Other" {{ old('country', $location->country) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('country')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                        <select name="city" id="city" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select city</option>
                        </select>
                        @error('city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="3" 
                              placeholder="Enter the full address" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('address', $location->address) }}</textarea>
                    @error('address')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location type</label>
                        <select name="location_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">Select location type</option>
                            <option value="Co-Working Desks" {{ old('location_type', $location->location_type) == 'Co-Working Desks' ? 'selected' : '' }}>Co-Working Desks</option>
                            <option value="Office" {{ old('location_type', $location->location_type) == 'Office' ? 'selected' : '' }}>Office</option>
                            <option value="Warehouse" {{ old('location_type', $location->location_type) == 'Warehouse' ? 'selected' : '' }}>Warehouse</option>
                            <option value="Factory" {{ old('location_type', $location->location_type) == 'Factory' ? 'selected' : '' }}>Factory</option>
                            <option value="Retail Store" {{ old('location_type', $location->location_type) == 'Retail Store' ? 'selected' : '' }}>Retail Store</option>
                            <option value="Data Center" {{ old('location_type', $location->location_type) == 'Data Center' ? 'selected' : '' }}>Data Center</option>
                            <option value="Other" {{ old('location_type', $location->location_type) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('location_type')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Year start from</label>
                        <select name="fiscal_year_start" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="January" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'January' ? 'selected' : '' }}>January</option>
                            <option value="February" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'February' ? 'selected' : '' }}>February</option>
                            <option value="March" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'March' ? 'selected' : '' }}>March</option>
                            <option value="April" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'April' ? 'selected' : '' }}>April</option>
                            <option value="May" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'May' ? 'selected' : '' }}>May</option>
                            <option value="June" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'June' ? 'selected' : '' }}>June</option>
                            <option value="July" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'July' ? 'selected' : '' }}>July</option>
                            <option value="August" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'August' ? 'selected' : '' }}>August</option>
                            <option value="September" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'September' ? 'selected' : '' }}>September</option>
                            <option value="October" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'October' ? 'selected' : '' }}>October</option>
                            <option value="November" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'November' ? 'selected' : '' }}>November</option>
                            <option value="December" {{ old('fiscal_year_start', $location->fiscal_year_start) == 'December' ? 'selected' : '' }}>December</option>
                        </select>
                        @error('fiscal_year_start')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                
                <!-- Staff Details -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Staff Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total number of staff (FTE)</label>
                            <input type="number" name="staff_count" value="{{ old('staff_count', $location->staff_count) }}" min="0" 
                                   placeholder="10" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            @error('staff_count')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Have staff regularly worked from home?</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="staff_work_from_home" {{ old('staff_work_from_home', $location->staff_work_from_home) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Utility and Building Details -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Utility and Building Details</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Do you receive the utility bills for the entire office building?</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="receives_utility_bills" {{ old('receives_utility_bills', $location->receives_utility_bills) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Office Building Details</h4>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Do you pay your proportion of the electricity bill for this location?</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="pays_electricity_proportion" {{ old('pays_electricity_proportion', $location->pays_electricity_proportion) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Is your office space part of a larger building with shared services (lifts, lobbies, aircon)?</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="shared_building_services" {{ old('shared_building_services', $location->shared_building_services) ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Measurement Period -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Period</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reporting Period</label>
                            <select name="reporting_period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                <option value="">Select reporting period</option>
                                @for($year = date('Y'); $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ old('reporting_period', $location->reporting_period) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                            @error('reporting_period')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Measurement Frequency</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="measurement_frequency" value="Annually" {{ old('measurement_frequency', $location->measurement_frequency) == 'Annually' ? 'checked' : '' }} class="mr-2">
                                    <span class="text-sm">Annually</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="measurement_frequency" value="Half Yearly" {{ old('measurement_frequency', $location->measurement_frequency) == 'Half Yearly' ? 'checked' : '' }} class="mr-2">
                                    <span class="text-sm">Half Yearly</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="measurement_frequency" value="Quarterly" {{ old('measurement_frequency', $location->measurement_frequency) == 'Quarterly' ? 'checked' : '' }} class="mr-2">
                                    <span class="text-sm">Quarterly</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="measurement_frequency" value="Monthly" {{ old('measurement_frequency', $location->measurement_frequency) == 'Monthly' ? 'checked' : '' }} class="mr-2">
                                    <span class="text-sm">Monthly</span>
                                </label>
                            </div>
                            @error('measurement_frequency')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                
                <!-- Status Options -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Options</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Head Office</p>
                                <p class="text-xs text-gray-500">Mark this location as the head office</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_head_office" {{ old('is_head_office', $location->is_head_office) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Active</p>
                                <p class="text-xs text-gray-500">This location is currently active</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_active" {{ old('is_active', $location->is_active) ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-8">
                <a href="{{ route('locations.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                    Update Location
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}
.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: #f97316;
}
input:checked + .slider:before {
    transform: translateX(20px);
}
</style>

<script>
// City options based on country
document.getElementById('country').addEventListener('change', function() {
    const citySelect = document.getElementById('city');
    const country = this.value;
    
    citySelect.innerHTML = '<option value="">Select city</option>';
    
    const cities = {
        'UAE': ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'],
        'SA': ['Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam'],
        'KW': ['Kuwait City', 'Hawalli', 'Ahmadi'],
        'QA': ['Doha', 'Al Rayyan', 'Al Wakrah'],
        'BH': ['Manama', 'Riffa', 'Muharraq'],
        'OM': ['Muscat', 'Salalah', 'Nizwa'],
        'US': ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'],
        'UK': ['London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool'],
        'IN': ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata']
    };
    
    if (cities[country]) {
        cities[country].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            if (city === '{{ $location->city }}') {
                option.selected = true;
            }
            citySelect.appendChild(option);
        });
    }
});

// Initialize city options on page load
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country');
    if (countrySelect.value) {
        countrySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
