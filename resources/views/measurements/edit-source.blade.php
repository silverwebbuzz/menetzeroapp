@extends('layouts.app')

@section('title', 'Edit Emission Data - MenetZero')
@section('page-title', 'Edit Emission Data')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Emission Data</h1>
                <p class="mt-2 text-gray-600">Update data for {{ $emissionSource->name }}</p>
            </div>
            <a href="{{ route('measurements.show', $measurement) }}" 
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                Back to Measurement
            </a>
        </div>
    </div>

    <!-- Current Data Summary -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Data</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <div class="text-sm text-gray-500">Scope</div>
                <div class="text-xl font-bold text-gray-900">{{ $emissionSource->scope }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Last Updated</div>
                <div class="text-xl font-bold text-gray-900">
                    @if($existingData && $existingData->count() > 0)
                        {{ $existingData->first()->updated_at->format('M d, Y') }}
                    @else
                        No data yet
                    @endif
                </div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Status</div>
                <div class="text-xl font-bold text-gray-900">
                    @if($existingData && $existingData->count() > 0)
                        <span class="text-green-600">Data Entered</span>
                    @else
                        <span class="text-gray-500">No Data</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="{{ route('measurements.update-source-data', ['measurement' => $measurement->id, 'source' => $emissionSource->id]) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Update Your Data</h3>
            
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
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Updated CO2e Calculation</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-teal-100">Quantity</div>
                        <div class="text-2xl font-bold" id="preview-quantity">
                            @if($existingData && $existingData->has('quantity'))
                                {{ number_format($existingData['quantity']->field_value, 2) }}
                            @else
                                0.00
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">Emission Factor</div>
                        <div class="text-2xl font-bold">{{ number_format($emissionFactor->factor_value, 6) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">New CO2e</div>
                        <div class="text-3xl font-bold" id="preview-co2e">
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
        <div class="flex justify-between items-center pt-6">
            <div class="flex gap-4">
                <a href="{{ route('measurements.show', $measurement) }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="button" 
                        onclick="deleteData()"
                        class="px-6 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition">
                    Delete Data
                </button>
            </div>
            <button type="submit" 
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                Update Calculation
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
