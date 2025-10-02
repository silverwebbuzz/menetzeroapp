<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Company Name -->
        <div class="md:col-span-2">
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                Company Name <span class="text-red-500">*</span>
                <span class="ml-1 text-gray-400 cursor-help" title="Enter your organization's legal name">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <input type="text" 
                   id="company_name" 
                   name="company_name" 
                   value="{{ old('company_name', $emissionSource->company_name) }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('company_name') border-red-300 @enderror"
                   placeholder="Enter your company name"
                   required>
            @error('company_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Sector -->
        <div>
            <label for="sector" class="block text-sm font-medium text-gray-700 mb-2">
                Business Sector <span class="text-red-500">*</span>
                <span class="ml-1 text-gray-400 cursor-help" title="Select your primary business sector">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <select id="sector" 
                    name="sector" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('sector') border-red-300 @enderror"
                    required>
                <option value="">Select your sector</option>
                <option value="Manufacturing" {{ old('sector', $emissionSource->sector) == 'Manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                <option value="Technology" {{ old('sector', $emissionSource->sector) == 'Technology' ? 'selected' : '' }}>Technology</option>
                <option value="Healthcare" {{ old('sector', $emissionSource->sector) == 'Healthcare' ? 'selected' : '' }}>Healthcare</option>
                <option value="Education" {{ old('sector', $emissionSource->sector) == 'Education' ? 'selected' : '' }}>Education</option>
                <option value="Financial Services" {{ old('sector', $emissionSource->sector) == 'Financial Services' ? 'selected' : '' }}>Financial Services</option>
                <option value="Real Estate" {{ old('sector', $emissionSource->sector) == 'Real Estate' ? 'selected' : '' }}>Real Estate</option>
                <option value="Retail" {{ old('sector', $emissionSource->sector) == 'Retail' ? 'selected' : '' }}>Retail</option>
                <option value="Hospitality" {{ old('sector', $emissionSource->sector) == 'Hospitality' ? 'selected' : '' }}>Hospitality</option>
                <option value="Logistics" {{ old('sector', $emissionSource->sector) == 'Logistics' ? 'selected' : '' }}>Logistics</option>
                <option value="Construction" {{ old('sector', $emissionSource->sector) == 'Construction' ? 'selected' : '' }}>Construction</option>
                <option value="Government" {{ old('sector', $emissionSource->sector) == 'Government' ? 'selected' : '' }}>Government</option>
                <option value="Other" {{ old('sector', $emissionSource->sector) == 'Other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('sector')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Location -->
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                Location <span class="text-red-500">*</span>
                <span class="ml-1 text-gray-400 cursor-help" title="Enter your primary business location">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <input type="text" 
                   id="location" 
                   name="location" 
                   value="{{ old('location', $emissionSource->location) }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('location') border-red-300 @enderror"
                   placeholder="e.g., Dubai, UAE"
                   required>
            @error('location')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Reporting Year -->
        <div class="md:col-span-2">
            <label for="reporting_year" class="block text-sm font-medium text-gray-700 mb-2">
                Reporting Year <span class="text-red-500">*</span>
                <span class="ml-1 text-gray-400 cursor-help" title="Select the year for which you are reporting emissions">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <select id="reporting_year" 
                    name="reporting_year" 
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('reporting_year') border-red-300 @enderror"
                    required>
                <option value="">Select reporting year</option>
                @for($year = date('Y'); $year >= 2020; $year--)
                    <option value="{{ $year }}" {{ old('reporting_year', $emissionSource->reporting_year) == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endfor
            </select>
            @error('reporting_year')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Getting Started</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Please provide your organization's basic information. This will help us categorize your emissions data correctly and ensure accurate reporting.</p>
                </div>
            </div>
        </div>
    </div>
</div>
