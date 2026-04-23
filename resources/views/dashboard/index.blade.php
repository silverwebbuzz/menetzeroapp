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
    {{-- Dashboard Content --}}
    <div class="page-header">
        <div>
            <h1>Welcome back, {{ auth()->user()->name }}</h1>
            <p>Here's a snapshot of your carbon footprint performance and what needs attention.</p>
        </div>
        <div class="page-header-actions">
            <a href="{{ route('locations.index') }}" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Locations
            </a>
            <a href="{{ route('measurements.index') }}" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l4-4 4 4 5-7"/></svg>
                Measurements
            </a>
            <a href="{{ route('reports.index') }}" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                View Reports
            </a>
        </div>
    </div>

    {{-- KPI Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="stat-card" style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); border-color: var(--brand-dark); color: white;">
            <div class="stat-card-label" style="color: rgba(255,255,255,0.85);">Total Emissions</div>
            <div class="stat-card-value" style="color: white;">{{ number_format($kpis['total_emissions'] ?? 0, 2) }}<span style="font-size: 0.75rem; font-weight: 500; margin-left: 0.25rem; opacity: 0.85;">kg CO₂e</span></div>
            <div class="stat-card-delta" style="color: rgba(255,255,255,0.9);">
                @if(($kpis['monthly_change'] ?? 0) > 0)
                    ↗ {{ $kpis['monthly_change'] ?? 0 }}% vs last month
                @elseif(($kpis['monthly_change'] ?? 0) < 0)
                    ↘ {{ abs($kpis['monthly_change'] ?? 0) }}% vs last month
                @else
                    No change vs last month
                @endif
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Scope 1</div>
            <div class="stat-card-value">{{ number_format($kpis['scope1_total'] ?? 0, 2) }}<span class="text-slate-400 text-xs font-medium ml-1">kg CO₂e</span></div>
            <div class="stat-card-delta">Direct emissions</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Scope 2</div>
            <div class="stat-card-value">{{ number_format($kpis['scope2_total'] ?? 0, 2) }}<span class="text-slate-400 text-xs font-medium ml-1">kg CO₂e</span></div>
            <div class="stat-card-delta">Purchased energy</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Scope 3</div>
            <div class="stat-card-value">{{ number_format($kpis['scope3_total'] ?? 0, 2) }}<span class="text-slate-400 text-xs font-medium ml-1">kg CO₂e</span></div>
            <div class="stat-card-delta">Other indirect</div>
        </div>
    </div>

    {{-- UAE Net Zero Progress --}}
    <div class="card mb-5">
        <div class="card-body">
            <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
                <div>
                    <h3 class="text-slate-900 font-semibold">UAE Net Zero 2050 Progress</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Track your progress towards carbon neutrality</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-brand-dark leading-none">{{ $netZeroProgress['progress'] ?? 0 }}%</div>
                    <div class="text-xs text-slate-500 mt-1">{{ $netZeroProgress['years_remaining'] ?? 25 }} years remaining</div>
                </div>
            </div>
            <div class="w-full bg-brand-100 rounded-full h-2 mb-4">
                <div class="bg-brand h-2 rounded-full transition-all duration-500"
                     style="width: {{ $netZeroProgress['progress'] ?? 0 }}%"></div>
            </div>
            <div class="grid grid-cols-3 gap-4 text-sm pt-3 border-t border-slate-100">
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-medium mb-1">Current</div>
                    <div class="font-semibold text-slate-900">{{ $netZeroProgress['current'] ?? 0 }} <span class="text-slate-400 text-xs font-medium">tCO₂e</span></div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-medium mb-1">Baseline</div>
                    <div class="font-semibold text-slate-900">{{ $netZeroProgress['baseline'] ?? 1000 }} <span class="text-slate-400 text-xs font-medium">tCO₂e</span></div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-medium mb-1">Target 2050</div>
                    <div class="font-semibold text-slate-900">{{ $netZeroProgress['target'] ?? 0 }} <span class="text-slate-400 text-xs font-medium">tCO₂e</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Monthly Emissions Trend</h3>
                    <p class="card-subtitle">Total CO₂e over the last period</p>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 16rem;">
                    <canvas id="monthlyEmissionsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Emissions by Scope</h3>
                    <p class="card-subtitle">Share of total emissions per scope</p>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 16rem;">
                    <canvas id="emissionsByScopeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Sources + Recent Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Top Emission Sources</h3>
                    <p class="card-subtitle">Locations contributing most to your footprint</p>
                </div>
            </div>
            <div class="card-body">
                <div class="space-y-2">
                    @forelse($topSources as $source)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100 gap-3">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-900 truncate">{{ $source['location'] }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $source['period'] }} · {{ ucfirst($source['status']) }}</div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-semibold text-slate-900">{{ number_format($source['emissions'], 2) }} <span class="text-slate-400 text-xs font-medium">kg CO₂e</span></div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    S1 {{ number_format($source['scope1'], 0) }} · S2 {{ number_format($source['scope2'], 0) }} · S3 {{ number_format($source['scope3'], 0) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-brand-soft rounded-full flex items-center justify-center mx-auto mb-3 border border-brand-100">
                                <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 14l4-4 4 4 5-7"/>
                                </svg>
                            </div>
                            <div class="font-semibold text-slate-900 mb-1">No emission sources yet</div>
                            <p class="text-xs text-slate-500 mb-4">Start by adding locations and creating your first measurement.</p>
                            <a href="{{ route('locations.index') }}" class="btn btn-primary btn-sm">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Add your first location
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Recent Activity</h3>
                    <p class="card-subtitle">Latest measurements submitted</p>
                </div>
            </div>
            <div class="card-body">
                <div class="space-y-2">
                    @forelse($recentActivity as $activity)
                        <div class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-50 transition-colors">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-brand-soft border border-brand-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-dark" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-slate-900 truncate">{{ $activity->location->name ?? 'Unknown Location' }}</div>
                                <div class="text-xs text-slate-500">{{ $activity->created_at->format('M d, Y') }} · {{ number_format($activity->total_co2e ?? 0, 2) }} kg CO₂e</div>
                            </div>
                            <div class="flex-shrink-0">
                                @if($activity->status === 'draft')
                                    <span class="badge badge-warning">Draft</span>
                                @elseif($activity->status === 'submitted')
                                    <span class="badge badge-info">Submitted</span>
                                @elseif($activity->status === 'verified')
                                    <span class="badge badge-success">Verified</span>
                                @else
                                    <span class="badge badge-neutral">{{ ucfirst($activity->status) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-brand-soft rounded-full flex items-center justify-center mx-auto mb-3 border border-brand-100">
                                <svg class="w-5 h-5 text-brand-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="font-semibold text-slate-900 mb-1">No recent activity</div>
                            <p class="text-xs text-slate-500 mb-4">Your measurement activities will appear here.</p>
                            <a href="{{ route('measurements.index') }}" class="btn btn-primary btn-sm">
                                Create first measurement
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Reports Summary --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Reports Summary</h3>
                <p class="card-subtitle">Status of your carbon disclosure reports</p>
            </div>
            <a href="{{ route('reports.index') }}" class="btn btn-ghost btn-sm">
                View reports
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="stat-card">
                    <div class="stat-card-label">Total Reports</div>
                    <div class="stat-card-value">{{ $kpis['reports_count'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Draft Reports</div>
                    <div class="stat-card-value">{{ $kpis['draft_reports'] ?? 0 }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Submitted Reports</div>
                    <div class="stat-card-value">{{ $kpis['submitted_reports'] ?? 0 }}</div>
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
                    backgroundColor: ['#10b981', '#34d399', '#6ee7b7'],
                    borderColor: '#ffffff',
                    borderWidth: 2
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

function generateReport() {
    // Implement report generation logic
    alert('Report generation feature coming soon!');
}
</script>
@endpush
@endsection