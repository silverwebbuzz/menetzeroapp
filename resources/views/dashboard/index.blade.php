@extends('layouts.app')



@section('content')
@if(isset($needsCompanySetup) && $needsCompanySetup)
    <!-- Company Setup Form - Show ONLY when company not added -->
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Setup Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <img src="https://app.menetzero.com/public/images/menetzero.svg" alt="MIDDLE EAST NET Zero" class="h-6 w-auto">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Complete Your Business Profile</h2>
                        <p class="text-sm text-gray-600">Please complete your business profile to get started with carbon tracking.</p>
                    </div>
                </div>
            </div>

            <!-- Setup Body -->
            <div class="p-6">
                <!-- Progress Indicator -->
                <div class="flex items-center gap-4 mb-6">
                    <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Personal Profile ✓
                    </div>
                    <div class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        Business Details
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('company.setup.store') }}" class="space-y-6" id="companySetupForm">
                    @csrf
                    
                    <!-- Business Information -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Name *</label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                       name="company_name" type="text" value="{{ old('company_name', isset($company) ? $company->name : '') }}" required placeholder="Enter your business name">
                                @error('company_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Email</label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                       name="business_email" type="email" value="{{ old('business_email', isset($company) ? $company->email : auth()->user()->email) }}" placeholder="business@company.com">
                                @error('business_email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Website</label>
                                <input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                       name="business_website" type="url" value="{{ old('business_website', isset($company) ? $company->website : '') }}" placeholder="https://www.company.com">
                                @error('business_website')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="UAE" {{ old('country', isset($company) ? $company->country : '') == 'UAE' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="SA" {{ old('country', isset($company) ? $company->country : '') == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="KW" {{ old('country', isset($company) ? $company->country : '') == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                    <option value="QA" {{ old('country', isset($company) ? $company->country : '') == 'QA' ? 'selected' : '' }}>Qatar</option>
                                    <option value="BH" {{ old('country', isset($company) ? $company->country : '') == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                    <option value="OM" {{ old('country', isset($company) ? $company->country : '') == 'OM' ? 'selected' : '' }}>Oman</option>
                                    <option value="US" {{ old('country', isset($company) ? $company->country : '') == 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="UK" {{ old('country', isset($company) ? $company->country : '') == 'UK' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="IN" {{ old('country', isset($company) ? $company->country : '') == 'IN' ? 'selected' : '' }}>India</option>
                                    <option value="Other" {{ old('country', isset($company) ? $company->country : '') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('country')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                      name="business_address" rows="3" placeholder="Enter your registered business address">{{ old('business_address', isset($company) ? $company->address : '') }}</textarea>
                            @error('business_address')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Business Details</h3>
                        <p class="text-sm text-gray-600 mb-4">Describe briefly what your business does.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Sector -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sector *</label>
                                <select name="sector" id="sector" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                                    <option value="">Select Sector</option>
                                    @if(isset($sectors))
                                        @foreach($sectors as $sector)
                                            <option value="{{ $sector->name }}" data-id="{{ $sector->id }}" {{ old('sector', isset($company) ? $company->sector : '') == $sector->name ? 'selected' : '' }}>{{ $sector->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('sector')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <!-- Industry -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Industry *</label>
                                <select name="industry" id="industry" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" disabled required>
                                    <option value="">Select Industry</option>
                                </select>
                                @error('industry')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <!-- Business Subcategory -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Subcategory</label>
                                <select name="business_subcategory" id="business_subcategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" disabled>
                                    <option value="">Select Subcategory (Optional)</option>
                                </select>
                                @error('business_subcategory')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Description</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                      name="business_description" rows="4" placeholder="Tell us a bit about what you do...">{{ old('business_description', isset($company) ? $company->description : '') }}</textarea>
                            @error('business_description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const sectorSelect = document.getElementById('sector');
                        const industrySelect = document.getElementById('industry');
                        const subcategorySelect = document.getElementById('business_subcategory');
                        
                        if (!sectorSelect || !industrySelect || !subcategorySelect) {
                            return;
                        }
                        
                        sectorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const sectorId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                            
                            if (sectorId) {
                                fetch(`/api/industries?sector_id=${sectorId}`)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (Array.isArray(data)) {
                                            industrySelect.innerHTML = '<option value="">Select Industry</option>';
                                            data.forEach(industry => {
                                                industrySelect.innerHTML += `<option value="${industry.name}" data-id="${industry.id}">${industry.name}</option>`;
                                            });
                                            industrySelect.disabled = false;
                                            
                                            // Reset subcategory
                                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                            subcategorySelect.disabled = true;
                                        } else {
                                            console.error('Invalid data format received:', data);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching industries:', error);
                                        industrySelect.innerHTML = '<option value="">Error loading industries</option>';
                                        industrySelect.disabled = true;
                                        subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                        subcategorySelect.disabled = true;
                                    });
                            } else {
                                industrySelect.innerHTML = '<option value="">Select Industry</option>';
                                industrySelect.disabled = true;
                                subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                subcategorySelect.disabled = true;
                            }
                        });

                        industrySelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const industryId = selectedOption ? selectedOption.getAttribute('data-id') : null;
                            
                            if (industryId) {
                                fetch(`/api/subcategories?industry_id=${industryId}`)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error(`HTTP error! status: ${response.status}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (Array.isArray(data)) {
                                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                            data.forEach(subcategory => {
                                                subcategorySelect.innerHTML += `<option value="${subcategory.name}">${subcategory.name}</option>`;
                                            });
                                            subcategorySelect.disabled = false;
                                        } else {
                                            console.error('Invalid data format received:', data);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching subcategories:', error);
                                        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                                        subcategorySelect.disabled = true;
                                    });
                            } else {
                                subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                subcategorySelect.disabled = true;
                            }
                        });
                    });
                    </script>
                    </div>

                    <!-- Why Complete Section -->
                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-lg p-6 mt-6">
                        <h3 class="text-lg font-semibold text-emerald-900 mb-4">Why complete your business profile?</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-emerald-900">Accurate Carbon Tracking</h4>
                                    <p class="text-sm text-emerald-700">Get precise emissions data for your specific industry</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-emerald-900">Industry Benchmarks</h4>
                                    <p class="text-sm text-emerald-700">Compare your performance with similar businesses</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-emerald-900">Compliance Reporting</h4>
                                    <p class="text-sm text-emerald-700">Meet UAE sustainability reporting requirements</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-emerald-900">Custom Recommendations</h4>
                                    <p class="text-sm text-emerald-700">Receive tailored sustainability strategies</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end pt-6 border-t border-gray-200">
                        <div class="flex gap-3">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">Complete Setup</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@else
    @include('dashboard.partials.enterprise')
@endif
@endsection