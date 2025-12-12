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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" name="country">
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sector</label>
                                <select name="sector" id="sector" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                <select name="industry" id="industry" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" disabled>
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
                    document.getElementById('sector').addEventListener('change', function() {
                        const sectorId = this.options[this.selectedIndex].getAttribute('data-id');
                        const industrySelect = document.getElementById('industry');
                        const subcategorySelect = document.getElementById('business_subcategory');
                        
                        if (sectorId) {
                            fetch(`/api/industries?sector_id=${sectorId}`)
                                .then(response => response.json())
                                .then(data => {
                                    industrySelect.innerHTML = '<option value="">Select Industry</option>';
                                    data.forEach(industry => {
                                        industrySelect.innerHTML += `<option value="${industry.name}" data-id="${industry.id}">${industry.name}</option>`;
                                    });
                                    industrySelect.disabled = false;
                                    
                                    // Reset subcategory
                                    subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                    subcategorySelect.disabled = true;
                                })
                                .catch(error => {
                                    console.error('Error fetching industries:', error);
                                });
                        } else {
                            industrySelect.innerHTML = '<option value="">Select Industry</option>';
                            industrySelect.disabled = true;
                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                            subcategorySelect.disabled = true;
                        }
                    });

                    document.getElementById('industry').addEventListener('change', function() {
                        const industryId = this.options[this.selectedIndex].getAttribute('data-id');
                        const subcategorySelect = document.getElementById('business_subcategory');
                        
                        if (industryId) {
                            fetch(`/api/subcategories?industry_id=${industryId}`)
                                .then(response => response.json())
                                .then(data => {
                                    subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                                    data.forEach(subcategory => {
                                        subcategorySelect.innerHTML += `<option value="${subcategory.name}">${subcategory.name}</option>`;
                                    });
                                    subcategorySelect.disabled = false;
                                })
                                .catch(error => {
                                    console.error('Error fetching subcategories:', error);
                                });
                        } else {
                            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';
                            subcategorySelect.disabled = true;
                        }
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
    <!-- Dashboard Content - Show ONLY when company is added -->
<div class="space-y-8">
    <!-- Header with Quick Actions -->
        <div class="mb-8">
            <div class="mb-4">
                <h2 class="text-2xl font-semibold" style="color: #111827;">Dashboard</h2>
                <p class="text-sm" style="color: #6b7280;">Welcome back, {{ auth()->user()->name }}</p>
        </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('locations.index') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <span class="hidden sm:inline">Manage Locations</span>
                    <span class="sm:hidden">Locations</span>
            </a>
                <a href="{{ route('measurements.index') }}" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden sm:inline">Manage Reports</span>
                    <span class="sm:hidden">Reports</span>
            </a>
                <button onclick="uploadBill()" class="btn btn-outline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <span class="hidden sm:inline">Upload Bill</span>
                    <span class="sm:hidden">Upload</span>
            </button>
                <button onclick="generateReport()" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden sm:inline">Generate Report</span>
                    <span class="sm:hidden">Generate</span>
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Total Emissions -->
        <div class="p-6 rounded-2xl text-white shadow-sm" style="background:linear-gradient(90deg, #26A69A 0%, #1f8e86 100%); border:1px solid rgba(38,166,154,.25)">
            <div class="flex items-start justify-between">
                <p class="text-sm/5 opacity-90">Total Emissions</p>
                <span class="text-white/80">🌿</span>
            </div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($kpis['total_emissions'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 opacity-90">
                    @if(($kpis['monthly_change'] ?? 0) > 0)
                        <span class="text-red-200">↗ {{ $kpis['monthly_change'] ?? 0 }}%</span> from last month
                    @elseif(($kpis['monthly_change'] ?? 0) < 0)
                        <span class="text-green-200">↘ {{ abs($kpis['monthly_change'] ?? 0) }}%</span> from last month
                @else
                    <span class="text-white/70">No change</span> from last month
                @endif
            </p>
        </div>

        <!-- Scope 1 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 1 Emissions</p><span class="text-rose-500">📈</span></div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope1_total'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-rose-600">Direct emissions</p>
        </div>

        <!-- Scope 2 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 2 Emissions</p><span class="text-amber-500">⚡</span></div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope2_total'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-emerald-600">Purchased energy</p>
        </div>

        <!-- Scope 3 -->
        <div class="p-6 rounded-2xl bg-white shadow-sm border border-gray-100">
            <div class="flex items-start justify-between"><p class="text-sm/5 text-gray-600">Scope 3 Emissions</p><span class="text-purple-500">🌐</span></div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($kpis['scope3_total'] ?? 0, 2) }} kg CO₂e</div>
            <p class="mt-1 text-xs/5 text-purple-600">Other indirect</p>
        </div>
    </div>

    <!-- UAE Net Zero Progress -->
        <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-blue-900">UAE Net Zero 2050 Progress</h3>
                <p class="text-sm text-blue-700">Track your progress towards carbon neutrality</p>
            </div>
            <div class="text-right">
                    <div class="text-2xl font-bold text-blue-900">{{ $netZeroProgress['progress'] ?? 0 }}%</div>
                    <div class="text-sm text-blue-600">{{ $netZeroProgress['years_remaining'] ?? 25 }} years remaining</div>
                </div>
        </div>
        
        <div class="w-full bg-blue-200 rounded-full h-3 mb-4">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                    style="width: {{ $netZeroProgress['progress'] ?? 0 }}%"></div>
        </div>
        
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="text-center">
                    <div class="font-semibold text-blue-900">{{ $netZeroProgress['current'] ?? 0 }} tCO₂e</div>
                <div class="text-blue-600">Current</div>
            </div>
            <div class="text-center">
                    <div class="font-semibold text-blue-900">{{ $netZeroProgress['baseline'] ?? 1000 }} tCO₂e</div>
                <div class="text-blue-600">Baseline</div>
            </div>
            <div class="text-center">
                    <div class="font-semibold text-blue-900">{{ $netZeroProgress['target'] ?? 0 }} tCO₂e</div>
                <div class="text-blue-600">Target 2050</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Monthly Emissions Trend -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Emissions Trend</h3>
            <div class="h-64">
                    <canvas id="monthlyEmissionsChart"></canvas>
                </div>
        </div>

        <!-- Emissions by Scope -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Emissions by Scope</h3>
            <div class="h-64">
                    <canvas id="emissionsByScopeChart"></canvas>
                </div>
        </div>
    </div>

    <!-- Top Emission Sources & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Top Emission Sources -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Emission Sources</h3>
            <div class="space-y-3">
                @forelse($topSources as $source)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium text-gray-900">{{ $source['location'] }}</div>
                            <div class="text-sm text-gray-500">{{ $source['period'] }} • {{ ucfirst($source['status']) }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-900">{{ number_format($source['emissions'], 2) }} kg CO₂e</div>
                            <div class="text-xs text-gray-500">
                                S1: {{ number_format($source['scope1'], 0) }} | 
                                S2: {{ number_format($source['scope2'], 0) }} | 
                                S3: {{ number_format($source['scope3'], 0) }}
                            </div>
                        </div>
                    </div>
                @empty
                        <!-- Empty state when no data exists -->
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No emission sources yet</h3>
                            <p class="text-sm text-gray-500 mb-4">Start by adding locations and creating your first measurement.</p>
                            <a href="{{ route('locations.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                                Add Your First Location
                            </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                <div class="space-y-4">
                @forelse($recentActivity as $activity)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->location->name ?? 'Unknown Location' }}</p>
                            <p class="text-sm text-gray-500">{{ $activity->created_at->format('M d, Y') }} • {{ number_format($activity->total_co2e ?? 0, 2) }} kg CO₂e</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($activity->status === 'draft') bg-amber-100 text-amber-800
                                @elseif($activity->status === 'submitted') bg-green-100 text-green-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ ucfirst($activity->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                        <!-- Empty state when no activity exists -->
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No recent activity</h3>
                            <p class="text-sm text-gray-500 mb-4">Your measurement activities will appear here.</p>
                            <a href="{{ route('measurements.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Create First Measurement
                            </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Reports Summary -->
    <div class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reports Summary</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg text-center">
                    <p class="text-sm text-blue-700">Total Reports</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $kpis['reports_count'] ?? 0 }}</p>
                </div>
                <div class="bg-amber-50 p-4 rounded-lg text-center">
                    <p class="text-sm text-amber-700">Draft Reports</p>
                    <p class="text-2xl font-bold text-amber-900">{{ $kpis['draft_reports'] ?? 0 }}</p>
            </div>
                <div class="bg-green-50 p-4 rounded-lg text-center">
                    <p class="text-sm text-green-700">Submitted Reports</p>
                    <p class="text-2xl font-bold text-green-900">{{ $kpis['submitted_reports'] ?? 0 }}</p>
            </div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
// Chart.js configuration
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Emissions Trend Chart
    const monthlyCtx = document.getElementById('monthlyEmissionsChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
    type: 'line',
    data: {
                labels: {!! json_encode($chartData['monthly_labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!},
        datasets: [{
                    label: 'Total Emissions',
                    data: {!! json_encode($chartData['monthly_emissions'] ?? [0, 0, 0, 0, 0, 0]) !!},
            borderColor: '#26A69A',
            backgroundColor: 'rgba(38, 166, 154, 0.1)',
                    tension: 0.4,
                    fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
    }

    // Emissions by Scope Chart
    const scopeCtx = document.getElementById('emissionsByScopeChart');
    if (scopeCtx) {
        new Chart(scopeCtx, {
    type: 'doughnut',
    data: {
                labels: ['Scope 1', 'Scope 2', 'Scope 3'],
        datasets: [{
                    data: [
                        {{ $kpis['scope1_total'] ?? 0 }},
                        {{ $kpis['scope2_total'] ?? 0 }},
                        {{ $kpis['scope3_total'] ?? 0 }}
                    ],
                    backgroundColor: ['#EF4444', '#F59E0B', '#8B5CF6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});

// Quick action functions
function uploadBill() {
    window.location.href = "{{ route('document-uploads.create') }}";
}

function generateReport() {
    // Implement report generation logic
    alert('Report generation feature coming soon!');
}
</script>
@endpush
@endsection