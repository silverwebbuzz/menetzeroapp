@extends('layouts.app')

@section('title', 'Calculate Emissions - MenetZero')
@section('page-title', 'Calculate Emissions')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add Data to Calculate Emissions for {{ $emissionSource->name }}</h1>
                <p class="mt-2 text-gray-600">{{ $emissionSource->description ?? 'Enter your data to calculate CO2 emissions for this source.' }}</p>
            </div>
            <a href="{{ route('measurements.show', $measurement) }}" 
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Back to Measurement
            </a>
        </div>
    </div>

    <!-- Measurement Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Location</h3>
                <div class="font-semibold text-gray-900">{{ $measurement->location->name }}</div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Period</h3>
                <div class="font-semibold text-gray-900">
                    {{ \Carbon\Carbon::parse($measurement->period_start)->format('M Y') }} - 
                    {{ \Carbon\Carbon::parse($measurement->period_end)->format('M Y') }}
                </div>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-2">Scope</h3>
                <div class="font-semibold text-gray-900">{{ $emissionSource->scope }}</div>
            </div>
        </div>
    </div>

    <!-- Emission Source Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $emissionSource->name }}</h3>
        @if($emissionSource->description)
            <p class="text-gray-600 mb-4">{{ $emissionSource->description }}</p>
        @endif
        
        <!-- Emission Factor Info -->
        @php
            $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSource->id, $emissionSource->scope, 'UAE', $measurement->fiscal_year);
        @endphp
        
        @if($emissionFactor)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 mb-2">Emission Factor</h4>
                <div class="text-sm text-blue-800">
                    <div><strong>Factor:</strong> {{ number_format($emissionFactor->factor_value, 6) }} {{ $emissionFactor->unit }}</div>
                    <div><strong>Method:</strong> {{ $emissionFactor->calculation_method ?? 'Standard' }}</div>
                    <div><strong>Region:</strong> {{ $emissionFactor->region }}</div>
                </div>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-red-800">
                    <strong>Warning:</strong> No emission factor found for this source. Please contact support.
                </div>
            </div>
        @endif
    </div>

    <!-- Calculation Form -->
    <form method="POST" action="{{ route('measurements.store-source-data', ['measurement' => $measurement->id, 'source' => $emissionSource->id]) }}" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Enter Your Data</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                        Quantity <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="quantity" 
                           id="quantity" 
                           step="0.0001"
                           min="0"
                           value="{{ old('quantity', $existingData->quantity ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('quantity') border-red-500 @enderror"
                           placeholder="Enter quantity"
                           required>
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                        Unit <span class="text-red-500">*</span>
                    </label>
                    <select name="unit" 
                            id="unit"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('unit') border-red-500 @enderror"
                            required>
                        <option value="">Select unit</option>
                        <option value="kWh" {{ old('unit', $existingData->unit ?? '') == 'kWh' ? 'selected' : '' }}>kWh</option>
                        <option value="litres" {{ old('unit', $existingData->unit ?? '') == 'litres' ? 'selected' : '' }}>Litres</option>
                        <option value="kg" {{ old('unit', $existingData->unit ?? '') == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                        <option value="tonnes" {{ old('unit', $existingData->unit ?? '') == 'tonnes' ? 'selected' : '' }}>Tonnes</option>
                        <option value="m³" {{ old('unit', $existingData->unit ?? '') == 'm³' ? 'selected' : '' }}>Cubic meters (m³)</option>
                        <option value="km" {{ old('unit', $existingData->unit ?? '') == 'km' ? 'selected' : '' }}>Kilometers</option>
                        <option value="hours" {{ old('unit', $existingData->unit ?? '') == 'hours' ? 'selected' : '' }}>Hours</option>
                        <option value="days" {{ old('unit', $existingData->unit ?? '') == 'days' ? 'selected' : '' }}>Days</option>
                    </select>
                    @error('unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Calculation Method -->
                <div>
                    <label for="calculation_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Calculation Method
                    </label>
                    <input type="text" 
                           name="calculation_method" 
                           id="calculation_method"
                           value="{{ old('calculation_method', $existingData->calculation_method ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('calculation_method') border-red-500 @enderror"
                           placeholder="e.g., Direct measurement, Estimation">
                    @error('calculation_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Notes
                    </label>
                    <textarea name="notes" 
                              id="notes" 
                              rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('notes') border-red-500 @enderror"
                              placeholder="Add any additional notes...">{{ old('notes', $existingData->notes ?? '') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- CO2e Calculation Preview -->
        @if($emissionFactor)
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">CO2e Calculation Preview</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-teal-100">Quantity</div>
                        <div class="text-2xl font-bold" id="preview-quantity">0.00</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">Emission Factor</div>
                        <div class="text-2xl font-bold">{{ number_format($emissionFactor->factor_value, 6) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">Total CO2e</div>
                        <div class="text-3xl font-bold" id="preview-co2e">0.00t</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-6">
            <a href="{{ route('measurements.show', $measurement) }}" 
               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                {{ $existingData ? 'Update Calculation' : 'Save Calculation' }}
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const previewQuantity = document.getElementById('preview-quantity');
    const previewCo2e = document.getElementById('preview-co2e');
    
    @if($emissionFactor)
        const emissionFactor = {{ $emissionFactor->factor_value }};
        
        function updatePreview() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const co2e = quantity * emissionFactor;
            
            previewQuantity.textContent = quantity.toFixed(2);
            previewCo2e.textContent = co2e.toFixed(2) + 't';
        }
        
        quantityInput.addEventListener('input', updatePreview);
        
        // Initial update
        updatePreview();
    @endif
});
</script>
@endsection
