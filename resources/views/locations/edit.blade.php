@extends('layouts.app')

@section('title', 'Edit Location - MenetZero')
@section('page-title', 'Edit Location')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<style>
    .step-indicator { 
        display: flex; 
        align-items: center; 
        padding: 0.75rem 1rem; 
        border-radius: 0.5rem; 
        font-weight: 500; 
        transition: all 0.2s; 
        border: 1px solid transparent;
    }
    .step-indicator.active { 
        background: #f97316; 
        color: white; 
        border-color: #f97316;
    }
    .step-indicator.completed { 
        background: #10b981; 
        color: white; 
        border-color: #10b981;
    }
    .step-indicator.pending { 
        background: #f8fafc; 
        color: #6b7280; 
        border-color: #e5e7eb;
    }
    .step-content { display: none; }
    .step-content.active { display: block; }
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

<div class="max-w-4xl mx-auto">
    <!-- Progress Steps -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Location</h2>
        <div class="flex space-x-4">
            <div class="step-indicator active" id="step-1-indicator">
                <span class="w-6 h-6 rounded-full bg-white text-orange-600 flex items-center justify-center text-sm font-bold mr-3">1</span>
                Choose business location
            </div>
            <div class="step-indicator pending" id="step-2-indicator">
                <span class="w-6 h-6 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-sm font-bold mr-3">2</span>
                Staff Details
            </div>
            <div class="step-indicator pending" id="step-3-indicator">
                <span class="w-6 h-6 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center text-sm font-bold mr-3">3</span>
                Create a new measurement period
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('locations.update', $location) }}" id="location-form">
        @csrf
        @method('PUT')
        
        <!-- Step 1: Choose business location -->
        <div class="step-content active" id="step-1">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Choose business location</h3>
                
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
                                @if($location->city)
                                    <option value="{{ $location->city }}" selected>{{ $location->city }}</option>
                                @endif
                            </select>
                            @error('city')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <textarea name="address" rows="3" 
                                  placeholder="Enter the full address" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('address', $location->address) }}</textarea>
                        @error('address')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <!-- Utility and Building Details -->
                    <div class="border-t pt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Utility and Building Details</h4>
                        
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
                            
                            <div class="bg-gray-50 rounded-lg p-4" id="building-details">
                                <h5 class="text-sm font-semibold text-gray-900 mb-3">Office Building Details</h5>
                                
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
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" onclick="nextStep()" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Staff Details -->
        <div class="step-content" id="step-2">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Staff Details</h3>
                <p class="text-sm text-gray-600 mb-6">Tell us about your employees and staff</p>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Input the average number for the period you are measuring: Total number of staff(FTE)</label>
                        <p class="text-xs text-gray-500 mb-3">Staff are a source of indirect emissions. Include full-time, flexible, and remote employees. Do not include independent contractors not on your payroll.</p>
                        <input type="number" name="staff_count" value="{{ old('staff_count', $location->staff_count) }}" min="1" required
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
                
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="nextStep()" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Create a new measurement period -->
        <div class="step-content" id="step-3">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Create a new measurement period</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-gray-600" id="location-preview">{{ $location->name }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal year start</label>
                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-gray-600" id="fiscal-preview">{{ $location->fiscal_year_start }}</div>
                    </div>
                    
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
                
                <div class="flex justify-between mt-6">
                    <button type="button" onclick="prevStep()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Back
                    </button>
                    <button type="button" onclick="saveAndFinish()" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Save and Close
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.step-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Show current step
    document.getElementById(`step-${step}`).classList.add('active');
    
    // Update indicators
    for (let i = 1; i <= totalSteps; i++) {
        const indicator = document.getElementById(`step-${i}-indicator`);
        if (i < step) {
            indicator.className = 'step-indicator completed';
        } else if (i === step) {
            indicator.className = 'step-indicator active';
        } else {
            indicator.className = 'step-indicator pending';
        }
    }
    
    // Update previews in step 3
    if (step === 3) {
        const locationName = document.querySelector('input[name="name"]').value || 'Location';
        const fiscalYear = document.querySelector('select[name="fiscal_year_start"]').value || 'January';
        
        document.getElementById('location-preview').textContent = locationName;
        document.getElementById('fiscal-preview').textContent = fiscalYear;
    }
}

function nextStep() {
    if (currentStep < totalSteps) {
        // Save current step data before moving to next step
        saveStepData();
        currentStep++;
        showStep(currentStep);
    }
}

function saveStepData() {
    const formData = new FormData();
    const step = currentStep;
    
    // Add CSRF token
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    // Collect data based on current step
    if (step === 1) {
        formData.append('name', document.querySelector('input[name="name"]').value);
        formData.append('address', document.querySelector('textarea[name="address"]').value);
        formData.append('city', document.querySelector('select[name="city"]').value);
        formData.append('country', document.querySelector('select[name="country"]').value);
        formData.append('location_type', document.querySelector('select[name="location_type"]').value);
        formData.append('fiscal_year_start', document.querySelector('select[name="fiscal_year_start"]').value);
        formData.append('receives_utility_bills', document.querySelector('input[name="receives_utility_bills"]').checked ? '1' : '0');
        formData.append('pays_electricity_proportion', document.querySelector('input[name="pays_electricity_proportion"]').checked ? '1' : '0');
        formData.append('shared_building_services', document.querySelector('input[name="shared_building_services"]').checked ? '1' : '0');
    } else if (step === 2) {
        formData.append('staff_count', document.querySelector('input[name="staff_count"]').value);
        formData.append('staff_work_from_home', document.querySelector('input[name="staff_work_from_home"]').checked ? '1' : '0');
    } else if (step === 3) {
        formData.append('reporting_period', document.querySelector('select[name="reporting_period"]').value);
        formData.append('measurement_frequency', document.querySelector('input[name="measurement_frequency"]:checked')?.value || 'Annually');
    }
    
    // Send AJAX request to save step
    fetch(`/locations/step/step${step}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            // If redirected (final step), follow the redirect
            window.location.href = response.url;
        } else {
            return response.json();
        }
    })
    .then(data => {
        if (data && data.success) {
            console.log('Step data saved successfully');
        }
    })
    .catch(error => {
        console.error('Error saving step data:', error);
    });
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function saveAndFinish() {
    // Save final step data - this will redirect automatically for step 3
    saveStepData();
}

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
            citySelect.appendChild(option);
        });
        
        // Set the current city if it exists and matches the country
        const currentCity = '{{ $location->city }}';
        if (currentCity && cities[country].includes(currentCity)) {
            citySelect.value = currentCity;
        }
    }
});

// Handle utility bills logic
document.querySelector('input[name="receives_utility_bills"]').addEventListener('change', function() {
    const buildingDetails = document.getElementById('building-details');
    if (this.checked) {
        // If company receives utility bills for entire building, hide the other questions
        buildingDetails.style.display = 'none';
        // Uncheck the other options since they don't apply
        document.querySelector('input[name="pays_electricity_proportion"]').checked = false;
        document.querySelector('input[name="shared_building_services"]').checked = false;
    } else {
        // If company doesn't receive utility bills, show the other questions
        buildingDetails.style.display = 'block';
    }
});

// Initialize utility bills logic on page load
document.addEventListener('DOMContentLoaded', function() {
    const receivesUtilityBills = document.querySelector('input[name="receives_utility_bills"]');
    const buildingDetails = document.getElementById('building-details');
    
    if (receivesUtilityBills.checked) {
        buildingDetails.style.display = 'none';
    } else {
        buildingDetails.style.display = 'block';
    }
    
    // Initialize city options
    const countrySelect = document.getElementById('country');
    if (countrySelect.value) {
        // Trigger city population
        countrySelect.dispatchEvent(new Event('change'));
    }
});

// Initialize
showStep(1);
</script>
@endsection