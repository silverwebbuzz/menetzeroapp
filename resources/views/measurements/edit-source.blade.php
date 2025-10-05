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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm text-gray-500">Quantity</div>
                <div class="text-xl font-bold text-gray-900">{{ number_format($existingData->quantity, 2) }} {{ $existingData->unit }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Current CO2e</div>
                <div class="text-xl font-bold text-gray-900">{{ number_format($existingData->calculated_co2e, 2) }}t</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Scope</div>
                <div class="text-xl font-bold text-gray-900">{{ $emissionSource->scope }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">Last Updated</div>
                <div class="text-xl font-bold text-gray-900">{{ $existingData->updated_at->format('M d, Y') }}</div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" action="{{ route('measurements.update-source-data', ['measurement' => $measurement->id, 'source' => $emissionSource->id]) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Update Your Data</h3>
            
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
                           value="{{ old('quantity', $existingData->quantity) }}"
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
                        <option value="kWh" {{ old('unit', $existingData->unit) == 'kWh' ? 'selected' : '' }}>kWh</option>
                        <option value="litres" {{ old('unit', $existingData->unit) == 'litres' ? 'selected' : '' }}>Litres</option>
                        <option value="kg" {{ old('unit', $existingData->unit) == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                        <option value="tonnes" {{ old('unit', $existingData->unit) == 'tonnes' ? 'selected' : '' }}>Tonnes</option>
                        <option value="m³" {{ old('unit', $existingData->unit) == 'm³' ? 'selected' : '' }}>Cubic meters (m³)</option>
                        <option value="km" {{ old('unit', $existingData->unit) == 'km' ? 'selected' : '' }}>Kilometers</option>
                        <option value="hours" {{ old('unit', $existingData->unit) == 'hours' ? 'selected' : '' }}>Hours</option>
                        <option value="days" {{ old('unit', $existingData->unit) == 'days' ? 'selected' : '' }}>Days</option>
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
                           value="{{ old('calculation_method', $existingData->calculation_method) }}"
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
                              placeholder="Add any additional notes...">{{ old('notes', $existingData->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- CO2e Calculation Preview -->
        @php
            $emissionFactor = \App\Models\EmissionFactor::where('emission_source_id', $emissionSource->id)
                ->where('scope', $emissionSource->scope)
                ->where('is_active', true)
                ->first();
        @endphp
        
        @if($emissionFactor)
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg shadow-sm p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Updated CO2e Calculation</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-teal-100">Quantity</div>
                        <div class="text-2xl font-bold" id="preview-quantity">{{ number_format($existingData->quantity, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">Emission Factor</div>
                        <div class="text-2xl font-bold">{{ number_format($emissionFactor->factor_value, 6) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-teal-100">New CO2e</div>
                        <div class="text-3xl font-bold" id="preview-co2e">{{ number_format($existingData->calculated_co2e, 2) }}t</div>
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
