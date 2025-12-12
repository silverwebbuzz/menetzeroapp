@extends('layouts.app')

@section('title', 'My Profile - MenetZero')
@section('page-title', 'My Profile')

@section('content')
<style>
    .tab-button { 
        padding: 0.75rem 1.5rem; 
        border-radius: 0.5rem; 
        font-weight: 500; 
        transition: all 0.2s; 
        border: 1px solid transparent;
    }
    .tab-button.active { 
        background: #10b981; 
        color: white; 
        border-color: #10b981;
    }
    .tab-button.inactive { 
        background: #f8fafc; 
        color: #6b7280; 
        border-color: #e5e7eb;
    }
    .tab-button.inactive:hover { 
        background: #f1f5f9; 
        color: #374151;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
</style>

<div class="max-w-6xl mx-auto">
    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center">
                <span class="text-2xl font-semibold text-emerald-700">{{ substr($user->name, 0, 1) }}</span>
            </div>
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                <p class="text-sm text-gray-500">{{ $user->designation ?? 'No designation set' }}</p>
                @if($company)
                    <p class="text-sm text-emerald-600 font-medium">{{ $company->name }}</p>
                @else
                    <p class="text-sm text-amber-600 font-medium">No company associated</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex space-x-2 mb-6">
        <button onclick="showTab('personal')" id="personal-tab" class="tab-button active">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Personal Information
        </button>
        <button onclick="showTab('company')" id="company-tab" class="tab-button inactive">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Company Information
        </button>
        <button onclick="showTab('password')" id="password-tab" class="tab-button inactive">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
            Change Password
        </button>
    </div>

    <!-- Personal Information Tab -->
    <div id="personal-content" class="tab-content active">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Personal Information</h3>
            
            <form method="POST" action="{{ route('profile.update.personal') }}" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" value="{{ $user->email }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('phone')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Designation</label>
                        <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        @error('designation')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                        Update Personal Information
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Tab -->
    <div id="password-content" class="tab-content">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Change Password</h3>
            
            <form method="POST" action="{{ route('profile.update.password') }}" class="space-y-6">
                @csrf
                
                <div class="max-w-2xl">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                        <input type="password" name="current_password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                               required autocomplete="current-password">
                        @error('current_password')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                        <input type="password" name="new_password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                               required autocomplete="new-password" minlength="8">
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long.</p>
                        @error('new_password')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                        <input type="password" name="new_password_confirmation" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                               required autocomplete="new-password" minlength="8">
                        @error('new_password_confirmation')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">Password Requirements:</p>
                                <ul class="list-disc list-inside space-y-1 text-blue-700">
                                    <li>Minimum 8 characters</li>
                                    <li>Use a combination of letters, numbers, and symbols for better security</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Company Information Tab -->
    <div id="company-content" class="tab-content">
        @if($company)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Company Information</h3>
                
                <form method="POST" action="{{ route('profile.update.company') }}" class="space-y-6">
                    @csrf
                    
                    <!-- Basic Company Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $company->name) }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                            @error('company_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Email</label>
                            <input type="email" name="business_email" value="{{ old('business_email', $company->email) }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('business_email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Website</label>
                            <input type="url" name="business_website" value="{{ old('business_website', $company->website) }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('business_website')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select name="country" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">Select Country</option>
                                <option value="UAE" {{ old('country', $company->country) == 'UAE' ? 'selected' : '' }}>United Arab Emirates</option>
                                <option value="SA" {{ old('country', $company->country) == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                <option value="KW" {{ old('country', $company->country) == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                <option value="QA" {{ old('country', $company->country) == 'QA' ? 'selected' : '' }}>Qatar</option>
                                <option value="BH" {{ old('country', $company->country) == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                <option value="OM" {{ old('country', $company->country) == 'OM' ? 'selected' : '' }}>Oman</option>
                                <option value="US" {{ old('country', $company->country) == 'US' ? 'selected' : '' }}>United States</option>
                                <option value="UK" {{ old('country', $company->country) == 'UK' ? 'selected' : '' }}>United Kingdom</option>
                                <option value="IN" {{ old('country', $company->country) == 'IN' ? 'selected' : '' }}>India</option>
                                <option value="Other" {{ old('country', $company->country) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('country')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                        <textarea name="business_address" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('business_address', $company->address) }}</textarea>
                        @error('business_address')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <!-- UAE Specific Fields -->
                    <div class="border-t pt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">UAE Specific Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Emirate</label>
                                <select name="emirate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Select Emirate</option>
                                    <option value="Abu Dhabi" {{ old('emirate', $company->emirate) == 'Abu Dhabi' ? 'selected' : '' }}>Abu Dhabi</option>
                                    <option value="Dubai" {{ old('emirate', $company->emirate) == 'Dubai' ? 'selected' : '' }}>Dubai</option>
                                    <option value="Sharjah" {{ old('emirate', $company->emirate) == 'Sharjah' ? 'selected' : '' }}>Sharjah</option>
                                    <option value="Ajman" {{ old('emirate', $company->emirate) == 'Ajman' ? 'selected' : '' }}>Ajman</option>
                                    <option value="Ras Al Khaimah" {{ old('emirate', $company->emirate) == 'Ras Al Khaimah' ? 'selected' : '' }}>Ras Al Khaimah</option>
                                    <option value="Fujairah" {{ old('emirate', $company->emirate) == 'Fujairah' ? 'selected' : '' }}>Fujairah</option>
                                    <option value="Umm Al Quwain" {{ old('emirate', $company->emirate) == 'Umm Al Quwain' ? 'selected' : '' }}>Umm Al Quwain</option>
                                </select>
                                @error('emirate')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">License Number</label>
                                <input type="text" name="license_no" value="{{ old('license_no', $company->license_no) }}" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('license_no')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Business Details -->
                    <div class="border-t pt-6">
                        <h4 class="text-md font-semibold text-gray-900 mb-4">Business Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Sector -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sector</label>
                                <select name="sector" id="sector" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">Select Sector</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->name }}" data-id="{{ $sector->id }}" {{ old('sector', $company->sector) == $sector->name ? 'selected' : '' }}>{{ $sector->name }}</option>
                                    @endforeach
                                </select>
                                @error('sector')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <!-- Industry -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                <select name="industry" id="industry" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" {{ $industries->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">Select Industry</option>
                                    @foreach($industries as $industry)
                                        <option value="{{ $industry->name }}" data-id="{{ $industry->id }}" {{ old('industry', $company->industry) == $industry->name ? 'selected' : '' }}>{{ $industry->name }}</option>
                                    @endforeach
                                </select>
                                @error('industry')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                            
                            <!-- Business Subcategory -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Subcategory</label>
                                <select name="business_subcategory" id="business_subcategory" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" {{ $subcategories->isEmpty() ? 'disabled' : '' }}>
                                    <option value="">Select Subcategory (Optional)</option>
                                    @foreach($subcategories as $subcategory)
                                        <option value="{{ $subcategory->name }}" {{ old('business_subcategory', $company->business_subcategory) == $subcategory->name ? 'selected' : '' }}>{{ $subcategory->name }}</option>
                                    @endforeach
                                </select>
                                @error('business_subcategory')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Description</label>
                            <textarea name="business_description" rows="4" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">{{ old('business_description', $company->description) }}</textarea>
                            @error('business_description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                            Update Company Information
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Company Associated</h3>
                <p class="text-gray-600 mb-4">You don't have a company profile yet. Complete your business profile to get started.</p>
                <a href="{{ route('client.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Complete Business Profile
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
        button.classList.add('inactive');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.add('active');
    
    // Add active class to selected tab button
    document.getElementById(tabName + '-tab').classList.remove('inactive');
    document.getElementById(tabName + '-tab').classList.add('active');
}
</script>
@endsection
