<div class="space-y-6">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">Scope 2: Purchased Energy</h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>These are indirect emissions from the generation of purchased energy consumed by your organization.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Electricity -->
        <div>
            <label for="electricity_kwh" class="block text-sm font-medium text-gray-700 mb-2">
                Electricity Consumption (kWh)
                <span class="ml-1 text-gray-400 cursor-help" title="Total electricity consumed from the grid">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="electricity_kwh" 
                       name="electricity_kwh" 
                       value="{{ old('electricity_kwh', $emissionSource->electricity_kwh) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('electricity_kwh') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">kWh</span>
                </div>
            </div>
            @error('electricity_kwh')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- District Cooling -->
        <div>
            <label for="district_cooling_kwh" class="block text-sm font-medium text-gray-700 mb-2">
                District Cooling (kWh)
                <span class="ml-1 text-gray-400 cursor-help" title="District cooling energy consumption">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="district_cooling_kwh" 
                       name="district_cooling_kwh" 
                       value="{{ old('district_cooling_kwh', $emissionSource->district_cooling_kwh) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('district_cooling_kwh') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">kWh</span>
                </div>
            </div>
            @error('district_cooling_kwh')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Electricity Sources Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Scope 2 Emissions</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Include all purchased energy consumption:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Electricity:</strong> From utility bills (DEWA, ADDC, etc.)</li>
                        <li><strong>District Cooling:</strong> Centralized cooling systems (common in UAE)</li>
                        <li><strong>Steam:</strong> If applicable to your operations</li>
                        <li><strong>Heating:</strong> District heating systems</li>
                    </ul>
                    <p class="mt-2 text-xs text-blue-600">
                        💡 <strong>Tip:</strong> Check your utility bills for accurate consumption data. 
                        Most UAE utilities provide detailed monthly consumption reports.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- UAE-Specific Information -->
    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-emerald-800">UAE Energy Context</h3>
                <div class="mt-2 text-sm text-emerald-700">
                    <p>The UAE's electricity grid has an average emission factor of 0.424 kg CO₂e/kWh. 
                    District cooling is common in UAE cities and has a lower emission factor than individual AC units.</p>
                </div>
            </div>
        </div>
    </div>
</div>
