<div class="space-y-6">
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">Scope 1: Direct Emissions</h3>
                <div class="mt-2 text-sm text-amber-700">
                    <p>These are direct greenhouse gas emissions from sources that are owned or controlled by your organization.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Diesel Litres -->
        <div>
            <label for="diesel_litres" class="block text-sm font-medium text-gray-700 mb-2">
                Diesel Fuel (Litres)
                <span class="ml-1 text-gray-400 cursor-help" title="Total diesel fuel consumed in litres">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="diesel_litres" 
                       name="diesel_litres" 
                       value="{{ old('diesel_litres', $emissionSource->diesel_litres) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('diesel_litres') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">L</span>
                </div>
            </div>
            @error('diesel_litres')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Petrol Litres -->
        <div>
            <label for="petrol_litres" class="block text-sm font-medium text-gray-700 mb-2">
                Petrol/Gasoline (Litres)
                <span class="ml-1 text-gray-400 cursor-help" title="Total petrol/gasoline consumed in litres">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="petrol_litres" 
                       name="petrol_litres" 
                       value="{{ old('petrol_litres', $emissionSource->petrol_litres) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('petrol_litres') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">L</span>
                </div>
            </div>
            @error('petrol_litres')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Natural Gas -->
        <div>
            <label for="natural_gas_m3" class="block text-sm font-medium text-gray-700 mb-2">
                Natural Gas (m³)
                <span class="ml-1 text-gray-400 cursor-help" title="Total natural gas consumed in cubic meters">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="natural_gas_m3" 
                       name="natural_gas_m3" 
                       value="{{ old('natural_gas_m3', $emissionSource->natural_gas_m3) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('natural_gas_m3') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">m³</span>
                </div>
            </div>
            @error('natural_gas_m3')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Refrigerant -->
        <div>
            <label for="refrigerant_kg" class="block text-sm font-medium text-gray-700 mb-2">
                Refrigerant (kg)
                <span class="ml-1 text-gray-400 cursor-help" title="Refrigerant gases used in cooling systems">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="refrigerant_kg" 
                       name="refrigerant_kg" 
                       value="{{ old('refrigerant_kg', $emissionSource->refrigerant_kg) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('refrigerant_kg') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">kg</span>
                </div>
            </div>
            @error('refrigerant_kg')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Other Direct Emissions -->
        <div class="md:col-span-2">
            <label for="other_emissions" class="block text-sm font-medium text-gray-700 mb-2">
                Other Direct Emissions (kg CO₂e)
                <span class="ml-1 text-gray-400 cursor-help" title="Any other direct emissions not covered above">
                    <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm7 4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </span>
            </label>
            <div class="relative">
                <input type="number" 
                       id="other_emissions" 
                       name="other_emissions" 
                       value="{{ old('other_emissions', $emissionSource->other_emissions) }}"
                       step="0.01"
                       min="0"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm @error('other_emissions') border-red-300 @enderror"
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">kg CO₂e</span>
                </div>
            </div>
            @error('other_emissions')
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
                <h3 class="text-sm font-medium text-blue-800">Scope 1 Emissions</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Include all direct emissions from sources you own or control, such as:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li>Company vehicles (cars, trucks, forklifts)</li>
                        <li>On-site fuel combustion (generators, boilers)</li>
                        <li>Refrigerant leaks from air conditioning systems</li>
                        <li>Industrial processes and manufacturing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
