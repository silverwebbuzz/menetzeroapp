@extends('layouts.app')

@section('page-title', 'Field Mapping - DEWA Bill')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg border">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">DEWA Bill Field Mapping</h1>
            <p class="mt-1 text-sm text-gray-600">Map extracted values to our system fields for accurate carbon calculations</p>
        </div>
        
        <form method="POST" action="{{ route('document-uploads.update-mapping', $document) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Document Info -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Document Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">File:</span>
                        <span class="font-medium">{{ $document->original_name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Type:</span>
                        <span class="font-medium">{{ ucfirst($document->source_type) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Confidence:</span>
                        <span class="font-medium">{{ $document->ocr_confidence ?? 'N/A' }}%</span>
                    </div>
                </div>
            </div>
            
            <!-- Extracted Values from PDF -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-3">📄 Extracted Values from PDF</h3>
                <p class="text-xs text-blue-700 mb-4">These values were automatically extracted from your DEWA bill</p>
                
                @if(isset($extractedValues) && !empty($extractedValues))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($extractedValues as $key => $value)
                            @if($value !== null && $value !== '')
                                <div class="bg-white border border-blue-200 rounded-lg p-3">
                                    <div class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="text-lg font-bold text-blue-600">{{ $value }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500">No values extracted yet. Please retry OCR processing.</p>
                    </div>
                @endif
            </div>
            
            <!-- Field Mapping Section -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-green-900 mb-3">🔗 Field Mapping</h3>
                <p class="text-xs text-green-700 mb-4">Map the extracted values to our system fields. Required fields are marked with *</p>
                
                <div class="space-y-4">
                    @foreach($fieldMappingOptions as $fieldKey => $fieldConfig)
                        <div class="bg-white border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <label class="text-sm font-medium text-gray-900">
                                        {{ $fieldConfig['label'] }}
                                        @if($fieldConfig['required'])
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <p class="text-xs text-gray-500">{{ $fieldConfig['description'] }}</p>
                                </div>
                                @if($fieldConfig['carbon_relevant'])
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        🌱 Carbon Relevant
                                    </span>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Map to Field</label>
                                    <select name="field_mapping[{{ $fieldKey }}]" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Select field to map</option>
                                        @foreach($fieldConfig['mapping_options'] as $optionKey => $optionLabel)
                                            <option value="{{ $optionKey }}" 
                                                    {{ old('field_mapping.' . $fieldKey, $currentMapping[$fieldKey] ?? '') == $optionKey ? 'selected' : '' }}>
                                                {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Extracted Value</label>
                                    <input type="text" 
                                           value="{{ $extractedValues[$fieldKey] ?? 'Not found' }}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" 
                                           readonly>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Carbon Footprint Section (if available) -->
            @if(isset($carbonFootprint) && !empty($carbonFootprint))
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-emerald-900 mb-3">🌱 Carbon Footprint Data</h3>
                    <p class="text-xs text-emerald-700 mb-4">Carbon footprint information found in the bill</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($carbonFootprint as $key => $value)
                            @if($value !== null && $value !== '')
                                <div class="bg-white border border-emerald-200 rounded-lg p-3">
                                    <div class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="text-lg font-bold text-emerald-600">{{ $value }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Location Selection -->
            <div>
                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">Location Assignment</label>
                <select name="location_id" id="location_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">No specific location</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ (old('location_id', $document->location_id) == $location->id) ? 'selected' : '' }}>
                            {{ $location->name }} - {{ $location->address }}
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('document-uploads.show', $document) }}" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Document
                </a>
                
                <div class="flex space-x-3">
                    <button type="button" onclick="autoMapFields()" class="btn btn-outline">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Auto Map
                    </button>
                    
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Mapping
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function autoMapFields() {
    // Auto-map common field patterns
    const autoMappings = {
        'electricity_consumption_kwh': 'electricity_consumption_kwh',
        'electricity_charges_aed': 'electricity_charges_aed',
        'water_consumption_cubic_meters': 'water_consumption_cubic_meters',
        'water_charges_aed': 'water_charges_aed',
        'total_due_aed': 'total_due_aed',
        'vat_amount_aed': 'vat_amount_aed'
    };
    
    Object.keys(autoMappings).forEach(fieldKey => {
        const selectElement = document.querySelector(`select[name="field_mapping[${fieldKey}]"]`);
        if (selectElement) {
            selectElement.value = autoMappings[fieldKey];
        }
    });
    
    // Show success message
    alert('Fields have been auto-mapped based on common patterns. Please review and adjust if needed.');
}
</script>
@endsection
