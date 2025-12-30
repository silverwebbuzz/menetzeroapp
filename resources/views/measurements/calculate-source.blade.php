@extends('layouts.app')

@section('title', 'Calculate Emissions - MenetZero')
@section('page-title', 'Calculate Emissions')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Add Data to Calculate Emissions for {{ $emissionSource->name }}</h1>
                <p class="mt-1 text-gray-600">{{ $emissionSource->description ?? 'Enter your data to calculate CO2 emissions for this source.' }}</p>
            </div>
            <a href="{{ route('measurements.show', $measurement) }}" 
               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-200 shadow-md hover:shadow-lg">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Measurement
            </a>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Left Column - Information -->
        <div class="space-y-6">
            <!-- Measurement Context Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Measurement Context</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Location</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $measurement->location->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Period</div>
                        <div class="text-lg font-semibold text-gray-900">
                            {{ \Carbon\Carbon::parse($measurement->period_start)->format('M Y') }} - 
                            {{ \Carbon\Carbon::parse($measurement->period_end)->format('M Y') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Scope</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $emissionSource->scope }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Last Updated</div>
                        <div class="text-lg font-semibold text-gray-900">No data yet</div>
                    </div>
                </div>
            </div>

            <!-- Emission Factor Information -->
            @php
                $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSource->id, 'UAE', $measurement->fiscal_year);
            @endphp
            
            @if($emissionFactor)
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">📊 Emission Factor Information</h3>
                        <p class="text-sm text-gray-600">Current emission factor for calculations</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Emission Factor</div>
                        <div class="text-lg font-semibold text-gray-900">{{ number_format($emissionFactor->factor_value, 6) }} {{ $emissionFactor->unit }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Calculation Method</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $emissionFactor->calculation_method ?? 'Standard Method' }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Region</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $emissionFactor->region ?? 'UAE' }}</div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <strong>Note:</strong> CO2e emission factor for {{ strtolower($emissionSource->name) }}.
                    </p>
                </div>
            </div>
            @endif

            <!-- How It Works -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">🧮 How It Works</h3>
                        <p class="text-sm text-gray-600">Understanding the calculation process</p>
                    </div>
                </div>
                @if($emissionFactor)
                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 mb-2">CO2e = Your Quantity × {{ number_format($emissionFactor->factor_value, 6) }} {{ $emissionFactor->unit }}</div>
                        <p class="text-sm text-gray-600">Enter your consumption data below, and we'll automatically calculate your CO2e emissions.</p>
                    </div>
                </div>
                @else
                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 mb-2">CO2e = Your Quantity × Emission Factor</div>
                        <p class="text-sm text-gray-600">Enter your consumption data below, and we'll automatically calculate your CO2e emissions.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Form -->
        <div class="space-y-6">

            <!-- Calculation Form -->
            <form method="POST" action="{{ route('measurements.store-source-data', ['measurement' => $measurement->id, 'source' => $emissionSource->id]) }}" class="space-y-6">
                @csrf
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Enter Your Data</h3>
            
            @if($formFields && $formFields->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($formFields as $field)
                        <div class="{{ $field->field_name === 'quantity' ? 'md:col-span-2' : '' }}">
                            <label for="{{ $field->field_name }}" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $field->field_label }}
                                @if($field->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            
                            @if($field->field_type === 'number')
                                <input type="number" 
                                       name="{{ $field->field_name }}" 
                                       id="{{ $field->field_name }}" 
                                       step="{{ $field->validation_rules['step'] ?? '0.01' }}"
                                       min="{{ $field->validation_rules['min'] ?? '0' }}"
                                       value="{{ old($field->field_name, $existingData[$field->field_name]->field_value ?? '') }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error($field->field_name) border-red-500 @enderror"
                                       placeholder="{{ $field->field_placeholder }}"
                                       {{ $field->is_required ? 'required' : '' }}>
                            @elseif($field->field_type === 'select')
                                <select name="{{ $field->field_name }}" 
                                        id="{{ $field->field_name }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error($field->field_name) border-red-500 @enderror"
                                        {{ $field->is_required ? 'required' : '' }}>
                                    <option value="">Select {{ $field->field_label }}</option>
                                    @if($field->field_options)
                                        @foreach($field->field_options as $option)
                                            <option value="{{ $option['value'] }}" 
                                                    {{ old($field->field_name, $existingData[$field->field_name]->field_value ?? '') == $option['value'] ? 'selected' : '' }}>
                                                {{ $option['label'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            @elseif($field->field_type === 'textarea')
                                <textarea name="{{ $field->field_name }}" 
                                          id="{{ $field->field_name }}"
                                          rows="3"
                                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error($field->field_name) border-red-500 @enderror"
                                          placeholder="{{ $field->field_placeholder }}"
                                          {{ $field->is_required ? 'required' : '' }}>{{ old($field->field_name, $existingData[$field->field_name]->field_value ?? '') }}</textarea>
                            @else
                                <input type="{{ $field->field_type }}" 
                                       name="{{ $field->field_name }}" 
                                       id="{{ $field->field_name }}"
                                       value="{{ old($field->field_name, $existingData[$field->field_name]->field_value ?? '') }}"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error($field->field_name) border-red-500 @enderror"
                                       placeholder="{{ $field->field_placeholder }}"
                                       {{ $field->is_required ? 'required' : '' }}>
                            @endif
                            
                            @if($field->help_text)
                                <p class="mt-1 text-xs text-gray-600">{{ $field->help_text }}</p>
                            @endif
                            
                            @error($field->field_name)
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback static form if no dynamic fields are configured -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                            @if($emissionFactor)
                                <span class="text-xs text-gray-500 font-normal">
                                    (Expected unit: {{ explode(' per ', $emissionFactor->unit)[0] ?? 'varies' }})
                                </span>
                            @endif
                        </label>
                        <input type="number" 
                               name="quantity" 
                               id="quantity" 
                               step="0.0001"
                               min="0"
                               value="{{ old('quantity', $existingData['quantity']->field_value ?? '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('quantity') border-red-500 @enderror"
                               placeholder="Enter your consumption quantity"
                               required>
                        @if($emissionFactor)
                            <p class="mt-1 text-xs text-gray-600">
                                💡 Example: For {{ $emissionSource->name }}, enter your total consumption amount
                            </p>
                        @endif
                    @error('quantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">
                        Unit <span class="text-red-500">*</span>
                        @if($emissionFactor)
                            <span class="text-xs text-gray-500 font-normal">
                                (Recommended: {{ explode(' per ', $emissionFactor->unit)[0] ?? 'varies' }})
                            </span>
                        @endif
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
                    @if($emissionFactor)
                        <p class="mt-1 text-xs text-gray-600">
                            💡 The emission factor is: {{ $emissionFactor->unit }}
                        </p>
                    @endif
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
            @endif
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
    </div>
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
