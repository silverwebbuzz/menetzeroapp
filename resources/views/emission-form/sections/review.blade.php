<div class="space-y-8">
    <!-- Company Information Review -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500">Company Name:</span>
                <p class="text-sm text-gray-900">{{ $emissionSource->company_name }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Sector:</span>
                <p class="text-sm text-gray-900">{{ $emissionSource->sector }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Location:</span>
                <p class="text-sm text-gray-900">{{ $emissionSource->location }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Reporting Year:</span>
                <p class="text-sm text-gray-900">{{ $emissionSource->reporting_year }}</p>
            </div>
        </div>
    </div>

    <!-- Emissions Summary -->
    <div class="bg-gradient-to-r from-emerald-50 to-blue-50 border border-emerald-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Emissions Summary</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Scope 1 -->
            <div class="bg-white rounded-lg p-4 border border-amber-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-amber-800">Scope 1</h4>
                    <span class="text-xs text-amber-600">Direct Emissions</span>
                </div>
                <p class="text-2xl font-bold text-amber-900">{{ number_format($emissionSource->scope1_total ?? 0, 2) }}</p>
                <p class="text-xs text-amber-600">kg CO₂e</p>
            </div>

            <!-- Scope 2 -->
            <div class="bg-white rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-green-800">Scope 2</h4>
                    <span class="text-xs text-green-600">Purchased Energy</span>
                </div>
                <p class="text-2xl font-bold text-green-900">{{ number_format($emissionSource->scope2_total ?? 0, 2) }}</p>
                <p class="text-xs text-green-600">kg CO₂e</p>
            </div>

            <!-- Scope 3 -->
            <div class="bg-white rounded-lg p-4 border border-purple-200">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-purple-800">Scope 3</h4>
                    <span class="text-xs text-purple-600">Other Indirect</span>
                </div>
                <p class="text-2xl font-bold text-purple-900">{{ number_format($emissionSource->scope3_total ?? 0, 2) }}</p>
                <p class="text-xs text-purple-600">kg CO₂e</p>
            </div>
        </div>

        <!-- Grand Total -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-900">Total Emissions</h4>
                <div class="text-right">
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($emissionSource->grand_total ?? 0, 2) }}</p>
                    <p class="text-sm text-gray-600">kg CO₂e</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Scope 1 Details -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-amber-800 mb-3">Scope 1 Breakdown</h4>
            <div class="space-y-2 text-sm">
                @if($emissionSource->diesel_litres)
                    <div class="flex justify-between">
                        <span class="text-amber-700">Diesel Fuel:</span>
                        <span class="text-amber-900">{{ number_format($emissionSource->diesel_litres, 2) }} L</span>
                    </div>
                @endif
                @if($emissionSource->petrol_litres)
                    <div class="flex justify-between">
                        <span class="text-amber-700">Petrol:</span>
                        <span class="text-amber-900">{{ number_format($emissionSource->petrol_litres, 2) }} L</span>
                    </div>
                @endif
                @if($emissionSource->natural_gas_m3)
                    <div class="flex justify-between">
                        <span class="text-amber-700">Natural Gas:</span>
                        <span class="text-amber-900">{{ number_format($emissionSource->natural_gas_m3, 2) }} m³</span>
                    </div>
                @endif
                @if($emissionSource->refrigerant_kg)
                    <div class="flex justify-between">
                        <span class="text-amber-700">Refrigerant:</span>
                        <span class="text-amber-900">{{ number_format($emissionSource->refrigerant_kg, 2) }} kg</span>
                    </div>
                @endif
                @if($emissionSource->other_emissions)
                    <div class="flex justify-between">
                        <span class="text-amber-700">Other:</span>
                        <span class="text-amber-900">{{ number_format($emissionSource->other_emissions, 2) }} kg CO₂e</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Scope 2 Details -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-green-800 mb-3">Scope 2 Breakdown</h4>
            <div class="space-y-2 text-sm">
                @if($emissionSource->electricity_kwh)
                    <div class="flex justify-between">
                        <span class="text-green-700">Electricity:</span>
                        <span class="text-green-900">{{ number_format($emissionSource->electricity_kwh, 2) }} kWh</span>
                    </div>
                @endif
                @if($emissionSource->district_cooling_kwh)
                    <div class="flex justify-between">
                        <span class="text-green-700">District Cooling:</span>
                        <span class="text-green-900">{{ number_format($emissionSource->district_cooling_kwh, 2) }} kWh</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Scope 3 Details -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-purple-800 mb-3">Scope 3 Breakdown</h4>
            <div class="space-y-2 text-sm">
                @if($emissionSource->business_travel_flights_km)
                    <div class="flex justify-between">
                        <span class="text-purple-700">Business Travel:</span>
                        <span class="text-purple-900">{{ number_format($emissionSource->business_travel_flights_km, 2) }} km</span>
                    </div>
                @endif
                @if($emissionSource->car_hire_km)
                    <div class="flex justify-between">
                        <span class="text-purple-700">Car Hire:</span>
                        <span class="text-purple-900">{{ number_format($emissionSource->car_hire_km, 2) }} km</span>
                    </div>
                @endif
                @if($emissionSource->waste_tonnes)
                    <div class="flex justify-between">
                        <span class="text-purple-700">Waste:</span>
                        <span class="text-purple-900">{{ number_format($emissionSource->waste_tonnes, 2) }} tonnes</span>
                    </div>
                @endif
                @if($emissionSource->water_m3)
                    <div class="flex justify-between">
                        <span class="text-purple-700">Water:</span>
                        <span class="text-purple-900">{{ number_format($emissionSource->water_m3, 2) }} m³</span>
                    </div>
                @endif
                @if($emissionSource->purchased_goods)
                    <div class="flex justify-between">
                        <span class="text-purple-700">Purchased Goods:</span>
                        <span class="text-purple-900">{{ number_format($emissionSource->purchased_goods, 2) }} kg CO₂e</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Supporting Documents -->
    @if($emissionSource->uploaded_files && count($emissionSource->uploaded_files) > 0)
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-indigo-800 mb-3">Supporting Documents</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($emissionSource->uploaded_files as $file)
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-indigo-100">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-indigo-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $file['original_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ number_format($file['size'] / 1024, 2) }} KB</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Final Confirmation -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-blue-800">Ready to Submit</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Please review all the information above carefully. Once submitted, this data will be processed and included in your organization's carbon footprint report.</p>
                    <p class="mt-2 text-xs text-blue-600">
                        💡 <strong>Note:</strong> You can always edit this data later if needed. The form will be saved as a draft until you submit it.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
