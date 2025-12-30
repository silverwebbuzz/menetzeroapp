@extends('layouts.app')

@section('title', 'Edit Emission Data - MenetZero')
@section('page-title', 'Edit Emission Data')

@section('content')
<div class="w-full">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Data for {{ $emissionSource->name ?? 'Unknown Source' }}</h1>
                <p class="mt-1 text-gray-600">{{ $emissionSource->description ?? 'Update your data to recalculate CO2 emissions for this source.' }}</p>
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
                        <div class="text-lg font-semibold text-gray-900">
                            @if($existingData && $existingData->count() > 0)
                                {{ $existingData->first()->updated_at->format('M d, Y') }}
                            @else
                                No data yet
                            @endif
                        </div>
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
                        <p class="text-sm text-gray-600">Update your consumption data below, and we'll automatically recalculate your CO2e emissions.</p>
                    </div>
                </div>
                @else
                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 mb-2">CO2e = Your Quantity × Emission Factor</div>
                        <p class="text-sm text-gray-600">Update your consumption data below, and we'll automatically recalculate your CO2e emissions.</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Form -->
        <div class="space-y-6">

            <!-- Edit Form -->
            <form method="POST" action="{{ route('measurements.update-source-data', ['measurement' => $measurement->id, 'source' => $emissionSource->id]) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Update Your Data</h3>
            
                    @if($formFields && $formFields->count() > 0)
                        <div class="space-y-6">
                            @foreach($formFields as $field)
                                <div>
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
                        </label>
                        <input type="number" 
                               name="quantity" 
                               id="quantity" 
                               step="0.0001"
                               min="0"
                               value="{{ old('quantity', $existingData['quantity']->field_value ?? '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('quantity') border-red-500 @enderror"
                               placeholder="Enter quantity"
                               required>
                        @error('quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            @endif
        </div>

        <!-- CO2e Calculation Preview -->
        @php
            $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSource->id, 'UAE', $measurement->fiscal_year);
        @endphp
        
        @if($emissionFactor)
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-4 text-white">
                <h3 class="text-lg font-semibold mb-3">Updated CO2e Calculation</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-teal-100">Quantity</div>
                        <div class="text-xl font-bold" id="preview-quantity">
                            @if($existingData && $existingData->has('quantity'))
                                {{ number_format($existingData['quantity']->field_value, 2) }}
                            @else
                                0.00
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">Emission Factor</div>
                        <div class="text-xl font-bold">{{ number_format($emissionFactor->factor_value, 6) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">New CO2e</div>
                        <div class="text-2xl font-bold" id="preview-co2e">
                            @if($existingData && $existingData->has('quantity'))
                                @php
                                    $quantity = $existingData['quantity']->field_value ?? 0;
                                    $co2e = $quantity * $emissionFactor->factor_value;
                                @endphp
                                {{ number_format($co2e, 2) }}t
                            @else
                                0.00t
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form Actions -->
        <div class="flex justify-between items-center pt-4">
            <div class="flex gap-3">
                <a href="{{ route('measurements.show', $measurement) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button type="button" 
                        onclick="deleteData()"
                        class="inline-flex items-center px-4 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Data
                </button>
            </div>
            <button type="submit" 
                    class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all duration-200 shadow-md hover:shadow-lg">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Update Calculation
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
            if (quantityInput && previewQuantity && previewCo2e) {
                const quantity = parseFloat(quantityInput.value) || 0;
                const co2e = quantity * emissionFactor;
                
                previewQuantity.textContent = quantity.toFixed(2);
                previewCo2e.textContent = co2e.toFixed(2) + 't';
            }
        }
        
        if (quantityInput) {
            quantityInput.addEventListener('input', updatePreview);
            // Initial update
            updatePreview();
        }
    @endif
});

function deleteData() {
    if (confirm('Are you sure you want to delete this emission data? This action cannot be undone.')) {
        fetch('{{ route("measurements.delete-source-data", ["measurement" => $measurement->id, "source" => $emissionSource->id]) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("measurements.show", $measurement) }}';
            } else {
                alert('Failed to delete emission data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the data');
        });
    }
}
</script>
@endsection
